<?php

namespace App\Traits;

trait SendsEmails
{
    // Duración del token de verificación: 24 horas
    private const TOKEN_EXPIRY_HOURS = 24;

    // Duración del token de reseteo: 1 hora
    private const RESET_TOKEN_EXPIRY_HOURS = 1;

    /**
     * Enviar correo de verificación
     */
    protected function enviarCorreoVerificacion(string $email, string $nombre, string $token): void
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
        $logoUrl = base_url('assets/images/logo.png');

        $mensaje = $this->getVerificationEmailTemplate($appName, $appSlogan, $logoUrl, $nombre, $activationUrl, $expiryHours);

        $emailService->setMessage($mensaje);
        $emailService->setMailType('html');
        $emailService->send();
    }

    /**
     * Enviar correo de recuperación de contraseña
     */
    protected function enviarCorreoRecuperacion(string $email, string $nombre, string $token): void
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

        $mensaje = $this->getRecoveryEmailTemplate($appName, $appSlogan, $logoUrl, $nombre, $resetUrl, $expiryHours);

        $emailService->setMessage($mensaje);
        $emailService->setMailType('html');
        $emailService->send();
    }

    /**
     * Template de correo de verificación
     */
    private function getVerificationEmailTemplate(string $appName, string $appSlogan, string $logoUrl, string $nombre, string $activationUrl, int $expiryHours): string
    {
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Verifica tu correo - {$appName}</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
            <table role='presentation' style='width: 100%; border-collapse: collapse; background-color: #f0f2f5;'>
                <tr>
                    <td style='padding: 40px 20px;'>
                        <table role='presentation' style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); overflow: hidden;'>
                            <tr>
                                <td style='padding: 40px 40px 30px; text-align: center; background: linear-gradient(135deg, #FFF5EB 0%, #FFE8D6 100%);'>
                                    <img src='{$logoUrl}' alt='{$appName}' style='width: 100px; height: 100px; object-fit: contain; margin-bottom: 15px;'>
                                    <h1 style='margin: 0; font-size: 28px; font-weight: 700; color: #F28C28;'>{$appName}</h1>
                                    <p style='margin: 8px 0 0; font-size: 14px; color: #6c757d; font-style: italic;'>{$appSlogan}</p>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 40px;'>
                                    <h2 style='margin: 0 0 20px; font-size: 24px; font-weight: 700; color: #212529;'>¡Hola, {$nombre}! 👋</h2>
                                    <p style='margin: 0 0 15px; font-size: 16px; line-height: 1.6; color: #495057;'>
                                        Gracias por registrarte en <strong style='color: #F28C28;'>{$appName}</strong>.
                                    </p>
                                    <p style='margin: 0 0 30px; font-size: 16px; line-height: 1.6; color: #495057;'>
                                        Para completar tu registro y activar tu cuenta, por favor verifica tu dirección de correo electrónico haciendo clic en el botón de abajo:
                                    </p>
                                    <table role='presentation' style='width: 100%; border-collapse: collapse;'>
                                        <tr>
                                            <td style='text-align: center; padding: 10px 0 30px;'>
                                                <a href='{$activationUrl}' 
                                                style='display: inline-block; background-color: #F28C28; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-size: 16px; font-weight: 600; box-shadow: 0 4px 12px rgba(242, 140, 40, 0.3);'>
                                                    📀 Verificar Correo Electrónico
                                                </a>
                                            </td>
                                        </tr>
                                    </table>
                                    <p style='margin: 0 0 10px; font-size: 14px; line-height: 1.5; color: #6c757d;'>
                                        ⏰ Este enlace expirará en <strong style='color: #F28C28;'>{$expiryHours} horas</strong>.
                                    </p>
                                    <p style='margin: 0; font-size: 14px; line-height: 1.5; color: #6c757d;'>
                                        Si no creaste una cuenta, no es necesario realizar ninguna acción.
                                    </p>
                                </td>
                            </tr>
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
    }

    /**
     * Template de correo de recuperación
     */
    private function getRecoveryEmailTemplate(string $appName, string $appSlogan, string $logoUrl, string $nombre, string $resetUrl, int $expiryHours): string
    {
        return "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Recuperación de contraseña - {$appName}</title>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;'>
            <table role='presentation' style='width: 100%; border-collapse: collapse; background-color: #f0f2f5;'>
                <tr>
                    <td style='padding: 40px 20px;'>
                        <table role='presentation' style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); overflow: hidden;'>
                            <tr>
                                <td style='padding: 40px 40px 30px; text-align: center; background: linear-gradient(135deg, #FFF5EB 0%, #FFE8D6 100%);'>
                                    <img src='{$logoUrl}' alt='{$appName}' style='width: 100px; height: 100px; object-fit: contain; margin-bottom: 15px;'>
                                    <h1 style='margin: 0; font-size: 28px; font-weight: 700; color: #F28C28;'>{$appName}</h1>
                                    <p style='margin: 8px 0 0; font-size: 14px; color: #6c757d; font-style: italic;'>{$appSlogan}</p>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 40px;'>
                                    <h2 style='margin: 0 0 20px; font-size: 24px; font-weight: 700; color: #212529;'>¡Hola, {$nombre}! 🔑</h2>
                                    <p style='margin: 0 0 15px; font-size: 16px; line-height: 1.6; color: #495057;'>
                                        Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en <strong style='color: #F28C28;'>{$appName}</strong>.
                                    </p>
                                    <p style='margin: 0 0 30px; font-size: 16px; line-height: 1.6; color: #495057;'>
                                        Si fuiste tú, haz clic en el botón de abajo para crear una nueva contraseña:
                                    </p>
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
    }
}
