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
        <div class="sidebar-header d-flex align-items-center justify-content-between">
            <span><i class="fa-solid fa-record-vinyl text-primary me-2"></i><?= env('app.name', 'Viniloteca') ?></span>
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

            <!-- Acciones de la Topbar (Botón Tema + Menú Usuario) -->
            <div class="d-flex align-items-center gap-3">
                <!-- BOTÓN TOGGLE MODO CLARO / OSCURO -->
                <button onclick="toggleTheme()" class="btn btn-outline-secondary btn-sm rounded-circle" title="Cambiar modo claro/oscuro" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                    <i id="theme-icon" class="fa-solid fa-moon"></i>
                </button>

                <!-- Menú de Usuario / Avatar -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                        <img id="user-avatar" src="https://ui-avatars.com/api/?name=US&background=0d6efd&color=fff" alt="Avatar" width="35" height="35" class="rounded-circle me-2">
                        <span id="user-name" class="d-none d-sm-inline fw-semibold text-dark">Cargando...</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser1">
                        <li><a class="dropdown-item" href="#"><i class="fa-solid fa-user me-2"></i>Mi Perfil</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="#" id="btnLogout"><i class="fa-solid fa-right-from-bracket me-2"></i>Cerrar Sesión</a></li>
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
            <span>&copy; <?= date('Y') ?> <?= env('app.name', 'Viniloteca') ?>. Todos los derechos reservados.</span>
        </footer>
    </div>

    <!-- 2. jQuery y Librerías JS Centralizadas -->
    <script src="<?= base_url('assets/js/jquery-3.7.0.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/jquery.dataTables.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/dataTables.bootstrap5.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/sweetalert2.all.min.js') ?>"></script>

    <!-- SCRIPT DE CAMBIO DE TEMA -->
    <script src="<?= base_url('assets/js/theme-switcher.js') ?>"></script>

    <!-- FUNCIONES GLOBALES DE JAVASCRIPT -->
    <script>
        const API_URL = "<?= site_url() ?>";
        let currentUserEmail = null;

        /**
         * Cargar información del usuario actual
         */
        function loadUserInfo() {
            const token = localStorage.getItem('jwt_token');
            if (!token) {
                window.location.href = API_URL + '/login';
                return;
            }

            fetch(API_URL + '/perfil', {
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    if (response.status === 401 || response.status === 403) {
                        throw new Error('No autorizado');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 200 && data.data && data.data.usuario) {
                        const usuario = data.data.usuario;
                        currentUserEmail = usuario.email;

                        // Actualizar nombre en el topbar
                        const userNameEl = document.getElementById('user-name');
                        const userAvatarEl = document.getElementById('user-avatar');

                        if (userNameEl) {
                            userNameEl.textContent = usuario.nombre;
                        }

                        if (userAvatarEl) {
                            // Generar iniciales
                            const initials = getInitials(usuario.nombre);
                            const color = stringToColor(usuario.email);
                            userAvatarEl.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(initials)}&background=${encodeURIComponent(color)}&color=fff`;
                        }
                    }
                })
                .catch(() => {
                    localStorage.removeItem('jwt_token');
                    window.location.href = API_URL + '/login';
                });
        }

        /**
         * Obtener iniciales del nombre
         */
        function getInitials(name) {
            const parts = name.trim().split(/\s+/);
            if (parts.length >= 2) {
                return parts[0].charAt(0) + parts[1].charAt(0);
            }
            return parts[0].substring(0, 2).toUpperCase();
        }

        /**
         * Generar color basado en el email (para el avatar)
         */
        function stringToColor(str) {
            let hash = 0;
            for (let i = 0; i < str.length; i++) {
                hash = str.charCodeAt(i) + ((hash << 5) - hash);
            }
            const colors = ['#0d6efd', '#F28C28', '#28a745', '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#6c757d'];
            return colors[Math.abs(hash) % colors.length];
        }

        /**
         * Detectar cuando otra pestaña cambia la sesión
         */
        window.addEventListener('storage', function(e) {
            if (e.key === 'jwt_token') {
                const newToken = localStorage.getItem('jwt_token');

                // Si el token cambió, verificar si es del mismo usuario
                if (e.oldValue !== e.newValue && newToken) {
                    // Pequeña pausa para dar tiempo a que se actualice
                    setTimeout(() => {
                        fetch(API_URL + '/perfil', {
                                headers: {
                                    'Authorization': 'Bearer ' + newToken,
                                    'Content-Type': 'application/json'
                                }
                            })
                            .then(response => {
                                if (response.status === 401 || response.status === 403) {
                                    showAlertAndRedirect();
                                    return;
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data && data.data && data.data.usuario) {
                                    const newEmail = data.data.usuario.email;

                                    // Si es un usuario diferente al actual
                                    if (currentUserEmail && newEmail !== currentUserEmail) {
                                        showAlertAndRedirect();
                                    }
                                }
                            })
                            .catch(() => {
                                showAlertAndRedirect();
                            });
                    }, 500);
                }
            }
        });

        /**
         * Mostrar alerta y redirigir al login
         */
        function showAlertAndRedirect() {
            Swal.fire({
                icon: 'warning',
                title: 'Sesión cerrada',
                text: 'Otra cuenta ha iniciado sesión en este navegador. Serás redirigido al login.',
                confirmButtonColor: '#F28C28',
                confirmButtonText: 'Entendido',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(() => {
                localStorage.removeItem('jwt_token');
                window.location.href = API_URL + '/login';
            });
        }

        /**
         * Función centralizada para llamadas AJAX autenticadas con JWT.
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
                    Swal.fire({
                        icon: 'error',
                        title: 'Sesión expirada',
                        text: 'Tu sesión ha expirado o no tienes permisos. Serás redirigido al login.',
                        confirmButtonColor: '#F28C28',
                        confirmButtonText: 'Entendido',
                        allowOutsideClick: false
                    }).then(() => {
                        localStorage.removeItem('jwt_token');
                        window.location.href = API_URL + '/login';
                    });
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
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: mensaje,
                        confirmButtonColor: '#F28C28'
                    });
                }
            };

            $.ajax(options);
        }

        $(document).ready(function() {
            // Cargar información del usuario
            loadUserInfo();

            // Control del Sidebar responsive
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
                $('#content-wrapper').toggleClass('active');
            });

            // Botón Global de Cerrar Sesión
            $('#btnLogout').on('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: '¿Cerrar sesión?',
                    text: '¿Estás seguro de que deseas cerrar sesión?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#F28C28',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, cerrar sesión',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        localStorage.removeItem('jwt_token');
                        window.location.href = API_URL + '/login';
                    }
                });
            });
        });
    </script>

    <?= $this->renderSection('scripts') ?>
</body>

</html>