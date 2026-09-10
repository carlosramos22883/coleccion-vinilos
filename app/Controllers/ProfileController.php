<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;
use App\Traits\SendsEmails;

class ProfileController extends BaseController
{
    use SendsEmails;

    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        helper('form');
    }

    public function index()
    {
        $user = session()->get('auth_user') ?? null;

        if (!$user || !isset($user['id'])) {
            return redirect()->to('/login')->with('error', 'Usuario no autenticado');
        }

        $userData = $this->userModel->find($user['id']);

        if (!$userData) {
            return redirect()->to('/login')->with('error', 'Usuario no encontrado');
        }

        unset($userData['password']);

        // ✅ Obtener permisos del usuario
        $permisos = $this->userModel->getPermissions($user['id']);

        return view('perfil/index', [
            'user' => $userData,
            'title' => 'Mi Perfil',
            'permisos' => $permisos  // ← AGREGAR ESTO
        ]);
    }

    public function getProfile(): ResponseInterface
    {
        $user = session()->get('auth_user') ?? null;

        if (!$user || !isset($user['id'])) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 401,
                'error'  => 'Usuario no autenticado'
            ]);
        }

        $userData = $this->userModel->find($user['id']);
        $permisos = $this->userModel->getPermissions($user['id']);
        unset($userData['password']);

        return $this->response->setStatusCode(200)->setJSON([
            'status' => 200,
            'data'   => [
                'usuario'  => $userData,
                'permisos' => $permisos
            ]
        ]);
    }

    public function update(): ResponseInterface
    {
        $user = session()->get('auth_user') ?? null;
        if (!$user || !isset($user['id'])) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 401,
                'error' => 'No autenticado'
            ]);
        }
        $userId = $user['id'];

        $rules = [
            'nombre' => 'required|min_length[3]|max_length[100]',
            'email'  => 'required|valid_email',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'error' => 'Validación fallida',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $data = [
            'nombre' => $this->request->getPost('nombre'),
            'email'  => $this->request->getPost('email'),
        ];

        $currentUser = $this->userModel->find($userId);
        if ($currentUser['email'] !== $data['email']) {
            // ✅ Verificar si el email ya existe (incluyendo eliminados para evitar Duplicate Entry en BD)
            $existingEmail = $this->userModel->withDeleted()->where('email', $data['email'])->first();
            if ($existingEmail) {
                return $this->response->setStatusCode(400)->setJSON([
                    'status' => 400,
                    'error' => 'Este correo electrónico no está disponible.'
                ]);
            }

            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $data['email_verified'] = 0;
            $data['verification_token'] = $token;
            $data['verification_token_expires_at'] = $expiresAt;

            $this->enviarCorreoVerificacion($data['email'], $data['nombre'], $token);
            $this->userModel->update($userId, $data);
            session()->destroy();

            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Perfil actualizado. Se ha enviado un correo de verificación a ' . $data['email']
            ]);
        }

        $this->userModel->update($userId, $data);

        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Perfil actualizado correctamente.'
        ]);
    }

    public function cambiarPassword(): ResponseInterface
    {
        $user = session()->get('auth_user') ?? null;
        if (!$user || !isset($user['id'])) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 401,
                'error' => 'No autenticado'
            ]);
        }
        $userId = $user['id'];

        $rules = [
            'password_actual'  => 'required',
            'password_nuevo'   => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password_nuevo]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'error' => 'Validación fallida',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $passwordNuevo = $this->request->getPost('password_nuevo');
        $passwordErrors = $this->validatePasswordStrength($passwordNuevo);
        if (!empty($passwordErrors)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'error' => 'Validación fallida',
                'errors' => $passwordErrors
            ]);
        }

        $userData = $this->userModel->find($userId);

        if (!password_verify($this->request->getPost('password_actual'), $userData['password'])) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'error' => 'La contraseña actual es incorrecta'
            ]);
        }

        $this->userModel->update($userId, [
            'password' => $passwordNuevo
        ]);

        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Contraseña actualizada exitosamente'
        ]);
    }

    private function validatePasswordStrength(string $password): array
    {
        $errors = [];
        if (strlen($password) < 8) $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'La contraseña debe contener al menos una letra mayúscula';
        if (!preg_match('/[a-z]/', $password)) $errors[] = 'La contraseña debe contener al menos una letra minúscula';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'La contraseña debe contener al menos un número';
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'"\\\\|,.<>\/?]/', $password)) $errors[] = 'La contraseña debe contener al menos un carácter especial (!@#$%^&*...)';
        return $errors;
    }

    public function uploadAvatar(): ResponseInterface
    {
        $user = session()->get('auth_user') ?? null;
        if (!$user || !isset($user['id'])) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ]);
        }

        $userId = $user['id'];
        $imageData = $this->request->getPost('image');

        if (!$imageData) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se recibió la imagen'
            ]);
        }

        try {
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageData = base64_decode($imageData);

            if ($imageData === false) {
                throw new \Exception('Error al decodificar la imagen');
            }

            $filename = $userId . '_' . time() . '.png';
            $filepath = FCPATH . 'uploads/avatars/' . $filename;

            if (!is_dir(FCPATH . 'uploads/avatars')) {
                mkdir(FCPATH . 'uploads/avatars', 0755, true);
            }

            if (file_put_contents($filepath, $imageData) === false) {
                throw new \Exception('Error al guardar la imagen en el servidor');
            }

            $currentUser = $this->userModel->find($userId);
            if (!empty($currentUser['avatar'])) {
                $oldFile = FCPATH . 'uploads/' . $currentUser['avatar'];
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }

            $relativePath = 'avatars/' . $filename;
            $updated = $this->userModel->update($userId, ['avatar' => $relativePath]);

            if (!$updated) {
                throw new \Exception('Error al actualizar la base de datos');
            }

            return $this->response->setJSON([
                'success' => true,
                'avatar_url' => base_url('uploads/avatars/' . $filename) . '?v=' . time()
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error en uploadAvatar: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function checkEmail(): ResponseInterface
    {
        $input = $this->request->getJSON(true);
        $email = $input['email'] ?? '';
        $userId = $input['user_id'] ?? 0;

        $exists = $this->userModel
            ->where('email', $email)
            ->where('id !=', $userId)
            ->countAllResults() > 0;

        return $this->response->setJSON([
            'exists' => $exists
        ]);
    }

    public function eliminar(): ResponseInterface
    {
        $user = session()->get('auth_user') ?? null;
        if (!$user || !isset($user['id'])) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 401,
                'error' => 'No autenticado'
            ]);
        }
        $userId = $user['id'];

        $rules = ['password' => 'required'];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'error' => 'La contraseña es obligatoria'
            ]);
        }

        $password = $this->request->getPost('password');
        $userData = $this->userModel->find($userId);

        if (!password_verify($password, $userData['password'])) {
            return $this->response->setStatusCode(400)->setJSON([
                'status' => 400,
                'error' => 'La contraseña es incorrecta'
            ]);
        }

        // ✅ SOFT DELETE: El modelo se encarga de poner deleted_at gracias a useSoftDeletes = true
        $this->userModel->delete($userId);

        session()->destroy();

        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Cuenta eliminada correctamente'
        ]);
    }
}
