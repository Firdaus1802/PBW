// assets/js/theme.js - Smooth iOS Dark/Light Mode Switcher with Persistence
(function() {
    const savedTheme = localStorage.getItem('dompetku_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    document.documentElement.setAttribute('data-bs-theme', savedTheme);
})();

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
    const newTheme = (currentTheme === 'dark') ? 'light' : 'dark';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    document.documentElement.setAttribute('data-bs-theme', newTheme);
    localStorage.setItem('dompetku_theme', newTheme);
    
    updateThemeIcons(newTheme);
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: newTheme } }));
}

function updateThemeIcons(theme) {
    const icons = document.querySelectorAll('.theme-toggle-icon');
    icons.forEach(icon => {
        if (theme === 'dark') {
            icon.className = 'bi bi-sun-fill theme-toggle-icon';
        } else {
            icon.className = 'bi bi-moon-stars-fill theme-toggle-icon';
        }
    });

    const modeText = document.getElementById('themeModeText');
    if (modeText) {
        modeText.innerText = (theme === 'dark') ? 'Mode Gelap' : 'Mode Terang';
    }

    const metaTheme = document.getElementById('metaThemeColor');
    if (metaTheme) {
        metaTheme.setAttribute('content', theme === 'dark' ? '#090A0F' : '#F2F2F7');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
    document.documentElement.setAttribute('data-bs-theme', currentTheme);
    updateThemeIcons(currentTheme);
});
