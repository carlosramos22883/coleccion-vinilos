<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Mi Perfil - <?= env('app.name', 'Viniloteca') ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<!-- Croppie CSS -->
<link rel="stylesheet" href="<?= base_url('assets/css/croppie/croppie.min.css') ?>">
<style>
    .profile-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    .profile-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    [data-theme="dark"] .profile-card {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .profile-header {
        text-align: center;
        margin-bottom: 40px;
        padding-bottom: 30px;
        border-bottom: 1px solid var(--border-color);
    }

    .profile-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 10px;
    }

    .profile-subtitle {
        color: var(--text-secondary);
        font-size: 0.95rem;
    }

    .avatar-section {
        text-align: center;
        margin-bottom: 40px;
    }

    .avatar-wrapper {
        position: relative;
        display: inline-block;
        margin-bottom: 20px;
    }

    .avatar-preview {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid var(--border-color);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .avatar-upload-btn {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 40px;
        height: 40px;
        background: var(--vinilo-orange);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .avatar-upload-btn:hover {
        background: var(--vinilo-orange-dark);
        transform: scale(1.1);
    }

    .avatar-upload-btn input[type="file"] {
        display: none;
    }

    .avatar-upload-btn i {
        color: white;
        font-size: 1.1rem;
    }

    .form-section {
        margin-bottom: 30px;
    }

    .form-section-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--vinilo-orange);
    }

    .form-section-description {
        color: var(--text-secondary);
        font-size: 0.9rem;
        margin-bottom: 25px;
    }

    .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: flex-end;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid var(--border-color);
    }

    .btn-danger-custom {
        background: #dc3545;
        color: white;
        border: none;
    }

    .btn-danger-custom:hover {
        background: #c82333;
        color: white;
    }

    /* Modal de Croppie */
    .crop-modal .modal-content {
        background: var(--card-bg);
        color: var(--text-primary);
    }

    .crop-modal .modal-header {
        border-bottom: 1px solid var(--border-color);
    }

    .crop-modal .modal-body {
        padding: 20px;
    }

    #crop-image-container {
        height: 400px;
        margin-bottom: 20px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="profile-container">

    <!-- Header -->
    <div class="profile-header">
        <h1 class="profile-title">Mi Perfil</h1>
        <p class="profile-subtitle">Gestiona tu información personal y preferencias</p>
    </div>

    <!-- Tarjeta de Avatar -->
    <div class="profile-card avatar-section">
        <h3 class="form-section-title">Foto de Perfil</h3>
        <p class="form-section-description">Haz clic en el icono de la cámara para cambiar tu foto.</p>

        <div class="avatar-wrapper">
            <img id="avatar-preview"
                src="<?= !empty($user['avatar']) ? base_url('uploads/' . $user['avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['nombre']) . '&background=F28C28&color=fff&size=256' ?>"
                alt="Avatar"
                class="avatar-preview">
            <label for="avatar-upload" class="avatar-upload-btn" title="Cambiar foto de perfil">
                <i class="fa-solid fa-camera"></i>
                <input id="avatar-upload" type="file" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/webp">
            </label>
        </div>
    </div>

    <!-- Tarjeta de Información -->
    <div class="profile-card">
        <h3 class="form-section-title">Información del Perfil</h3>
        <p class="form-section-description">Actualiza la información de perfil y correo electrónico de tu cuenta.</p>

        <form id="profile-form" method="POST" action="<?= site_url('perfil/update') ?>" novalidate data-user-id="<?= esc($user['id']) ?>">
            <?= csrf_field() ?>

            <div class="row">
                <div class="col-md-12 mb-4">
                    <label class="form-label" for="nombre">Nombre Completo</label>
                    <input type="text"
                        class="form-control"
                        id="nombre"
                        name="nombre"
                        value="<?= esc($user['nombre']) ?>"
                        required>
                    <div class="invalid-feedback">El nombre es obligatorio.</div>
                </div>

                <div class="col-md-12 mb-4">
                    <label class="form-label" for="email">Correo Electrónico</label>
                    <input type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        value="<?= esc($user['email']) ?>"
                        required>
                    <div class="invalid-feedback">Ingresa un correo válido.</div>
                    <?php if (empty($user['email_verified'])): ?>
                        <small class="text-warning mt-2 d-block">
                            <i class="fa-solid fa-exclamation-triangle me-1"></i>
                            Tu correo no ha sido verificado.
                            <button type="button" id="btn-resend-verification" class="btn btn-link p-0">
                                Reenviar correo de verificación
                            </button>
                        </small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn btn-primary-custom">
                    <i class="fa-solid fa-save me-2"></i>Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    <!-- Tarjeta de Contraseña -->
    <div class="profile-card">
        <h3 class="form-section-title">Seguridad</h3>
        <p class="form-section-description">Actualiza tu contraseña para mantener tu cuenta segura.</p>

        <div class="action-buttons">
            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modal-change-password">
                <i class="fa-solid fa-key me-2"></i>Cambiar Contraseña
            </button>
            <button type="button" class="btn btn-danger-custom" data-bs-toggle="modal" data-bs-target="#modal-delete-account">
                <i class="fa-solid fa-trash me-2"></i>Eliminar Cuenta
            </button>
        </div>
    </div>

</div>

<!-- MODAL: Cambiar Contraseña -->
<div class="modal fade" id="modal-change-password" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Contraseña</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-change-password" method="POST" action="<?= site_url('perfil/cambiar-password') ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Contraseña Actual</label>
                        <input type="password" class="form-control" name="password_actual" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" name="password_nuevo" required>
                        <small class="text-muted">Mín. 8 caracteres, 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control" name="password_confirm" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Eliminar Cuenta -->
<div class="modal fade" id="modal-delete-account" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">Eliminar Cuenta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-delete-account" method="POST" action="<?= site_url('perfil/eliminar') ?>">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>
                        <strong>¡Advertencia!</strong> Esta acción no se puede deshacer. Todos tus datos serán eliminados permanentemente.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirma tu contraseña para continuar</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar Cuenta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Croppie (Recortar imagen) -->
<div class="modal fade" id="modal-crop-image" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Recortar Imagen de Perfil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="crop-image-container" style="max-width: 100%;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancel-crop">Cancelar</button>
                <button type="button" class="btn btn-primary" id="save-crop">
                    <i class="fa-solid fa-crop me-2"></i>Guardar y Recortar
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Croppie JS -->
<script src="<?= base_url('assets/js/croppie/croppie.min.js') ?>"></script>
<script src="<?= base_url('assets/js/perfil.js') ?>"></script>
<?= $this->endSection() ?>