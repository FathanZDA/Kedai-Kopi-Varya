// Interaksi khusus halaman Pelanggan A: carousel hero dan filter menu.

const AUTOPLAY_DELAY = 6000;

/** Carousel slide promo di hero. */
function initHeroCarousel() {
    const slides = Array.from(document.querySelectorAll('[data-hero-slide]'));
    if (slides.length < 2) return;

    const dots = Array.from(document.querySelectorAll('[data-hero-dot]'));
    let current = 0;
    let timer = null;

    const render = (next) => {
        current = (next + slides.length) % slides.length;

        slides.forEach((slide, i) => {
            slide.classList.toggle('hidden', i !== current);
        });

        dots.forEach((dot, i) => {
            const active = i === current;
            dot.classList.toggle('w-7', active);
            dot.classList.toggle('bg-varya', active);
            dot.classList.toggle('w-2.5', !active);
            dot.classList.toggle('bg-white/60', !active);
        });
    };

    const start = () => {
        stop();
        timer = setInterval(() => render(current + 1), AUTOPLAY_DELAY);
    };

    const stop = () => {
        if (timer) clearInterval(timer);
        timer = null;
    };

    document.querySelector('[data-hero-prev]')?.addEventListener('click', () => {
        render(current - 1);
        start();
    });

    document.querySelector('[data-hero-next]')?.addEventListener('click', () => {
        render(current + 1);
        start();
    });

    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            render(i);
            start();
        });
    });

    // Jeda autoplay saat kursor di atas hero atau tab tidak aktif.
    const root = slides[0].closest('.brut');
    root?.addEventListener('mouseenter', stop);
    root?.addEventListener('mouseleave', start);
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));

    start();
}

/** Pencarian + filter kategori pada daftar menu. */
function initMenuFiltering() {
    const grid = document.querySelector('[data-menu-grid]');
    if (!grid) return;

    const items = Array.from(grid.querySelectorAll('[data-menu-item]'));
    const buttons = Array.from(document.querySelectorAll('[data-filter]'));
    const input = document.querySelector('[data-menu-search-input]');
    const emptyState = document.querySelector('[data-menu-empty]');

    let activeCategory = 'all';

    const apply = () => {
        const term = (input?.value ?? '').trim().toLowerCase();
        let visible = 0;

        items.forEach((item) => {
            const matchesCategory =
                activeCategory === 'all' || item.dataset.category === activeCategory;
            const matchesTerm = term === '' || item.dataset.name.includes(term);

            const show = matchesCategory && matchesTerm;
            item.hidden = !show;

            if (show) visible += 1;
        });

        if (emptyState) emptyState.hidden = visible > 0;
    };

    const markActive = (slug) => {
        buttons.forEach((button) => {
            const active = button.dataset.filter === slug;
            button.classList.toggle('bg-varya-dark', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('bg-surface', !active);
            button.classList.toggle('text-ink', !active);
        });
    };

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            activeCategory = button.dataset.filter;
            markActive(activeCategory);
            apply();
        });
    });

    input?.addEventListener('input', apply);

    document.querySelector('[data-menu-search]')?.addEventListener('submit', (e) => e.preventDefault());
}

document.addEventListener('DOMContentLoaded', () => {
    initHeroCarousel();
    initMenuFiltering();
});