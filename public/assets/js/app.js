/**
 * Aplicación Principal - Viniloteca
 * Funciones globales y manejo de sesión
 */

(function() {
    'use strict';

    let currentUserEmail = null;

    /**
     * Cargar información del usuario actual
     */
    function loadUserInfo() {
        // Evitar bucle infinito si ya estamos en login
        if (window.location.pathname.indexOf('login') !== -1) {
            console.log('Ya estamos en login, no hacer nada');
            return;
        }
        
        const token = localStorage.getItem('jwt_token');
        
        // Si no hay token, redirigir inmediatamente
        if (!token) {
            console.log('No hay token, redirigiendo al login...');
            localStorage.removeItem('jwt_token');
            window.location.replace(API_URL + 'login');
            return;
        }
        
        fetch(API_URL + 'perfil/api', {
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                
                if (response.status === 401 || response.status === 403) {
                    console.log('Token inválido o expirado');
                    localStorage.removeItem('jwt_token');
                    window.location.href = API_URL + 'login';
                    throw new Error('No autorizado');
                }
                return response.json();
            })
            .then(data => {
                console.log('User data loaded:', data);
                
                if (data.status === 200 && data.data && data.data.usuario) {
                    const usuario = data.data.usuario;
                    currentUserEmail = usuario.email;

                    // Actualizar elementos del DOM
                    const userNameEl = document.getElementById('user-name');
                    const userAvatarEl = document.getElementById('user-avatar');
                    const sidebarUserNameEl = document.getElementById('sidebar-user-name');
                    const sidebarUserEmailEl = document.getElementById('sidebar-user-email');
                    const sidebarUserAvatarEl = document.getElementById('sidebar-user-avatar');

                    if (userNameEl) userNameEl.textContent = usuario.nombre;
                    if (sidebarUserNameEl) sidebarUserNameEl.textContent = usuario.nombre;
                    if (sidebarUserEmailEl) sidebarUserEmailEl.textContent = usuario.email;

                    if (userAvatarEl && sidebarUserAvatarEl) {
                        let avatarUrl;
                        
                        if (usuario.avatar && usuario.avatar.trim() !== '') {
                            avatarUrl = API_URL + 'uploads/' + usuario.avatar;
                            console.log('Using avatar:', avatarUrl);
                        } else {
                            const initials = getInitials(usuario.nombre);
                            const color = stringToColor(usuario.email);
                            avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(initials)}&background=${encodeURIComponent(color)}&color=fff`;
                        }
                        
                        userAvatarEl.src = avatarUrl;
                        sidebarUserAvatarEl.src = avatarUrl;
                    }
                }
            })
            .catch((error) => {
                console.error('Error loading user info:', error);
                // Solo redirigir si es un error de autenticación
                if (error.message === 'No autorizado') {
                    return;
                }
                // Para otros errores, no redirigir automáticamente
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
                    fetch(API_URL + 'perfil/api', {
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
    window.authAjax = function(options) {
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
    };
    
    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        // Cargar información del usuario
        loadUserInfo();

        // Control del Sidebar - Toggle
        $('#sidebarCollapse').on('click', function(e) {
            e.preventDefault(); // Prevenir comportamientos por defecto
            $('#sidebar').toggleClass('expanded');
        });

        // Botón de cerrar sidebar (la X)
        $('#sidebar-close').on('click', function(e) {
            e.preventDefault();
            $('#sidebar').removeClass('expanded');
        });

        // Cerrar sidebar al hacer clic fuera
        $(document).on('click', function(e) {
            const $sidebar = $('#sidebar');
            const $toggle = $('#sidebarCollapse');
            const $closeBtn = $('#sidebar-close');
            
            // Si el sidebar está expandido y el clic NO fue en el sidebar, ni en el botón toggle, ni en el botón cerrar
            if ($sidebar.hasClass('expanded') && 
                !$(e.target).closest($sidebar).length && 
                !$(e.target).closest($toggle).length &&
                !$(e.target).closest($closeBtn).length) {
                $sidebar.removeClass('expanded');
            }
        });

        // Cerrar sidebar al hacer clic en un enlace del menú (mejor UX)
        $('#sidebar ul.components li a').on('click', function() {
            $('#sidebar').removeClass('expanded');
        });

        // Botón Global de Cerrar Sesión (topbar)
                $('#btnLogout').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            Swal.fire({
                title: '¿Cerrar sesión?',
                text: '¿Estás seguro de que deseas cerrar sesión?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#F28C28',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Llamar al endpoint de logout para destruir sesión del servidor
                    fetch(API_URL + 'auth/logout', {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
                        }
                    }).finally(() => {
                        // Limpiar localStorage y redirigir
                        localStorage.removeItem('jwt_token');
                        sessionStorage.clear();
                        window.location.replace(API_URL + 'login');
                    });
                }
            });
        });

        // Botón de Logout en el sidebar
        $('#sidebar-btn-logout').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            Swal.fire({
                title: '¿Cerrar sesión?',
                text: '¿Estás seguro de que deseas cerrar sesión?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#F28C28',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Llamar al endpoint de logout para destruir sesión del servidor
                    fetch(API_URL + 'auth/logout', {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + localStorage.getItem('jwt_token')
                        }
                    }).finally(() => {
                        // Limpiar localStorage y redirigir
                        localStorage.removeItem('jwt_token');
                        sessionStorage.clear();
                        window.location.replace(API_URL + 'login');
                    });
                }
            });
        });
    });    

})();