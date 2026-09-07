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
        // 1. Intentar obtener token del header Authorization
        $authHeader = $request->getHeaderLine('Authorization');

        // 2. Si no hay header, intentar obtener de la sesión de PHP
        if (empty($authHeader)) {
            $sessionToken = session()->get('jwt_token');
            if ($sessionToken) {
                $authHeader = 'Bearer ' . $sessionToken;
            }
        }

        // 3. Si aún no hay token, intentar obtener de cookie
        if (empty($authHeader)) {
            $cookieToken = $request->getCookie('jwt_token');
            if ($cookieToken) {
                $authHeader = 'Bearer ' . $cookieToken;
            }
        }

        if (empty($authHeader)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => 401,
                    'error' => 'Acceso denegado: Token no proporcionado'
                ]);
        }

        // Extraer token
        $token = str_replace('Bearer ', '', $authHeader);

        try {
            $key = getenv('JWT_SECRET');
            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            // Inyectar usuario en la request
            $request->user = $decoded->data;
        } catch (\Exception $e) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status' => 401,
                    'error' => 'Token inválido o expirado'
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Si hay token en la request, guardarlo en sesión para futuras peticiones
        $authHeader = $request->getHeaderLine('Authorization');
        if (!empty($authHeader)) {
            $token = str_replace('Bearer ', '', $authHeader);
            session()->set('jwt_token', $token);
        }
    }
}
