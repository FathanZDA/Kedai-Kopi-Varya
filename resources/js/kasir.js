// Interaksi front-end area kasir Kedai Kopi Varya.
// Belum ada pemanggilan API/database; data contoh berasal dari halaman Blade
// dan penyimpanan sementara browser (localStorage) untuk kebutuhan demo UI.

const STORAGE_KEYS = {
    orders: 'varya.kasir.orders.v1',
    menuAvailability: 'varya.kasir.menu-availability.v1',
};

const PAGE_SIZE = 6;
const numberFormatter = new Intl.NumberFormat('id-ID');

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

function hasOwn(object, key) {
    return Object.prototype.hasOwnProperty.call(object, key);
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
    }, 3200);
}

function formatDateTime(date = new Date()) {
    const dateText = new Intl.DateTimeFormat('id-ID', {
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

function randomOrderCode() {
    const characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let code = '';

    for (let index = 0; index < 8; index += 1) {
        code += characters[Math.floor(Math.random() * characters.length)];
    }

    return code;
}

function renderPagination(container, totalPages, currentPage, onSelect) {
    if (!container) return;

    container.replaceChildren();

    const makeButton = (label, page, { disabled = false, active = false } = {}) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = label;
        button.disabled = disabled;

        button.className = [
            'tag inline-flex h-8 min-w-8 items-center justify-center rounded border-2 border-ink px-2 text-[8px] font-bold shadow-[1px_1px_0_0_#1b1c1d] transition-colors',
            active
                ? 'bg-varya-dark text-white'
                : 'bg-kasir-panel text-ink hover:bg-varya-light',
            disabled ? 'cursor-not-allowed opacity-40' : '',
        ]
            .filter(Boolean)
            .join(' ');

        if (active) button.setAttribute('aria-current', 'page');

        if (!disabled && !active) {
            button.addEventListener('click', () => onSelect(page));
        }

        container.appendChild(button);
    };

    makeButton('Sebelumnya', currentPage - 1, { disabled: currentPage <= 1 });

    for (let page = 1; page <= totalPages; page += 1) {
        makeButton(String(page), page, { active: page === currentPage });
    }

    makeButton('Berikutnya', currentPage + 1, { disabled: currentPage >= totalPages });
}

/* -------------------------------------------------------------------------- */
/* Metadata tampilan                                                          */
/* -------------------------------------------------------------------------- */

function statusMeta(status) {
    const map = {
        menunggu_pembayaran: {
            label: 'Belum Dibayar',
            pill: 'border-[#b58a2b]/40 bg-kasir-amber-soft text-[#8a6415]',
            dot: 'bg-[#c79225]',
        },
        antre: {
            label: 'Antrean',
            pill: 'border-varya/30 bg-varya-light text-varya-dark',
            dot: 'bg-varya',
        },
        diproses: {
            label: 'Diproses',
            pill: 'border-[#2e6b47]/30 bg-[#e3eef8] text-[#245a83]',
            dot: 'bg-[#2f6d9e]',
        },
        selesai: {
            label: 'Selesai',
            pill: 'border-line-strong bg-[#e9e7de] text-ink-soft',
            dot: 'bg-muted',
        },
        dibatalkan: {
            label: 'Dibatalkan',
            pill: 'border-line-strong bg-[#e9e7de] text-muted',
            dot: 'bg-muted',
        },
    };

    return map[status] ?? map.menunggu_pembayaran;
}

function paymentMeta(status) {
    const map = {
        lunas: {
            label: 'Lunas / Verified',
            pill: 'border-varya/30 bg-varya-light text-varya-dark',
        },
        menunggu: {
            label: 'Menunggu',
            pill: 'border-[#b58a2b]/40 bg-kasir-amber-soft text-[#8a6415]',
        },
        perlu_diperiksa: {
            label: 'Perlu Diperiksa',
            pill: 'border-rust/30 bg-[#fbe1de] text-rust',
        },
        gagal: {
            label: 'Gagal',
            pill: 'border-rust/30 bg-[#fbe1de] text-rust',
        },
        kedaluwarsa: {
            label: 'Kedaluwarsa',
            pill: 'border-line-strong bg-[#e9e7de] text-muted',
        },
        dibatalkan: {
            label: 'Dibatalkan',
            pill: 'border-line-strong bg-[#e9e7de] text-muted',
        },
    };

    return map[status] ?? map.menunggu;
}

function typeMeta(type) {
    return type === 'take_away'
        ? {
              label: 'Take Away',
              pill: 'border-ink bg-kasir-amber-soft text-brown',
          }
        : {
              label: 'Dine In',
              pill: 'border-ink bg-ink text-white',
          };
}

function paymentIcon(payment) {
    if (payment === 'qris') {
        return `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="6" height="6"/><rect x="15" y="3" width="6" height="6"/><rect x="3" y="15" width="6" height="6"/><path d="M15 15h2v2h-2zM19 15h2v6h-2M15 19h2v2"/></svg>`;
    }

    return `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6 12h.01M18 12h.01"/></svg>`;
}

/* -------------------------------------------------------------------------- */
/* Sidebar & jam                                                              */
/* -------------------------------------------------------------------------- */

function initSidebar() {
    const sidebar = document.querySelector('[data-kasir-sidebar]');
    const backdrop = document.querySelector('[data-kasir-sidebar-backdrop]');
    const openButton = document.querySelector('[data-kasir-sidebar-open]');
    const closeButton = document.querySelector('[data-kasir-sidebar-close]');

    if (!sidebar || !backdrop) return;

    const open = () => {
        sidebar.classList.remove('-translate-x-full');
        backdrop.hidden = false;
        document.body.classList.add('overflow-hidden');
    };

    const close = () => {
        sidebar.classList.add('-translate-x-full');
        backdrop.hidden = true;
        document.body.classList.remove('overflow-hidden');
    };

    openButton?.addEventListener('click', open);
    closeButton?.addEventListener('click', close);
    backdrop.addEventListener('click', close);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') close();
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) close();
    });
}

function initClock() {
    const clock = document.querySelector('[data-kasir-clock]');
    if (!clock) return;

    const tick = () => {
        const now = new Date();

        const day = new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            timeZone: 'Asia/Jakarta',
        }).format(now);

        const time = new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
            timeZone: 'Asia/Jakarta',
        })
            .format(now)
            .replaceAll('.', ':');

        clock.textContent = `${day} • ${time} WIB`;
    };

    tick();
    window.setInterval(tick, 30000);
}

/* -------------------------------------------------------------------------- */
/* Status ketersediaan menu                                                   */
/* -------------------------------------------------------------------------- */

function getAvailabilityOverrides() {
    return readStorage(STORAGE_KEYS.menuAvailability, {}) ?? {};
}

function saveAvailabilityOverride(menuId, available) {
    const overrides = getAvailabilityOverrides();
    overrides[String(menuId)] = Boolean(available);
    writeStorage(STORAGE_KEYS.menuAvailability, overrides);
}

function setMenuCardAvailability(card, available) {
    const button = card.querySelector('[data-add-to-cart]');
    const badge = card.querySelector('[data-menu-unavailable-badge]');
    const image = card.querySelector('img');

    card.dataset.menuAvailable = available ? '1' : '0';
    card.classList.toggle('opacity-75', !available);
    image?.classList.toggle('grayscale', !available);

    if (badge) {
        badge.classList.toggle('hidden', available);
        badge.hidden = available;
    }

    if (button) {
        button.disabled = !available;
    }
}

function setMenuRowAvailability(row, available) {
    row.dataset.available = available ? '1' : '0';

    const thumbnail = row.querySelector('[data-menu-thumb]');
    const unavailableBadge = row.querySelector('[data-row-unavailable]');
    const status = row.querySelector('[data-menu-status]');
    const dot = row.querySelector('[data-menu-status-dot]');
    const label = row.querySelector('[data-menu-status-label]');
    const button = row.querySelector('[data-toggle-menu]');

    thumbnail?.classList.toggle('grayscale', !available);

    if (unavailableBadge) {
        unavailableBadge.classList.toggle('hidden', available);
        unavailableBadge.hidden = available;
    }

    if (status) {
        status.classList.toggle('border-varya/30', available);
        status.classList.toggle('bg-varya-light', available);
        status.classList.toggle('text-varya-dark', available);
        status.classList.toggle('border-rust/30', !available);
        status.classList.toggle('bg-[#fbe1de]', !available);
        status.classList.toggle('text-rust', !available);
    }

    dot?.classList.toggle('bg-varya', available);
    dot?.classList.toggle('bg-rust', !available);

    if (label) label.textContent = available ? 'Tersedia' : 'Habis';

    if (button) {
        button.textContent = available ? 'Set Habis' : 'Set Tersedia';
        button.classList.toggle('bg-kasir-panel', available);
        button.classList.toggle('text-rust', available);
        button.classList.toggle('bg-varya-dark', !available);
        button.classList.toggle('text-white', !available);
    }
}

/* -------------------------------------------------------------------------- */
/* Halaman Buat Pesanan                                                       */
/* -------------------------------------------------------------------------- */

function initBuatPesanan() {
    const root = document.querySelector('[data-kasir-pesanan-baru]');
    if (!root) return;

    const config = window.KASIR_CONFIG ?? {};
    const cards = Array.from(root.querySelectorAll('[data-menu-card]'));
    const cart = new Map();

    let activeCategory = 'all';
    let currentNoteId = null;
    let paymentTotal = 0;
    let paymentCash = 0;
    const storedOrders = readStorage(STORAGE_KEYS.orders, []);
    let queueCounter = Math.max(
        Number(config.nextQueue) || 1,
        ...(Array.isArray(storedOrders) ? storedOrders : []).map(
            (order) => (Number(order.queue) || 0) + 1,
        ),
    );

    const menuGrid = root.querySelector('[data-menu-grid]');
    const menuEmpty = root.querySelector('[data-menu-empty]');
    const searchInput = root.querySelector('[data-menu-search-input]');
    const filterButtons = Array.from(root.querySelectorAll('[data-menu-filter]'));

    const cartItems = root.querySelector('[data-cart-items]');
    const cartEmpty = root.querySelector('[data-cart-empty]');
    const cartCount = root.querySelector('[data-cart-count]');
    const cartSubtotal = root.querySelector('[data-cart-subtotal]');
    const cartTotal = root.querySelector('[data-cart-total]');
    const clearCartButton = root.querySelector('[data-clear-cart]');

    const customerName = root.querySelector('[data-customer-name]');
    const orderTypeInputs = Array.from(root.querySelectorAll('[data-order-type]'));
    const orderError = root.querySelector('[data-order-error]');
    const openPaymentButton = root.querySelector('[data-open-payment]');

    const noteDialog = root.querySelector('[data-note-dialog]');
    const noteForm = root.querySelector('[data-note-form]');
    const noteItemName = root.querySelector('[data-note-item-name]');
    const noteItemPrice = root.querySelector('[data-note-item-price]');
    const noteTextarea = root.querySelector('[data-note-textarea]');
    const noteCount = root.querySelector('[data-note-count]');
    const noteTotal = root.querySelector('[data-note-total]');

    const paymentDialog = root.querySelector('[data-payment-dialog]');
    const paymentTotalLabel = root.querySelector('[data-payment-total]');
    const paymentCashLabel = root.querySelector('[data-payment-cash]');
    const paymentChangeLabel = root.querySelector('[data-payment-change]');
    const paymentError = root.querySelector('[data-payment-error]');
    const paymentKeypad = root.querySelector('[data-payment-keypad]');
    const paymentConfirm = root.querySelector('[data-payment-confirm]');

    const successDialog = root.querySelector('[data-success-dialog]');
    const successQueue = root.querySelector('[data-success-queue]');
    const successType = root.querySelector('[data-success-type]');
    const successCustomer = root.querySelector('[data-success-customer]');
    const successTotal = root.querySelector('[data-success-total]');
    const successItems = root.querySelector('[data-success-items]');
    const successTime = root.querySelector('[data-success-time]');

    /* ------------------------- ketersediaan menu ------------------------- */

    const overrides = getAvailabilityOverrides();

    cards.forEach((card) => {
        if (hasOwn(overrides, card.dataset.menuId)) {
            setMenuCardAvailability(card, Boolean(overrides[card.dataset.menuId]));
        }
    });

    /* --------------------------- filter menu ----------------------------- */

    const markFilter = () => {
        filterButtons.forEach((button) => {
            const active = button.dataset.menuFilter === activeCategory;

            button.classList.toggle('bg-varya-dark', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('bg-kasir-panel', !active);
            button.classList.toggle('text-ink', !active);
            button.classList.toggle('hover:bg-varya-light/60', !active);
            button.setAttribute('aria-pressed', String(active));
        });
    };

    const applyMenuFilter = () => {
        const term = (searchInput?.value ?? '').trim().toLowerCase();
        let visible = 0;

        cards.forEach((card) => {
            const matchesCategory =
                activeCategory === 'all' || card.dataset.menuCategory === activeCategory;
            const matchesTerm = term === '' || card.dataset.menuSearch.includes(term);
            const show = matchesCategory && matchesTerm;

            card.hidden = !show;
            if (show) visible += 1;
        });

        if (menuEmpty) menuEmpty.hidden = visible > 0;
    };

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeCategory = button.dataset.menuFilter;
            markFilter();
            applyMenuFilter();
        });
    });

    searchInput?.addEventListener('input', applyMenuFilter);
    root.querySelector('[data-menu-search]')?.addEventListener('submit', (event) => {
        event.preventDefault();
        applyMenuFilter();
    });

    /* ----------------------------- keranjang ----------------------------- */

    const totalItems = () =>
        Array.from(cart.values()).reduce((total, item) => total + item.qty, 0);

    const subtotal = () =>
        Array.from(cart.values()).reduce(
            (total, item) => total + item.price * item.qty,
            0,
        );

    const hideOrderError = () => {
        if (orderError) orderError.hidden = true;
    };

    const showOrderError = (message) => {
        if (!orderError) return;

        orderError.textContent = message;
        orderError.hidden = false;
        orderError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    const cartItemMarkup = (item) => {
        const noteButton = item.note
            ? `<button type="button" data-cart-note class="mt-2 flex w-full items-start gap-1.5 rounded border border-dashed border-line-strong bg-white px-2 py-1.5 text-left font-serif text-[10px] leading-snug text-ink-soft transition-colors hover:border-ink">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0" aria-hidden="true"><path d="M4 4h16v13H9l-5 4V4Z"/><path d="M8 9h8M8 13h5"/></svg>
                    <span>${escapeHtml(item.note)}</span>
               </button>`
            : `<button type="button" data-cart-note class="mt-2 flex w-full items-center gap-1.5 rounded border border-dashed border-line-strong bg-white px-2 py-1.5 text-left font-serif text-[10px] text-muted transition-colors hover:border-ink hover:text-ink">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16v13H9l-5 4V4Z"/><path d="M8 9h8M8 13h5"/></svg>
                    Tambah catatan
               </button>`;

        return `<div class="rounded-md border-2 border-ink bg-[#f7f5ec] p-2.5" data-cart-item="${escapeHtml(item.id)}">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="font-display text-[12px] leading-tight font-extrabold">${escapeHtml(item.name)}</p>
                    <p class="mt-0.5 font-mono text-[8px] text-varya-dark">${rupiah(item.price)}</p>
                </div>
                <p class="shrink-0 font-display text-[12px] font-bold">${rupiah(item.price * item.qty)}</p>
            </div>

            ${noteButton}

            <div class="mt-2 flex items-center justify-between gap-2">
                <button type="button" data-cart-remove class="tag text-[7px] font-bold text-rust underline underline-offset-2 hover:text-ink">
                    Hapus
                </button>

                <div class="flex items-stretch overflow-hidden rounded border-2 border-ink bg-white shadow-[1px_1px_0_0_#1b1c1d]">
                    <button type="button" data-cart-minus class="grid w-7 place-items-center text-sm font-bold transition-colors hover:bg-varya-light" aria-label="Kurangi jumlah ${escapeHtml(item.name)}">−</button>
                    <span class="tag flex w-7 items-center justify-center text-[9px] font-bold">${item.qty}</span>
                    <button type="button" data-cart-plus class="grid w-7 place-items-center border-l-2 border-ink bg-varya-dark text-sm font-bold text-white transition-colors hover:bg-varya" aria-label="Tambah jumlah ${escapeHtml(item.name)}">+</button>
                </div>
            </div>
        </div>`;
    };

    const renderCart = () => {
        const items = Array.from(cart.values());
        const count = totalItems();
        const total = subtotal();

        if (cartItems) {
            cartItems.innerHTML = items.map(cartItemMarkup).join('');
        }

        if (cartEmpty) cartEmpty.hidden = items.length > 0;
        if (cartCount) cartCount.textContent = count;
        if (cartSubtotal) cartSubtotal.textContent = rupiah(total);
        if (cartTotal) cartTotal.textContent = rupiah(total);
    };

    const addToCart = (card) => {
        const id = card.dataset.menuId;
        const existing = cart.get(id);

        if (existing) {
            existing.qty += 1;
        } else {
            cart.set(id, {
                id,
                name: card.dataset.menuName,
                price: Number(card.dataset.menuPrice),
                qty: 1,
                note: '',
            });
        }

        hideOrderError();
        renderCart();
        showToast(`${card.dataset.menuName} ditambahkan ke pesanan.`);
    };

    menuGrid?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-add-to-cart]');
        if (!button) return;

        const card = button.closest('[data-menu-card]');
        if (!card || card.dataset.menuAvailable !== '1') {
            showToast('Menu ini sedang habis dan tidak dapat dipesan.');
            return;
        }

        addToCart(card);
    });

    cartItems?.addEventListener('click', (event) => {
        const itemElement = event.target.closest('[data-cart-item]');
        if (!itemElement) return;

        const id = itemElement.dataset.cartItem;
        const item = cart.get(id);
        if (!item) return;

        if (event.target.closest('[data-cart-plus]')) {
            item.qty += 1;
            renderCart();
            return;
        }

        if (event.target.closest('[data-cart-minus]')) {
            if (item.qty <= 1) {
                cart.delete(id);
            } else {
                item.qty -= 1;
            }

            renderCart();
            return;
        }

        if (event.target.closest('[data-cart-remove]')) {
            cart.delete(id);
            renderCart();
            showToast(`${item.name} dihapus dari pesanan.`);
            return;
        }

        if (event.target.closest('[data-cart-note]')) {
            openNoteDialog(id);
        }
    });

    clearCartButton?.addEventListener('click', () => {
        if (cart.size === 0) {
            showToast('Pesanan masih kosong.');
            return;
        }

        cart.clear();
        renderCart();
        hideOrderError();
        showToast('Semua item dihapus dari pesanan.');
    });

    customerName?.addEventListener('input', hideOrderError);

    /* ---------------------------- modal catatan -------------------------- */

    const updateNoteCounter = () => {
        if (noteCount) noteCount.textContent = noteTextarea?.value.length ?? 0;
    };

    function openNoteDialog(id) {
        const item = cart.get(id);
        if (!item) return;

        currentNoteId = id;

        if (noteItemName) noteItemName.textContent = item.name;
        if (noteItemPrice) noteItemPrice.textContent = rupiah(item.price);
        if (noteTotal) noteTotal.textContent = rupiah(item.price * item.qty);
        if (noteTextarea) noteTextarea.value = item.note ?? '';

        updateNoteCounter();
        openDialog(noteDialog);

        window.setTimeout(() => noteTextarea?.focus(), 80);
    }

    root.querySelectorAll('[data-note-close]').forEach((button) => {
        button.addEventListener('click', () => closeDialog(noteDialog));
    });

    noteTextarea?.addEventListener('input', updateNoteCounter);

    noteForm?.addEventListener('submit', (event) => {
        event.preventDefault();

        if (!currentNoteId) return;

        const item = cart.get(currentNoteId);
        if (item) {
            item.note = noteTextarea?.value.trim() ?? '';
            renderCart();
            showToast('Catatan item disimpan.');
        }

        closeDialog(noteDialog);
        currentNoteId = null;
    });

    /* -------------------------- modal pembayaran ------------------------- */

    const updatePaymentView = () => {
        if (paymentCashLabel) paymentCashLabel.textContent = angka(paymentCash);

        if (!paymentChangeLabel) return;

        if (paymentCash >= paymentTotal && paymentTotal > 0) {
            paymentChangeLabel.textContent = rupiah(paymentCash - paymentTotal);
            paymentChangeLabel.classList.remove('text-rust');
            paymentChangeLabel.classList.add('text-varya-dark');
        } else if (paymentCash > 0) {
            paymentChangeLabel.textContent = `− ${rupiah(paymentTotal - paymentCash)}`;
            paymentChangeLabel.classList.remove('text-varya-dark');
            paymentChangeLabel.classList.add('text-rust');
        } else {
            paymentChangeLabel.textContent = 'Rp 0';
            paymentChangeLabel.classList.remove('text-rust');
            paymentChangeLabel.classList.add('text-varya-dark');
        }
    };

    const appendCash = (digits) => {
        const current = paymentCash === 0 ? '' : String(paymentCash);
        const next = `${current}${digits}`;

        if (next.length > 9) return;

        paymentCash = Number(next) || 0;
        if (paymentError) paymentError.hidden = true;
        updatePaymentView();
    };

    const removeLastCashDigit = () => {
        paymentCash = Math.floor(paymentCash / 10);
        if (paymentError) paymentError.hidden = true;
        updatePaymentView();
    };

    openPaymentButton?.addEventListener('click', () => {
        const name = customerName?.value.trim() ?? '';

        if (cart.size === 0) {
            showOrderError('Tambahkan minimal satu menu sebelum memproses pembayaran.');
            return;
        }

        if (name === '') {
            showOrderError('Nama pelanggan wajib diisi sebelum memproses pembayaran.');
            customerName?.focus();
            return;
        }

        hideOrderError();

        paymentTotal = subtotal();
        paymentCash = 0;

        if (paymentTotalLabel) paymentTotalLabel.textContent = angka(paymentTotal);
        if (paymentError) paymentError.hidden = true;

        updatePaymentView();
        openDialog(paymentDialog);
    });

    paymentKeypad?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-payment-key]');
        if (!button) return;

        const key = button.dataset.paymentKey;

        if (key === 'C') {
            paymentCash = 0;
            if (paymentError) paymentError.hidden = true;
            updatePaymentView();
            return;
        }

        appendCash(key);
    });

    root.querySelector('[data-payment-reset]')?.addEventListener('click', () => {
        paymentCash = 0;
        if (paymentError) paymentError.hidden = true;
        updatePaymentView();
    });

    root.querySelector('[data-payment-backspace]')?.addEventListener('click', removeLastCashDigit);

    root.querySelectorAll('[data-payment-close]').forEach((button) => {
        button.addEventListener('click', () => closeDialog(paymentDialog));
    });

    paymentDialog?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') return;

        if (/^\d$/.test(event.key)) {
            event.preventDefault();
            appendCash(event.key);
            return;
        }

        if (event.key === 'Backspace') {
            event.preventDefault();
            removeLastCashDigit();
            return;
        }

        if (event.key === 'Enter' && !event.target.closest('button')) {
            event.preventDefault();
            paymentConfirm?.click();
        }
    });

    const saveOrder = () => {
        const typeInput = orderTypeInputs.find((input) => input.checked);
        const type = typeInput?.value ?? 'dine_in';
        const typeLabel = type === 'take_away' ? 'Take Away' : 'Dine In';
        const queue = String(queueCounter).padStart(3, '0');
        queueCounter += 1;

        const items = Array.from(cart.values()).map((item) => ({
            name: item.name,
            price: item.price,
            qty: item.qty,
            subtotal: item.price * item.qty,
            note: item.note || null,
        }));

        const order = {
            id: `KSR-${Date.now()}-${Math.floor(Math.random() * 1000)}`,
            code: randomOrderCode(),
            queue,
            customer: customerName?.value.trim() ?? '',
            type,
            type_label: typeLabel,
            source: 'kasir',
            status: 'antre',
            payment: 'tunai',
            payment_label: 'Tunai',
            payment_status: 'lunas',
            total: paymentTotal,
            created_at: formatDateTime(),
            midtrans_id: null,
            cash_received: paymentCash,
            change: paymentCash - paymentTotal,
            notes: null,
            items,
        };

        const savedOrders = readStorage(STORAGE_KEYS.orders, []);
        const list = Array.isArray(savedOrders) ? savedOrders : [];
        list.push(order);
        writeStorage(STORAGE_KEYS.orders, list.slice(-50));

        return order;
    };

    const resetForm = () => {
        cart.clear();
        renderCart();

        if (customerName) customerName.value = '';

        const dineIn = orderTypeInputs.find((input) => input.value === 'dine_in');
        if (dineIn) dineIn.checked = true;

        hideOrderError();
        paymentCash = 0;
    };

    const showSuccess = (order) => {
        const quantity = order.items.reduce((total, item) => total + item.qty, 0);

        if (successQueue) successQueue.textContent = `#${order.queue}`;

        if (successType) {
            successType.textContent = order.type_label;
            successType.className = `tag mt-1 inline-block rounded border-2 px-2.5 py-1 text-[8px] font-bold ${
                order.type === 'take_away'
                    ? 'border-ink bg-kasir-amber-soft text-brown'
                    : 'border-ink bg-ink text-white'
            }`;
        }

        if (successCustomer) successCustomer.textContent = order.customer;
        if (successTotal) successTotal.textContent = rupiah(order.total ?? paymentTotal);
        if (successItems) successItems.textContent = `${quantity} Menu`;
        if (successTime) successTime.textContent = order.created_at;

        closeDialog(paymentDialog);
        openDialog(successDialog);
    };

    paymentConfirm?.addEventListener('click', () => {
        if (paymentCash < paymentTotal) {
            if (paymentError) {
                paymentError.textContent =
                    paymentCash === 0
                        ? 'Masukkan uang yang diterima terlebih dahulu.'
                        : `Uang diterima kurang ${rupiah(paymentTotal - paymentCash)} dari total tagihan.`;
                paymentError.hidden = false;
            }

            return;
        }

        const order = saveOrder();
        resetForm();
        showSuccess(order);
    });

    root.querySelector('[data-success-close]')?.addEventListener('click', () => {
        closeDialog(successDialog);
    });

    /* ------------------------------- init -------------------------------- */

    markFilter();
    applyMenuFilter();
    renderCart();

    [noteDialog, paymentDialog, successDialog].forEach(enableBackdropClose);
}

/* -------------------------------------------------------------------------- */
/* Halaman Pesanan Masuk                                                      */
/* -------------------------------------------------------------------------- */

function initPesananMasuk() {
    const root = document.querySelector('[data-kasir-pesanan-masuk]');
    if (!root) return;

    const dataElement = root.querySelector('[data-orders-data]');
    let orders = [];

    try {
        orders = JSON.parse(dataElement?.textContent ?? '[]');
    } catch {
        orders = [];
    }

    const savedOrders = readStorage(STORAGE_KEYS.orders, []);
    if (Array.isArray(savedOrders)) {
        savedOrders.forEach((order) => {
            if (order?.id && !orders.some((existing) => existing.id === order.id)) {
                orders.push(order);
            }
        });
    }

    let activeTab = 'all';
    let searchTerm = '';
    let currentPage = 1;
    let currentDetailId = null;
    let pendingCancel = null;

    const tbody = root.querySelector('[data-orders-body]');
    const searchInput = root.querySelector('[data-order-search-input]');
    const record = root.querySelector('[data-order-record]');
    const pagination = root.querySelector('[data-order-pagination]');
    const tabButtons = Array.from(root.querySelectorAll('[data-order-tab]'));

    const detailDialog = root.querySelector('[data-order-detail-dialog]');
    const detailQueue = root.querySelector('[data-detail-queue]');
    const detailType = root.querySelector('[data-detail-type]');
    const detailCustomer = root.querySelector('[data-detail-customer]');
    const detailStatus = root.querySelector('[data-detail-status]');
    const detailTime = root.querySelector('[data-detail-time]');
    const detailItemCount = root.querySelector('[data-detail-item-count]');
    const detailItems = root.querySelector('[data-detail-items]');
    const detailNotesWrap = root.querySelector('[data-detail-notes-wrap]');
    const detailNotes = root.querySelector('[data-detail-notes]');
    const detailPayment = root.querySelector('[data-detail-payment]');
    const detailPaymentStatus = root.querySelector('[data-detail-payment-status]');
    const detailTotal = root.querySelector('[data-detail-total]');
    const detailCashWrap = root.querySelector('[data-detail-cash-wrap]');
    const detailCash = root.querySelector('[data-detail-cash]');
    const detailChange = root.querySelector('[data-detail-change]');
    const detailCode = root.querySelector('[data-detail-code]');
    const detailSource = root.querySelector('[data-detail-source]');
    const detailMidtransWrap = root.querySelector('[data-detail-midtrans-wrap]');
    const detailMidtrans = root.querySelector('[data-detail-midtrans]');
    const detailExpiresWrap = root.querySelector('[data-detail-expires-wrap]');
    const detailExpires = root.querySelector('[data-detail-expires]');
    const detailActions = root.querySelector('[data-detail-actions]');

    const confirmDialog = root.querySelector('[data-confirm-dialog]');
    const confirmMessage = root.querySelector('[data-confirm-message]');

    const statusRank = {
        menunggu_pembayaran: 0,
        antre: 1,
        diproses: 2,
        selesai: 3,
        dibatalkan: 4,
    };

    const orderQuantity = (order) =>
        (order.items ?? []).reduce((total, item) => total + Number(item.qty), 0);

    const persistSavedOrder = (order) => {
        if (!String(order.id).startsWith('KSR-')) return;

        const saved = readStorage(STORAGE_KEYS.orders, []);
        if (!Array.isArray(saved)) return;

        const index = saved.findIndex((item) => item.id === order.id);
        if (index >= 0) {
            saved[index] = order;
            writeStorage(STORAGE_KEYS.orders, saved);
        }
    };

    const removeSavedOrder = (id) => {
        const saved = readStorage(STORAGE_KEYS.orders, []);
        writeStorage(
            STORAGE_KEYS.orders,
            Array.isArray(saved) ? saved.filter((order) => order.id !== id) : [],
        );
    };

    const updateTabStyles = () => {
        tabButtons.forEach((button) => {
            const active = button.dataset.orderTab === activeTab;
            const chip = button.querySelector('[data-tab-count]');

            button.classList.toggle('bg-varya-dark', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('text-ink-soft', !active);
            button.classList.toggle('hover:bg-varya-light/50', !active);
            button.setAttribute('aria-selected', String(active));

            if (chip) {
                chip.classList.toggle('border-white/40', active);
                chip.classList.toggle('bg-white/15', active);
                chip.classList.toggle('text-white', active);
                chip.classList.toggle('border-line-strong', !active);
                chip.classList.toggle('bg-white', !active);
                chip.classList.toggle('text-ink-soft', !active);
            }
        });
    };

    const updateCounts = () => {
        const counts = {
            all: orders.length,
            menunggu_pembayaran: orders.filter((order) => order.status === 'menunggu_pembayaran').length,
            antre: orders.filter((order) => order.status === 'antre').length,
            diproses: orders.filter((order) => order.status === 'diproses').length,
            selesai: orders.filter((order) => order.status === 'selesai').length,
        };

        Object.entries(counts).forEach(([key, value]) => {
            const element = root.querySelector(`[data-tab-count="${key}"]`);
            if (element) element.textContent = value;
        });
    };

    const orderHaystack = (order) =>
        [
            order.customer,
            order.code,
            order.queue,
            order.type_label,
            order.payment_label,
            statusMeta(order.status).label,
            paymentMeta(order.payment_status).label,
            ...(order.items ?? []).map((item) => `${item.name} ${item.note ?? ''}`),
        ]
            .join(' ')
            .toLowerCase();

    const filteredOrders = () => {
        const filtered = orders.filter((order) => {
            const matchesTab = activeTab === 'all' || order.status === activeTab;
            const matchesSearch = searchTerm === '' || orderHaystack(order).includes(searchTerm);
            return matchesTab && matchesSearch;
        });

        return filtered.sort((a, b) => {
            const rankA = statusRank[a.status] ?? 9;
            const rankB = statusRank[b.status] ?? 9;
            const queueA = Number(a.queue) || 9999;
            const queueB = Number(b.queue) || 9999;

            return rankA - rankB || queueA - queueB;
        });
    };

    const actionButton = (action, label, style, order) => {
        const styles = {
            primary:
                'border-ink bg-varya-dark text-white shadow-[2px_2px_0_0_#1b1c1d] hover:bg-varya',
            secondary:
                'border-ink bg-kasir-panel text-ink shadow-[2px_2px_0_0_#1b1c1d] hover:bg-varya-light',
            danger:
                'border-rust bg-[#fbe9e2] text-rust shadow-[2px_2px_0_0_#1b1c1d] hover:bg-[#f7d7d3]',
        };

        return `<button type="button" data-order-action="${action}" data-order-id="${escapeHtml(order.id)}" class="tag rounded border-2 px-2.5 py-1.5 text-[7px] font-bold transition-transform hover:-translate-y-0.5 ${styles[style]}">${label}</button>`;
    };

    const rowMarkup = (order) => {
        const type = typeMeta(order.type);
        const status = statusMeta(order.status);
        const payment = paymentMeta(order.payment_status);
        const quantity = orderQuantity(order);
        const itemNames = (order.items ?? []).map((item) => item.name);
        const shownNames = itemNames.slice(0, 2).join(', ');
        const extraNames = itemNames.length > 2 ? ` +${itemNames.length - 2} lainnya` : '';
        const notes = order.notes ? ` • ${escapeHtml(order.notes)}` : '';
        const rowTint =
            order.payment_status === 'perlu_diperiksa'
                ? 'bg-[#fff4d8] border-l-4 border-[#c79225]'
                : order.status === 'menunggu_pembayaran'
                  ? 'bg-[#fffaf0] border-l-4 border-[#c79225]'
                  : 'bg-kasir-panel border-l-4 border-transparent';

        let actions = `<button type="button" data-order-action="detail" data-order-id="${escapeHtml(order.id)}" class="tag rounded border-2 border-ink bg-kasir-panel px-2.5 py-1.5 text-[7px] font-bold shadow-[2px_2px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5 hover:bg-varya-light">Detail</button>`;

        if (order.status === 'antre') {
            actions += actionButton('process', 'Mulai Proses', 'primary', order);
        } else if (order.status === 'diproses') {
            actions += actionButton('finish', 'Selesai', 'primary', order);
        } else if (order.status === 'menunggu_pembayaran') {
            actions += actionButton('check', 'Cek Status', 'secondary', order);

            if (order.payment_status === 'menunggu') {
                actions += actionButton('cancel', 'Batalkan', 'danger', order);
            }
        } else if (order.status === 'selesai') {
            actions += `<span class="tag inline-flex items-center gap-1 rounded border-2 border-line-strong bg-[#e9e7de] px-2 py-1.5 text-[7px] font-bold text-muted">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                Selesai
            </span>`;
        }

        return `<tr class="${rowTint} align-top transition-colors hover:bg-[#f8f6ee]" data-order-row data-order-id="${escapeHtml(order.id)}">
            <td class="px-4 py-4">
                <span class="tag inline-flex min-w-10 items-center justify-center rounded border-2 border-ink bg-varya-light px-2 py-1 text-[9px] font-bold text-varya-dark">
                    ${order.queue ? `#${escapeHtml(order.queue)}` : '—'}
                </span>
                <p class="tag mt-1 text-[7px] text-muted">${escapeHtml(order.queue ? order.code : 'Belum dibayar')}</p>
            </td>

            <td class="px-3 py-4">
                <span class="tag inline-flex items-center gap-1.5 rounded border-2 px-2 py-1 text-[8px] font-bold ${status.pill}">
                    <span class="h-1.5 w-1.5 rounded-full ${status.dot}" aria-hidden="true"></span>
                    ${status.label}
                </span>
                <span class="tag mt-1.5 inline-block rounded border-2 px-2 py-0.5 text-[7px] font-bold ${type.pill}">
                    ${type.label}
                </span>
            </td>

            <td class="px-3 py-4">
                <p class="font-display text-[13px] leading-tight font-extrabold">${escapeHtml(order.customer)}</p>
                <p class="tag mt-1 text-[7px] text-muted">${order.source === 'qr' ? 'QR Pelanggan' : 'Kasir'}</p>
            </td>

            <td class="max-w-[340px] px-3 py-4">
                <p class="font-display text-[12px] leading-snug font-bold">${escapeHtml(shownNames)}${escapeHtml(extraNames)}</p>
                <p class="tag mt-1 text-[7px] leading-relaxed text-muted">
                    ${(order.items ?? []).length} Menu (${quantity} Item)${notes}
                </p>
            </td>

            <td class="px-3 py-4">
                <span class="tag inline-flex items-center gap-1 rounded border-2 border-ink bg-[#f0eee3] px-2 py-1 text-[7px] font-bold text-ink">
                    ${paymentIcon(order.payment)}
                    ${escapeHtml(order.payment_label)}
                </span>
                <span class="tag mt-1 inline-block rounded border px-1.5 py-0.5 text-[7px] font-bold ${payment.pill}">
                    ${payment.label}
                </span>
            </td>

            <td class="px-4 py-4 text-right">
                <div class="flex flex-wrap items-center justify-end gap-1.5">
                    ${actions}
                </div>
            </td>
        </tr>`;
    };

    const renderOrders = () => {
        const filtered = filteredOrders();
        const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
        currentPage = Math.min(currentPage, totalPages);

        const start = (currentPage - 1) * PAGE_SIZE;
        const visible = filtered.slice(start, start + PAGE_SIZE);

        updateCounts();
        updateTabStyles();

        if (tbody) {
            tbody.innerHTML =
                visible.length > 0
                    ? visible.map(rowMarkup).join('')
                    : `<tr>
                        <td colspan="6" class="px-4 py-14 text-center">
                            <p class="font-display text-base font-extrabold">Tidak ada pesanan</p>
                            <p class="mt-1 font-serif text-sm text-muted">Belum ada pesanan pada filter ini.</p>
                        </td>
                    </tr>`;
        }

        if (record) {
            record.textContent = `// Record: Menampilkan ${visible.length} dari ${filtered.length} pesanan`;
        }

        renderPagination(pagination, totalPages, currentPage, (page) => {
            currentPage = page;
            renderOrders();
        });
    };

    /* ---------------------------- detail pesanan ------------------------- */

    const detailItemMarkup = (item) => `
        <div class="flex items-start gap-3 rounded-md border-2 border-ink bg-kasir-panel px-3 py-2.5">
            <span class="tag grid h-7 min-w-7 place-items-center rounded border-2 border-ink bg-varya-dark px-1 text-[8px] font-bold text-white">${item.qty}×</span>
            <div class="min-w-0 flex-1">
                <p class="font-display text-[12px] leading-tight font-extrabold">${escapeHtml(item.name)}</p>
                ${item.note ? `<span class="tag mt-1 inline-block rounded border border-line-strong bg-[#f0eee3] px-1.5 py-0.5 text-[7px] leading-relaxed text-ink-soft">${escapeHtml(item.note)}</span>` : ''}
            </div>
            <p class="shrink-0 font-display text-[12px] font-bold">${rupiah(item.subtotal ?? item.price * item.qty)}</p>
        </div>`;

    const detailActionsMarkup = (order) => {
        if (order.status === 'antre') return actionButton('process', 'Mulai Proses', 'primary', order);
        if (order.status === 'diproses') return actionButton('finish', 'Selesai', 'primary', order);

        if (order.status === 'menunggu_pembayaran') {
            let markup = actionButton('check', 'Cek Status', 'secondary', order);
            if (order.payment_status === 'menunggu') {
                markup += actionButton('cancel', 'Batalkan Pesanan', 'danger', order);
            }
            return markup;
        }

        return '';
    };

    const openDetail = (order) => {
        currentDetailId = order.id;

        const status = statusMeta(order.status);
        const payment = paymentMeta(order.payment_status);
        const type = typeMeta(order.type);
        const quantity = orderQuantity(order);

        if (detailQueue) detailQueue.textContent = order.queue ? `#${order.queue}` : '—';

        if (detailType) {
            detailType.textContent = type.label;
            detailType.className = `tag inline-block rounded border-2 px-2.5 py-1 text-[8px] font-bold ${type.pill}`;
        }

        if (detailCustomer) detailCustomer.textContent = order.customer;

        if (detailStatus) {
            detailStatus.textContent = status.label;
            detailStatus.className = `tag ml-auto inline-block rounded border-2 px-2.5 py-1 text-[8px] font-bold ${status.pill}`;
        }

        if (detailTime) detailTime.textContent = order.created_at;
        if (detailItemCount) detailItemCount.textContent = quantity;
        if (detailItems) detailItems.innerHTML = (order.items ?? []).map(detailItemMarkup).join('');

        if (detailNotesWrap) detailNotesWrap.hidden = !order.notes;
        if (detailNotes) detailNotes.textContent = order.notes ?? '—';

        if (detailPayment) {
            detailPayment.textContent = order.payment_label;
            detailPayment.className =
                'tag inline-flex items-center gap-1 rounded border-2 border-ink bg-kasir-panel px-2 py-1 text-[8px] font-bold';
        }

        if (detailPaymentStatus) {
            detailPaymentStatus.textContent = payment.label;
            detailPaymentStatus.className = `tag rounded border px-2 py-1 text-[7px] font-bold ${payment.pill}`;
        }

        if (detailTotal) detailTotal.textContent = rupiah(order.total);

        if (detailCashWrap) detailCashWrap.hidden = order.payment !== 'tunai';
        if (detailCash) detailCash.textContent = order.cash_received ? rupiah(order.cash_received) : '—';
        if (detailChange) detailChange.textContent = order.change != null ? rupiah(order.change) : '—';

        if (detailCode) detailCode.textContent = order.code;
        if (detailSource) detailSource.textContent = order.source === 'qr' ? 'QR Pelanggan' : 'Kasir';

        if (detailMidtransWrap) detailMidtransWrap.hidden = !order.midtrans_id;
        if (detailMidtrans) detailMidtrans.textContent = order.midtrans_id ?? '—';

        if (detailExpiresWrap) detailExpiresWrap.hidden = !order.expires_at;
        if (detailExpires) detailExpires.textContent = order.expires_at ?? '—';

        if (detailActions) detailActions.innerHTML = detailActionsMarkup(order);

        openDialog(detailDialog);
    };

    /* ------------------------- aksi status pesanan ----------------------- */

    const simulateCheckStatus = (order) => {
        showToast('Memeriksa status pembayaran ke Midtrans...');

        window.setTimeout(() => {
            if (order.payment_status === 'menunggu') {
                showToast(`Pesanan ${order.code} masih menunggu pembayaran.`);
            } else if (order.payment_status === 'perlu_diperiksa') {
                showToast('Pesanan perlu diperiksa manual melalui dashboard Midtrans.');
            } else {
                showToast(`Status pembayaran ${order.code} sudah lunas.`);
            }
        }, 900);
    };

    const openCancelConfirm = (order) => {
        pendingCancel = order;

        if (confirmMessage) {
            confirmMessage.textContent = `Pesanan ${order.code} atas nama ${order.customer} belum dibayar. Pesanan akan dibatalkan dan tidak masuk antrean produksi.`;
        }

        openDialog(confirmDialog);
    };

    const handleOrderAction = (action, orderId) => {
        const order = orders.find((item) => item.id === orderId);
        if (!order) return;

        if (action === 'detail') {
            openDetail(order);
            return;
        }

        if (action === 'check') {
            simulateCheckStatus(order);
            return;
        }

        if (action === 'cancel') {
            closeDialog(detailDialog);
            openCancelConfirm(order);
            return;
        }

        if (action === 'process') {
            order.status = 'diproses';
            persistSavedOrder(order);
            closeDialog(detailDialog);
            showToast(`Pesanan ${order.code} mulai diproses.`);
            renderOrders();
            return;
        }

        if (action === 'finish') {
            order.status = 'selesai';
            persistSavedOrder(order);
            closeDialog(detailDialog);
            showToast(`Pesanan ${order.code} selesai.`);
            renderOrders();
        }
    };

    tbody?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-order-action]');
        if (!button) return;

        handleOrderAction(button.dataset.orderAction, button.dataset.orderId);
    });

    detailActions?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-order-action]');
        if (!button) return;

        handleOrderAction(button.dataset.orderAction, button.dataset.orderId);
    });

    root.querySelectorAll('[data-order-detail-close]').forEach((button) => {
        button.addEventListener('click', () => closeDialog(detailDialog));
    });

    root.querySelector('[data-confirm-cancel]')?.addEventListener('click', () => {
        pendingCancel = null;
        closeDialog(confirmDialog);
    });

    root.querySelector('[data-confirm-ok]')?.addEventListener('click', () => {
        if (!pendingCancel) return;

        const canceled = pendingCancel;
        orders = orders.filter((order) => order.id !== canceled.id);
        removeSavedOrder(canceled.id);

        pendingCancel = null;
        closeDialog(confirmDialog);
        showToast(`Pesanan ${canceled.code} dibatalkan.`);
        renderOrders();
    });

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeTab = button.dataset.orderTab;
            currentPage = 1;
            renderOrders();
        });
    });

    searchInput?.addEventListener('input', () => {
        searchTerm = searchInput.value.trim().toLowerCase();
        currentPage = 1;
        renderOrders();
    });

    root.querySelector('[data-order-search]')?.addEventListener('submit', (event) => {
        event.preventDefault();
        searchTerm = searchInput?.value.trim().toLowerCase() ?? '';
        currentPage = 1;
        renderOrders();
    });

    [detailDialog, confirmDialog].forEach(enableBackdropClose);

    renderOrders();
}

/* -------------------------------------------------------------------------- */
/* Halaman Kelola Menu                                                        */
/* -------------------------------------------------------------------------- */

function initKelolaMenu() {
    const root = document.querySelector('[data-kasir-kelola-menu]');
    if (!root) return;

    const rows = Array.from(root.querySelectorAll('[data-menu-row]'));
    const tabButtons = Array.from(root.querySelectorAll('[data-menu-tab]'));
    const searchInput = root.querySelector('[data-menu-list-search-input]');
    const record = root.querySelector('[data-menu-record]');
    const pagination = root.querySelector('[data-menu-pagination]');
    const empty = root.querySelector('[data-menu-empty]');

    let activeTab = 'all';
    let searchTerm = '';
    let currentPage = 1;

    const overrides = getAvailabilityOverrides();
    rows.forEach((row) => {
        if (hasOwn(overrides, row.dataset.menuId)) {
            setMenuRowAvailability(row, Boolean(overrides[row.dataset.menuId]));
        }
    });

    const updateTabStyles = () => {
        tabButtons.forEach((button) => {
            const active = button.dataset.menuTab === activeTab;
            const chip = button.querySelector('[data-menu-tab-count]');

            button.classList.toggle('bg-varya-dark', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('text-ink-soft', !active);
            button.classList.toggle('hover:bg-varya-light/50', !active);
            button.setAttribute('aria-selected', String(active));

            if (chip) {
                chip.classList.toggle('border-white/40', active);
                chip.classList.toggle('bg-white/15', active);
                chip.classList.toggle('text-white', active);
                chip.classList.toggle('border-line-strong', !active);
                chip.classList.toggle('bg-white', !active);
                chip.classList.toggle('text-ink-soft', !active);
            }
        });
    };

    const updateCounts = () => {
        const counts = {
            all: rows.length,
            available: rows.filter((row) => row.dataset.available === '1').length,
            unavailable: rows.filter((row) => row.dataset.available === '0').length,
        };

        Object.entries(counts).forEach(([key, value]) => {
            const element = root.querySelector(`[data-menu-tab-count="${key}"]`);
            if (element) element.textContent = value;
        });
    };

    const filteredRows = () =>
        rows.filter((row) => {
            const matchesTab =
                activeTab === 'all' ||
                (activeTab === 'available' && row.dataset.available === '1') ||
                (activeTab === 'unavailable' && row.dataset.available === '0');

            const matchesSearch =
                searchTerm === '' || row.dataset.menuSearch.includes(searchTerm);

            return matchesTab && matchesSearch;
        });

    const renderMenu = () => {
        const filtered = filteredRows();
        const totalPages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
        currentPage = Math.min(currentPage, totalPages);

        const start = (currentPage - 1) * PAGE_SIZE;
        const visible = filtered.slice(start, start + PAGE_SIZE);

        rows.forEach((row) => {
            row.hidden = true;
        });

        visible.forEach((row) => {
            row.hidden = false;
        });

        updateCounts();
        updateTabStyles();

        if (record) {
            record.textContent = `// Record: Menampilkan ${visible.length} dari ${filtered.length} menu`;
        }

        if (empty) empty.hidden = filtered.length > 0;

        renderPagination(pagination, totalPages, currentPage, (page) => {
            currentPage = page;
            renderMenu();
        });
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeTab = button.dataset.menuTab;
            currentPage = 1;
            renderMenu();
        });
    });

    searchInput?.addEventListener('input', () => {
        searchTerm = searchInput.value.trim().toLowerCase();
        currentPage = 1;
        renderMenu();
    });

    root.querySelector('[data-menu-search-form]')?.addEventListener('submit', (event) => {
        event.preventDefault();
        searchTerm = searchInput?.value.trim().toLowerCase() ?? '';
        currentPage = 1;
        renderMenu();
    });

    root.querySelector('[data-menu-table]')?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-toggle-menu]');
        if (!button) return;

        const row = button.closest('[data-menu-row]');
        if (!row) return;

        const nextAvailable = row.dataset.available !== '1';
        setMenuRowAvailability(row, nextAvailable);
        saveAvailabilityOverride(row.dataset.menuId, nextAvailable);

        showToast(
            nextAvailable
                ? `${row.dataset.menuName} ditandai Tersedia.`
                : `${row.dataset.menuName} ditandai Habis.`,
        );

        renderMenu();
    });

    renderMenu();
}

/* -------------------------------------------------------------------------- */
/* Init                                                                       */
/* -------------------------------------------------------------------------- */

function initKasir() {
    initSidebar();
    initClock();
    initBuatPesanan();
    initPesananMasuk();
    initKelolaMenu();

    document.querySelectorAll('dialog').forEach(enableBackdropClose);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initKasir);
} else {
    initKasir();
}
