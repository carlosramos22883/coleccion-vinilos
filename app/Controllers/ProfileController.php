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

    /**
     * 1. Mostrar vista del perfil (HTML) - GET /perfil
     */
    public function index()
    {
        // DEBUG: Ver qué hay en la request
        log_message('debug', 'ProfileController::index() llamado');
        log_message('debug', 'Authorization header: ' . $this->request->getHeaderLine('Authorization'));
        log_message('debug', 'Request user: ' . print_r($this->request->user ?? null, true));
        $user = $this->request->user ?? null;

        if (!$user || !isset($user->id)) {
            return redirect()->to('/login')->with('error', 'Usuario no autenticado');
        }

        $userData = $this->userModel->find($user->id);

        if (!$userData) {
            return redirect()->to('/login')->with('error', 'Usuario no encontrado');
        }

        unset($userData['password']);

        // ¡AQUÍ ESTÁ LA CLAVE! Devolvemos la vista HTML, no JSON
        return view('perfil/index', [
            'user' => $userData,
            'title' => 'Mi Perfil'
        ]);
    }

    /**
     * 2. Obtener perfil (API JSON para app.js) - GET /perfil/api
     */
    public function getProfile(): ResponseInterface
    {
        $user = $this->request->user ?? null;

        if (!$user || !isset($user->id)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 401,
                'error'  => 'Usuario no autenticado'
            ]);
        }

        $userData = $this->userModel->find($user->id);
        $permisos = $this->userModel->getPermissions($user->id);
        unset($userData['password']);

        return $this->response->setStatusCode(200)->setJSON([
            'status' => 200,
            'data'   => [
                'usuario'  => $userData,
                'permisos' => $permisos
            ]
        ]);
    }

    /**
     * 3. Actualizar perfil (Formulario HTML) - POST /perfil/update
     */
    public function update(): ResponseInterface
    {
        $user = $this->request->user ?? null;
        if (!$user || !isset($user->id)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status' => 401,
                'error' => 'No autenticado'
            ]);
        }
        $userId = $user->id;

        $rules = [
            'nombre' => 'required|min_length[3]|max_length[100]',
            'email'  => "required|valid_email|is_unique[users.email,id,{$userId}]",
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

        // Verificar si el email cambió
        $currentUser = $this->userModel->find($userId);
        if ($currentUser['email'] !== $data['email']) {
            // Generar nuevo token de verificación
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

            $data['email_verified'] = 0;
            $data['verification_token'] = $token;
            $data['verification_token_expires_at'] = $expiresAt;

            // Enviar nuevo correo de verificación
            $this->enviarCorreoVerificacion($data['email'], $data['nombre'], $token);

            // Actualizar en base de datos
            $this->userModel->update($userId, $data);

            // Cerrar sesión del servidor
            session()->destroy();

            return $this->response->setJSON([
                'status' => 200,
                'message' => 'Perfil actualizado. Se ha enviado un correo de verificación a ' . $data['email']
            ]);
        }

        // Si no cambió el email, actualizar normalmente
        $this->userModel->update($userId, $data);

        return $this->response->setJSON([
            'status' => 200,
            'message' => 'Perfil actualizado correctamente.'
        ]);
    }

    /**
     * 4. Cambiar contraseña (Formulario HTML) - POST /perfil/cambiar-password
     */
    public function cambiarPassword(): ResponseInterface
    {
        $user = $this->request->user ?? null;
        if (!$user || !isset($user->id)) {
            return redirect()->to('/login')->with('error', 'No autenticado');
        }
        $userId = $user->id;

        $rules = [
            'password_actual'  => 'required',
            'password_nuevo'   => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password_nuevo]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $passwordNuevo = $this->request->getPost('password_nuevo');

        // Validar fortaleza de contraseña
        $passwordErrors = $this->validatePasswordStrength($passwordNuevo);
        if (!empty($passwordErrors)) {
            return redirect()->back()->withInput()->with('errors', $passwordErrors);
        }

        $userData = $this->userModel->find($userId);

        if (!password_verify($this->request->getPost('password_actual'), $userData['password'])) {
            return redirect()->back()->with('error', 'La contraseña actual es incorrecta');
        }

        $this->userModel->update($userId, [
            'password' => password_hash($passwordNuevo, PASSWORD_BCRYPT)
        ]);

        return redirect()->back()->with('success', 'Contraseña actualizada exitosamente');
    }

    /**
     * Validar fortaleza de contraseña
     */
    private function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una letra mayúscula';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una letra minúscula';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un número';
        }

        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'"\\\\|,.<>\/?]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un carácter especial (!@#$%^&*...)';
        }

        return $errors;
    }

    /**
     * Subir y recortar avatar
     * POST /perfil/upload-avatar
     */
    public function uploadAvatar(): ResponseInterface
    {
        $user = $this->request->user ?? null;
        if (!$user || !isset($user->id)) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ]);
        }

        $userId = $user->id;
        $imageData = $this->request->getPost('image');

        if (!$imageData) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No se recibió la imagen'
            ]);
        }

        try {
            // Decodificar base64
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageData = base64_decode($imageData);

            if ($imageData === false) {
                throw new \Exception('Error al decodificar la imagen');
            }

            // Generar nombre único
            $filename = $userId . '_' . time() . '.png';

            // CAMBIO IMPORTANTE: Guardar en public/uploads en lugar de writable
            $filepath = FCPATH . 'uploads/avatars/' . $filename;

            // Crear directorio si no existe
            if (!is_dir(FCPATH . 'uploads/avatars')) {
                mkdir(FCPATH . 'uploads/avatars', 0755, true);
            }

            // Guardar imagen
            if (file_put_contents($filepath, $imageData) === false) {
                throw new \Exception('Error al guardar la imagen en el servidor');
            }

            // Eliminar avatar anterior si existe
            $currentUser = $this->userModel->find($userId);
            if (!empty($currentUser['avatar'])) {
                $oldFile = FCPATH . 'uploads/' . $currentUser['avatar'];
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }

            // Guardar SOLO el nombre del archivo (no la ruta completa)
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

    /**
     * Verificar si un email ya está en uso
     * POST /perfil/check-email
     */
    public function checkEmail(): ResponseInterface
    {
        $input = $this->request->getJSON(true);

        $email = $input['email'] ?? '';
        $userId = $input['user_id'] ?? 0;

        // Buscar si el email existe y NO es del usuario actual
        $exists = $this->userModel
            ->where('email', $email)
            ->where('id !=', $userId)
            ->countAllResults() > 0;

        return $this->response->setJSON([
            'exists' => $exists
        ]);
    }
}
