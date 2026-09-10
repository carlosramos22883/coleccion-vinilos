<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\UserModel;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return response()
                ->setStatusCode(401)
                ->setJSON(['status' => 401, 'error' => 'Acceso no autorizado. Token no proporcionado.']);
        }

        $token = $matches[1];

        // ✅ USAR env() EN LUGAR DE getenv()
        $key = env('JWT_SECRET');

        if (!$key) {
            return response()
                ->setStatusCode(500)
                ->setJSON(['status' => 500, 'error' => 'JWT_SECRET no está configurado']);
        }

        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            $userId = $decoded->data->id ?? null;

            if (!$userId) {
                return response()
                    ->setStatusCode(401)
                    ->setJSON(['status' => 401, 'error' => 'Token inválido o malformado.']);
            }

            // Adjuntar payload decodificado a la petición (como array)
            $request->user = (array) $decoded->data;

            // Verificar permisos si se requiere
            if (!empty($arguments) && isset($arguments[0])) {
                $permisoRequerido = $arguments[0];

                $userModel = new UserModel();
                $permisosUsuario = $userModel->getPermissions($userId);

                if (!in_array($permisoRequerido, $permisosUsuario)) {
                    return response()
                        ->setStatusCode(403)
                        ->setJSON([
                            'status' => 403,
                            'error'  => "Acceso denegado. Se requiere el permiso '{$permisoRequerido}'."
                        ]);
                }
            }
        } catch (\Throwable $e) { // ✅ Capturar Throwable en lugar de Exception
            return response()
                ->setStatusCode(401)
                ->setJSON(['status' => 401, 'error' => 'Token expirado o inválido.']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Sin acciones posteriores
    }
}
