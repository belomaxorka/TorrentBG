// js/app.js
function updateClock() {
    const now = new Date();
    let hours = now.getHours().toString().padStart(2, '0');
    let minutes = now.getMinutes().toString().padStart(2, '0');
    let seconds = now.getSeconds().toString().padStart(2, '0');
    document.getElementById('current-time')?.textContent = `${hours}:${minutes}:${seconds}`;
}

// Обновяване на часовника
setInterval(updateClock, 1000);
updateClock();

// Обновяване на shoutbox
function loadShoutboxMessages() {
    fetch('/shoutbox.php?action=get')
    .then(r => r.text())
    .then(html => {
        const container = document.getElementById('shoutboxMessages');
        if (container) {
            container.innerHTML = html;
            container.scrollTop = container.scrollHeight;
        }
    });
}

// На всеки 10 секунди
setInterval(loadShoutboxMessages, 10000);