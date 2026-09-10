/**
 * Script global para la gestión del Modo Claro / Oscuro en Viniloteca
 */

// 1. Aplicar el tema guardado INMEDIATAMENTE (evita parpadeo FOUC)
(function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    if (savedTheme === 'dark') {
        document.body.setAttribute('data-theme', 'dark');
    }
})();

// 2. Función global para alternar el tema
function toggleTheme() {
    const currentTheme = document.body.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    if (newTheme === 'dark') {
        document.body.setAttribute('data-theme', 'dark');
    } else {
        document.body.removeAttribute('data-theme');
    }
    
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
    updateAllLogos(newTheme); // ✅ Llamamos directamente aquí, sin sobreescribir la función
}

// 3. Cambiar el ícono del botón (Luna / Sol)
function updateThemeIcon(theme) {
    const icon = document.getElementById('theme-icon');
    if (icon) {
        icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    }
}

// 4. Actualizar todos los logos con data-light y data-dark
function updateAllLogos(theme) {
    const logos = document.querySelectorAll('img[data-light][data-dark]');
    const isDark = theme === 'dark';
    
    logos.forEach(logo => {
        const newSrc = isDark ? logo.getAttribute('data-dark') : logo.getAttribute('data-light');
        if (logo.src !== newSrc) {
            logo.src = newSrc;
        }
    });
}

// 5. Inicialización al cargar el DOM (unificado y limpio)
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    
    // Actualizar ícono
    updateThemeIcon(savedTheme);
    
    // Actualizar logos
    updateAllLogos(savedTheme);
});