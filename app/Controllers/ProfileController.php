<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class ProfileController extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Obtener el perfil del usuario autenticado
     * GET /perfil
     */
    public function index(): ResponseInterface
    {
        // El ID del usuario autenticado fue inyectado en la request por el PermissionFilter
        $userId = $this->request->user_id;

        $user = $this->userModel->find($userId);

        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 404,
                'error'   => 'Usuario no encontrado'
            ]);
        }

        // Obtener permisos del usuario
        $permisos = $this->userModel->getPermissions($userId);

        unset($user['password']);

        return $this->response->setStatusCode(200)->setJSON([
            'status' => 200,
            'data'   => [
                'usuario'  => $user,
                'permisos' => $permisos
            ]
        ]);
    }

    /**
     * Actualizar datos básicos del perfil (nombre, email)
     * PUT /perfil
     */
    public function update(): ResponseInterface
    {
        $userId = $this->request->user_id;
        $input  = $this->request->getJSON(true) ?? $this->request->getRawInput();

        $rules = [
            'nombre' => 'required|min_length[3]|max_length[100]',
            'email'  => "required|valid_email|is_unique[users.email,id,{$userId}]",
        ];

        if (!$this->validateData($input, $rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'errors' => $this->validator->getErrors()
            ]);
        }

        $this->userModel->update($userId, [
            'nombre' => $input['nombre'],
            'email'  => $input['email'],
        ]);

        $userActualizado = $this->userModel->find($userId);
        unset($userActualizado['password']);

        return $this->response->setStatusCode(200)->setJSON([
            'status'  => 200,
            'message' => 'Perfil actualizado correctamente',
            'data'    => $userActualizado
        ]);
    }

    /**
     * Cambiar la contraseña del usuario
     * PUT /perfil/cambiar-password
     */
    public function cambiarPassword(): ResponseInterface
    {
        $userId = $this->request->user_id;
        $input  = $this->request->getJSON(true) ?? $this->request->getRawInput();

        $rules = [
            'password_actual' => 'required',
            'password_nuevo'  => 'required|min_length[8]',
        ];

        if (!$this->validateData($input, $rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'errors' => $this->validator->getErrors()
            ]);
        }

        $user = $this->userModel->find($userId);

        // Verificar la contraseña actual
        if (!password_verify($input['password_actual'], $user['password'])) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 401,
                'error'  => 'La contraseña actual no es correcta'
            ]);
        }

        // Actualizar con el nuevo hash
        $this->userModel->update($userId, [
            'password' => password_hash($input['password_nuevo'], PASSWORD_BCRYPT)
        ]);

        return $this->response->setStatusCode(200)->setJSON([
            'status'  => 200,
            'message' => 'Contraseña actualizada exitosamente'
        ]);
    }
}
