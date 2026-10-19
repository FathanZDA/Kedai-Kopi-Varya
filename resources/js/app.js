// Perilaku global seluruh halaman publik.

/** Buka/tutup menu navigasi di layar kecil. */
function initNavToggle() {
    const button = document.querySelector('[data-nav-toggle]');
    const panel = document.querySelector('[data-nav-panel]');

    if (!button || !panel) return;

    button.addEventListener('click', () => {
        const isOpen = !panel.hidden;

        panel.hidden = isOpen;
        button.setAttribute('aria-expanded', String(!isOpen));
    });
}

document.addEventListener('DOMContentLoaded', initNavToggle);