<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            $sessionToken = session()->get('jwt_token');
            if ($sessionToken) {
                $authHeader = 'Bearer ' . $sessionToken;
            }
        }

        if (empty($authHeader)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 401, 'error' => 'Acceso denegado: Token no proporcionado']);
        }

        $token = str_replace('Bearer ', '', $authHeader);

        try {
            $key = env('JWT_SECRET');
            if (!$key) {
                throw new \Exception('JWT_SECRET no está configurada');
            }

            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $userData = (array) $decoded->data;
            session()->set('auth_user', $userData);
        } catch (\Throwable $e) { // ✅ Captura Exception Y TypeError
            // 🔥 NUCLEAR: Si el token falla, destruimos la sesión para evitar bucles
            session()->destroy();

            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 401, 'error' => 'Token inválido o expirado']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!empty($authHeader)) {
            $token = str_replace('Bearer ', '', $authHeader);
            session()->set('jwt_token', $token);
        }
    }
}
