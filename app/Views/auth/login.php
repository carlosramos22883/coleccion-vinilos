<?= $this->extend('layouts/auth') ?>

<?= $this->section('title') ?>Iniciar Sesión - <?= env('app.name', 'Viniloteca') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="login-container">
    <div class="glass-card">
        <!-- Logo y Header -->
        <div class="login-header">
            <div class="logo-container">
                <img src="<?= base_url('assets/images/logo.png') ?>"
                    alt="<?= env('app.name', 'Viniloteca') ?> Logo"
                    class="vinilo-logo"
                    data-light="<?= base_url('assets/images/logo.png') ?>"
                    data-dark="<?= base_url('assets/images/logo-dark.png') ?>">
            </div>
            <h1 class="login-title"><?= env('app.name', 'Viniloteca') ?></h1>
            <p class="login-subtitle"><?= env('app.slogan', 'Tu colección de vinilos') ?></p>
        </div>

        <!-- Formulario -->
        <div class="login-body">
            <!-- Alerta de éxito (cuenta verificada) -->
            <div id="success-alert" class="alert-custom alert-success-custom d-none" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>
                <span class="alert-message">¡Cuenta verificada! Ya puedes iniciar sesión.</span>
            </div>

            <!-- Alerta de error (token inválido/expirado) -->
            <div id="error-alert" class="alert-custom d-none" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                <span class="alert-message"></span>
            </div>

            <!-- Alerta general del login -->
            <div id="login-alert" class="alert-custom d-none" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                <span class="alert-message"></span>
            </div>

            <form id="form-login" novalidate>
                <div class="input-group-custom">
                    <label class="input-label" for="login-email">
                        <i class="fa-solid fa-envelope me-2"></i>Correo Electrónico
                    </label>
                    <div class="input-wrapper">
                        <input type="email" id="login-email" class="input-custom" placeholder="tu@email.com" autocomplete="email">
                        <i class="fa-solid fa-envelope input-icon"></i>
                    </div>
                    <small id="error-email" class="error-text d-none"></small>
                </div>

                <div class="input-group-custom">
                    <label class="input-label" for="login-password">
                        <i class="fa-solid fa-lock me-2"></i>Contraseña
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="login-password" class="input-custom" placeholder="••••••••" autocomplete="current-password">
                        <i class="fa-solid fa-lock input-icon"></i>
                    </div>
                    <small id="error-password" class="error-text d-none"></small>
                </div>

                <div class="login-options">
                    <label class="remember-me">
                        <input type="checkbox" id="remember-me">
                        <span class="checkmark"></span>
                        <span>Recordarme</span>
                    </label>
                    <a href="#" class="forgot-password" data-bs-toggle="modal" data-bs-target="#modalForgotPassword">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="btn-login">
                    <span>Ingresar</span>
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
            </form>

            <!-- NUEVO: Enlace para reenviar correo de verificación -->
            <div class="resend-verification-section">
                <p>¿No recibiste el correo de verificación?</p>
                <button type="button" id="btn-resend-verification" class="btn-resend">
                    <i class="fa-solid fa-paper-plane me-1"></i>
                    Reenviar correo de verificación
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <p>¿No tienes una cuenta? <a href="<?= site_url('register') ?>" class="register-link">Regístrate aquí</a></p>
        </div>
    </div>
</div>

<!-- MODAL OLVIDÉ MI CONTRASEÑA -->
<div class="modal fade" id="modalForgotPassword" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-card-modal">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    <i class="fa-solid fa-key me-2" style="color: var(--vinilo-orange);"></i>
                    Recuperar Contraseña
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>

                <div id="forgot-alert" class="alert-custom d-none" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i>
                    <span class="alert-message"></span>
                </div>

                <form id="form-forgot-password" novalidate>
                    <div class="input-group-custom">
                        <label class="input-label" for="forgot-email">
                            <i class="fa-solid fa-envelope me-2"></i>Correo Electrónico
                        </label>
                        <div class="input-wrapper">
                            <input type="email" id="forgot-email" class="input-custom" placeholder="tu@email.com" autocomplete="email">
                            <i class="fa-solid fa-envelope input-icon"></i>
                        </div>
                        <small id="error-forgot-email" class="error-text d-none"></small>
                    </div>

                    <button type="submit" class="btn-login mt-3">
                        <span>Enviar enlace de recuperación</span>
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/auth-login.js') ?>"></script>
<?= $this->endSection() ?>