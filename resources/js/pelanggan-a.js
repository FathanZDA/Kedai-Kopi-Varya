// Interaksi khusus halaman Pelanggan A: carousel hero dan filter menu.

const AUTOPLAY_DELAY = 6000;
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

/** Carousel slide promo di hero. */
function initHeroCarousel() {
    const slides = Array.from(document.querySelectorAll('[data-hero-slide]'));
    if (slides.length < 2) return;

    const dots = Array.from(document.querySelectorAll('[data-hero-dot]'));
    const progressBar = document.querySelector('[data-hero-progress-bar]');
    const root = slides[0].closest('[data-hero-root]');

    let current = slides.findIndex((slide) => slide.classList.contains('is-active'));
    if (current < 0) current = 0;
    let timer = null;

    const render = (next) => {
        current = (next + slides.length) % slides.length;

        slides.forEach((slide, i) => {
            const active = i === current;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', active ? 'false' : 'true');
        });

        dots.forEach((dot, i) => {
            const active = i === current;
            dot.classList.toggle('w-7', active);
            dot.classList.toggle('bg-varya', active);
            dot.classList.toggle('w-2.5', !active);
            dot.classList.toggle('bg-white/60', !active);

            if (active) {
                dot.setAttribute('aria-current', 'true');
            } else {
                dot.removeAttribute('aria-current');
            }
        });
    };

    const restartProgress = () => {
        if (!progressBar) return;

        progressBar.classList.remove('is-running');
        // Baca offsetWidth memaksa reflow agar animasi CSS dimulai dari awal.
        void progressBar.offsetWidth;
        progressBar.classList.add('is-running');
    };

    const stop = () => {
        if (timer) clearTimeout(timer);
        timer = null;
        progressBar?.classList.remove('is-running');
    };

    const start = () => {
        stop();

        // Hormati preferensi reduced motion: slide hanya berganti lewat navigasi.
        if (reduceMotion.matches) return;

        restartProgress();
        timer = setTimeout(() => {
            render(current + 1);
            start();
        }, AUTOPLAY_DELAY);
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
    root?.addEventListener('mouseenter', stop);
    root?.addEventListener('mouseleave', start);
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));

    render(current);
    start();
}

/** Pencarian, filter kategori, dan pratinjau menu di beranda. */
function initMenuFiltering() {
    const grid = document.querySelector('[data-menu-grid]');
    if (!grid) return;

    const items = Array.from(grid.querySelectorAll('[data-menu-item]'));
    const buttons = Array.from(document.querySelectorAll('[data-filter]'));
    const input = document.querySelector('[data-menu-search-input]');
    const emptyState = document.querySelector('[data-menu-empty]');
    const moreButton = grid.closest('section')?.querySelector('[data-menu-more]');
    const moreLabel = moreButton?.querySelector('[data-menu-more-label]');
    const moreIcon = moreButton?.querySelector('[data-menu-more-icon]');
    const previewLimit = Number.parseInt(grid.dataset.menuPreviewLimit ?? '0', 10) || 0;

    let activeCategory = 'all';
    let expanded = false;

    const apply = () => {
        const term = (input?.value ?? '').trim().toLowerCase();
        const filtering = activeCategory !== 'all' || term !== '';
        let visible = 0;

        items.forEach((item, index) => {
            const matchesCategory =
                activeCategory === 'all' || item.dataset.category === activeCategory;
            const matchesTerm = term === '' || item.dataset.name.includes(term);
            const matches = matchesCategory && matchesTerm;
            const beyondPreview =
                !filtering && !expanded && previewLimit > 0 && index >= previewLimit;

            item.hidden = !matches || beyondPreview;

            if (matches) visible += 1;
        });

        if (emptyState) emptyState.hidden = visible > 0;

        if (moreButton) {
            const canToggle = previewLimit > 0 && items.length > previewLimit;
            moreButton.hidden = !canToggle || filtering;
            moreButton.setAttribute('aria-expanded', expanded ? 'true' : 'false');

            if (moreLabel) {
                moreLabel.textContent = expanded ? 'Tampilkan Lebih Sedikit' : 'Lihat Semua Menu';
            }

            moreIcon?.classList.toggle('rotate-180', expanded);
        }
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

    moreButton?.addEventListener('click', () => {
        expanded = !expanded;
        apply();
    });

    input?.addEventListener('input', apply);

    document.querySelector('[data-menu-search]')?.addEventListener('submit', (e) => e.preventDefault());

    // Terapkan pratinjau setelah semua listener siap; tanpa JS semua kartu tetap tampil.
    apply();
}

document.addEventListener('DOMContentLoaded', () => {
    initHeroCarousel();
    initMenuFiltering();
});