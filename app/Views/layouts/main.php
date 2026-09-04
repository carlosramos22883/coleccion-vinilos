<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?></title>

    <!-- 1. Bootstrap 5 CSS (Local) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>">

    <!-- FontAwesome (Local) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/all.min.css') ?>">

    <!-- DataTables CSS (Local) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/dataTables.bootstrap5.min.css') ?>">

    <!-- SweetAlert2 CSS Local (GLOBAL) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/sweetalert2.min.css') ?>">

    <!-- TU CSS PERSONALIZADO Y CENTRALIZADO -->
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>">

    <?= $this->renderSection('styles') ?>
</head>

<body>

    <!-- MENÚ LATERAL (SIDEBAR) -->
    <nav id="sidebar">
        <div class="sidebar-header d-flex align-items-center justify-content-between">
            <span><i class="fa-solid fa-record-vinyl text-primary me-2"></i>Vinilos App</span>
        </div>
        <ul class="list-unstyled components">
            <li>
                <a href="<?= site_url('dashboard') ?>"><i class="fa-solid fa-house"></i> Dashboard</a>
            </li>
            <li>
                <a href="<?= site_url('vinilos') ?>"><i class="fa-solid fa-compact-disc"></i> Catálogo</a>
            </li>
            <li>
                <a href="<?= site_url('usuarios') ?>"><i class="fa-solid fa-users"></i> Usuarios</a>
            </li>
            <li>
                <a href="<?= site_url('roles') ?>"><i class="fa-solid fa-shield-halved"></i> Roles y Permisos</a>
            </li>
        </ul>
    </nav>

    <!-- CONTENEDOR PRINCIPAL -->
    <div id="content-wrapper">

        <!-- TOPBAR -->
        <header class="topbar shadow-sm">
            <button type="button" id="sidebarCollapse" class="btn btn-light border-0">
                <i class="fa-solid fa-bars fa-lg"></i>
            </button>

            <!-- Menú de Usuario / Avatar -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="https://ui-avatars.com/api/?name=Usuario&background=0d6efd&color=fff" alt="Avatar" width="35" height="35" class="rounded-circle me-2">
                    <span class="d-none d-sm-inline fw-semibold text-dark">Mi Cuenta</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser1">
                    <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user me-2"></i>Mi Perfil</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger" href="#" id="btnLogout"><i class="fa-solid fa-right-from-bracket me-2"></i>Cerrar Sesión</a></li>
                </ul>
            </div>
        </header>

        <!-- CONTENIDO DINÁMICO -->
        <main class="main-container">
            <?= $this->renderSection('content') ?>
        </main>

        <!-- FOOTER -->
        <footer>
            <span>&copy; 2026 Catálogo de Vinilos. Todos los derechos reservados.</span>
        </footer>
    </div>

    <!-- 2. jQuery y Librerías JS Centralizadas -->
    <script src="<?= base_url('assets/js/jquery-3.7.0.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/jquery.dataTables.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/dataTables.bootstrap5.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/sweetalert2.all.min.js') ?>"></script>

    <!-- FUNCIONES GLOBALES DE JAVASCRIPT -->
    <script>
        const API_URL = "<?= site_url() ?>";

        /**
         * Función centralizada para llamadas AJAX autenticadas con JWT.
         * Evita repetir headers y maneja automáticamente el cierre de sesión si el token expira (401/403).
         */
        function authAjax(options) {
            const token = localStorage.getItem('jwt_token');

            if (!token && window.location.pathname.indexOf('login') === -1) {
                window.location.href = API_URL + '/login';
                return;
            }

            options.headers = Object.assign({}, options.headers, {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            });

            const originalError = options.error;
            options.error = function(xhr, status, error) {
                if (xhr.status === 401 || xhr.status === 403) {
                    alert('Su sesión ha expirado o no tiene permisos.');
                    localStorage.removeItem('jwt_token');
                    window.location.href = API_URL + '/login';
                    return;
                }
                if (typeof originalError === 'function') {
                    originalError(xhr, status, error);
                } else {
                    let mensaje = 'Ocurrió un error en la petición.';
                    if (xhr.responseJSON && xhr.responseJSON.messages) {
                        mensaje = typeof xhr.responseJSON.messages === 'string' ?
                            xhr.responseJSON.messages :
                            Object.values(xhr.responseJSON.messages).join('\n');
                    }
                    alert(mensaje);
                }
            };

            $.ajax(options);
        }

        $(document).ready(function() {
            // Control del Sidebar responsive
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('#content-wrapper').toggleClass('active');
            });

            // Botón Global de Cerrar Sesión
            $('#btnLogout').on('click', function(e) {
                e.preventDefault();
                if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                    localStorage.removeItem('jwt_token');
                    window.location.href = API_URL + '/login';
                }
            });
        });
    </script>

    <?= $this->renderSection('scripts') ?>
</body>

</html>