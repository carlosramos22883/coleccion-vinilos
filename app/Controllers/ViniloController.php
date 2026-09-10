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
        $buscar  = $this->request->getGet('buscar');
        $limit   = (int) ($this->request->getGet('limit') ?? 10);
        $page    = (int) ($this->request->getGet('page') ?? 1);

        if (!empty($buscar)) {
            $this->model->groupStart()
                ->like('titulo', $buscar)
                ->orLike('artista', $buscar)
                ->orLike('genero', $buscar)
                ->groupEnd();
        }

        $vinilos = $this->model->paginate($limit, 'default', $page);
        $pager   = $this->model->pager;

        // Agregar fotos a cada vinilo
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
        $data = $this->request->getJSON(true);
        if (empty($data)) {
            return $this->fail('No se enviaron datos válidos.', 400);
        }

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
        $vinilo = $this->model->find($id);
        if (!$vinilo) {
            return $this->failNotFound('Vinilo no encontrado');
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
        $vinilo = $this->model->find($id);
        if (!$vinilo) {
            return $this->failNotFound('Vinilo no encontrado');
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
}
