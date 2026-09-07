/**
 * Dashboard specific JavaScript
 */
$(document).ready(function() {
    // Cambiar logo del sidebar según el tema
    function updateSidebarLogo(theme) {
        const logo = $('.sidebar-logo');
        if (logo.length) {
            const logoLight = logo.data('light');
            const logoDark = logo.data('dark');
            logo.attr('src', theme === 'dark' ? logoDark : logoLight);
        }
    }

    // Observar cambios de tema
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                const newTheme = document.body.getAttribute('data-theme') || 'light';
                updateSidebarLogo(newTheme);
            }
        });
    });

    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['data-theme']
    });

    // Inicializar logo correcto
    const savedTheme = localStorage.getItem('theme') || 'light';
    updateSidebarLogo(savedTheme);

    console.log("Dashboard JS initialized successfully 📀");
});