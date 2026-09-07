<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Dashboard - <?= env('app.name', 'Viniloteca') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="dashboard-container">

    <!-- Mensaje de Bienvenida -->
    <div class="welcome-card glass-card-dashboard mb-4">
        <div class="welcome-content">
            <h2 class="welcome-title">¡Bienvenido a <?= env('app.name', 'Viniloteca') ?>! 🎵</h2>
            <p class="welcome-subtitle">Aquí tienes un resumen rápido de tu colección de vinilos.</p>
        </div>
        <div class="welcome-icon">
            <i class="fa-solid fa-record-vinyl"></i>
        </div>
    </div>

    <!-- Tarjetas de Estadísticas -->
    <div class="row g-4 mb-4">
        <!-- Total Vinilos -->
        <div class="col-md-6 col-xl-3">
            <div class="stat-card glass-card-dashboard">
                <div class="stat-icon bg-primary-soft">
                    <i class="fa-solid fa-compact-disc text-primary"></i>
                </div>
                <div class="stat-info">
                    <h3 class="stat-number">0</h3> <!-- Aquí irá el dato real luego -->
                    <p class="stat-label">Total Vinilos</p>
                </div>
            </div>
        </div>

        <!-- Géneros Diferentes -->
        <div class="col-md-6 col-xl-3">
            <div class="stat-card glass-card-dashboard">
                <div class="stat-icon bg-success-soft">
                    <i class="fa-solid fa-music text-success"></i>
                </div>
                <div class="stat-info">
                    <h3 class="stat-number">0</h3>
                    <p class="stat-label">Géneros</p>
                </div>
            </div>
        </div>

        <!-- Valor Estimado -->
        <div class="col-md-6 col-xl-3">
            <div class="stat-card glass-card-dashboard">
                <div class="stat-icon bg-warning-soft">
                    <i class="fa-solid fa-coins text-warning"></i>
                </div>
                <div class="stat-info">
                    <h3 class="stat-number">$0.00</h3>
                    <p class="stat-label">Valor Estimado</p>
                </div>
            </div>
        </div>

        <!-- Último Agregado -->
        <div class="col-md-6 col-xl-3">
            <div class="stat-card glass-card-dashboard">
                <div class="stat-icon bg-info-soft">
                    <i class="fa-solid fa-clock-rotate-left text-info"></i>
                </div>
                <div class="stat-info">
                    <h3 class="stat-number text-truncate" style="max-width: 120px;">-</h3>
                    <p class="stat-label">Último Agregado</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Acciones Rápidas -->
    <div class="row g-4">
        <div class="col-12">
            <div class="glass-card-dashboard p-4">
                <h5 class="mb-3"><i class="fa-solid fa-bolt me-2 text-warning"></i>Acciones Rápidas</h5>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="<?= site_url('vinilos/view') ?>" class="btn btn-action btn-primary-custom">
                        <i class="fa-solid fa-plus me-2"></i> Agregar Nuevo Vinilo
                    </a>
                    <a href="<?= site_url('vinilos/view') ?>" class="btn btn-action btn-secondary-custom">
                        <i class="fa-solid fa-list me-2"></i> Ver Catálogo Completo
                    </a>
                    <a href="<?= site_url('perfil') ?>" class="btn btn-action btn-outline-custom">
                        <i class="fa-solid fa-user-gear me-2"></i> Mi Perfil
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/dashboard.js') ?>"></script>
<?= $this->endSection() ?>