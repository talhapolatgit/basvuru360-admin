/**
 * Toast notifications — bottom-right.
 */
export function showToast(message, type = 'success') {
    if (!message) {
        return;
    }

    let host = document.getElementById('toast-host');
    if (!host) {
        host = document.createElement('div');
        host.id = 'toast-host';
        host.className = 'toast-host';
        document.body.appendChild(host);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.setAttribute('role', 'status');

    const icon = document.createElement('span');
    icon.className = 'toast-icon';
    icon.textContent = type === 'error' ? '!' : '✓';

    const text = document.createElement('span');
    text.className = 'toast-message';
    text.textContent = message;

    toast.append(icon, text);
    host.appendChild(toast);

    requestAnimationFrame(() => toast.classList.add('show'));

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 250);
    }, 2800);
}

export function consumeFlashToasts() {
    const flash = window.__flash;
    if (!flash) {
        return;
    }

    if (flash.success) {
        showToast(flash.success, 'success');
    }
    if (flash.error) {
        showToast(flash.error, 'error');
    }

    window.__flash = null;
}
