/**
 * Inicialización específica para páginas de autenticación (login/register)
 * Maneja el cambio de logo según el tema
 */

(function() {
    'use strict';

    // URLs de los logos (inyectadas desde PHP vía data attributes)
    const logoLight = document.body.dataset.logoLight || '';
    const logoDark = document.body.dataset.logoDark || '';

    /**
     * Actualiza el logo según el tema actual
     */
    function updateLogoForTheme(theme) {
        const logo = document.querySelector('.vinilo-logo');
        if (logo && logoLight && logoDark) {
            logo.src = theme === 'dark' ? logoDark : logoLight;
        }
    }

    /**
     * Observa cambios en el atributo data-theme del body
     * para actualizar el logo automáticamente
     */
    function observeThemeChanges() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                    const newTheme = document.body.getAttribute('data-theme') || 'light';
                    updateLogoForTheme(newTheme);
                }
            });
        });

        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['data-theme']
        });
    }

    // Inicializar al cargar el DOM
    document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        updateLogoForTheme(savedTheme);
        observeThemeChanges();
    });
})();