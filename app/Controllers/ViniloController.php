<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ViniloModel;
use App\Models\ViniloFotoModel;

class ViniloController extends ResourceController
{
    protected $modelName = 'App\Models\ViniloModel';
    protected $format    = 'json';

    // GET /api/vinilos
    public function index()
    {
        // ✅ Obtener usuario logueado
        $userId = session()->get('auth_user')['id'] ?? null;

        if (!$userId) {
            return $this->failUnauthorized('Usuario no autenticado');
        }

        $buscar  = $this->request->getGet('buscar');
        $formato = $this->request->getGet('formato');
        $estado  = $this->request->getGet('estado');
        $limit   = (int) ($this->request->getGet('limit') ?? 10);
        $page    = (int) ($this->request->getGet('page') ?? 1);

        $sort    = $this->request->getGet('sort') ?? 'created_at';
        $order   = $this->request->getGet('order') ?? 'desc';

        // Validar columnas permitidas
        $allowedSortColumns = ['id', 'titulo', 'artista', 'anio_lanzamiento', 'genero', 'formato', 'estado_conservacion', 'precio', 'created_at'];
        if (!in_array($sort, $allowedSortColumns)) {
            $sort = 'created_at';
        }
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        // ✅ FILTRAR POR USUARIO LOGUEADO
        $this->model->where('user_id', $userId);

        // Aplicar búsqueda
        if (!empty($buscar)) {
            $this->model->groupStart()
                ->like('titulo', $buscar)
                ->orLike('artista', $buscar)
                ->orLike('genero', $buscar)
                ->groupEnd();
        }

        // Aplicar filtros
        if (!empty($formato)) {
            $this->model->where('formato', $formato);
        }

        if (!empty($estado)) {
            $this->model->where('estado_conservacion', $estado);
        }

        $this->model->orderBy($sort, $order);

        $vinilos = $this->model->paginate($limit, 'default', $page);
        $pager   = $this->model->pager;

        // Agregar fotos
        $viniloFotoModel = new ViniloFotoModel();
        foreach ($vinilos as &$vinilo) {
            $vinilo['fotos'] = $viniloFotoModel
                ->where('vinilo_id', $vinilo['id'])
                ->findAll();
        }

        return $this->respond([
            'status'     => 200,
            'total'      => $pager->getTotal(),
            'per_page'   => $limit,
            'page'       => $page,
            'page_count' => $pager->getPageCount(),
            'sort'       => $sort,
            'order'      => $order,
            'data'       => $vinilos
        ]);
    }

    // GET /api/vinilos/(:num)
    public function show($id = null)
    {
        $vinilo = $this->model->find($id);
        if (!$vinilo) {
            return $this->failNotFound('Vinilo no encontrado');
        }

        $viniloFotoModel = new ViniloFotoModel();
        $vinilo['fotos'] = $viniloFotoModel
            ->where('vinilo_id', $id)
            ->findAll();

        return $this->respond($vinilo);
    }

    // POST /api/vinilos
    public function create()
    {
        $userId = session()->get('auth_user')['id'] ?? null;

        if (!$userId) {
            return $this->failUnauthorized('Usuario no autenticado');
        }

        $data = $this->request->getJSON(true);
        if (empty($data)) {
            return $this->fail('No se enviaron datos válidos.', 400);
        }

        // ✅ Agregar user_id automáticamente
        $data['user_id'] = $userId;

        if (!$this->model->save($data)) {
            return $this->fail($this->model->errors());
        }

        $viniloId = $this->model->getInsertID();

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Vinilo registrado exitosamente',
            'id'      => $viniloId
        ]);
    }

    // PUT /api/vinilos/(:num)
    public function update($id = null)
    {
        $userId = session()->get('auth_user')['id'] ?? null;

        if (!$userId) {
            return $this->failUnauthorized('Usuario no autenticado');
        }

        $vinilo = $this->model->find($id);
        if (!$vinilo) {
            return $this->failNotFound('Vinilo no encontrado');
        }

        // ✅ Verificar que el vinilo pertenezca al usuario
        if ($vinilo['user_id'] != $userId) {
            return $this->failForbidden('No tienes permiso para editar este vinilo');
        }

        $data = $this->request->getJSON(true);
        if (empty($data)) {
            return $this->fail('No se enviaron datos válidos.', 400);
        }

        if (!$this->model->update($id, $data)) {
            return $this->fail($this->model->errors());
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Vinilo actualizado correctamente'
        ]);
    }

    // DELETE /api/vinilos/(:num)
    public function delete($id = null)
    {
        $userId = session()->get('auth_user')['id'] ?? null;

        if (!$userId) {
            return $this->failUnauthorized('Usuario no autenticado');
        }

        $vinilo = $this->model->find($id);
        if (!$vinilo) {
            return $this->failNotFound('Vinilo no encontrado');
        }

        // ✅ Verificar que el vinilo pertenezca al usuario
        if ($vinilo['user_id'] != $userId) {
            return $this->failForbidden('No tienes permiso para eliminar este vinilo');
        }

        // Eliminar fotos físicas
        $viniloFotoModel = new ViniloFotoModel();
        $fotos = $viniloFotoModel->where('vinilo_id', $id)->findAll();

        foreach ($fotos as $foto) {
            $path = FCPATH . $foto['ruta_foto'];
            if (file_exists($path)) {
                unlink($path);
            }
        }

        // Eliminar registros de fotos y vinilo
        $viniloFotoModel->where('vinilo_id', $id)->delete();
        $this->model->delete($id);

        return $this->respondDeleted([
            'status'  => 200,
            'message' => 'Vinilo y sus fotografías eliminados correctamente'
        ]);
    }

    // POST /api/vinilos/(:num)/fotos
    public function agregarFotos($viniloId = null)
    {
        $vinilo = $this->model->find($viniloId);
        if (!$vinilo) {
            return $this->failNotFound('Vinilo no encontrado');
        }

        $files = $this->request->getFileMultiple('fotos');
        if (empty($files)) {
            return $this->fail('No se recibieron archivos.', 400);
        }

        $viniloFotoModel = new ViniloFotoModel();
        $uploadedPhotos = [];

        foreach ($files as $file) {
            if ($file->isValid() && !$file->hasMoved()) {
                // Generar nombre único
                $newName = $file->getRandomName();

                // Mover archivo
                $file->move(FCPATH . 'uploads/vinilos/', $newName);

                // Guardar en BD
                $fotoData = [
                    'vinilo_id'  => $viniloId,
                    'ruta_foto'  => 'uploads/vinilos/' . $newName,
                    'es_portada' => 0
                ];

                $viniloFotoModel->insert($fotoData);
                $uploadedPhotos[] = $fotoData;
            }
        }

        return $this->respond([
            'status'  => 200,
            'message' => 'Fotografías subidas correctamente',
            'fotos'   => $uploadedPhotos
        ]);
    }

    // DELETE /api/vinilos/fotos/(:num)
    public function eliminarFoto($fotoId = null)
    {
        $viniloFotoModel = new ViniloFotoModel();
        $foto = $viniloFotoModel->find($fotoId);

        if (!$foto) {
            return $this->failNotFound('Fotografía no encontrada');
        }

        // Eliminar archivo físico
        $path = FCPATH . $foto['ruta_foto'];
        if (file_exists($path)) {
            unlink($path);
        }

        // Eliminar registro
        $viniloFotoModel->delete($fotoId);

        return $this->respond([
            'status'  => 200,
            'message' => 'Fotografía eliminada correctamente'
        ]);
    }

    // GET /api/vinilos/export
    public function export()
    {
        $userId = session()->get('auth_user')['id'] ?? null;

        if (!$userId) {
            return $this->failUnauthorized('Usuario no autenticado');
        }

        $tipo = $this->request->getGet('export') ?? 'csv';
        $buscar = $this->request->getGet('buscar');
        $formato = $this->request->getGet('formato');
        $estado = $this->request->getGet('estado');
        $sort = $this->request->getGet('sort') ?? 'created_at';
        $order = $this->request->getGet('order') ?? 'desc';

        // Validar columnas
        $allowedSortColumns = ['id', 'titulo', 'artista', 'anio_lanzamiento', 'genero', 'formato', 'estado_conservacion', 'precio', 'created_at'];
        if (!in_array($sort, $allowedSortColumns)) {
            $sort = 'created_at';
        }
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        // ✅ FILTRAR POR USUARIO
        $this->model->where('user_id', $userId);

        // Aplicar filtros
        if (!empty($buscar)) {
            $this->model->groupStart()
                ->like('titulo', $buscar)
                ->orLike('artista', $buscar)
                ->orLike('genero', $buscar)
                ->groupEnd();
        }

        if (!empty($formato)) {
            $this->model->where('formato', $formato);
        }

        if (!empty($estado)) {
            $this->model->where('estado_conservacion', $estado);
        }

        $this->model->orderBy($sort, $order);
        $vinilos = $this->model->findAll();

        switch ($tipo) {
            case 'pdf':
                return $this->exportPDF($vinilos);
            case 'xlsx':
                return $this->exportExcel($vinilos);
            case 'csv':
            default:
                return $this->exportCSV($vinilos);
        }
    }

    private function exportCSV($vinilos)
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="vinilos_' . date('Y-m-d_H-i-s') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($output, ['Título', 'Artista', 'Año', 'Género', 'Formato', 'Estado', 'Precio']);

        foreach ($vinilos as $vinilo) {
            fputcsv($output, [
                $vinilo['titulo'],
                $vinilo['artista'],
                $vinilo['anio_lanzamiento'],
                $vinilo['genero'],
                $vinilo['formato'],
                $vinilo['estado_conservacion'],
                '$' . number_format($vinilo['precio'], 2)
            ]);
        }

        fclose($output);
        exit;
    }

    private function exportExcel($vinilos)
    {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="vinilos_' . date('Y-m-d_H-i-s') . '.xls"');

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head><meta charset="UTF-8"></head><body>';
        echo '<table border="1">';
        echo '<tr><th>Título</th><th>Artista</th><th>Año</th><th>Género</th><th>Formato</th><th>Estado</th><th>Precio</th></tr>';

        foreach ($vinilos as $vinilo) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($vinilo['titulo']) . '</td>';
            echo '<td>' . htmlspecialchars($vinilo['artista']) . '</td>';
            echo '<td>' . $vinilo['anio_lanzamiento'] . '</td>';
            echo '<td>' . htmlspecialchars($vinilo['genero']) . '</td>';
            echo '<td>' . htmlspecialchars($vinilo['formato']) . '</td>';
            echo '<td>' . htmlspecialchars($vinilo['estado_conservacion']) . '</td>';
            echo '<td>$' . number_format($vinilo['precio'], 2) . '</td>';
            echo '</tr>';
        }

        echo '</table></body></html>';
        exit;
    }

    private function exportPDF($vinilos)
    {
        $dompdf = new \Dompdf\Dompdf();

        $html = '<html><head><meta charset="utf-8"><style>
            table { width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px; }
            th { background: #F28C28; color: white; padding: 8px; border: 1px solid #ddd; text-align: left; }
            td { padding: 8px; border: 1px solid #ddd; }
            tr:nth-child(even) { background: #f9f9f9; }
            h2 { text-align: center; color: #F28C28; }
        </style></head><body>';

        $html .= '<h2>Catálogo de Vinilos</h2>';
        $html .= '<p>Total de registros: ' . count($vinilos) . '</p>';
        $html .= '<table>';
        $html .= '<tr><th>Título</th><th>Artista</th><th>Año</th><th>Género</th><th>Formato</th><th>Estado</th><th>Precio</th></tr>';

        foreach ($vinilos as $vinilo) {
            $html .= '<tr>';
            $html .= '<td>' . esc($vinilo['titulo']) . '</td>';
            $html .= '<td>' . esc($vinilo['artista']) . '</td>';
            $html .= '<td>' . $vinilo['anio_lanzamiento'] . '</td>';
            $html .= '<td>' . esc($vinilo['genero']) . '</td>';
            $html .= '<td>' . esc($vinilo['formato']) . '</td>';
            $html .= '<td>' . esc($vinilo['estado_conservacion']) . '</td>';
            $html .= '<td>$' . number_format($vinilo['precio'], 2) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table></body></html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->render();
        $dompdf->stream('vinilos_' . date('Y-m-d_H-i-s') . '.pdf');
        exit;
    }
}
