<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;
use Firebase\JWT\JWT;

class AuthController extends ResourceController
{
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
     * Método privado para enviar correo de verificación
     */
    private function enviarCorreoVerificacion(string $email, string $nombre, string $token): void
    {
        $appName   = env('app.name', 'Viniloteca');
        $appSlogan = env('app.slogan', 'Tu colección de vinilos');
        $fromEmail = env('email.fromEmail', 'no-reply@viniloteca.local');
        $fromName  = env('email.fromName', 'Viniloteca');
        $expiryHours = self::TOKEN_EXPIRY_HOURS;

        $emailService = \Config\Services::email();
        $emailService->setFrom($fromEmail, $fromName);
        $emailService->setTo($email);
        $emailService->setSubject("Verifica tu correo - {$appName}");

        $activationUrl = base_url("auth/verify?token={$token}");

        // URL del logo (usa el logo claro para el correo)
        $logoUrl = base_url('assets/images/logo.png');

        $mensaje = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Verifica tu correo - {$appName}</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif;'>
            
            <!-- Contenedor principal -->
            <table role='presentation' style='width: 100%; border-collapse: collapse; background-color: #f0f2f5;'>
                <tr>
                    <td style='padding: 40px 20px;'>
                        
                        <!-- Tarjeta blanca centrada -->
                        <table role='presentation' style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); overflow: hidden;'>
                            
                            <!-- Logo y Header -->
                            <tr>
                                <td style='padding: 40px 40px 30px; text-align: center; background: linear-gradient(135deg, #FFF5EB 0%, #FFE8D6 100%);'>
                                    <img src='{$logoUrl}' alt='{$appName}' style='width: 100px; height: 100px; object-fit: contain; margin-bottom: 15px;'>
                                    <h1 style='margin: 0; font-size: 28px; font-weight: 700; color: #F28C28; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>{$appName}</h1>
                                    <p style='margin: 8px 0 0; font-size: 14px; color: #6c757d; font-style: italic;'>{$appSlogan}</p>
                                </td>
                            </tr>
                            
                            <!-- Contenido -->
                            <tr>
                                <td style='padding: 40px;'>
                                    <h2 style='margin: 0 0 20px; font-size: 24px; font-weight: 700; color: #212529; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>¡Hola, {$nombre}! 👋</h2>
                                    
                                    <p style='margin: 0 0 15px; font-size: 16px; line-height: 1.6; color: #495057; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
                                        Gracias por registrarte en <strong style='color: #F28C28;'>{$appName}</strong>.
                                    </p>
                                    
                                    <p style='margin: 0 0 30px; font-size: 16px; line-height: 1.6; color: #495057; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
                                        Para completar tu registro y activar tu cuenta, por favor verifica tu dirección de correo electrónico haciendo clic en el botón de abajo:
                                    </p>
                                    
                                    <!-- Botón de verificación -->
                                    <table role='presentation' style='width: 100%; border-collapse: collapse;'>
                                        <tr>
                                            <td style='text-align: center; padding: 10px 0 30px;'>
                                                <a href='{$activationUrl}' 
                                                style='display: inline-block; background-color: #F28C28; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 16px; font-weight: 600; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; box-shadow: 0 4px 12px rgba(242, 140, 40, 0.3);'>
                                                    📀 Verificar Correo Electrónico
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <!-- Nota de expiración -->
                                    <p style='margin: 0 0 10px; font-size: 14px; line-height: 1.5; color: #6c757d; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
                                        Si no creaste una cuenta, no es necesario realizar ninguna acción.
                                    </p>
                                    <p style='margin: 0; font-size: 14px; line-height: 1.5; color: #6c757d; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
                                        ⏰ Este enlace expirará en <strong style='color: #F28C28;'>{$expiryHours} horas</strong>.
                                    </p>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style='padding: 25px 40px; background-color: #f8f9fa; border-top: 1px solid #e9ecef; text-align: center;'>
                                    <p style='margin: 0; font-size: 13px; color: #6c757d; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
                                        Saludos, <strong>El equipo de {$appName}</strong>
                                    </p>
                                    <p style='margin: 8px 0 0; font-size: 12px; color: #adb5bd; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
                                        © {$appName} - {$appSlogan}
                                    </p>
                                </td>
                            </tr>
                            
                        </table>
                        
                    </td>
                </tr>
            </table>
            
        </body>
        </html>
        ";

        $emailService->setMessage($mensaje);
        $emailService->setMailType('html');
        $emailService->send();
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
     * Método privado para enviar correo de recuperación de contraseña
     */
    private function enviarCorreoRecuperacion(string $email, string $nombre, string $token): void
    {
        $appName   = env('app.name', 'Viniloteca');
        $appSlogan = env('app.slogan', 'Tu colección de vinilos');
        $fromEmail = env('email.fromEmail', 'no-reply@viniloteca.local');
        $fromName  = env('email.fromName', 'Viniloteca');
        $expiryHours = self::RESET_TOKEN_EXPIRY_HOURS;

        $emailService = \Config\Services::email();
        $emailService->setFrom($fromEmail, $fromName);
        $emailService->setTo($email);
        $emailService->setSubject("Recuperación de contraseña - {$appName}");

        $resetUrl = base_url("auth/reset-password?token={$token}");
        $logoUrl = base_url('assets/images/logo.png');

        $mensaje = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Recuperación de contraseña - {$appName}</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif;'>
            
            <table role='presentation' style='width: 100%; border-collapse: collapse; background-color: #f0f2f5;'>
                <tr>
                    <td style='padding: 40px 20px;'>
                        
                        <table role='presentation' style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); overflow: hidden;'>
                            
                            <!-- Header -->
                            <tr>
                                <td style='padding: 40px 40px 30px; text-align: center; background: linear-gradient(135deg, #FFF5EB 0%, #FFE8D6 100%);'>
                                    <img src='{$logoUrl}' alt='{$appName}' style='width: 100px; height: 100px; object-fit: contain; margin-bottom: 15px;'>
                                    <h1 style='margin: 0; font-size: 28px; font-weight: 700; color: #F28C28;'>{$appName}</h1>
                                    <p style='margin: 8px 0 0; font-size: 14px; color: #6c757d; font-style: italic;'>{$appSlogan}</p>
                                </td>
                            </tr>
                            
                            <!-- Contenido -->
                            <tr>
                                <td style='padding: 40px;'>
                                    <h2 style='margin: 0 0 20px; font-size: 24px; font-weight: 700; color: #212529;'>¡Hola, {$nombre}! 🔑</h2>
                                    
                                    <p style='margin: 0 0 15px; font-size: 16px; line-height: 1.6; color: #495057;'>
                                        Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en <strong style='color: #F28C28;'>{$appName}</strong>.
                                    </p>
                                    
                                    <p style='margin: 0 0 30px; font-size: 16px; line-height: 1.6; color: #495057;'>
                                        Si fuiste tú, haz clic en el botón de abajo para crear una nueva contraseña:
                                    </p>
                                    
                                    <!-- Botón -->
                                    <table role='presentation' style='width: 100%; border-collapse: collapse;'>
                                        <tr>
                                            <td style='text-align: center; padding: 10px 0 30px;'>
                                                <a href='{$resetUrl}' 
                                                style='display: inline-block; background-color: #F28C28; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 12px rgba(242, 140, 40, 0.3);'>
                                                    Restablecer Contraseña
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <!-- Advertencia de seguridad -->
                                    <div style='background-color: #fff3cd; border-left: 4px solid #F28C28; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                                        <p style='margin: 0; font-size: 14px; line-height: 1.5; color: #856404;'>
                                            ⚠️ <strong>Importante:</strong> Si no solicitaste este cambio, puedes ignorar este correo. Tu contraseña actual no será modificada.
                                        </p>
                                    </div>
                                    
                                    <p style='margin: 0 0 10px; font-size: 14px; line-height: 1.5; color: #6c757d;'>
                                        ⏰ Este enlace expirará en <strong style='color: #F28C28;'>{$expiryHours} hora(s)</strong>.
                                    </p>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style='padding: 25px 40px; background-color: #f8f9fa; border-top: 1px solid #e9ecef; text-align: center;'>
                                    <p style='margin: 0; font-size: 13px; color: #6c757d;'>
                                        Saludos, <strong>El equipo de {$appName}</strong>
                                    </p>
                                    <p style='margin: 8px 0 0; font-size: 12px; color: #adb5bd;'>
                                        © {$appName} - {$appSlogan}
                                    </p>
                                </td>
                            </tr>
                            
                        </table>
                        
                    </td>
                </tr>
            </table>
            
        </body>
        </html>
        ";

        $emailService->setMessage($mensaje);
        $emailService->setMailType('html');
        $emailService->send();
    }
}
