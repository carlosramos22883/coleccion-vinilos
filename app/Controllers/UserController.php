<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class UserController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Listar todos los usuarios
     * GET /usuarios
     */
    public function index(): ResponseInterface
    {
        $usuarios = $this->userModel
            ->select('users.id, users.nombre, users.email, users.role_id, roles.nombre as rol, users.created_at')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->findAll();

        return $this->response->setStatusCode(200)->setJSON([
            'status' => 200,
            'data'   => $usuarios
        ]);
    }

    /**
     * Ver un usuario específico por ID
     * GET /usuarios/{id}
     */
    public function show($id = null): ResponseInterface
    {
        $usuario = $this->userModel
            ->select('users.id, users.nombre, users.email, users.role_id, roles.nombre as rol, users.created_at')
            ->join('roles', 'roles.id = users.role_id', 'left')
            ->find($id);

        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'error'  => 'Usuario no encontrado'
            ]);
        }

        $permisos = $this->userModel->getPermissions($id);

        return $this->response->setStatusCode(200)->setJSON([
            'status' => 200,
            'data'   => [
                'usuario'  => $usuario,
                'permisos' => $permisos
            ]
        ]);
    }

    /**
     * Crear un nuevo usuario
     * POST /usuarios
     */
    public function create(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        $rules = [
            'nombre'   => 'required|min_length[3]|max_length[100]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'role_id'  => 'required|is_not_unique[roles.id]'
        ];

        if (!$this->validateData($input, $rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'errors' => $this->validator->getErrors()
            ]);
        }

        $userId = $this->userModel->insert([
            'nombre'   => $input['nombre'],
            'email'    => $input['email'],
            'password' => password_hash($input['password'], PASSWORD_BCRYPT),
            'role_id'  => $input['role_id'],
        ]);

        $nuevoUsuario = $this->userModel->find($userId);
        unset($nuevoUsuario['password']);

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 201,
            'message' => 'Usuario creado exitosamente',
            'data'    => $nuevoUsuario
        ]);
    }

    /**
     * Actualizar datos de un usuario
     * PUT /usuarios/{id}
     */
    public function update($id = null): ResponseInterface
    {
        $usuario = $this->userModel->find($id);

        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'error'  => 'Usuario no encontrado'
            ]);
        }

        $input = $this->request->getJSON(true) ?? $this->request->getRawInput();

        $rules = [
            'nombre' => 'required|min_length[3]|max_length[100]',
            'email'  => "required|valid_email|is_unique[users.email,id,{$id}]",
        ];

        if (!$this->validateData($input, $rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'errors' => $this->validator->getErrors()
            ]);
        }

        $dataUpdate = [
            'nombre' => $input['nombre'],
            'email'  => $input['email'],
        ];

        if (!empty($input['password'])) {
            $dataUpdate['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
        }

        $this->userModel->update($id, $dataUpdate);

        $usuarioActualizado = $this->userModel->find($id);
        unset($usuarioActualizado['password']);

        return $this->response->setStatusCode(200)->setJSON([
            'status'  => 200,
            'message' => 'Usuario actualizado correctamente',
            'data'    => $usuarioActualizado
        ]);
    }

    /**
     * Eliminar un usuario
     * DELETE /usuarios/{id}
     */
    public function delete($id = null): ResponseInterface
    {
        $usuario = $this->userModel->find($id);

        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'error'  => 'Usuario no encontrado'
            ]);
        }

        $this->userModel->delete($id);

        return $this->response->setStatusCode(200)->setJSON([
            'status'  => 200,
            'message' => 'Usuario eliminado correctamente'
        ]);
    }

    /**
     * Asignar o cambiar el rol de un usuario
     * PUT /usuarios/{id}/rol
     */
    public function assignRole($id = null): ResponseInterface
    {
        $usuario = $this->userModel->find($id);

        if (!$usuario) {
            return $this->response->setStatusCode(404)->setJSON([
                'status' => 404,
                'error'  => 'Usuario no encontrado'
            ]);
        }

        $input = $this->request->getJSON(true) ?? $this->request->getRawInput();

        $rules = [
            'role_id' => 'required|is_not_unique[roles.id]'
        ];

        if (!$this->validateData($input, $rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'errors' => $this->validator->getErrors()
            ]);
        }

        $this->userModel->update($id, ['role_id' => $input['role_id']]);

        return $this->response->setStatusCode(200)->setJSON([
            'status'  => 200,
            'message' => 'Rol de usuario actualizado correctamente'
        ]);
    }
}
