<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\ViniloModel;
use App\Models\ViniloFotoModel;

class ViniloController extends ResourceController
{
    protected $modelName = 'App\Models\ViniloModel';
    protected $format    = 'json';

    // GET /vinilos
    public function index()
    {
        $viniloModel = new ViniloModel();
        $viniloFotoModel = new ViniloFotoModel();

        // Obtener parámetros de búsqueda y filtro desde la URL
        $buscar   = $this->request->getGet('buscar');
        $artista  = $this->request->getGet('artista');
        $genero   = $this->request->getGet('genero');
        $anio     = $this->request->getGet('anio_lanzamiento');
        $formato  = $this->request->getGet('formato');
        $estado   = $this->request->getGet('estado_conservacion');
        $limit    = (int) ($this->request->getGet('limit') ?? 10);
        $page     = (int) ($this->request->getGet('page') ?? 1);

        if (!empty($buscar)) {
            $viniloModel->groupStart()
                ->like('titulo', $buscar)
                ->orLike('artista', $buscar)
                ->orLike('genero', $buscar)
                ->groupEnd();
        }

        if (!empty($artista)) {
            $viniloModel->like('artista', $artista);
        }

        if (!empty($genero)) {
            $viniloModel->where('genero', $genero);
        }

        if (!empty($anio)) {
            $viniloModel->where('anio_lanzamiento', $anio);
        }

        if (!empty($formato)) {
            $viniloModel->where('formato', $formato);
        }

        if (!empty($estado)) {
            $viniloModel->where('estado_conservacion', $estado);
        }

        $vinilos = $viniloModel->paginate($limit, 'default', $page);
        $pager   = $viniloModel->pager;

        foreach ($vinilos as &$vinilo) {
            $vinilo['fotos'] = $viniloFotoModel->where('vinilo_id', $vinilo['id'])->findAll();
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

    // GET /vinilos/(:num)
    public function show($id = null)
    {
        $vinilo = $this->model->find($id);
        if (!$vinilo) {
            return $this->failNotFound('Vinilo no encontrado');
        }

        $viniloFotoModel = new ViniloFotoModel();
        $vinilo['fotos'] = $viniloFotoModel->where('vinilo_id', $vinilo['id'])->findAll();

        return $this->respond($vinilo);
    }

    // POST /vinilos
    public function create()
    {
        $data = $this->request->getPost();
        if (empty($data)) {
            $data = $this->request->getJSON(true);
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

    // DELETE /vinilos/(:num)
    public function delete($id = null)
    {
        $vinilo = $this->model->find($id);
        if (!$vinilo) {
            return $this->failNotFound('Vinilo no encontrado');
        }

        $viniloFotoModel = new ViniloFotoModel();
        $fotos = $viniloFotoModel->where('vinilo_id', $id)->findAll();

        foreach ($fotos as $foto) {
            $path = FCPATH . $foto['ruta_foto'];
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $this->model->delete($id);

        return $this->respondDeleted([
            'status'  => 200,
            'message' => 'Vinilo y sus fotografías eliminados correctamente'
        ]);
    }

    // PUT /vinilos/(:num)
    public function update($id = null)
    {
        $vinilo = $this->model->find($id);
        if (!$vinilo) {
            return $this->failNotFound('Vinilo no encontrado');
        }

        $data = $this->request->getJSON(true);
        if (empty($data)) {
            $data = $this->request->getRawInput();
        }

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
}
