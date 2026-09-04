<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?></title>

    <!-- Bootstrap 5 CSS Local -->
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">

    <!-- FontAwesome Local -->
    <link rel="stylesheet" href="<?= base_url('assets/css/all.min.css') ?>">

    <!-- SweetAlert2 CSS Local (GLOBAL) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/sweetalert2.min.css') ?>">

    <!-- TU CSS PERSONALIZADO Y CENTRALIZADO -->
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>">

    <?= $this->renderSection('styles') ?>
</head>

<body class="auth-body">

    <!-- CONTENEDOR CENTRALIZADO SIN SIDEBAR NI TOPBAR -->
    <main class="container">
        <?= $this->renderSection('content') ?>
    </main>

    <!-- jQuery y Bootstrap JS -->
    <script src="<?= base_url('assets/js/jquery-3.7.0.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/sweetalert2.all.min.js') ?>"></script>

    <script>
        const API_URL = "<?= site_url() ?>";
    </script>

    <?= $this->renderSection('scripts') ?>
</body>

</html>