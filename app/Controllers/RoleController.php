<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class RoleController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Listar todos los roles con sus permisos asociados
     * GET /roles
     */
    public function index(): ResponseInterface
    {
        $roles = $this->db->table('roles')->get()->getResultArray();

        foreach ($roles as &$rol) {
            $rol['permisos'] = $this->db->table('role_permissions')
                ->select('permissions.id, permissions.slug, permissions.nombre')
                ->join('permissions', 'permissions.id = role_permissions.permission_id')
                ->where('role_permissions.role_id', $rol['id'])
                ->get()->getResultArray();
        }

        return $this->response->setStatusCode(200)->setJSON([
            'status' => 200,
            'data'   => $roles
        ]);
    }

    /**
     * Ver detalles de un rol específico
     * GET /roles/{id}
     */
    public function show($id = null): ResponseInterface
    {
        $rol = $this->db->table('roles')->where('id', $id)->get()->getRowArray();

        if (!$rol) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'error'  => 'Rol no encontrado'
            ]);
        }

        $rol['permisos'] = $this->db->table('role_permissions')
            ->select('permissions.id, permissions.slug, permissions.nombre')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('role_permissions.role_id', $id)
            ->get()->getResultArray();

        return $this->response->setStatusCode(200)->setJSON([
            'status' => 200,
            'data'   => $rol
        ]);
    }

    /**
     * Crear un nuevo rol
     * POST /roles
     */
    public function create(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        $rules = [
            'nombre'      => 'required|min_length[3]|is_unique[roles.nombre]',
            'descripcion' => 'permit_empty|max_length[255]'
        ];

        if (!$this->validateData($input, $rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'errors' => $this->validator->getErrors()
            ]);
        }

        $this->db->table('roles')->insert([
            'nombre'      => $input['nombre'],
            'descripcion' => $input['descripcion'] ?? null
        ]);

        $roleId = $this->db->insertID();

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 201,
            'message' => 'Rol creado correctamente',
            'data'    => ['id' => $roleId, 'nombre' => $input['nombre']]
        ]);
    }

    /**
     * Actualizar la lista de permisos de un rol (Sincronización)
     * POST /roles/{id}/permisos
     */
    public function syncPermissions($id = null): ResponseInterface
    {
        $rol = $this->db->table('roles')->where('id', $id)->get()->getRowArray();

        if (!$rol) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'error'  => 'Rol no encontrado'
            ]);
        }

        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        $rules = [
            'permisos'   => 'required|is_array',
            'permisos.*' => 'integer|is_not_unique[permissions.id]'
        ];

        if (!$this->validateData($input, $rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'errors' => $this->validator->getErrors()
            ]);
        }

        // Limpiar permisos anteriores
        $this->db->table('role_permissions')->where('role_id', $id)->delete();

        // Asignar los nuevos permisos
        $batchData = [];
        foreach ($input['permisos'] as $permisoId) {
            $batchData[] = [
                'role_id'       => $id,
                'permission_id' => $permisoId
            ];
        }

        if (!empty($batchData)) {
            $this->db->table('role_permissions')->insertBatch($batchData);
        }

        return $this->response->setStatusCode(200)->setJSON([
            'status'  => 200,
            'message' => 'Permisos del rol sincronizados correctamente'
        ]);
    }

    /**
     * Eliminar un rol
     * DELETE /roles/{id}
     */
    public function delete($id = null): ResponseInterface
    {
        $rol = $this->db->table('roles')->where('id', $id)->get()->getRowArray();

        if (!$rol) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'error'  => 'Rol no encontrado'
            ]);
        }

        // Validar si hay usuarios asignados a este rol antes de borrar
        $usuariosConRol = $this->db->table('users')->where('role_id', $id)->countAllResults();

        if ($usuariosConRol > 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'error'  => "No se puede eliminar el rol porque está asignado a {$usuariosConRol} usuario(s)."
            ]);
        }

        $this->db->table('roles')->where('id', $id)->delete();

        return $this->response->setStatusCode(200)->setJSON([
            'status'  => 200,
            'message' => 'Rol eliminado correctamente'
        ]);
    }
}
