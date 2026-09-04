<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;
use Firebase\JWT\JWT;

class AuthController extends ResourceController
{
    protected $format = 'json';

    public function register()
    {
        $rules = [
            'nombre'   => 'required|min_length[2]|max_length[100]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $userModel = new UserModel();
        $userId = $userModel->insert([
            'nombre'   => $this->request->getVar('nombre'),
            'email'    => $this->request->getVar('email'),
            'password' => $this->request->getVar('password'),
        ]);

        // Asignar rol por defecto (Coleccionista -> ID 2)
        $db = \Config\Database::connect();
        $db->table('user_roles')->insert([
            'user_id' => $userId,
            'role_id' => 2,
        ]);

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Usuario registrado exitosamente',
        ]);
    }

    public function login()
    {
        // Permitir CORS desde el navegador
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');

        // Manejar preflight options en caso de llegar al controlador
        if (strtolower($this->request->getMethod()) === 'options') {
            return $this->response->setStatusCode(200);
        }

        $email    = $this->request->getVar('email');
        $password = $this->request->getVar('password');

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->failUnauthorized('Credenciales inválidas');
        }

        $roles = array_column($userModel->getRoles($user['id']), 'nombre');

        $key = getenv('JWT_SECRET');
        $ttl = (int)(getenv('JWT_TIME_TO_LIVE') ?: 86400);
        $iat = time();
        $exp = $iat + $ttl;

        $payload = [
            'iss'  => 'ColeccionVinilosAPI',
            'aud'  => 'ColeccionVinilosApp',
            'iat'  => $iat,
            'exp'  => $exp,
            'data' => [
                'id'     => $user['id'],
                'nombre' => $user['nombre'],
                'email'  => $user['email'],
                'roles'  => $roles,
            ],
        ];

        $token = JWT::encode($payload, $key, 'HS256');

        return $this->respond([
            'status' => 200,
            'token'  => $token,
            'user'   => $payload['data'],
        ]);
    }
}
