/**
 * Helpers globales para SweetAlert2 en Viniloteca
 * Centraliza la configuración y el tema (claro/oscuro)
 */

(function() {
    'use strict';

    /**
     * Obtiene la configuración de colores según el tema actual
     */
    function getThemeConfig() {
        const currentTheme = document.body.getAttribute('data-theme') || 'light';
        const isDark = currentTheme === 'dark';

        return {
            background: isDark ? '#1e2227' : '#ffffff',
            color: isDark ? '#e9ecef' : '#212529',
            confirmButtonColor: '#F28C28',
            cancelButtonColor: '#6c757d',
            dangerButtonColor: '#dc3545',
        };
    }

    /**
     * Configuración base común para todos los SweetAlerts
     */
    function getBaseConfig(options = {}) {
        const theme = getThemeConfig();
        
        return Object.assign({
            background: theme.background,
            color: theme.color,
            confirmButtonColor: theme.confirmButtonColor,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showCloseButton: false,
            customClass: {
                popup: 'swal-custom-popup'
            }
        }, options);
    }

    /**
     * Alerta de ÉXITO
     * @param {Object} options - { title, text, html, timer, showConfirmButton, confirmButtonText, onConfirm }
     */
    window.showSuccessAlert = function(options = {}) {
        const config = getBaseConfig({
            icon: 'success',
            title: options.title || '¡Éxito!',
            text: options.text || '',
            html: options.html || '',
            timer: options.timer || 5000,
            timerProgressBar: true,
            showConfirmButton: options.showConfirmButton !== false,
            confirmButtonText: options.confirmButtonText || 'Aceptar',
        });

        return Swal.fire(config).then((result) => {
            if (options.onConfirm) {
                options.onConfirm(result);
            }
        });
    };

    /**
     * Alerta de ERROR
     * @param {Object} options - { title, text, html, confirmButtonText, onConfirm }
     */
    window.showErrorAlert = function(options = {}) {
        const config = getBaseConfig({
            icon: 'error',
            title: options.title || 'Error',
            text: options.text || '',
            html: options.html || '',
            timer: options.timer || 0, // Sin auto-cierre para errores
            showConfirmButton: true,
            confirmButtonText: options.confirmButtonText || 'Entendido',
        });

        return Swal.fire(config).then((result) => {
            if (options.onConfirm) {
                options.onConfirm(result);
            }
        });
    };

    /**
     * Alerta de ADVERTENCIA
     * @param {Object} options - { title, text, html, confirmButtonText, onConfirm }
     */
    window.showWarningAlert = function(options = {}) {
        const config = getBaseConfig({
            icon: 'warning',
            title: options.title || 'Advertencia',
            text: options.text || '',
            html: options.html || '',
            timer: options.timer || 0,
            showConfirmButton: true,
            confirmButtonText: options.confirmButtonText || 'Entendido',
        });

        return Swal.fire(config).then((result) => {
            if (options.onConfirm) {
                options.onConfirm(result);
            }
        });
    };

    /**
     * Alerta de INFORMACIÓN
     * @param {Object} options - { title, text, html, timer, confirmButtonText, onConfirm }
     */
    window.showInfoAlert = function(options = {}) {
        const config = getBaseConfig({
            icon: 'info',
            title: options.title || 'Información',
            text: options.text || '',
            html: options.html || '',
            timer: options.timer || 5000,
            timerProgressBar: true,
            showConfirmButton: options.showConfirmButton !== false,
            confirmButtonText: options.confirmButtonText || 'Aceptar',
        });

        return Swal.fire(config).then((result) => {
            if (options.onConfirm) {
                options.onConfirm(result);
            }
        });
    };

    /**
     * Alerta de CONFIRMACIÓN (Sí/No)
     * @param {Object} options - { title, text, html, confirmButtonText, cancelButtonText, onConfirm, onCancel }
     */
    window.showConfirmAlert = function(options = {}) {
        const theme = getThemeConfig();
        const config = getBaseConfig({
            icon: 'question',
            title: options.title || '¿Estás seguro?',
            text: options.text || '',
            html: options.html || '',
            showConfirmButton: true,
            showCancelButton: true,
            confirmButtonText: options.confirmButtonText || 'Sí, continuar',
            cancelButtonText: options.cancelButtonText || 'Cancelar',
            confirmButtonColor: theme.dangerButtonColor,
            cancelButtonColor: theme.cancelButtonColor,
            reverseButtons: true,
        });

        return Swal.fire(config).then((result) => {
            if (result.isConfirmed && options.onConfirm) {
                options.onConfirm(result);
            } else if (result.dismiss === Swal.DismissReason.cancel && options.onCancel) {
                options.onCancel(result);
            }
        });
    };

    /**
     * Alerta tipo TOAST (pequeña, esquina superior)
     * @param {Object} options - { icon, title, text, timer, position }
     */
    window.showToastAlert = function(options = {}) {
        const config = getBaseConfig({
            toast: true,
            position: options.position || 'top-end',
            icon: options.icon || 'success',
            title: options.title || '',
            text: options.text || '',
            timer: options.timer || 3000,
            timerProgressBar: true,
            showConfirmButton: false,
            allowOutsideClick: true,
            allowEscapeKey: true,
        });

        return Swal.fire(config);
    };

    /**
     * Alerta de CARGA (loading)
     * @param {Object} options - { title, text }
     */
    window.showLoadingAlert = function(options = {}) {
        const config = getBaseConfig({
            title: options.title || 'Procesando...',
            text: options.text || 'Por favor espera',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            showCloseButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        return Swal.fire(config);
    };

})();