<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('assets/images/icon.png') ?>">

    <!-- Bootstrap 5 CSS Local -->
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">

    <!-- FontAwesome Local -->
    <link rel="stylesheet" href="<?= base_url('assets/css/all.min.css') ?>">

    <!-- SweetAlert2 CSS Local -->
    <link rel="stylesheet" href="<?= base_url('assets/css/sweetalert2.min.css') ?>">

    <!-- TU CSS PERSONALIZADO -->
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>">

    <?= $this->renderSection('styles') ?>
</head>

<!-- Data attributes para pasar URLs de logos al JS -->

<body class="auth-body"
    data-logo-light="<?= base_url('assets/images/logo.png') ?>"
    data-logo-dark="<?= base_url('assets/images/logo-dark.png') ?>">

    <!-- BOTÓN FLOTANTE DE CAMBIO DE TEMA -->
    <div class="auth-theme-toggle">
        <button onclick="toggleTheme()" class="btn-theme-toggle" title="Cambiar modo claro/oscuro">
            <i id="theme-icon" class="fa-solid fa-moon"></i>
        </button>
    </div>

    <!-- CONTENEDOR CENTRALIZADO -->
    <main class="container">
        <?= $this->renderSection('content') ?>
    </main>

    <!-- jQuery y Bootstrap JS -->
    <script src="<?= base_url('assets/js/jquery-3.7.0.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/sweetalert2.all.min.js') ?>"></script>

    <!-- HELPERS DE SWEETALERT -->
    <script src="<?= base_url('assets/js/sweetalert-helpers.js') ?>"></script>

    <!-- SCRIPT DE CAMBIO DE TEMA (Global) -->
    <script src="<?= base_url('assets/js/theme-switcher.js') ?>"></script>

    <!-- SCRIPT ESPECÍFICO DE AUTH (Logo + inicialización) -->
    <script src="<?= base_url('assets/js/auth-init.js') ?>"></script>

    <script>
        const API_URL = "<?= site_url() ?>";
    </script>

    <?= $this->renderSection('scripts') ?>
</body>

</html>