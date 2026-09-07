<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;
use Firebase\JWT\JWT;
use App\Traits\SendsEmails;

class AuthController extends ResourceController
{
    use SendsEmails;

    protected $format = 'json';
    // Duración del token de verificación: 24 horas
    private const TOKEN_EXPIRY_HOURS = 24;

    // Duración del token de reseteo: 1 hora (más corto por seguridad)
    private const RESET_TOKEN_EXPIRY_HOURS = 1;

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

        $token = bin2hex(random_bytes(32)); // Generar token seguro de verificación
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRY_HOURS . ' hours'));

        $userModel = new UserModel();
        $userId = $userModel->insert([
            'nombre'             => $this->request->getVar('nombre'),
            'email'              => $this->request->getVar('email'),
            'password'           => $this->request->getVar('password'),
            'email_verified'     => 0, // Inicia no verificado
            'verification_token' => $token,
            'verification_token_expires_at' => $expiresAt,
        ]);

        // Asignar rol por defecto (Coleccionista -> ID 2)
        $db = \Config\Database::connect();
        $db->table('user_roles')->insert([
            'user_id'    => $userId,
            'role_id'    => 2,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Enviar correo de verificación
        $this->enviarCorreoVerificacion(
            $this->request->getVar('email'),
            $this->request->getVar('nombre'),
            $token
        );

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Usuario registrado exitosamente. Por favor revisa tu correo electrónico para verificar tu cuenta antes de iniciar sesión.',
        ]);
    }

    public function login()
    {
        // Permitir CORS desde el navegador
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');

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

        // Validar si el correo ha sido verificado
        if ((int)$user['email_verified'] !== 1) {
            // Verificar si el token está expirado
            $tokenExpirado = false;
            if (!empty($user['verification_token_expires_at'])) {
                $tokenExpirado = strtotime($user['verification_token_expires_at']) < time();
            }

            return $this->respond([
                'status'         => 403,
                'error'          => 'Tu cuenta aún no ha sido verificada.',
                'token_expirado' => $tokenExpirado,
                'email'          => $email,
            ], 403);
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

    /**
     * Método para verificar el correo mediante el token del enlace
     */
    public function verify()
    {
        $token = $this->request->getGet('token');

        if (!$token) {
            return redirect()->to('/login?error=token_faltante');
        }

        $userModel = new UserModel();
        $user = $userModel->where('verification_token', $token)->first();

        if (!$user) {
            return redirect()->to('/login?error=token_invalido');
        }

        // Verificar expiración
        if (!empty($user['verification_token_expires_at'])) {
            if (strtotime($user['verification_token_expires_at']) < time()) {
                return redirect()->to('/login?error=token_expirado&email=' . urlencode($user['email']));
            }
        }

        // Actualizar usuario a verificado y limpiar el token
        $userModel->update($user['id'], [
            'email_verified'     => 1,
            'verification_token' => null,
            'verification_token_expires_at' => null,
        ]);

        // Puedes redirigir a una vista bonita de éxito o devolver una respuesta JSON
        return redirect()->to('/login?verified=1');
    }

    /**
     * Reenviar correo de verificación
     * POST /auth/resend-verification
     */
    public function resendVerification()
    {
        $email = $this->request->getVar('email');

        if (!$email) {
            return $this->fail('El correo electrónico es obligatorio');
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        if (!$user) {
            // Por seguridad, no revelamos si el email existe o no
            return $this->respond([
                'status'  => 200,
                'message' => 'Si el correo está registrado, recibirás un enlace de verificación.',
            ]);
        }

        // Si ya está verificado
        if ((int)$user['email_verified'] === 1) {
            return $this->respond([
                'status'  => 200,
                'message' => 'Esta cuenta ya está verificada. Puedes iniciar sesión.',
            ]);
        }

        // Generar nuevo token con expiración
        $nuevoToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRY_HOURS . ' hours'));

        $userModel->update($user['id'], [
            'verification_token'            => $nuevoToken,
            'verification_token_expires_at' => $expiresAt,
        ]);

        // Enviar correo
        $this->enviarCorreoVerificacion($email, $user['nombre'], $nuevoToken);

        return $this->respond([
            'status'  => 200,
            'message' => 'Si el correo está registrado, recibirás un nuevo enlace de verificación.',
        ]);
    }

    /**
     * Solicitar recuperación de contraseña
     * POST /auth/forgot-password
     */
    public function forgotPassword()
    {
        $email = $this->request->getVar('email');

        if (!$email) {
            return $this->fail('El correo electrónico es obligatorio');
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        // Si el usuario NO existe, respondemos igual (por seguridad)
        if (!$user) {
            return $this->respond([
                'status'  => 200,
                'message' => 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.',
            ]);
        }

        // Si la cuenta NO está verificada, enviamos correo de VERIFICACIÓN (no de recuperación)
        if ((int)$user['email_verified'] !== 1) {
            // Generar nuevo token de verificación
            $verificationToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRY_HOURS . ' hours'));

            $userModel->update($user['id'], [
                'verification_token'            => $verificationToken,
                'verification_token_expires_at' => $expiresAt,
            ]);

            // Enviar correo de verificación
            $this->enviarCorreoVerificacion($email, $user['nombre'], $verificationToken);

            return $this->respond([
                'status'  => 200,
                'message' => 'Si el correo está registrado, recibirás un enlace. Revisa también tu bandeja de spam.',
                'tipo'    => 'verificacion', // Para que el frontend pueda personalizar el mensaje
            ]);
        }

        // Si la cuenta SÍ está verificada, enviamos correo de RECUPERACIÓN
        $resetToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::RESET_TOKEN_EXPIRY_HOURS . ' hours'));

        $userModel->update($user['id'], [
            'reset_token'            => $resetToken,
            'reset_token_expires_at' => $expiresAt,
        ]);

        // Enviar correo de recuperación
        $this->enviarCorreoRecuperacion($email, $user['nombre'], $resetToken);

        return $this->respond([
            'status'  => 200,
            'message' => 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.',
            'tipo'    => 'recuperacion',
        ]);
    }

    /**
     * Verificar token de reseteo (para mostrar el formulario)
     * GET /auth/reset-password?token=xxx
     */
    public function verifyResetToken()
    {
        $token = $this->request->getGet('token');

        if (!$token) {
            return redirect()->to('/login?error=reset_token_faltante');
        }

        $userModel = new UserModel();
        $user = $userModel->where('reset_token', $token)->first();

        if (!$user) {
            return redirect()->to('/login?error=reset_token_invalido');
        }

        // Verificar expiración
        if (!empty($user['reset_token_expires_at'])) {
            if (strtotime($user['reset_token_expires_at']) < time()) {
                return redirect()->to('/login?error=reset_token_expirado');
            }
        }

        // Token válido, mostrar formulario
        return view('auth/reset-password', ['token' => $token]);
    }

    /**
     * Procesar el cambio de contraseña
     * POST /auth/reset-password
     */
    public function resetPassword()
    {
        $token = $this->request->getVar('token');
        $password = $this->request->getVar('password');
        $passwordConfirm = $this->request->getVar('password_confirm');

        if (!$token) {
            return $this->fail('Token no proporcionado');
        }

        if (!$password || !$passwordConfirm) {
            return $this->fail('Las contraseñas son obligatorias');
        }

        if ($password !== $passwordConfirm) {
            return $this->fail('Las contraseñas no coinciden');
        }

        // Validar fortaleza de contraseña
        $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
        if (!preg_match($passwordRegex, $password)) {
            return $this->fail('La contraseña debe tener mín. 8 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial.');
        }

        $userModel = new UserModel();
        $user = $userModel->where('reset_token', $token)->first();

        if (!$user) {
            return $this->fail('Token inválido');
        }

        // Verificar expiración
        if (!empty($user['reset_token_expires_at'])) {
            if (strtotime($user['reset_token_expires_at']) < time()) {
                return $this->fail('El token ha expirado. Solicita un nuevo enlace.');
            }
        }

        // Actualizar contraseña y limpiar token
        $userModel->update($user['id'], [
            'password'               => $password, // El callback hashPassword del modelo lo hashea
            'reset_token'            => null,
            'reset_token_expires_at' => null,
        ]);

        return $this->respond([
            'status'  => 200,
            'message' => 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.',
        ]);
    }

    /**
     * Cerrar sesión
     * POST /auth/logout
     */
    public function logout(): ResponseInterface
    {
        // Destruir sesión del servidor
        session()->destroy();

        return $this->respond([
            'status' => 200,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }
}
