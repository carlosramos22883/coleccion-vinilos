<?= $this->extend('layouts/auth') ?>

<?= $this->section('title') ?>Restablecer Contraseña - <?= env('app.name', 'Viniloteca') ?><?= $this->endSection() ?>

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
            <p class="login-subtitle">Restablece tu contraseña</p>
        </div>

        <!-- Formulario -->
        <div class="login-body">
            <div id="reset-alert" class="alert-custom d-none" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i>
                <span class="alert-message"></span>
            </div>

            <form id="form-reset-password" novalidate>
                <input type="hidden" id="reset-token" value="<?= esc($token) ?>">

                <div class="input-group-custom">
                    <label class="input-label" for="reset-password">
                        <i class="fa-solid fa-lock me-2"></i>Nueva Contraseña
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="reset-password" class="input-custom" placeholder="••••••••" autocomplete="new-password">
                        <i class="fa-solid fa-lock input-icon"></i>
                    </div>
                    <small id="error-password" class="error-text d-none"></small>
                    <small class="password-hint">Mín. 8 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial.</small>
                </div>

                <div class="input-group-custom">
                    <label class="input-label" for="reset-password-confirm">
                        <i class="fa-solid fa-key me-2"></i>Confirmar Contraseña
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="reset-password-confirm" class="input-custom" placeholder="••••••••" autocomplete="new-password">
                        <i class="fa-solid fa-key input-icon"></i>
                    </div>
                    <small id="error-password-confirm" class="error-text d-none"></small>
                </div>

                <button type="submit" class="btn-login">
                    <span>Actualizar Contraseña</span>
                    <i class="fa-solid fa-shield-halved"></i>
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <p>¿Recordaste tu contraseña? <a href="<?= site_url('login') ?>" class="register-link">Inicia sesión aquí</a></p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/auth-reset-password.js') ?>"></script>
<?= $this->endSection() ?>