/**
 * Aplicación Principal - Viniloteca
 * Funciones globales y manejo de sesión
 */
(function() {
    'use strict';

    let currentUserEmail = null;

    function loadUserInfo() {
        if (window.location.pathname.indexOf('login') !== -1) return;
        
        const token = localStorage.getItem('jwt_token');
        if (!token) {
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
            if (response.status === 401 || response.status === 403) {
                localStorage.removeItem('jwt_token');
                showWarningAlert({
                    title: 'Sesión expirada',
                    text: 'Tu sesión ha expirado. Serás redirigido al login.',
                    onConfirm: () => { window.location.href = API_URL + 'login'; }
                });
                throw new Error('No autorizado');
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 200 && data.data && data.data.usuario) {
                const usuario = data.data.usuario;                    
                currentUserEmail = usuario.email;

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
                    } else {
                        const initials = usuario.nombre.trim().split(/\s+/);
                        const nameForAvatar = initials.length >= 2 ? initials[0].charAt(0) + initials[1].charAt(0) : initials[0].substring(0, 2).toUpperCase();
                        
                        let hash = 0;
                        for (let i = 0; i < usuario.email.length; i++) {
                            hash = usuario.email.charCodeAt(i) + ((hash << 5) - hash);
                        }
                        const colors = ['#0d6efd', '#F28C28', '#28a745', '#dc3545', '#6f42c1', '#20c997', '#fd7e14', '#6c757d'];
                        const color = colors[Math.abs(hash) % colors.length];
                        
                        avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(nameForAvatar)}&background=${encodeURIComponent(color)}&color=fff`;
                    }
                    userAvatarEl.src = avatarUrl;
                    sidebarUserAvatarEl.src = avatarUrl;
                }
            }
        })
        .catch((error) => {
            if (error.message !== 'No autorizado') {
                console.error('Error loading user info:', error);
            }
        });
    }

    window.addEventListener('storage', function(e) {
        if (e.key === 'jwt_token') {
            const newToken = localStorage.getItem('jwt_token');
            if (e.oldValue !== e.newValue && newToken) {
                setTimeout(() => {
                    fetch(API_URL + 'perfil/api', {
                        headers: { 'Authorization': 'Bearer ' + newToken, 'Content-Type': 'application/json' }
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
                            if (currentUserEmail && data.data.usuario.email !== currentUserEmail) {
                                showAlertAndRedirect();
                            }
                        }
                    })
                    .catch(() => { showAlertAndRedirect(); });
                }, 500);
            }
        }
    });

    function showAlertAndRedirect() {
        showWarningAlert({
            title: 'Sesión cerrada',
            text: 'Otra cuenta ha iniciado sesión en este navegador. Serás redirigido al login.',
            onConfirm: () => {
                localStorage.removeItem('jwt_token');
                window.location.href = API_URL + '/login';
            }
        });
    }

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
                showErrorAlert({
                    title: 'Sesión expirada',
                    text: 'Tu sesión ha expirado o no tienes permisos. Serás redirigido al login.',
                    onConfirm: () => {
                        localStorage.removeItem('jwt_token');
                        window.location.href = API_URL + '/login';
                    }
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
                        Object.values(xhr.responseJSON.messages).join('<br>');
                }
                showErrorAlert({ title: 'Error', html: mensaje });
            }
        };
        $.ajax(options);
    };
    
    $(document).ready(function() {
        loadUserInfo();

        $('#sidebarCollapse').on('click', function(e) {
            e.preventDefault();
            $('#sidebar').toggleClass('expanded');
        });

        $('#sidebar-close').on('click', function(e) {
            e.preventDefault();
            $('#sidebar').removeClass('expanded');
        });

        $(document).on('click', function(e) {
            const $sidebar = $('#sidebar');
            const $toggle = $('#sidebarCollapse');
            const $closeBtn = $('#sidebar-close');
            if ($sidebar.hasClass('expanded') && 
                !$(e.target).closest($sidebar).length && 
                !$(e.target).closest($toggle).length &&
                !$(e.target).closest($closeBtn).length) {
                $sidebar.removeClass('expanded');
            }
        });

        $('#sidebar ul.components li a').on('click', function() {
            $('#sidebar').removeClass('expanded');
        });

        const handleLogout = function(e) {
            e.preventDefault();
            e.stopPropagation();
            showConfirmAlert({
                title: '¿Cerrar sesión?',
                text: '¿Estás seguro de que deseas cerrar sesión?',
                confirmButtonText: 'Sí, cerrar sesión',
                cancelButtonText: 'Cancelar',
                onConfirm: () => {
                    fetch(API_URL + 'auth/logout', {
                        method: 'POST',
                        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('jwt_token') }
                    }).finally(() => {
                        localStorage.removeItem('jwt_token');
                        sessionStorage.clear();
                        window.location.replace(API_URL + 'login');
                    });
                }
            });
        };

        $('#btnLogout').on('click', handleLogout);
        $('#sidebar-btn-logout').on('click', handleLogout);
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modalEl => {
            const modal = bootstrap.Modal.getInstance(modalEl) || 
                         new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false });
        });
    });
})();