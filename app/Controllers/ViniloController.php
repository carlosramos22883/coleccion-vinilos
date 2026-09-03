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
        $viniloFotoModel = new ViniloFotoModel();
        $vinilos = $this->model->findAll();

        foreach ($vinilos as &$vinilo) {
            $vinilo['fotos'] = $viniloFotoModel->where('vinilo_id', $vinilo['id'])->findAll();
        }

        return $this->respond($vinilos);
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

        if (!$this->model->save($data)) {
            return $this->fail($this->model->errors());
        }

        $viniloId = $this->model->getInsertID();
        $viniloFotoModel = new ViniloFotoModel();

        // Procesar subida de fotografías
        $fotos = $this->request->getFiles();
        if (isset($fotos['fotos'])) {
            $esPrimera = true;
            foreach ($fotos['fotos'] as $foto) {
                if ($foto->isValid() && !$foto->hasMoved()) {
                    $newName = $foto->getRandomName();
                    $foto->move(FCPATH . 'uploads/vinilos', $newName);

                    $viniloFotoModel->save([
                        'vinilo_id'  => $viniloId,
                        'ruta_foto'  => 'uploads/vinilos/' . $newName,
                        'es_portada' => $esPrimera ? 1 : 0
                    ]);
                    $esPrimera = false;
                }
            }
        }

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
}
