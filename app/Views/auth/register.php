<?= $this->extend('layouts/auth') ?>

<?= $this->section('title') ?>Registro de Usuario<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center w-100">
    <div class="col-md-8 col-lg-7 col-xl-7 col-xxl-5">
        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-header bg-primary text-white text-center py-4 rounded-top-4">
                <h4 class="mb-0"><i class="fa-solid fa-user-plus me-2"></i>Crear Cuenta</h4>
                <p class="text-white-50 small mb-0 mt-1">Únete a nuestra plataforma de vinilos</p>
            </div>
            <div class="card-body p-4">
                <div id="register-alert" class="alert alert-danger d-none" role="alert"></div>

                <form id="form-register" novalidate>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre Completo</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-user text-muted"></i></span>
                            <input type="text" id="reg-nombre" class="form-control" placeholder="Tu nombre completo">
                        </div>
                        <small id="error-nombre" class="text-danger fw-semibold mt-1" style="display: none;"></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-envelope text-muted"></i></span>
                            <input type="email" id="reg-email" class="form-control" placeholder="Correo electrónico">
                        </div>
                        <small id="error-email" class="text-danger fw-semibold mt-1" style="display: none;"></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-key text-muted"></i></span>
                            <input type="password" id="reg-password" class="form-control" placeholder="Contraseña">
                        </div>
                        <small id="error-password" class="text-danger fw-semibold mt-1" style="display: none;"></small>
                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Mín. 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirmar Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-key text-muted"></i></span>
                            <input type="password" id="reg-password-confirm" class="form-control" placeholder="Confirmar Contraseña">
                        </div>
                        <small id="error-password-confirm" class="text-danger fw-semibold mt-1" style="display: none;"></small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                        <i class="fa-solid fa-check me-1"></i> Registrarse
                    </button>
                </form>
            </div>
            <div class="card-footer text-center py-3 bg-light rounded-bottom-4">
                <small class="text-muted">¿Ya tienes una cuenta? <a href="<?= site_url('login') ?>" class="text-decoration-none fw-semibold">Inicia sesión aquí</a></small>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<!-- Llamamos al script externo de registro -->
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/auth-register.js') ?>"></script>
<?= $this->endSection() ?>