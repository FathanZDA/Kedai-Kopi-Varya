// Interaksi front-end area admin Kedai Kopi Varya.
// Belum ada pemanggilan API/database. Data contoh berasal dari Blade dan
// perubahan demo disimpan sementara di localStorage browser.

const CONFIG = window.ADMIN_CONFIG ?? {};
const ROUTES = CONFIG.routes ?? {};
const numberFormatter = new Intl.NumberFormat('id-ID');

const STORAGE_KEYS = {
    menus: 'varya.admin.menus.v1',
    promos: 'varya.admin.promos.v1',
    store: 'varya.admin.store.v1',
};

const EMPTY_IMAGE = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600">'
    + '<rect width="800" height="600" fill="#e9e5d9"/>'
    + '<path d="M330 280h140v100H330z" fill="none" stroke="#8d8d8e" stroke-width="8"/>'
    + '<path d="m345 355 35-35 28 28 20-20 27 27" fill="none" stroke="#8d8d8e" stroke-width="8"/>'
    + '<circle cx="360" cy="310" r="12" fill="#8d8d8e"/>'
    + '<text x="400" y="425" text-anchor="middle" font-family="monospace" font-size="20" fill="#8d8d8e" letter-spacing="4">FOTO DIHAPUS</text>'
    + '</svg>'
);

/* -------------------------------------------------------------------------- */
/* Utilitas                                                                   */
/* -------------------------------------------------------------------------- */

const rupiah = (value) => `Rp ${numberFormatter.format(Number(value) || 0)}`;
const angka = (value) => numberFormatter.format(Number(value) || 0);

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function clone(value) {
    return JSON.parse(JSON.stringify(value ?? null));
}

function readStorage(key, fallback) {
    try {
        const raw = window.localStorage.getItem(key);
        return raw ? JSON.parse(raw) : fallback;
    } catch {
        return fallback;
    }
}

function writeStorage(key, value) {
    try {
        window.localStorage.setItem(key, JSON.stringify(value));
    } catch {
        // localStorage bisa tidak tersedia pada mode privat; abaikan.
    }
}

function openDialog(dialog) {
    if (dialog && !dialog.open) dialog.showModal();
}

function closeDialog(dialog) {
    if (dialog?.open) dialog.close();
}

function enableBackdropClose(dialog) {
    dialog?.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
}

let toastTimer = null;

function showToast(message) {
    const toast = document.querySelector('[data-toast]');
    if (!toast) return;

    toast.textContent = message;
    toast.hidden = false;

    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => {
        toast.hidden = true;
    }, 3400);
}

function formatClock(date = new Date()) {
    const dateText = new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        timeZone: 'Asia/Jakarta',
    }).format(date);

    const timeText = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        timeZone: 'Asia/Jakarta',
    })
        .format(date)
        .replaceAll('.', ':');

    return `${dateText} • ${timeText} WIB`;
}

function formatDateShort(value) {
    if (!value) return '';
    const date = new Date(`${value}T00:00:00`);
    if (Number.isNaN(date.getTime())) return value;

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(date);
}

function formatPeriod(start, end) {
    if (start && end) return `${formatDateShort(start)} – ${formatDateShort(end)}`;
    if (start) return `Mulai ${formatDateShort(start)}`;
    if (end) return `Hingga ${formatDateShort(end)}`;
    return 'Tanpa batas periode';
}

function readImageFile(file, maxBytes, onSuccess, onError) {
    if (!file) return;

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        onError?.('Format file harus berupa gambar JPG, PNG, atau WEBP.');
        return;
    }

    if (file.size > maxBytes) {
        onError?.(`Ukuran file maksimal ${Math.round(maxBytes / 1024 / 1024)} MB.`);
        return;
    }

    const reader = new FileReader();
    reader.onload = () => onSuccess?.(String(reader.result), file.name);
    reader.onerror = () => onError?.('File tidak bisa dibaca. Coba pilih file lain.');
    reader.readAsDataURL(file);
}

function renderPagination(container, totalPages, currentPage, onSelect, { compact = false } = {}) {
    if (!container) return;

    container.replaceChildren();

    const makeButton = (label, page, { disabled = false, active = false, ariaLabel = null } = {}) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        button.disabled = disabled;

        button.className = [
            'tag inline-flex h-8 min-w-8 items-center justify-center rounded border-2 border-ink px-2 text-[8px] font-bold shadow-[1px_1px_0_0_#1b1c1d] transition-colors',
            active
                ? 'bg-varya-dark text-white'
                : 'bg-admin-panel text-ink hover:bg-varya-light',
            disabled ? 'cursor-not-allowed opacity-40' : '',
        ]
            .filter(Boolean)
            .join(' ');

        if (active) button.setAttribute('aria-current', 'page');
        if (ariaLabel) button.setAttribute('aria-label', ariaLabel);

        if (!disabled && !active) {
            button.addEventListener('click', () => onSelect(page));
        }

        container.appendChild(button);
    };

    makeButton(compact ? '‹' : 'Sebelumnya', currentPage - 1, {
        disabled: currentPage <= 1,
        ariaLabel: 'Halaman sebelumnya',
    });

    for (let page = 1; page <= totalPages; page += 1) {
        makeButton(String(page), page, { active: page === currentPage });
    }

    makeButton(compact ? '›' : 'Berikutnya', currentPage + 1, {
        disabled: currentPage >= totalPages,
        ariaLabel: 'Halaman berikutnya',
    });
}

/* -------------------------------------------------------------------------- */
/* Konfirmasi generik                                                         */
/* -------------------------------------------------------------------------- */

let confirmCallback = null;

function askConfirm({ title, message, label = 'Hapus', onConfirm }) {
    const dialog = document.querySelector('[data-admin-confirm-dialog]');
    if (!dialog) {
        if (window.confirm(message)) onConfirm?.();
        return;
    }

    dialog.querySelector('[data-confirm-title]').textContent = title;
    dialog.querySelector('[data-confirm-message]').textContent = message;
    dialog.querySelector('[data-confirm-accept]').textContent = label;

    confirmCallback = onConfirm;
    openDialog(dialog);
}

function initConfirmDialog() {
    const dialog = document.querySelector('[data-admin-confirm-dialog]');
    if (!dialog) return;

    dialog.querySelectorAll('[data-confirm-cancel]').forEach((button) => {
        button.addEventListener('click', () => closeDialog(dialog));
    });

    dialog.querySelector('[data-confirm-accept]')?.addEventListener('click', () => {
        const callback = confirmCallback;
        confirmCallback = null;
        closeDialog(dialog);
        callback?.();
    });

    dialog.addEventListener('close', () => {
        confirmCallback = null;
    });

    enableBackdropClose(dialog);
}

/* -------------------------------------------------------------------------- */
/* Shell: sidebar dan jam                                                     */
/* -------------------------------------------------------------------------- */

function initShell() {
    const sidebar = document.querySelector('[data-admin-sidebar]');
    const backdrop = document.querySelector('[data-admin-sidebar-backdrop]');
    const openButton = document.querySelector('[data-admin-sidebar-open]');
    const closeButton = document.querySelector('[data-admin-sidebar-close]');

    const openSidebar = () => {
        sidebar?.classList.remove('-translate-x-full');
        if (backdrop) backdrop.hidden = false;
        document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
    };

    const closeSidebar = () => {
        sidebar?.classList.add('-translate-x-full');
        if (backdrop) backdrop.hidden = true;
        document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
    };

    openButton?.addEventListener('click', openSidebar);
    closeButton?.addEventListener('click', closeSidebar);
    backdrop?.addEventListener('click', closeSidebar);

    sidebar?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 1024) closeSidebar();
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !backdrop?.hidden) closeSidebar();
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) closeSidebar();
    });

    const clock = document.querySelector('[data-admin-clock]');
    if (clock) {
        const tick = () => {
            clock.textContent = formatClock();
        };
        tick();
        window.setInterval(tick, 30000);
    }
}

/* -------------------------------------------------------------------------- */
/* Dashboard                                                                  */
/* -------------------------------------------------------------------------- */

function initDashboard() {
    const root = document.querySelector('[data-admin-dashboard]');
    if (!root) return;

    const orders = CONFIG.orders ?? [];
    const tbody = root.querySelector('[data-dashboard-orders]');
    const record = root.querySelector('[data-dashboard-orders-record]');
    const pagination = root.querySelector('[data-dashboard-orders-pagination]');
    const tabs = Array.from(root.querySelectorAll('[data-order-tab]'));
    const pageSize = 4;
    const state = { tab: 'all', page: 1 };

    const statusMeta = {
        selesai: {
            label: 'Selesai',
            pill: 'border-varya/30 bg-varya-light text-varya-dark',
            dot: 'bg-varya',
        },
        siap_saji: {
            label: 'Siap Saji',
            pill: 'border-[#8a3b1d] bg-[#8a3b1d] text-white',
            dot: 'bg-white',
        },
        diproses: {
            label: 'Diproses',
            pill: 'border-[#b58a2b]/40 bg-kasir-amber-soft text-[#8a6415]',
            dot: 'bg-[#c79225]',
        },
    };

    const orderRow = (order) => {
        const status = statusMeta[order.status] ?? statusMeta.selesai;
        const typeClass = order.type === 'take_away'
            ? 'bg-varya-dark text-white'
            : 'bg-varya-light text-varya-dark';

        return `
            <tr class="align-top transition-colors hover:bg-varya-light/15">
                <td class="px-4 py-6">
                    <p class="font-mono text-[9px] leading-tight font-bold text-ink">#${escapeHtml(order.code)}</p>
                    <p class="mt-1 flex items-center gap-1 font-serif text-[9px] text-muted">
                        <span class="h-1.5 w-1.5 rounded-full bg-varya"></span>
                        ${escapeHtml(order.time)}
                    </p>
                </td>
                <td class="px-3 py-6">
                    <p class="font-display text-[13px] leading-tight font-extrabold">${escapeHtml(order.customer)}</p>
                    <p class="tag mt-1 text-[7px] text-muted">Pelanggan</p>
                </td>
                <td class="px-3 py-6">
                    <span class="tag inline-block rounded border-2 border-ink px-2 py-1 text-[7px] font-bold ${typeClass}">
                        ${escapeHtml(order.type_label)}
                    </span>
                </td>
                <td class="px-3 py-6">
                    <p class="font-serif text-[11px] font-semibold text-ink">${escapeHtml(order.items)} Menu</p>
                    <p class="tag mt-0.5 text-[7px] text-muted">(${escapeHtml(order.portions)} porsi)</p>
                </td>
                <td class="px-3 py-6">
                    <p class="font-display text-[13px] leading-tight font-bold">${rupiah(order.total)}</p>
                    <p class="tag mt-1 text-[7px] text-muted">Lunas &bull; ${escapeHtml(order.payment ?? 'QRIS')}</p>
                </td>
                <td class="px-4 py-6">
                    <span class="tag inline-flex items-center gap-1.5 rounded border-2 px-2 py-1 text-[7px] font-bold ${status.pill}">
                        <span class="h-1.5 w-1.5 rounded-full ${status.dot}"></span>
                        ${status.label}
                    </span>
                </td>
            </tr>
        `;
    };

    const render = () => {
        const filtered = state.tab === 'all'
            ? orders
            : orders.filter((order) => order.type === state.tab);

        const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
        state.page = Math.min(Math.max(1, state.page), totalPages);

        const start = (state.page - 1) * pageSize;
        const visible = filtered.slice(start, start + pageSize);

        if (tbody) tbody.innerHTML = visible.map(orderRow).join('');

        if (record) {
            record.textContent = `Menampilkan ${visible.length} dari ${filtered.length} pesanan tercatat hari ini`;
        }

        renderPagination(pagination, totalPages, state.page, (page) => {
            state.page = page;
            render();
        }, { compact: true });

        tabs.forEach((button) => {
            const active = button.dataset.orderTab === state.tab;
            button.classList.toggle('bg-varya-dark', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('bg-admin-panel', !active);
            button.classList.toggle('text-ink', !active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    };

    tabs.forEach((button) => {
        button.addEventListener('click', () => {
            state.tab = button.dataset.orderTab ?? 'all';
            state.page = 1;
            render();
        });
    });

    render();
}

/* -------------------------------------------------------------------------- */
/* Data menu bersama                                                          */
/* -------------------------------------------------------------------------- */

function loadMenus() {
    const stored = readStorage(STORAGE_KEYS.menus, null);

    if (Array.isArray(stored)) return stored;

    const seeded = clone(CONFIG.menus ?? []);
    writeStorage(STORAGE_KEYS.menus, seeded);
    return seeded;
}

function saveMenus(menus) {
    writeStorage(STORAGE_KEYS.menus, menus);
}

function categoryLabel(slug) {
    return (CONFIG.categories ?? []).find((category) => category.slug === slug)?.label
        ?? (slug ? slug.replaceAll('-', ' ') : 'Kategori');
}

function generateSku(category, menus) {
    const prefixes = {
        kopi: 'KOP',
        'non-kopi': 'NON',
        makanan: 'MKN',
        camilan: 'CML',
    };

    const prefix = prefixes[category] ?? 'SKU';
    const total = menus.filter((menu) => menu.category === category && !menu.deleted).length + 1;

    return `${prefix}-${String(total).padStart(3, '0')}`;
}

/* -------------------------------------------------------------------------- */
/* Daftar menu                                                                */
/* -------------------------------------------------------------------------- */

function initMenuList() {
    const root = document.querySelector('[data-admin-menu-list]');
    if (!root) return;

    const tbody = root.querySelector('[data-admin-menu-rows]');
    const record = root.querySelector('[data-admin-menu-record]');
    const pagination = root.querySelector('[data-admin-menu-pagination]');
    const empty = root.querySelector('[data-admin-menu-empty]');
    const searchInput = root.querySelector('[data-admin-menu-search]');
    const availability = root.querySelector('[data-admin-menu-availability]');
    const categoryButtons = Array.from(root.querySelectorAll('[data-admin-menu-category]'));
    const totalLabel = root.querySelector('[data-admin-menu-total]');
    const unavailableLabel = root.querySelector('[data-admin-menu-unavailable]');

    const urlSearch = new URLSearchParams(window.location.search).get('search') ?? '';
    const state = {
        search: urlSearch,
        category: 'all',
        availability: 'all',
        page: 1,
    };

    const pageSize = 7;

    if (searchInput) searchInput.value = urlSearch;

    const setCategoryActive = () => {
        categoryButtons.forEach((button) => {
            const active = button.dataset.adminMenuCategory === state.category;
            button.classList.toggle('bg-varya-dark', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('bg-admin-panel', !active);
            button.classList.toggle('text-ink', !active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    };

    const filteredMenus = (menus) => menus.filter((menu) => {
        if (menu.deleted) return false;

        if (state.category !== 'all' && menu.category !== state.category) return false;

        if (state.availability === 'available' && !menu.available) return false;
        if (state.availability === 'unavailable' && menu.available) return false;

        if (state.search) {
            const haystack = [
                menu.name,
                menu.sku,
                menu.description,
                menu.category_label,
            ].join(' ').toLowerCase();

            if (!haystack.includes(state.search.toLowerCase())) return false;
        }

        return true;
    });

    const menuRow = (menu) => `
        <tr data-admin-menu-row data-menu-id="${escapeHtml(menu.id)}" class="align-top transition-colors hover:bg-varya-light/15">
            <td class="px-4 py-3">
                <img src="${escapeHtml(menu.image)}"
                     alt="${escapeHtml(menu.name)}"
                     loading="lazy"
                     class="h-12 w-12 rounded border-2 border-ink object-cover ${menu.available ? '' : 'grayscale'}">
            </td>
            <td class="px-3 py-3">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="font-display text-[14px] leading-tight font-extrabold">${escapeHtml(menu.name)}</p>
                    ${menu.featured ? '<span class="tag rounded bg-varya-dark px-1.5 py-0.5 text-[7px] font-bold text-white">Terlaris</span>' : ''}
                    ${!menu.available ? '<span class="tag rounded bg-brown px-1.5 py-0.5 text-[7px] font-bold text-white">Habis</span>' : ''}
                </div>
                <p class="mt-1 line-clamp-2 max-w-[390px] font-serif text-[11px] leading-relaxed text-ink-soft">${escapeHtml(menu.description)}</p>
                <p class="tag mt-1 text-[7px] text-muted">${escapeHtml(menu.sku)}</p>
            </td>
            <td class="px-3 py-3">
                <span class="tag inline-block rounded border-2 border-line-strong bg-[#f0eee3] px-2.5 py-1 text-[8px] font-bold text-ink-soft">
                    ${escapeHtml(menu.category_label)}
                </span>
            </td>
            <td class="px-3 py-3">
                <span class="font-display text-[14px] font-bold text-ink">${rupiah(menu.price)}</span>
            </td>
            <td class="px-3 py-3">
                <button type="button"
                        data-admin-menu-toggle
                        title="Klik untuk mengubah ketersediaan"
                        class="tag inline-flex items-center gap-1.5 rounded border-2 px-2.5 py-1 text-[8px] font-bold transition-colors ${
                            menu.available
                                ? 'border-varya/30 bg-varya-light text-varya-dark hover:bg-[#c9dfbf]'
                                : 'border-rust/30 bg-[#fbe1de] text-rust hover:bg-[#f7cfc9]'
                        }">
                    <span class="h-1.5 w-1.5 rounded-full ${menu.available ? 'bg-varya' : 'bg-rust'}"></span>
                    ${menu.available ? 'Tersedia' : 'Habis'}
                </button>
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1.5">
                    <a href="${ROUTES.menuEditBase}/${menu.id}/edit"
                       class="tag rounded border-2 border-ink bg-admin-panel px-2.5 py-1.5 text-[8px] font-bold transition-colors hover:bg-varya-light">
                        Ubah
                    </a>
                    <button type="button"
                            data-admin-menu-delete
                            class="tag rounded border-2 border-ink bg-[#fbe1de] px-2.5 py-1.5 text-[8px] font-bold text-rust transition-colors hover:bg-[#f7cfc9]">
                        Hapus
                    </button>
                </div>
            </td>
        </tr>
    `;

    const render = () => {
        const menus = loadMenus();
        const activeMenus = menus.filter((menu) => !menu.deleted);
        const filtered = filteredMenus(menus);

        const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
        state.page = Math.min(Math.max(1, state.page), totalPages);

        const start = (state.page - 1) * pageSize;
        const visible = filtered.slice(start, start + pageSize);

        if (tbody) {
            tbody.innerHTML = visible.length > 0
                ? visible.map(menuRow).join('')
                : '<tr><td colspan="6" class="px-4 py-10 text-center font-serif text-sm text-muted">Tidak ada menu yang sesuai dengan filter.</td></tr>';
        }

        if (record) {
            const from = filtered.length === 0 ? 0 : start + 1;
            const to = Math.min(start + pageSize, filtered.length);
            record.textContent = `// Menampilkan ${from} – ${to} dari ${filtered.length} menu terdaftar`;
        }

        if (totalLabel) totalLabel.textContent = String(activeMenus.length);
        if (unavailableLabel) unavailableLabel.textContent = String(activeMenus.filter((menu) => !menu.available).length);

        if (empty) empty.hidden = filtered.length > 0;

        renderPagination(pagination, totalPages, state.page, (page) => {
            state.page = page;
            render();
        });

        setCategoryActive();
    };

    categoryButtons.forEach((button) => {
        button.addEventListener('click', () => {
            state.category = button.dataset.adminMenuCategory ?? 'all';
            state.page = 1;
            render();
        });
    });

    availability?.addEventListener('change', () => {
        state.availability = availability.value;
        state.page = 1;
        render();
    });

    searchInput?.addEventListener('input', () => {
        state.search = searchInput.value.trim();
        state.page = 1;
        render();
    });

    tbody?.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-admin-menu-toggle]');
        const remove = event.target.closest('[data-admin-menu-delete]');

        if (toggle) {
            const row = toggle.closest('[data-admin-menu-row]');
            const id = Number(row?.dataset.menuId);
            const menus = loadMenus();
            const menu = menus.find((item) => item.id === id);
            if (!menu) return;

            menu.available = !menu.available;
            saveMenus(menus);
            render();
            showToast(`${menu.name} ditandai ${menu.available ? 'tersedia' : 'habis'}.`);
        }

        if (remove) {
            const row = remove.closest('[data-admin-menu-row]');
            const id = Number(row?.dataset.menuId);
            const menus = loadMenus();
            const menu = menus.find((item) => item.id === id);
            if (!menu) return;

            askConfirm({
                title: 'Hapus menu ini?',
                message: `Menu "${menu.name}" akan dihapus dari daftar penjualan baru. Riwayat pesanan lama tetap tersimpan pada demo ini.`,
                label: 'Hapus Menu',
                onConfirm: () => {
                    menu.deleted = true;
                    saveMenus(menus);
                    render();
                    showToast(`${menu.name} berhasil dihapus (demo).`);
                },
            });
        }
    });

    const params = new URLSearchParams(window.location.search);
    const saved = params.get('saved');
    const deleted = params.get('deleted');

    if (saved) showToast(`Menu "${saved}" berhasil disimpan (demo).`);
    if (deleted) showToast(`Menu "${deleted}" berhasil dihapus (demo).`);

    render();
}

/* -------------------------------------------------------------------------- */
/* Form tambah / ubah menu                                                    */
/* -------------------------------------------------------------------------- */

function initMenuForm() {
    const root = document.querySelector('[data-admin-menu-form]');
    if (!root) return;

    const form = root.querySelector('[data-menu-form]');
    if (!form) return;

    const mode = root.dataset.mode === 'edit' ? 'edit' : 'create';
    const menuId = Number(root.dataset.menuId) || null;

    const fields = {
        name: form.querySelector('[data-field="name"]'),
        category: form.querySelector('[data-field="category"]'),
        price: form.querySelector('[data-field="price"]'),
        available: form.querySelector('[data-field="available"]'),
        description: form.querySelector('[data-field="description"]'),
        featured: form.querySelector('[data-field="featured"]'),
    };

    const imageInput = form.querySelector('[data-menu-image-input]');
    const dropzone = form.querySelector('[data-menu-dropzone]');
    const imagePreview = root.querySelector('[data-menu-image-preview]');
    const imageName = form.querySelector('[data-menu-image-name]');
    const imageError = form.querySelector('[data-menu-image-error]');
    const descriptionCount = form.querySelector('[data-description-count]');
    const deleteButton = form.querySelector('[data-menu-delete]');

    const preview = {
        image: root.querySelector('[data-preview-image]'),
        name: root.querySelector('[data-preview-name]'),
        category: root.querySelector('[data-preview-category]'),
        sku: root.querySelector('[data-preview-sku]'),
        description: root.querySelector('[data-preview-description]'),
        price: root.querySelector('[data-preview-price]'),
        featured: root.querySelector('[data-preview-featured]'),
        unavailable: root.querySelector('[data-preview-unavailable]'),
    };

    const menus = loadMenus();
    const storedMenu = menuId ? menus.find((menu) => menu.id === menuId) : null;
    const current = storedMenu ?? clone(CONFIG.menu ?? {});
    const fallbackImage = current.image ?? CONFIG.menus?.[0]?.image ?? EMPTY_IMAGE;

    let selectedImage = null;

    if (mode === 'edit' && storedMenu) {
        if (fields.name) fields.name.value = storedMenu.name;
        if (fields.category) fields.category.value = storedMenu.category;
        if (fields.price) fields.price.value = storedMenu.price;
        if (fields.available) fields.available.value = storedMenu.available ? '1' : '0';
        if (fields.description) fields.description.value = storedMenu.description;
        if (fields.featured) fields.featured.checked = Boolean(storedMenu.featured);
    }

    const setError = (name, message) => {
        const error = form.querySelector(`[data-error-for="${name}"]`);
        if (!error) return;
        error.textContent = message;
        error.hidden = !message;
    };

    const clearErrors = () => {
        form.querySelectorAll('[data-error-for]').forEach((error) => {
            error.textContent = '';
            error.hidden = true;
        });
    };

    const updateDescriptionCount = () => {
        if (descriptionCount) descriptionCount.textContent = String(fields.description?.value.length ?? 0);
    };

    const updatePreview = () => {
        const name = fields.name?.value.trim() || 'Nama Menu Baru';
        const category = fields.category?.value || '';
        const price = Number(fields.price?.value) || 0;
        const description = fields.description?.value.trim() || 'Deskripsi menu akan tampil di sini.';
        const available = fields.available?.value !== '0';
        const featured = Boolean(fields.featured?.checked);

        if (preview.name) preview.name.textContent = name;
        if (preview.category) preview.category.textContent = category ? categoryLabel(category) : 'Kategori';
        if (preview.sku) preview.sku.textContent = current.sku ?? (category ? generateSku(category, menus) : 'SKU-BARU');
        if (preview.description) preview.description.textContent = description;
        if (preview.price) preview.price.textContent = rupiah(price);
        if (preview.featured) preview.featured.classList.toggle('hidden', !featured);
        if (preview.unavailable) preview.unavailable.classList.toggle('hidden', available);

        const image = selectedImage ?? fallbackImage;
        if (preview.image) preview.image.src = image;
        if (imagePreview) imagePreview.src = image;

        updateDescriptionCount();
    };

    const setImageError = (message) => {
        if (!imageError) return;
        imageError.textContent = message;
        imageError.hidden = !message;
    };

    const handleImage = (file) => {
        setImageError('');

        readImageFile(
            file,
            2 * 1024 * 1024,
            (dataUrl, name) => {
                selectedImage = dataUrl;
                if (imageName) imageName.textContent = name;
                updatePreview();
            },
            (message) => setImageError(message)
        );
    };

    imageInput?.addEventListener('change', () => {
        handleImage(imageInput.files?.[0]);
        imageInput.value = '';
    });

    dropzone?.addEventListener('click', (event) => {
        if (event.target.closest('label')) return;
        imageInput?.click();
    });

    dropzone?.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropzone.classList.add('border-varya-dark', 'bg-varya-light/40');
    });

    dropzone?.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-varya-dark', 'bg-varya-light/40');
    });

    dropzone?.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('border-varya-dark', 'bg-varya-light/40');
        handleImage(event.dataTransfer?.files?.[0]);
    });

    form.querySelectorAll('[data-field]').forEach((field) => {
        field.addEventListener('input', updatePreview);
        field.addEventListener('change', updatePreview);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        clearErrors();

        const name = fields.name?.value.trim() ?? '';
        const category = fields.category?.value ?? '';
        const price = Number(fields.price?.value);

        let valid = true;

        if (!name) {
            setError('name', 'Nama menu wajib diisi.');
            valid = false;
        }

        if (!category) {
            setError('category', 'Pilih salah satu kategori.');
            valid = false;
        }

        if (!Number.isFinite(price) || price <= 0 || !Number.isInteger(price)) {
            setError('price', 'Harga harus berupa bilangan bulat positif.');
            valid = false;
        }

        if (!valid) {
            form.querySelector('[data-error-for]:not([hidden])')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const allMenus = loadMenus();
        const image = selectedImage ?? current.image ?? fallbackImage;
        const description = fields.description?.value.trim() ?? '';
        const available = fields.available?.value !== '0';
        const featured = Boolean(fields.featured?.checked);

        if (mode === 'edit' && menuId) {
            const existing = allMenus.find((menu) => menu.id === menuId);

            if (existing) {
                Object.assign(existing, {
                    name,
                    category,
                    category_label: categoryLabel(category),
                    price,
                    description,
                    image,
                    available,
                    featured,
                    deleted: false,
                });
            } else {
                allMenus.push({
                    id: menuId,
                    name,
                    sku: generateSku(category, allMenus),
                    category,
                    category_label: categoryLabel(category),
                    price,
                    description,
                    image,
                    available,
                    featured,
                    deleted: false,
                });
            }

            saveMenus(allMenus);
            window.location.assign(`${ROUTES.menus}?saved=${encodeURIComponent(name)}`);
            return;
        }

        const nextId = allMenus.reduce((max, menu) => Math.max(max, Number(menu.id) || 0), 0) + 1;

        allMenus.push({
            id: nextId,
            name,
            sku: generateSku(category, allMenus),
            category,
            category_label: categoryLabel(category),
            price,
            description,
            image,
            available,
            featured,
            deleted: false,
        });

        saveMenus(allMenus);
        window.location.assign(`${ROUTES.menus}?saved=${encodeURIComponent(name)}`);
    });

    deleteButton?.addEventListener('click', () => {
        const allMenus = loadMenus();
        const menu = allMenus.find((item) => item.id === menuId);
        if (!menu) return;

        askConfirm({
            title: 'Hapus menu ini?',
            message: `Menu "${menu.name}" akan dihapus dari daftar penjualan baru. Riwayat pesanan lama tetap tersimpan pada demo ini.`,
            label: 'Hapus Menu',
            onConfirm: () => {
                menu.deleted = true;
                saveMenus(allMenus);
                window.location.assign(`${ROUTES.menus}?deleted=${encodeURIComponent(menu.name)}`);
            },
        });
    });

    updatePreview();
}

/* -------------------------------------------------------------------------- */
/* Laporan transaksi                                                          */
/* -------------------------------------------------------------------------- */

function initReports() {
    const root = document.querySelector('[data-admin-reports]');
    if (!root) return;

    const transactions = CONFIG.transactions ?? [];
    const monthSelect = root.querySelector('[data-report-month]');
    const searchInput = root.querySelector('[data-report-search]');
    const methodSelect = root.querySelector('[data-report-method]');
    const statusSelect = root.querySelector('[data-report-status]');
    const resetButton = root.querySelector('[data-report-reset]');
    const tbody = root.querySelector('[data-report-rows]');
    const record = root.querySelector('[data-report-record]');
    const pagination = root.querySelector('[data-report-pagination]');
    const empty = root.querySelector('[data-report-empty]');
    const filteredCount = root.querySelector('[data-report-filtered-count]');

    const dialog = document.querySelector('[data-report-dialog]');
    const detail = dialog?.querySelector('[data-report-detail]');
    const detailTitle = dialog?.querySelector('[data-report-detail-title]');
    const detailSubtitle = dialog?.querySelector('[data-report-detail-subtitle]');

    const state = {
        month: CONFIG.defaultMonth ?? monthSelect?.value ?? '',
        search: '',
        method: 'all',
        status: 'all',
        page: 1,
    };

    const pageSize = 6;

    const reportStatusMeta = {
        lunas: {
            label: 'Lunas',
            pill: 'border-varya/30 bg-varya-light text-varya-dark',
            dot: 'bg-varya',
        },
        menunggu: {
            label: 'Menunggu',
            pill: 'border-[#b58a2b]/40 bg-kasir-amber-soft text-[#8a6415]',
            dot: 'bg-[#c79225]',
        },
        perlu_diperiksa: {
            label: 'Perlu Diperiksa',
            pill: 'border-rust/30 bg-[#fbe1de] text-rust',
            dot: 'bg-rust',
        },
        gagal: {
            label: 'Gagal',
            pill: 'border-line-strong bg-[#e9e7de] text-muted',
            dot: 'bg-muted',
        },
        kedaluwarsa: {
            label: 'Kedaluwarsa',
            pill: 'border-line-strong bg-[#e9e7de] text-muted',
            dot: 'bg-muted',
        },
        dibatalkan: {
            label: 'Dibatalkan',
            pill: 'border-line-strong bg-[#e9e7de] text-muted',
            dot: 'bg-muted',
        },
    };

    const methodMeta = {
        qris: 'border-varya/30 bg-varya-light text-varya-dark',
        tunai: 'border-[#8a3b1d]/30 bg-[#fbe1de] text-[#8a3b1d]',
    };

    const previousMonth = (month) => {
        const [year, monthNumber] = month.split('-').map(Number);
        const date = new Date(year, monthNumber - 2, 1);

        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
    };

    const summarize = (month) => {
        const paid = transactions.filter((transaction) => transaction.month === month && transaction.status === 'lunas');
        const revenue = paid.reduce((total, transaction) => total + transaction.total, 0);
        const qris = paid
            .filter((transaction) => transaction.method === 'qris')
            .reduce((total, transaction) => total + transaction.total, 0);
        const cash = revenue - qris;

        return {
            revenue,
            count: paid.length,
            qris,
            cash,
            average: paid.length > 0 ? Math.round(revenue / paid.length) : 0,
            qrisPercent: revenue > 0 ? (qris / revenue) * 100 : 0,
            cashPercent: revenue > 0 ? (cash / revenue) * 100 : 0,
        };
    };

    const setText = (selector, value) => {
        root.querySelectorAll(selector).forEach((element) => {
            element.textContent = value;
        });
    };

    const renderSummary = () => {
        const summary = summarize(state.month);
        const previous = summarize(previousMonth(state.month));
        const monthLabel = transactions.find((transaction) => transaction.month === state.month)?.month_label ?? state.month;

        setText('[data-report-revenue]', rupiah(summary.revenue));
        setText('[data-report-count]', String(summary.count));
        setText('[data-report-average]', rupiah(summary.average));
        setText('[data-report-qris]', rupiah(summary.qris));
        setText('[data-report-cash]', rupiah(summary.cash));
        setText('[data-report-qris-percent]', `${numberFormatter.format(Number(summary.qrisPercent.toFixed(1)))}%`);
        setText('[data-report-cash-percent]', `${numberFormatter.format(Number(summary.cashPercent.toFixed(1)))}%`);
        setText('[data-report-qris-percent-small]', `${numberFormatter.format(Number(summary.qrisPercent.toFixed(1)))}%`);
        setText('[data-report-cash-percent-small]', `${numberFormatter.format(Number(summary.cashPercent.toFixed(1)))}%`);

        const periodBadge = root.querySelector('[data-report-period-badge]');
        if (periodBadge) periodBadge.textContent = `Periode: ${monthLabel}`;

        const qrisBar = root.querySelector('[data-report-qris-bar]');
        const cashBar = root.querySelector('[data-report-cash-bar]');
        if (qrisBar) qrisBar.style.width = `${summary.qrisPercent}%`;
        if (cashBar) cashBar.style.width = `${summary.cashPercent}%`;

        const change = root.querySelector('[data-report-change]');
        if (change) {
            if (previous.revenue > 0) {
                const percent = ((summary.revenue - previous.revenue) / previous.revenue) * 100;
                const positive = percent >= 0;
                const formatted = numberFormatter.format(Number(Math.abs(percent).toFixed(1)));

                change.className = `flex items-center gap-1.5 font-serif text-[11px] ${positive ? 'text-varya-dark' : 'text-rust'}`;
                change.innerHTML = `
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        ${positive ? '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>' : '<path d="m3 7 6 6 4-4 8 8"/><path d="M15 17h6v-6"/>'}
                    </svg>
                    <span><strong>${positive ? '+' : '-'}${formatted}%</strong> dibanding bulan lalu</span>
                `;
            } else {
                change.innerHTML = '<span class="font-serif text-[11px] text-muted">Belum ada data pembanding bulan lalu</span>';
            }
        }
    };

    const reportRow = (transaction) => {
        const status = reportStatusMeta[transaction.status] ?? reportStatusMeta.lunas;

        return `
            <tr data-report-row data-report-id="${escapeHtml(transaction.id)}"
                class="cursor-pointer align-top transition-colors hover:bg-varya-light/15">
                <td class="px-4 py-3">
                    <p class="font-mono text-[9px] leading-tight font-bold text-ink">#${escapeHtml(transaction.code)}</p>
                </td>
                <td class="px-3 py-3">
                    <p class="font-mono text-[9px] leading-tight text-ink-soft">#${escapeHtml(transaction.order_code)}</p>
                </td>
                <td class="px-3 py-3">
                    <p class="font-display text-[12px] leading-tight font-extrabold">${escapeHtml(transaction.customer)}</p>
                    <p class="tag mt-1 text-[7px] text-muted">${escapeHtml(transaction.source_label)}</p>
                </td>
                <td class="px-3 py-3">
                    <p class="font-display text-[13px] leading-tight font-bold">${rupiah(transaction.total)}</p>
                </td>
                <td class="px-3 py-3">
                    <span class="tag inline-block rounded border-2 px-2 py-1 text-[7px] font-bold ${methodMeta[transaction.method] ?? methodMeta.tunai}">
                        ${escapeHtml(transaction.method_label)}
                    </span>
                </td>
                <td class="px-3 py-3">
                    <span class="tag inline-flex items-center gap-1.5 rounded border-2 px-2 py-1 text-[7px] font-bold ${status.pill}">
                        <span class="h-1.5 w-1.5 rounded-full ${status.dot}"></span>
                        ${escapeHtml(status.label)}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-serif text-[10px] leading-tight text-ink-soft">${escapeHtml(transaction.datetime)}</p>
                        <button type="button" data-report-open
                                title="Lihat rincian transaksi"
                                aria-label="Lihat rincian transaksi ${escapeHtml(transaction.code)}"
                                class="grid h-6 w-6 shrink-0 place-items-center rounded border-2 border-ink bg-admin-panel transition-colors hover:bg-varya-light">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    };

    const filteredTransactions = () => transactions
        .filter((transaction) => transaction.month === state.month)
        .filter((transaction) => {
            if (state.method !== 'all' && transaction.method !== state.method) return false;
            if (state.status !== 'all' && transaction.status !== state.status) return false;

            if (state.search) {
                const haystack = [
                    transaction.code,
                    transaction.order_code,
                    transaction.customer,
                    transaction.method_label,
                    transaction.status_label,
                ].join(' ').toLowerCase();

                if (!haystack.includes(state.search.toLowerCase())) return false;
            }

            return true;
        })
        .sort((a, b) => b.date_iso.localeCompare(a.date_iso) || b.datetime.localeCompare(a.datetime));

    const renderTable = () => {
        const filtered = filteredTransactions();
        const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
        state.page = Math.min(Math.max(1, state.page), totalPages);

        const start = (state.page - 1) * pageSize;
        const visible = filtered.slice(start, start + pageSize);

        if (tbody) {
            tbody.innerHTML = visible.length > 0
                ? visible.map(reportRow).join('')
                : '<tr><td colspan="7" class="px-4 py-10 text-center font-serif text-sm text-muted">Tidak ada transaksi yang sesuai dengan filter.</td></tr>';
        }

        if (record) {
            const from = filtered.length === 0 ? 0 : start + 1;
            const to = Math.min(start + pageSize, filtered.length);
            record.textContent = `// Menampilkan ${from} – ${to} dari ${filtered.length} transaksi`;
        }

        if (filteredCount) filteredCount.textContent = String(filtered.length);
        if (empty) empty.hidden = filtered.length > 0;

        renderPagination(pagination, totalPages, state.page, (page) => {
            state.page = page;
            renderTable();
        });
    };

    const openDetail = (id) => {
        const transaction = transactions.find((item) => item.id === id);
        if (!transaction || !dialog || !detail) return;

        if (detailTitle) detailTitle.textContent = `#${transaction.code}`;
        if (detailSubtitle) {
            detailSubtitle.textContent = `${transaction.order_code} • ${transaction.datetime} • ${transaction.source_label}`;
        }

        const items = transaction.items.map((item) => `
            <tr class="border-b border-line last:border-0">
                <td class="py-2 pr-3 font-serif text-[11px] font-semibold text-ink">${escapeHtml(item.name)}</td>
                <td class="px-2 py-2 text-center font-mono text-[10px] text-ink-soft">${escapeHtml(item.qty)}x</td>
                <td class="px-2 py-2 text-right font-serif text-[10px] text-ink-soft">${rupiah(item.price)}</td>
                <td class="py-2 pl-3 text-right font-display text-[12px] font-bold">${rupiah(item.subtotal)}</td>
            </tr>
        `).join('');

        const paymentDetail = transaction.method === 'tunai'
            ? `
                <div class="rounded-md border-2 border-line-strong bg-admin-soft p-3">
                    <p class="tag text-[8px] font-bold text-muted">Uang Diterima</p>
                    <p class="mt-1 font-display text-[17px] font-bold">${rupiah(transaction.cash_received)}</p>
                </div>
                <div class="rounded-md border-2 border-line-strong bg-admin-soft p-3">
                    <p class="tag text-[8px] font-bold text-muted">Kembalian</p>
                    <p class="mt-1 font-display text-[17px] font-bold">${rupiah(transaction.change)}</p>
                </div>
            `
            : `
                <div class="rounded-md border-2 border-line-strong bg-admin-soft p-3 sm:col-span-2">
                    <p class="tag text-[8px] font-bold text-muted">ID Transaksi Midtrans</p>
                    <p class="mt-1 break-all font-mono text-[9px] leading-relaxed text-ink">${escapeHtml(transaction.midtrans_id)}</p>
                    <p class="mt-1 font-serif text-[9px] text-muted">Status pembayaran tidak dapat diubah manual dari aplikasi.</p>
                </div>
            `;

        detail.innerHTML = `
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-md border-2 border-line-strong bg-admin-soft p-3">
                    <p class="tag text-[8px] font-bold text-muted">Pelanggan</p>
                    <p class="mt-1 font-display text-[16px] font-bold">${escapeHtml(transaction.customer)}</p>
                    <p class="mt-1 tag text-[8px] text-muted">${escapeHtml(transaction.source_label)}</p>
                </div>
                <div class="rounded-md border-2 border-line-strong bg-admin-soft p-3">
                    <p class="tag text-[8px] font-bold text-muted">Status Pembayaran</p>
                    <span class="tag mt-2 inline-flex items-center gap-1.5 rounded border-2 px-2 py-1 text-[8px] font-bold ${(reportStatusMeta[transaction.status] ?? reportStatusMeta.lunas).pill}">
                        <span class="h-1.5 w-1.5 rounded-full ${(reportStatusMeta[transaction.status] ?? reportStatusMeta.lunas).dot}"></span>
                        ${escapeHtml(transaction.status_label)}
                    </span>
                    <p class="mt-1 tag text-[8px] text-muted">${escapeHtml(transaction.method_label)}</p>
                </div>
            </div>

            <div class="mt-4 overflow-hidden rounded-md border-2 border-ink">
                <div class="flex items-center justify-between gap-2 border-b-2 border-ink bg-admin-head px-3 py-2">
                    <p class="tag text-[8px] font-bold text-ink">Item Pesanan</p>
                    <p class="tag text-[8px] text-muted">${transaction.items.length} jenis</p>
                </div>
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-line bg-admin-soft">
                            <th class="px-3 py-2 text-left font-mono text-[8px] font-bold text-muted uppercase">Menu</th>
                            <th class="px-2 py-2 text-center font-mono text-[8px] font-bold text-muted uppercase">Qty</th>
                            <th class="px-2 py-2 text-right font-mono text-[8px] font-bold text-muted uppercase">Harga</th>
                            <th class="px-3 py-2 text-right font-mono text-[8px] font-bold text-muted uppercase">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="px-3">
                        ${items}
                    </tbody>
                </table>
            </div>

            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                ${paymentDetail}
            </div>

            <div class="mt-4 flex items-center justify-between gap-3 rounded-md border-2 border-ink bg-varya-light px-4 py-3">
                <p class="tag text-[9px] font-bold text-varya-dark">Total Pembayaran</p>
                <p class="font-display text-[24px] leading-none font-black text-varya-dark">${rupiah(transaction.total)}</p>
            </div>
        `;

        openDialog(dialog);
    };

    monthSelect?.addEventListener('change', () => {
        state.month = monthSelect.value;
        state.page = 1;
        renderSummary();
        renderTable();
    });

    searchInput?.addEventListener('input', () => {
        state.search = searchInput.value.trim();
        state.page = 1;
        renderTable();
    });

    methodSelect?.addEventListener('change', () => {
        state.method = methodSelect.value;
        state.page = 1;
        renderTable();
    });

    statusSelect?.addEventListener('change', () => {
        state.status = statusSelect.value;
        state.page = 1;
        renderTable();
    });

    resetButton?.addEventListener('click', () => {
        state.month = CONFIG.defaultMonth ?? monthSelect?.value ?? state.month;
        state.search = '';
        state.method = 'all';
        state.status = 'all';
        state.page = 1;

        if (monthSelect) monthSelect.value = state.month;
        if (searchInput) searchInput.value = '';
        if (methodSelect) methodSelect.value = 'all';
        if (statusSelect) statusSelect.value = 'all';

        renderSummary();
        renderTable();
        showToast('Filter laporan direset.');
    });

    tbody?.addEventListener('click', (event) => {
        const row = event.target.closest('[data-report-row]');
        if (!row) return;
        openDetail(row.dataset.reportId);
    });

    dialog?.querySelectorAll('[data-report-close]').forEach((button) => {
        button.addEventListener('click', () => closeDialog(dialog));
    });
    enableBackdropClose(dialog);

    renderSummary();
    renderTable();
}

/* -------------------------------------------------------------------------- */
/* Iklan & promosi                                                            */
/* -------------------------------------------------------------------------- */

function initPromotions() {
    const root = document.querySelector('[data-admin-promotions]');
    if (!root) return;

    const list = root.querySelector('[data-promo-list]');
    const empty = root.querySelector('[data-promo-empty]');
    const form = root.querySelector('[data-promo-form]');
    const editorTitle = root.querySelector('[data-promo-editor-title]');
    const cancelEdit = root.querySelector('[data-promo-cancel-edit]');
    const dropzone = root.querySelector('[data-promo-dropzone]');
    const imageInput = root.querySelector('[data-promo-image-input]');
    const imageName = root.querySelector('[data-promo-image-name]');
    const editorDesktop = root.querySelector('[data-promo-editor-desktop]');
    const editorMobile = root.querySelector('[data-promo-editor-mobile]');
    const newButton = root.querySelector('[data-promo-new]');
    const resetButton = root.querySelector('[data-promo-reset]');

    const aboutEdit = root.querySelector('[data-about-edit]');
    const aboutDelete = root.querySelector('[data-about-delete]');
    const aboutStatus = root.querySelector('[data-about-status]');
    const aboutDesktop = root.querySelector('[data-about-desktop]');
    const aboutMobile = root.querySelector('[data-about-mobile]');
    const aboutDialog = document.querySelector('[data-about-dialog]');
    const aboutDropzone = aboutDialog?.querySelector('[data-about-dropzone]');
    const aboutImageInput = aboutDialog?.querySelector('[data-about-image-input]');
    const aboutImageName = aboutDialog?.querySelector('[data-about-image-name]');
    const aboutDialogDesktop = aboutDialog?.querySelector('[data-about-dialog-desktop]');
    const aboutDialogMobile = aboutDialog?.querySelector('[data-about-dialog-mobile]');
    const aboutError = aboutDialog?.querySelector('[data-about-error]');

    const defaultPromoImage = () => CONFIG.promos?.[0]?.image ?? EMPTY_IMAGE;

    const loadPromos = () => {
        const stored = readStorage(STORAGE_KEYS.promos, null);
        if (Array.isArray(stored)) return stored;

        const seeded = clone(CONFIG.promos ?? []);
        writeStorage(STORAGE_KEYS.promos, seeded);
        return seeded;
    };

    const savePromos = (promos) => writeStorage(STORAGE_KEYS.promos, promos);

    const state = {
        editingId: null,
        editorImage: null,
        editorBaseImage: defaultPromoImage(),
        aboutImage: CONFIG.aboutImage ?? EMPTY_IMAGE,
        aboutDialogImage: null,
        aboutDialogBaseImage: CONFIG.aboutImage ?? EMPTY_IMAGE,
    };

    const fields = {
        title: form?.querySelector('[data-promo-field="title"]'),
        description: form?.querySelector('[data-promo-field="description"]'),
        order: form?.querySelector('[data-promo-field="order"]'),
        active: form?.querySelector('[data-promo-field="active"]'),
        periodStart: form?.querySelector('[data-promo-field="period_start"]'),
        periodEnd: form?.querySelector('[data-promo-field="period_end"]'),
    };

    const setError = (name, message) => {
        const error = form?.querySelector(`[data-promo-error="${name}"]`);
        if (!error) return;
        error.textContent = message;
        error.hidden = !message;
    };

    const clearErrors = () => {
        form?.querySelectorAll('[data-promo-error]').forEach((error) => {
            error.textContent = '';
            error.hidden = true;
        });
    };

    const setEditorImage = (src) => {
        state.editorBaseImage = src || defaultPromoImage();
        state.editorImage = null;

        if (editorDesktop) editorDesktop.src = state.editorBaseImage;
        if (editorMobile) editorMobile.src = state.editorBaseImage;
        if (imageName) imageName.textContent = 'Belum ada file dipilih';
    };

    const promoCard = (promo, index) => `
        <article data-promo-card data-promo-id="${escapeHtml(promo.id)}"
                 class="overflow-hidden rounded-md border-2 border-ink bg-admin-panel">
            <div class="flex flex-wrap items-center justify-between gap-2 bg-[#303031] px-3 py-2 text-white">
                <span class="tag text-[8px] font-bold tracking-[0.08em]">Slide ${String(index + 1).padStart(2, '0')}</span>
                <button type="button" data-promo-toggle
                        class="tag inline-flex items-center gap-1.5 rounded border px-2 py-1 text-[7px] font-bold transition-colors ${
                            promo.active
                                ? 'border-varya-light/30 bg-white/10 text-varya-light hover:bg-white/20'
                                : 'border-white/20 bg-white/5 text-white/60 hover:bg-white/10'
                        }">
                    <span class="h-1.5 w-1.5 rounded-full ${promo.active ? 'bg-[#7fc08d]' : 'bg-white/40'}"></span>
                    ${promo.active ? 'Status: Tampil (Slide Aktif)' : 'Status: Nonaktif'}
                </button>
            </div>

            <div class="p-3">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-display text-[15px] leading-tight font-extrabold">${escapeHtml(promo.title)}</p>
                        <p class="mt-1 line-clamp-2 font-serif text-[10px] leading-relaxed text-ink-soft">${escapeHtml(promo.description)}</p>
                    </div>
                    <span class="tag shrink-0 rounded border border-line-strong bg-admin-soft px-2 py-1 text-[7px] font-bold text-ink-soft">Urutan #${escapeHtml(promo.order)}</span>
                </div>

                <p class="tag mt-2 text-[7px] text-muted">Periode: ${escapeHtml(formatPeriod(promo.period_start, promo.period_end))}</p>

                <div class="mt-3 grid grid-cols-2 gap-2">
                    <figure>
                        <div class="flex items-center justify-between gap-1 border-2 border-b-0 border-ink bg-[#303031] px-2 py-1 text-white">
                            <span class="tag text-[6px] font-bold">Desktop</span>
                            <span class="tag rounded bg-varya-dark px-1 py-0.5 text-[6px] font-bold">16:9</span>
                        </div>
                        <div class="admin-preview-frame aspect-video rounded-b-md">
                            <img src="${escapeHtml(promo.image)}" alt="Banner desktop ${escapeHtml(promo.title)}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                    </figure>

                    <figure>
                        <div class="flex items-center justify-between gap-1 border-2 border-b-0 border-ink bg-[#303031] px-2 py-1 text-white">
                            <span class="tag text-[6px] font-bold">Mobile</span>
                            <span class="tag rounded bg-varya-light px-1 py-0.5 text-[6px] font-bold text-ink">1:1</span>
                        </div>
                        <div class="admin-preview-frame aspect-square rounded-b-md">
                            <img src="${escapeHtml(promo.image)}" alt="Banner mobile ${escapeHtml(promo.title)}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                    </figure>
                </div>

                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    <button type="button" data-promo-edit
                            class="tag inline-flex items-center gap-1.5 rounded border-2 border-ink bg-admin-panel px-2.5 py-1.5 text-[8px] font-bold transition-colors hover:bg-varya-light">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m4 20 4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20Z"/><path d="m13.5 7.5 3 3"/>
                        </svg>
                        Ubah
                    </button>

                    <button type="button" data-promo-delete
                            class="tag inline-flex items-center gap-1.5 rounded border-2 border-ink bg-admin-panel px-2.5 py-1.5 text-[8px] font-bold transition-colors hover:bg-[#fbe1de] hover:text-rust">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 11v5M14 11v5"/>
                        </svg>
                        Hapus
                    </button>
                </div>
            </div>
        </article>
    `;

    const render = () => {
        const promos = loadPromos().slice().sort((a, b) => Number(a.order) - Number(b.order));

        if (list) {
            list.innerHTML = promos.length > 0
                ? promos.map(promoCard).join('')
                : '<p class="rounded-md border-2 border-dashed border-line-strong bg-admin-soft px-4 py-8 text-center font-serif text-sm text-muted">Belum ada promosi.</p>';
        }

        if (empty) empty.hidden = promos.length > 0;
    };

    const fillEditor = (promo) => {
        state.editingId = promo?.id ?? null;
        setEditorImage(promo?.image ?? defaultPromoImage());

        if (fields.title) fields.title.value = promo?.title ?? '';
        if (fields.description) fields.description.value = promo?.description ?? '';
        if (fields.order) fields.order.value = promo?.order ?? (loadPromos().length + 1);
        if (fields.active) fields.active.checked = promo?.active ?? true;
        if (fields.periodStart) fields.periodStart.value = promo?.period_start ?? '';
        if (fields.periodEnd) fields.periodEnd.value = promo?.period_end ?? '';

        if (editorTitle) editorTitle.textContent = promo ? 'Edit Promosi' : 'Editor Promosi Baru';
        if (cancelEdit) cancelEdit.hidden = !promo;
        if (imageName) imageName.textContent = 'Belum ada file dipilih';

        clearErrors();
    };

    const handleEditorImage = (file) => {
        setError('image', '');

        readImageFile(
            file,
            3 * 1024 * 1024,
            (dataUrl, name) => {
                state.editorImage = dataUrl;
                if (editorDesktop) editorDesktop.src = dataUrl;
                if (editorMobile) editorMobile.src = dataUrl;
                if (imageName) imageName.textContent = name;
            },
            (message) => setError('image', message)
        );
    };

    imageInput?.addEventListener('change', () => {
        handleEditorImage(imageInput.files?.[0]);
        imageInput.value = '';
    });

    dropzone?.addEventListener('click', (event) => {
        if (event.target.closest('label')) return;
        imageInput?.click();
    });

    dropzone?.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropzone.classList.add('border-varya-dark', 'bg-varya-light/40');
    });

    dropzone?.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-varya-dark', 'bg-varya-light/40');
    });

    dropzone?.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('border-varya-dark', 'bg-varya-light/40');
        handleEditorImage(event.dataTransfer?.files?.[0]);
    });

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        clearErrors();

        const title = fields.title?.value.trim() ?? '';
        const order = Number(fields.order?.value);
        const start = fields.periodStart?.value ?? '';
        const end = fields.periodEnd?.value ?? '';

        let valid = true;

        if (!title) {
            setError('title', 'Judul promosi wajib diisi.');
            valid = false;
        }

        if (!Number.isFinite(order) || order <= 0 || !Number.isInteger(order)) {
            setError('order', 'Urutan harus berupa angka bulat positif.');
            valid = false;
        }

        if (start && end && end < start) {
            setError('period', 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.');
            valid = false;
        }

        if (!valid) return;

        const promos = loadPromos();
        const image = state.editorImage ?? state.editorBaseImage ?? defaultPromoImage();

        if (state.editingId) {
            const promo = promos.find((item) => item.id === state.editingId);
            if (promo) {
                Object.assign(promo, {
                    title,
                    description: fields.description?.value.trim() ?? '',
                    order,
                    active: Boolean(fields.active?.checked),
                    period_start: start || null,
                    period_end: end || null,
                    image,
                });
            }
        } else {
            const nextId = promos.reduce((max, promo) => Math.max(max, Number(promo.id) || 0), 0) + 1;
            promos.push({
                id: nextId,
                title,
                description: fields.description?.value.trim() ?? '',
                order,
                active: Boolean(fields.active?.checked),
                period_start: start || null,
                period_end: end || null,
                image,
            });
        }

        savePromos(promos);
        render();
        fillEditor(null);
        showToast(`Promosi "${title}" berhasil disimpan (demo).`);
    });

    const resetEditor = () => {
        fillEditor(null);
        if (window.innerWidth < 1280) {
            form?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    newButton?.addEventListener('click', resetEditor);
    resetButton?.addEventListener('click', resetEditor);
    cancelEdit?.addEventListener('click', resetEditor);

    list?.addEventListener('click', (event) => {
        const card = event.target.closest('[data-promo-card]');
        if (!card) return;

        const id = Number(card.dataset.promoId);
        const promos = loadPromos();
        const promo = promos.find((item) => item.id === id);
        if (!promo) return;

        if (event.target.closest('[data-promo-toggle]')) {
            promo.active = !promo.active;
            savePromos(promos);
            render();
            showToast(`Promosi "${promo.title}" ${promo.active ? 'diaktifkan' : 'dinonaktifkan'}.`);
            return;
        }

        if (event.target.closest('[data-promo-edit]')) {
            fillEditor(promo);
            if (window.innerWidth < 1280) {
                form?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            return;
        }

        if (event.target.closest('[data-promo-delete]')) {
            askConfirm({
                title: 'Hapus promosi ini?',
                message: `Promosi "${promo.title}" akan dihapus dari slider landing page. Tindakan ini hanya berlaku pada demo front-end.`,
                label: 'Hapus Promosi',
                onConfirm: () => {
                    const remaining = loadPromos().filter((item) => item.id !== id);
                    savePromos(remaining);
                    if (state.editingId === id) fillEditor(null);
                    render();
                    showToast(`Promosi "${promo.title}" berhasil dihapus (demo).`);
                },
            });
        }
    });

    /* Foto tentang kami */
    const setAboutImage = (src, statusLabel = 'Tampil') => {
        state.aboutImage = src || EMPTY_IMAGE;

        if (aboutDesktop) aboutDesktop.src = state.aboutImage;
        if (aboutMobile) aboutMobile.src = state.aboutImage;
        if (aboutStatus) aboutStatus.textContent = statusLabel;
    };

    const openAboutDialog = () => {
        if (!aboutDialog) return;

        state.aboutDialogImage = null;
        state.aboutDialogBaseImage = state.aboutImage;
        if (aboutDialogDesktop) aboutDialogDesktop.src = state.aboutImage;
        if (aboutDialogMobile) aboutDialogMobile.src = state.aboutImage;
        if (aboutImageName) aboutImageName.textContent = 'Belum ada file dipilih';
        if (aboutError) {
            aboutError.textContent = '';
            aboutError.hidden = true;
        }

        openDialog(aboutDialog);
    };

    const handleAboutImage = (file) => {
        if (aboutError) {
            aboutError.textContent = '';
            aboutError.hidden = true;
        }

        readImageFile(
            file,
            3 * 1024 * 1024,
            (dataUrl, name) => {
                state.aboutDialogImage = dataUrl;
                if (aboutDialogDesktop) aboutDialogDesktop.src = dataUrl;
                if (aboutDialogMobile) aboutDialogMobile.src = dataUrl;
                if (aboutImageName) aboutImageName.textContent = name;
            },
            (message) => {
                if (!aboutError) return;
                aboutError.textContent = message;
                aboutError.hidden = false;
            }
        );
    };

    aboutEdit?.addEventListener('click', openAboutDialog);
    aboutDialog?.querySelectorAll('[data-about-close]').forEach((button) => {
        button.addEventListener('click', () => closeDialog(aboutDialog));
    });
    enableBackdropClose(aboutDialog);

    aboutImageInput?.addEventListener('change', () => {
        handleAboutImage(aboutImageInput.files?.[0]);
        aboutImageInput.value = '';
    });

    aboutDropzone?.addEventListener('click', (event) => {
        if (event.target.closest('label')) return;
        aboutImageInput?.click();
    });

    aboutDropzone?.addEventListener('dragover', (event) => {
        event.preventDefault();
        aboutDropzone.classList.add('border-varya-dark', 'bg-varya-light/40');
    });

    aboutDropzone?.addEventListener('dragleave', () => {
        aboutDropzone.classList.remove('border-varya-dark', 'bg-varya-light/40');
    });

    aboutDropzone?.addEventListener('drop', (event) => {
        event.preventDefault();
        aboutDropzone.classList.remove('border-varya-dark', 'bg-varya-light/40');
        handleAboutImage(event.dataTransfer?.files?.[0]);
    });

    aboutDialog?.querySelector('[data-about-save]')?.addEventListener('click', () => {
        const image = state.aboutDialogImage ?? state.aboutDialogBaseImage ?? EMPTY_IMAGE;
        setAboutImage(image, 'Tampil');
        closeDialog(aboutDialog);
        showToast('Foto tentang kami berhasil diperbarui (demo).');
    });

    aboutDelete?.addEventListener('click', () => {
        askConfirm({
            title: 'Hapus foto tentang kami?',
            message: 'Foto akan dihapus dari pratinjau halaman tentang. Kamu dapat mengunggah foto baru kapan saja.',
            label: 'Hapus Foto',
            onConfirm: () => {
                setAboutImage(EMPTY_IMAGE, 'Tidak Tampil');
                showToast('Foto tentang kami dihapus dari pratinjau (demo).');
            },
        });
    });

    render();
    fillEditor(null);
}

/* -------------------------------------------------------------------------- */
/* Informasi toko                                                             */
/* -------------------------------------------------------------------------- */

function initStore() {
    const root = document.querySelector('[data-admin-store]');
    if (!root) return;

    const form = root.querySelector('[data-store-form]');
    if (!form) return;

    const descriptionCount = root.querySelector('[data-store-description-count]');
    const lastUpdated = root.querySelector('[data-store-last-updated]');
    const previewDescription = root.querySelector('[data-store-preview-description]');
    const previewPoints = root.querySelector('[data-store-preview-points]');
    const previewAddress = root.querySelector('[data-store-preview-address]');
    const previewLandmark = root.querySelector('[data-store-preview-landmark]');
    const previewHours = root.querySelector('[data-store-preview-hours]');
    const previewContact = root.querySelector('[data-store-preview-contact]');
    const previewFacilities = root.querySelector('[data-store-preview-facilities]');
    const todayHoursSummary = root.querySelector('[data-store-today-hours]');
    const previewMapLink = root.querySelector('[data-store-preview-map-link]');
    const previewMapName = root.querySelector('[data-store-preview-map-name]');
    const mapLink = root.querySelector('[data-store-map-link]');
    const facilitiesPreview = root.querySelector('[data-store-facilities-preview]');

    const dayKeys = ['minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];

    const loadStore = () => {
        const base = clone(CONFIG.store ?? {});
        const saved = readStorage(STORAGE_KEYS.store, null);

        if (!saved || typeof saved !== 'object') return base;

        return {
            ...base,
            ...saved,
            points: Array.isArray(saved.points) ? saved.points : base.points,
            hours: Array.isArray(saved.hours) ? saved.hours : base.hours,
            facilities: Array.isArray(saved.facilities) ? saved.facilities : base.facilities,
        };
    };

    const field = (name) => form.querySelector(`[data-store-field="${name}"]`);

    const setField = (name, value) => {
        const input = field(name);
        if (input) input.value = value ?? '';
    };

    const readPoints = () => [0, 1, 2].map((index) => ({
        value: form.querySelector(`[data-store-point-value="${index}"]`)?.value.trim() ?? '',
        label: form.querySelector(`[data-store-point-label="${index}"]`)?.value.trim() ?? '',
    }));

    const readHours = () => Array.from(form.querySelectorAll('[data-store-hour-row]')).map((row) => ({
        key: row.dataset.dayKey,
        day: row.dataset.dayLabel,
        open: row.querySelector('[data-hour-open]')?.value ?? '00:00',
        close: row.querySelector('[data-hour-close]')?.value ?? '00:00',
        closed: Boolean(row.querySelector('[data-hour-closed]')?.checked),
    }));

    const readFacilities = () => (field('facilities')?.value ?? '')
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean);

    const readForm = () => {
        const instagramRaw = field('instagram')?.value.trim() ?? '';

        return {
            name: field('name')?.value.trim() ?? '',
            tagline: field('tagline')?.value.trim() ?? '',
            email: field('email')?.value.trim() ?? '',
            description: field('description')?.value.trim() ?? '',
            address: field('address')?.value.trim() ?? '',
            maps_url: field('maps_url')?.value.trim() ?? '',
            landmark: field('landmark')?.value.trim() ?? '',
            phone: field('phone')?.value.trim() ?? '',
            instagram: instagramRaw ? `@${instagramRaw.replace(/^@+/, '')}` : '',
            points: readPoints(),
            hours: readHours(),
            facilities: readFacilities(),
            last_updated: lastUpdated?.textContent ?? '',
        };
    };

    const updateDescriptionCount = () => {
        if (descriptionCount) descriptionCount.textContent = String(field('description')?.value.length ?? 0);
    };

    const renderFacilities = (container, facilities) => {
        if (!container) return;

        container.innerHTML = facilities.length > 0
            ? facilities.map((facility) => `<span class="tag rounded border border-line-strong bg-white px-2 py-1 text-[8px] text-ink-soft">${escapeHtml(facility)}</span>`).join('')
            : '<span class="font-serif text-[9px] text-muted">Belum ada fasilitas.</span>';
    };

    const updatePreview = () => {
        const data = readForm();
        const todayKey = dayKeys[new Date().getDay()];
        const today = data.hours.find((hour) => hour.key === todayKey) ?? data.hours[0];

        if (previewDescription) previewDescription.textContent = data.description || 'Deskripsi tentang kami akan tampil di sini.';
        if (previewAddress) previewAddress.textContent = data.address || 'Alamat kedai akan tampil di sini.';
        if (previewLandmark) previewLandmark.textContent = data.landmark || '';
        if (previewContact) previewContact.textContent = `${data.phone || 'Nomor telepon'} • ${data.instagram || '@instagram'}`;
        if (previewMapName) previewMapName.textContent = data.name || 'Kedai Varya';

        if (previewHours) {
            previewHours.textContent = today
                ? `${today.day}: ${today.closed ? 'Tutup' : `${today.open} – ${today.close} WIB`}`
                : 'Jam operasional belum diatur';
        }

        if (todayHoursSummary) {
            todayHoursSummary.textContent = today
                ? `Hari Ini: ${today.closed ? 'Tutup' : `${today.open} – ${today.close} WIB (Buka)`}`
                : 'Hari Ini: Jam operasional belum diatur';
        }

        if (previewPoints) {
            previewPoints.innerHTML = data.points.map((point) => `
                <div class="rounded border-2 border-line-strong bg-admin-soft px-2 py-2 text-center">
                    <p class="font-display text-[15px] leading-none font-black">${escapeHtml(point.value || '—')}</p>
                    <p class="tag mt-1 text-[6px] leading-tight text-muted">${escapeHtml(point.label || 'Label')}</p>
                </div>
            `).join('');
        }

        renderFacilities(previewFacilities, data.facilities);
        renderFacilities(facilitiesPreview, data.facilities);

        const mapsUrl = data.maps_url || '#';
        if (previewMapLink) previewMapLink.href = mapsUrl;
        if (mapLink) mapLink.href = mapsUrl;

        updateDescriptionCount();
    };

    const applyStore = (store) => {
        setField('name', store.name);
        setField('tagline', store.tagline);
        setField('email', store.email);
        setField('description', store.description);
        setField('address', store.address);
        setField('maps_url', store.maps_url);
        setField('landmark', store.landmark);
        setField('phone', store.phone);
        setField('instagram', (store.instagram ?? '').replace(/^@+/, ''));
        setField('facilities', (store.facilities ?? []).join(', '));

        (store.points ?? []).forEach((point, index) => {
            const valueInput = form.querySelector(`[data-store-point-value="${index}"]`);
            const labelInput = form.querySelector(`[data-store-point-label="${index}"]`);
            if (valueInput) valueInput.value = point.value ?? '';
            if (labelInput) labelInput.value = point.label ?? '';
        });

        (store.hours ?? []).forEach((hour) => {
            const row = form.querySelector(`[data-store-hour-row][data-day-key="${hour.key}"]`);
            if (!row) return;

            const open = row.querySelector('[data-hour-open]');
            const close = row.querySelector('[data-hour-close]');
            const closed = row.querySelector('[data-hour-closed]');

            if (open) open.value = hour.open ?? '00:00';
            if (close) close.value = hour.close ?? '00:00';
            if (closed) closed.checked = Boolean(hour.closed);
        });

        if (lastUpdated) lastUpdated.textContent = store.last_updated ?? lastUpdated.textContent;
        updatePreview();
    };

    const setError = (name, message) => {
        const error = form.querySelector(`[data-store-error-for="${name}"]`);
        if (!error) return;
        error.textContent = message;
        error.hidden = !message;
    };

    const clearErrors = () => {
        form.querySelectorAll('[data-store-error-for]').forEach((error) => {
            error.textContent = '';
            error.hidden = true;
        });
    };

    form.querySelectorAll('input, textarea, select').forEach((input) => {
        input.addEventListener('input', updatePreview);
        input.addEventListener('change', updatePreview);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        clearErrors();

        const data = readForm();
        let valid = true;

        if (!data.name) {
            setError('name', 'Nama kedai wajib diisi.');
            valid = false;
        }

        if (!data.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
            setError('email', 'Masukkan alamat email yang valid.');
            valid = false;
        }

        if (!data.description) {
            setError('description', 'Deskripsi tentang kami wajib diisi.');
            valid = false;
        }

        if (!data.address) {
            setError('address', 'Alamat lengkap kedai wajib diisi.');
            valid = false;
        }

        if (!data.phone) {
            setError('phone', 'Nomor telepon / WhatsApp wajib diisi.');
            valid = false;
        }

        if (!valid) {
            form.querySelector('[data-store-error-for]:not([hidden])')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        const now = new Date();
        const time = new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            timeZone: 'Asia/Jakarta',
        })
            .format(now)
            .replaceAll('.', ':');

        data.last_updated = `Hari ini, ${time} WIB`;

        writeStorage(STORAGE_KEYS.store, data);
        if (lastUpdated) lastUpdated.textContent = data.last_updated;
        updatePreview();
        showToast('Informasi toko berhasil disimpan (demo).');
    });

    root.querySelector('[data-store-cancel]')?.addEventListener('click', () => {
        applyStore(loadStore());
        clearErrors();
        showToast('Perubahan dibatalkan.');
    });

    applyStore(loadStore());
}

/* -------------------------------------------------------------------------- */
/* Inisialisasi                                                               */
/* -------------------------------------------------------------------------- */

function init() {
    initConfirmDialog();
    initShell();
    initDashboard();
    initMenuList();
    initMenuForm();
    initReports();
    initPromotions();
    initStore();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
