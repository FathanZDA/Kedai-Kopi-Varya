// Perilaku global seluruh halaman publik.

/** Buka/tutup menu navigasi di layar kecil. */
function initNavToggle() {
    const button = document.querySelector('[data-nav-toggle]');
    const panel = document.querySelector('[data-nav-panel]');

    if (!button || !panel) return;

    const closePanel = () => {
        panel.hidden = true;
        button.setAttribute('aria-expanded', 'false');
    };

    button.addEventListener('click', () => {
        const isOpen = !panel.hidden;

        panel.hidden = isOpen;
        button.setAttribute('aria-expanded', String(!isOpen));
    });

    // Tutup panel setelah tautan dipilih, termasuk tautan anchor.
    panel.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', closePanel);
    });
}

/** Tandai satu tautan navigasi sebagai aktif. */
function setActiveNavLink(links, section) {
    links.forEach((link) => {
        const isActive = link.dataset.navSection === section;

        link.classList.toggle('bg-varya-dark', isActive);
        link.classList.toggle('text-white', isActive);
        link.classList.toggle('text-ink', !isActive);
        link.classList.toggle('hover:text-varya', !isActive && link.hasAttribute('data-nav-desktop'));

        if (isActive) {
            link.setAttribute('aria-current', 'page');
        } else {
            link.removeAttribute('aria-current');
        }
    });
}

/** Scroll-spy navigasi beranda; hanya aktif bila semua section tersedia. */
function initSectionNav() {
    const links = Array.from(document.querySelectorAll('[data-nav-link]'));
    const sections = ['beranda', 'tentang', 'menu', 'lokasi']
        .map((id) => document.getElementById(id))
        .filter(Boolean);

    if (links.length === 0 || sections.length !== 4) return;

    const header = document.querySelector('header');
    const root = document.documentElement;

    let activeSection = null;
    let ticking = false;
    let lockUntil = 0;

    const activeOffset = () => {
        const headerHeight = header?.offsetHeight ?? 0;
        const scrollPadding = Number.parseFloat(window.getComputedStyle(root).scrollPaddingTop) || 0;

        return Math.max(headerHeight, scrollPadding) + 8;
    };

    const update = () => {
        ticking = false;

        // Jangan biarkan scroll-spy menimpa pilihan pengguna selama smooth scroll.
        if (Date.now() < lockUntil) return;

        const offset = activeOffset();
        let nextSection = sections[0].id;

        sections.forEach((section) => {
            if (section.getBoundingClientRect().top <= offset) {
                nextSection = section.id;
            }
        });

        // Saat halaman mencapai dasar, section terakhir tetap aktif.
        if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2) {
            nextSection = sections[sections.length - 1].id;
        }

        if (nextSection !== activeSection) {
            activeSection = nextSection;
            setActiveNavLink(links, nextSection);
        }
    };

    const scheduleUpdate = () => {
        if (ticking) return;

        ticking = true;
        window.requestAnimationFrame(update);
    };

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(scheduleUpdate, {
            rootMargin: '-25% 0px -65% 0px',
            threshold: [0, 0.25, 0.5, 0.75, 1],
        });

        sections.forEach((section) => observer.observe(section));
    }

    links.forEach((link) => {
        link.addEventListener('click', () => {
            lockUntil = Date.now() + 1500;
            activeSection = link.dataset.navSection;
            setActiveNavLink(links, activeSection);
        });
    });

    const releaseLock = () => {
        if (Date.now() >= lockUntil) return;

        lockUntil = 0;
        scheduleUpdate();
    };

    if ('onscrollend' in window) {
        window.addEventListener('scrollend', releaseLock, { passive: true });
    }

    // Scroll manual atau keyboard membatalkan kunci klik.
    ['wheel', 'touchstart', 'keydown'].forEach((type) => {
        window.addEventListener(type, releaseLock, { passive: true });
    });

    window.addEventListener('scroll', scheduleUpdate, { passive: true });
    window.addEventListener('hashchange', scheduleUpdate);
    window.addEventListener('resize', scheduleUpdate, { passive: true });
    window.addEventListener('load', scheduleUpdate);

    scheduleUpdate();
}

document.addEventListener('DOMContentLoaded', () => {
    initNavToggle();
    initSectionNav();
});
