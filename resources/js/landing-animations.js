// Animasi editorial halus untuk beranda Pelanggan A dan B.
// Hanya berjalan di dalam [data-landing-animations], sehingga halaman lain
// tidak ikut tersembunyi atau beranimasi.

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const landingRoot = document.querySelector('[data-landing-animations]');

if (landingRoot && !reduceMotion.matches) {
    // Kelas ini baru dipasang setelah JS tersedia; tanpa JS semua konten tetap tampil.
    document.documentElement.classList.add('reveal-ready');
}

function initLandingAnimations() {
    const root = document.querySelector('[data-landing-animations]');
    if (!root) return;

    const targets = Array.from(root.querySelectorAll('[data-reveal]'));
    if (targets.length === 0) return;

    if (reduceMotion.matches || !('IntersectionObserver' in window)) {
        document.documentElement.classList.remove('reveal-ready');
        targets.forEach((target) => target.classList.add('is-revealed'));
        return;
    }

    const reveal = (target) => {
        target.classList.add('is-revealed');
    };

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;

                reveal(entry.target);
                observer.unobserve(entry.target);
            });
        },
        {
            threshold: 0.12,
            rootMargin: '0px 0px -8% 0px',
        },
    );

    targets.forEach((target) => {
        const delay = Number.parseInt(target.dataset.revealDelay ?? '', 10);

        if (Number.isFinite(delay) && delay > 0) {
            target.style.setProperty('--reveal-delay', `${delay}ms`);
        }

        observer.observe(target);
    });

    reduceMotion.addEventListener?.('change', (event) => {
        if (!event.matches) return;

        observer.disconnect();
        document.documentElement.classList.remove('reveal-ready');
        targets.forEach(reveal);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLandingAnimations, { once: true });
} else {
    initLandingAnimations();
}
