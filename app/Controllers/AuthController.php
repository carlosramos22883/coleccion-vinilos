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
    private const TOKEN_EXPIRY_HOURS = 24;
    private const RESET_TOKEN_EXPIRY_HOURS = 1;

    public function register()
    {
        $rules = [
            'nombre'   => 'required|min_length[2]|max_length[100]',
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors());
        }

        $email = $this->request->getVar('email');
        $userModel = new UserModel();

        // ✅ Verificar si el correo ya existe (incluyendo eliminados lógicamente)
        $existingUser = $userModel->withDeleted()->where('email', $email)->first();

        if ($existingUser) {
            if (!empty($existingUser['deleted_at'])) {
                // ✅ El usuario existe pero está eliminado. ¡Lo restauramos!
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRY_HOURS . ' hours'));

                $userModel->update($existingUser['id'], [
                    'nombre'                      => $this->request->getVar('nombre'),
                    'password'                    => $this->request->getVar('password'),
                    'deleted_at'                  => null, // Restaurar
                    'email_verified'              => 0,
                    'verification_token'          => $token,
                    'verification_token_expires_at' => $expiresAt,
                ]);

                $userId = $existingUser['id'];

                // Asegurar que tenga el rol por defecto si por alguna razón no lo tiene
                $db = \Config\Database::connect();
                $hasRole = $db->table('user_roles')->where('user_id', $userId)->countAllResults() > 0;
                if (!$hasRole) {
                    $db->table('user_roles')->insert([
                        'user_id'    => $userId,
                        'role_id'    => 2,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }

                $this->enviarCorreoVerificacion($email, $this->request->getVar('nombre'), $token);

                return $this->respondCreated([
                    'status'  => 201,
                    'message' => 'Tu cuenta ha sido restaurada exitosamente. Por favor revisa tu correo electrónico para verificarla.',
                ]);
            } else {
                // El usuario existe y está activo
                return $this->fail('Este correo electrónico ya está registrado.');
            }
        }

        // Crear nuevo usuario normalmente
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRY_HOURS . ' hours'));

        $userId = $userModel->insert([
            'nombre'                      => $this->request->getVar('nombre'),
            'email'                       => $email,
            'password'                    => $this->request->getVar('password'),
            'email_verified'              => 0,
            'verification_token'          => $token,
            'verification_token_expires_at' => $expiresAt,
        ]);

        $db = \Config\Database::connect();
        $db->table('user_roles')->insert([
            'user_id'    => $userId,
            'role_id'    => 2,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $this->enviarCorreoVerificacion($email, $this->request->getVar('nombre'), $token);

        return $this->respondCreated([
            'status'  => 201,
            'message' => 'Usuario registrado exitosamente. Por favor revisa tu correo electrónico para verificar tu cuenta antes de iniciar sesión.',
        ]);
    }

    public function login()
    {
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');

        if (strtolower($this->request->getMethod()) === 'options') {
            return $this->response->setStatusCode(200);
        }

        $email    = $this->request->getVar('email');
        $password = $this->request->getVar('password');

        $userModel = new UserModel();
        // ✅ Usamos withDeleted() para poder detectar si la cuenta fue eliminada
        $user = $userModel->withDeleted()->where('email', $email)->first();

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->failUnauthorized('Credenciales inválidas');
        }

        // ✅ Verificar si la cuenta está eliminada lógicamente
        if (!empty($user['deleted_at'])) {
            return $this->respond([
                'status'     => 403,
                'error'      => 'Tu cuenta ha sido eliminada. Por favor, ve a la pantalla de registro e ingresa este correo para restaurar tu cuenta.',
                'is_deleted' => true,
                'nombre'     => $user['nombre']
            ], 403);
        }

        if ((int)$user['email_verified'] !== 1) {
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

        $key = env('JWT_SECRET');
        $ttl = (int)(env('JWT_TIME_TO_LIVE') ?: 86400);
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

        // Guardamos el token en la sesión de PHP para que el Filter lo encuentre
        session()->set('jwt_token', $token);

        // Guardamos los datos del usuario en la sesión para que los controladores lo usen
        session()->set('auth_user', $payload['data']);

        return $this->respond([
            'status' => 200,
            'token'  => $token,
            'user'   => $payload['data'],
        ]);
    }

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

        if (!empty($user['verification_token_expires_at'])) {
            if (strtotime($user['verification_token_expires_at']) < time()) {
                return redirect()->to('/login?error=token_expirado&email=' . urlencode($user['email']));
            }
        }

        $userModel->update($user['id'], [
            'email_verified'     => 1,
            'verification_token' => null,
            'verification_token_expires_at' => null,
        ]);

        return redirect()->to('/login?verified=1');
    }

    public function resendVerification()
    {
        $email = $this->request->getVar('email');

        if (!$email) {
            return $this->fail('El correo electrónico es obligatorio');
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        if (!$user) {
            return $this->respond([
                'status'  => 200,
                'message' => 'Si el correo está registrado, recibirás un enlace de verificación.',
            ]);
        }

        if ((int)$user['email_verified'] === 1) {
            return $this->respond([
                'status'  => 200,
                'message' => 'Esta cuenta ya está verificada. Puedes iniciar sesión.',
            ]);
        }

        $nuevoToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRY_HOURS . ' hours'));

        $userModel->update($user['id'], [
            'verification_token'            => $nuevoToken,
            'verification_token_expires_at' => $expiresAt,
        ]);

        $this->enviarCorreoVerificacion($email, $user['nombre'], $nuevoToken);

        return $this->respond([
            'status'  => 200,
            'message' => 'Si el correo está registrado, recibirás un nuevo enlace de verificación.',
        ]);
    }

    public function forgotPassword()
    {
        $email = $this->request->getVar('email');

        if (!$email) {
            return $this->fail('El correo electrónico es obligatorio');
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        if (!$user) {
            return $this->respond([
                'status'  => 200,
                'message' => 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.',
            ]);
        }

        if ((int)$user['email_verified'] !== 1) {
            $verificationToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRY_HOURS . ' hours'));

            $userModel->update($user['id'], [
                'verification_token'            => $verificationToken,
                'verification_token_expires_at' => $expiresAt,
            ]);

            $this->enviarCorreoVerificacion($email, $user['nombre'], $verificationToken);

            return $this->respond([
                'status'  => 200,
                'message' => 'Si el correo está registrado, recibirás un enlace. Revisa también tu bandeja de spam.',
                'tipo'    => 'verificacion',
            ]);
        }

        $resetToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::RESET_TOKEN_EXPIRY_HOURS . ' hours'));

        $userModel->update($user['id'], [
            'reset_token'            => $resetToken,
            'reset_token_expires_at' => $expiresAt,
        ]);

        $this->enviarCorreoRecuperacion($email, $user['nombre'], $resetToken);

        return $this->respond([
            'status'  => 200,
            'message' => 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.',
            'tipo'    => 'recuperacion',
        ]);
    }

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

        if (!empty($user['reset_token_expires_at'])) {
            if (strtotime($user['reset_token_expires_at']) < time()) {
                return redirect()->to('/login?error=reset_token_expirado');
            }
        }

        return view('auth/reset-password', ['token' => $token]);
    }

    public function resetPassword()
    {
        $token = $this->request->getVar('token');
        $password = $this->request->getVar('password');
        $passwordConfirm = $this->request->getVar('password_confirm');

        if (!$token) return $this->fail('Token no proporcionado');
        if (!$password || !$passwordConfirm) return $this->fail('Las contraseñas son obligatorias');
        if ($password !== $passwordConfirm) return $this->fail('Las contraseñas no coinciden');

        $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
        if (!preg_match($passwordRegex, $password)) {
            return $this->fail('La contraseña debe tener mín. 8 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial.');
        }

        $userModel = new UserModel();
        $user = $userModel->where('reset_token', $token)->first();

        if (!$user) return $this->fail('Token inválido');

        if (!empty($user['reset_token_expires_at'])) {
            if (strtotime($user['reset_token_expires_at']) < time()) {
                return $this->fail('El token ha expirado. Solicita un nuevo enlace.');
            }
        }

        $userModel->update($user['id'], [
            'password'               => $password,
            'reset_token'            => null,
            'reset_token_expires_at' => null,
        ]);

        return $this->respond([
            'status'  => 200,
            'message' => 'Contraseña actualizada correctamente. Ya puedes iniciar sesión.',
        ]);
    }

    public function logout(): ResponseInterface
    {
        session()->destroy();
        return $this->respond([
            'status' => 200,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }
}
