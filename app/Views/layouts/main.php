<?php
$permisos = $permisos ?? session()->get('user_permissions') ?? [];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?></title>

    <!-- icono -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('assets/images/icon.png') ?>">

    <!-- 1. Bootstrap 5 CSS (Local) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">

    <!-- FontAwesome (Local) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/all.min.css') ?>">

    <!-- DataTables CSS (Local) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/dataTables.bootstrap5.min.css') ?>">

    <!-- SweetAlert2 CSS Local (GLOBAL) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/sweetalert2.min.css') ?>">

    <!-- TU CSS PERSONALIZADO -->
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>">

    <?= $this->renderSection('styles') ?>
</head>

<body>

    <!-- MENÚ LATERAL (SIDEBAR) -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-container">
                <img src="<?= base_url('assets/images/logo.png') ?>"
                    alt="<?= env('app.name', 'Viniloteca') ?>"
                    class="sidebar-logo"
                    data-light="<?= base_url('assets/images/logo.png') ?>"
                    data-dark="<?= base_url('assets/images/logo-dark.png') ?>">
            </div>
            <span><?= env('app.name', 'Viniloteca') ?></span>
            <!-- BOTÓN DE CERRAR SIDEBAR -->
            <button type="button" id="sidebar-close" class="btn-close-sidebar" title="Cerrar menú">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <ul class="list-unstyled components">
            <li class="<?= uri_string() === 'dashboard' ? 'active' : '' ?>">
                <a href="<?= site_url('dashboard') ?>"><i class="fa-solid fa-house"></i> Pantalla de inicio</a>
            </li>

            <?php if (in_array('vinilos.view', $permisos ?? [])): ?>
                <li class="<?= uri_string() === 'vinilos' ? 'active' : '' ?>">
                    <a href="<?= site_url('vinilos') ?>"><i class="fa-solid fa-compact-disc"></i> Vinilos</a>
                </li>
            <?php endif; ?>

            <?php if (in_array('usuarios.view', $permisos ?? [])): ?>
                <li class="<?= uri_string() === 'usuarios' ? 'active' : '' ?>">
                    <a href="<?= site_url('usuarios') ?>"><i class="fa-solid fa-users"></i> Usuarios</a>
                </li>
            <?php endif; ?>

            <?php if (in_array('roles.view', $permisos ?? [])): ?>
                <li class="<?= uri_string() === 'roles' ? 'active' : '' ?>">
                    <a href="<?= site_url('roles') ?>"><i class="fa-solid fa-shield-halved"></i> Roles y Permisos</a>
                </li>
            <?php endif; ?>
        </ul>

        <!-- NUEVO: Info del usuario al final del sidebar -->
        <div class="sidebar-user-info">
            <div class="user-avatar-wrapper">
                <img id="sidebar-user-avatar" src="https://ui-avatars.com/api/?name=US&background=0d6efd&color=fff"
                    alt="Avatar" class="user-avatar">
            </div>
            <div class="user-details">
                <div id="sidebar-user-name" class="user-name">Cargando...</div>
                <div id="sidebar-user-email" class="user-email">usuario@email.com</div>
            </div>
            <div class="user-actions">
                <a href="<?= site_url('perfil') ?>" class="user-action-btn" title="Mi Perfil">
                    <i class="fa-solid fa-user"></i>
                </a>
                <a href="#" id="sidebar-btn-logout" class="user-action-btn text-danger" title="Cerrar Sesión">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- CONTENEDOR PRINCIPAL -->
    <div id="content-wrapper">

        <!-- TOPBAR -->
        <header class="topbar shadow-sm">
            <!-- Lado izquierdo: Botón sidebar + Logo -->
            <div class="topbar-left">
                <button type="button" id="sidebarCollapse" class="btn btn-light border-0">
                    <i class="fa-solid fa-bars fa-lg"></i>
                </button>

                <div class="topbar-logo-container">
                    <a href="<?= site_url('dashboard') ?>" title="Ir al Dashboard">
                        <img src="<?= base_url('assets/images/logo.png') ?>"
                            alt="<?= env('app.name', 'Viniloteca') ?>"
                            class="topbar-logo"
                            data-light="<?= base_url('assets/images/logo.png') ?>"
                            data-dark="<?= base_url('assets/images/logo-dark.png') ?>">
                    </a>
                </div>
            </div>

            <!-- Acciones de la Topbar (Botón Tema + Menú Usuario) -->
            <div class="d-flex align-items-center gap-3">
                <!-- BOTÓN TOGGLE MODO CLARO / OSCURO -->
                <button onclick="toggleTheme()" class="btn btn-outline-secondary btn-sm rounded-circle"
                    title="Cambiar modo claro/oscuro"
                    style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                    <i id="theme-icon" class="fa-solid fa-moon"></i>
                </button>

                <!-- Menú de Usuario / Avatar -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle"
                        id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                        <img id="user-avatar"
                            src="https://ui-avatars.com/api/?name=US&background=0d6efd&color=fff"
                            alt="Avatar" width="35" height="35" class="rounded-circle me-2">
                        <span id="user-name" class="d-none d-sm-inline fw-semibold"
                            style="color: var(--text-primary);">Cargando...</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser1">
                        <li><a class="dropdown-item" href="<?= site_url('perfil') ?>">
                                <i class="fa-solid fa-user me-2"></i>Mi Perfil
                            </a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="#" id="btnLogout">
                                <i class="fa-solid fa-right-from-bracket me-2"></i>Cerrar Sesión
                            </a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- CONTENIDO DINÁMICO -->
        <main class="main-container">
            <?= $this->renderSection('content') ?>
        </main>

        <!-- FOOTER -->
        <footer>
            <div class="footer-logo-container">
                <img src="<?= base_url('assets/images/logo.png') ?>"
                    alt="<?= env('app.name', 'Viniloteca') ?>"
                    class="footer-logo"
                    data-light="<?= base_url('assets/images/logo.png') ?>"
                    data-dark="<?= base_url('assets/images/logo-dark.png') ?>">
            </div>

            <div class="footer-text">
                <span class="footer-app-name"><?= env('app.name', 'Viniloteca') ?></span>
                <span class="footer-copyright">&copy; <?= date('Y') ?>. Todos los derechos reservados.</span>
            </div>
        </footer>
    </div>

    <!-- 2. jQuery y Librerías JS Centralizadas -->
    <script src="<?= base_url('assets/js/jquery-3.7.0.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/jquery.dataTables.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/dataTables.bootstrap5.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/sweetalert2.all.min.js') ?>"></script>

    <!-- HELPERS DE SWEETALERT -->
    <script src="<?= base_url('assets/js/sweetalert-helpers.js') ?>"></script>

    <!-- SCRIPT DE CAMBIO DE TEMA -->
    <script src="<?= base_url('assets/js/theme-switcher.js') ?>"></script>

    <!-- DEFINIR VARIABLES GLOBALES ANTES DE CUALQUIER SCRIPT -->
    <script>
        const API_URL = "<?= rtrim(base_url(), '/') ?>/";
    </script>

    <!-- SCRIPT DEL DASHBOARD (Sidebar, etc.) -->
    <script src="<?= base_url('assets/js/dashboard.js') ?>"></script>

    <!-- SCRIPT PRINCIPAL DE LA APLICACIÓN -->
    <script src="<?= base_url('assets/js/app.js') ?>"></script>

    <?= $this->renderSection('scripts') ?>
</body>

</html>