<?= $this->extend('layouts/auth') ?>

<?= $this->section('title') ?>Registro - <?= env('app.name', 'Viniloteca') ?><?= $this->endSection() ?>

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
            <p class="login-subtitle">Únete a nuestra plataforma de vinilos</p>
        </div>

        <!-- Formulario -->
        <div class="login-body">
            <div id="register-alert" class="alert-custom d-none" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                <span class="alert-message"></span>
            </div>

            <form id="form-register" novalidate>
                <!-- Nombre Completo -->
                <div class="input-group-custom">
                    <label class="input-label" for="reg-nombre">
                        <i class="fa-solid fa-user me-2"></i>Nombre Completo
                    </label>
                    <div class="input-wrapper">
                        <input type="text" id="reg-nombre" class="input-custom" placeholder="Tu nombre completo" autocomplete="name">
                        <i class="fa-solid fa-user input-icon"></i>
                    </div>
                    <small id="error-nombre" class="error-text d-none"></small>
                </div>

                <!-- Correo Electrónico -->
                <div class="input-group-custom">
                    <label class="input-label" for="reg-email">
                        <i class="fa-solid fa-envelope me-2"></i>Correo Electrónico
                    </label>
                    <div class="input-wrapper">
                        <input type="email" id="reg-email" class="input-custom" placeholder="tu@email.com" autocomplete="email">
                        <i class="fa-solid fa-envelope input-icon"></i>
                    </div>
                    <small id="error-email" class="error-text d-none"></small>
                </div>

                <!-- Contraseña -->
                <div class="input-group-custom">
                    <label class="input-label" for="reg-password">
                        <i class="fa-solid fa-lock me-2"></i>Contraseña
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="reg-password" class="input-custom" placeholder="••••••••" autocomplete="new-password">
                        <i class="fa-solid fa-lock input-icon"></i>
                    </div>
                    <small id="error-password" class="error-text d-none"></small>
                    <small class="password-hint">Mín. 8 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial.</small>
                </div>

                <!-- Confirmar Contraseña -->
                <div class="input-group-custom">
                    <label class="input-label" for="reg-password-confirm">
                        <i class="fa-solid fa-key me-2"></i>Confirmar Contraseña
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="reg-password-confirm" class="input-custom" placeholder="••••••••" autocomplete="new-password">
                        <i class="fa-solid fa-key input-icon"></i>
                    </div>
                    <small id="error-password-confirm" class="error-text d-none"></small>
                </div>

                <button type="submit" class="btn-login">
                    <span>Registrarse</span>
                    <i class="fa-solid fa-user-plus"></i>
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <p>¿Ya tienes una cuenta? <a href="<?= site_url('login') ?>" class="register-link">Inicia sesión aquí</a></p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/auth-register.js') ?>"></script>
<?= $this->endSection() ?>