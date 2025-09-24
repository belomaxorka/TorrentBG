// js/themes.js
document.addEventListener('DOMContentLoaded', function() {
    // Превключване на тема
    document.querySelectorAll('[data-theme]').forEach(btn => {
        btn.addEventListener('click', function() {
            const theme = this.getAttribute('data-theme');
            document.body.className = theme + '-theme';
            localStorage.setItem('theme', theme);
        });
    });

    // Зареждане на тема от localStorage
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.body.className = savedTheme + '-theme';
    }
});