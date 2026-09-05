/**
 * Script global para la gestión del Modo Claro / Oscuro en Viniloteca
 */

// 1. Aplicar el tema guardado INMEDIATAMENTE (evita parpadeo)
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
}

// 3. Cambiar el ícono del botón (Luna / Sol)
function updateThemeIcon(theme) {
    const icon = document.getElementById('theme-icon');
    if (icon) {
        icon.className = theme === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    }
}

// 4. Inicializar icono al cargar
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    updateThemeIcon(savedTheme);
});