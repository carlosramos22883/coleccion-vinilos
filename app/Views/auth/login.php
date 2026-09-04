<?= $this->extend('layouts/auth') ?>

<?= $this->section('title') ?>Iniciar Sesión<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row justify-content-center w-100">
    <div class="col-md-8 col-lg-6 col-xl-5 col-xxl-4">
        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-header bg-primary text-white text-center py-4 rounded-top-4">
                <h4 class="mb-0"><i class="fa-solid fa-record-vinyl me-2"></i>Vinilos App</h4>
                <p class="text-white-50 small mb-0 mt-1">Inicia sesión en tu cuenta</p>
            </div>
            <div class="card-body p-4">
                <div id="login-alert" class="alert alert-danger d-none" role="alert"></div>

                <form id="form-login" novalidate>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-envelope text-muted"></i></span>
                            <input type="email" id="login-email" class="form-control" placeholder="Correo electrónico">
                        </div>
                        <small id="error-email" class="text-danger fw-semibold mt-1" style="display: none;"></small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-key text-muted"></i></span>
                            <input type="password" id="login-password" class="form-control" placeholder="Contraseña">
                        </div>
                        <small id="error-password" class="text-danger fw-semibold mt-1" style="display: none;"></small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                        <i class="fa-solid fa-right-to-bracket me-1"></i> Ingresar
                    </button>
                </form>
            </div>
            <div class="card-footer text-center py-3 bg-light rounded-bottom-4">
                <small class="text-muted">¿No tienes una cuenta? <a href="<?= site_url('register') ?>" class="text-decoration-none fw-semibold">Regístrate aquí</a></small>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<!-- Aquí llamamos al archivo JS externo usando base_url -->
<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/auth-login.js') ?>"></script>
<?= $this->endSection() ?>