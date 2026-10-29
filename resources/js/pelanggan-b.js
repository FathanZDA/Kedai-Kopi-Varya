// Interaksi Pelanggan B: keranjang, checkout QRIS, dan halaman pembayaran.
//
// Alur data:
//   menu dari database -> kartu produk
//   keranjang -> localStorage (belum ada endpoint keranjang)
//   checkout -> POST /pesan/checkout -> order QRIS + halaman pembayaran Snap
//
// Keranjang dihapus setelah order berhasil dibuat. Tidak ada data pembayaran
// atau status lunas yang disimpan di browser: status pembayaran hanya boleh
// berasal dari webhook Midtrans (dirender oleh server pada slice berikutnya).
// Halaman /pesan/pembayaran dan /pesan/berhasil masih sisa demo lama dan tidak
// lagi dipakai alur checkout.

const CART_KEY = 'varya_cart_v1';
const PENDING_KEY = 'varya_pending_order_v1';
const LAST_ORDER_KEY = 'varya_last_order_v1';

const ROUTES = window.VARYA_ROUTES ?? {};

const TONES = [
    ['#8a6a4f', '#5d4633'],
    ['#7d5a3c', '#4e3826'],
    ['#6b8f71', '#3f5c46'],
    ['#b07a3c', '#7a4f24'],
    ['#9c8a5a', '#6b5c34'],
    ['#8f5b4a', '#5d382c'],
    ['#a3835f', '#6f563a'],
    ['#7f6b52', '#54453a'],
    ['#96805c', '#635537'],
    ['#b5916a', '#7d6041'],
    ['#8c8577', '#5b574d'],
    ['#6f6357', '#453d34'],
];

let productQty = 1;
let toastTimer = null;
let paymentTimer = null;

/* -------------------------------------------------------------------------- */
/* Utilitas                                                                   */
/* -------------------------------------------------------------------------- */

const rupiah = (value) =>
    `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value) || 0)}`;

const clamp = (value, min, max) => Math.min(max, Math.max(min, Number(value) || 0));

const escapeHtml = (value) =>
    String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    })[char]);

const pad = (value) => String(value).padStart(2, '0');

const formatQueue = (number) => `#${pad(Number(number) || 0)}`;

function formatDate(value) {
    const date = value ? new Date(value) : new Date();

    if (Number.isNaN(date.getTime())) return '—';

    const formatted = new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'Asia/Jakarta',
    }).format(date);

    return `${formatted} WIB`;
}

function toneFor(seed) {
    const text = String(seed || 'menu');
    let hash = 0;

    for (let index = 0; index < text.length; index += 1) {
        hash = (hash * 31 + text.charCodeAt(index)) >>> 0;
    }

    return TONES[hash % TONES.length];
}

const storage = {
    get(key, fallback = null) {
        try {
            const raw = window.localStorage.getItem(key);
            return raw === null ? fallback : JSON.parse(raw);
        } catch {
            return fallback;
        }
    },
    set(key, value) {
        try {
            window.localStorage.setItem(key, JSON.stringify(value));
        } catch {
            // localStorage bisa diblokir di mode privat; abaikan.
        }
    },
    remove(key) {
        try {
            window.localStorage.removeItem(key);
        } catch {
            // abaikan
        }
    },
};

const session = {
    get(key, fallback = null) {
        try {
            const raw = window.sessionStorage.getItem(key);
            return raw === null ? fallback : JSON.parse(raw);
        } catch {
            return fallback;
        }
    },
    set(key, value) {
        try {
            window.sessionStorage.setItem(key, JSON.stringify(value));
        } catch {
            // abaikan
        }
    },
    remove(key) {
        try {
            window.sessionStorage.removeItem(key);
        } catch {
            // abaikan
        }
    },
};

/* -------------------------------------------------------------------------- */
/* Data keranjang                                                             */
/* -------------------------------------------------------------------------- */

function getCart() {
    const raw = storage.get(CART_KEY, []);

    if (!Array.isArray(raw)) return [];

    return raw
        .filter((item) => item && Number.isFinite(Number(item.id)) && Number(item.qty) > 0)
        .map((item) => ({
            id: Number(item.id),
            slug: String(item.slug ?? ''),
            name: String(item.name ?? 'Menu'),
            price: Number(item.price) || 0,
            image: item.image || null,
            initial: String(item.initial || item.name || '?').charAt(0).toUpperCase(),
            qty: clamp(item.qty, 1, 99),
        }));
}

function getPendingOrder() {
    const order = session.get(PENDING_KEY, null);

    if (!order || !Array.isArray(order.items)) return null;

    return order;
}

function getLastOrder() {
    const order = session.get(LAST_ORDER_KEY, null);

    if (!order || !Array.isArray(order.items)) return null;

    return order;
}

const cartCount = (cart = getCart()) =>
    cart.reduce((total, item) => total + Number(item.qty || 0), 0);

function saveCart(cart) {
    storage.set(CART_KEY, cart);
    syncAll();
}

function addToCart(item, qty = 1) {
    if (!item || !Number.isFinite(Number(item.id))) return;

    const amount = clamp(qty, 1, 99);
    const cart = getCart();
    const existing = cart.find((entry) => entry.id === Number(item.id));

    if (existing) {
        existing.qty = clamp(existing.qty + amount, 1, 99);
    } else {
        cart.push({
            id: Number(item.id),
            slug: String(item.slug ?? ''),
            name: String(item.name ?? 'Menu'),
            price: Number(item.price) || 0,
            image: item.image || null,
            initial: String(item.initial || item.name || '?').charAt(0).toUpperCase(),
            qty: amount,
        });
    }

    saveCart(cart);
    showToast(`${item.name} masuk keranjang`);
}

function setQty(id, qty) {
    const cart = getCart()
        .map((item) => (item.id === Number(id) ? { ...item, qty: clamp(qty, 0, 99) } : item))
        .filter((item) => item.qty > 0);

    saveCart(cart);
}

function removeItem(id) {
    saveCart(getCart().filter((item) => item.id !== Number(id)));
}

function clearCart() {
    storage.remove(CART_KEY);
    syncAll();
    showToast('Keranjang dikosongkan');
}

/* -------------------------------------------------------------------------- */
/* Render potongan HTML                                                       */
/* -------------------------------------------------------------------------- */

function thumbHtml(item, size = 'h-12 w-12') {
    if (item.image) {
        return `<img src="${escapeHtml(item.image)}" alt="" loading="lazy"
                     class="${size} shrink-0 rounded-md border-2 border-ink object-cover">`;
    }

    const [from, to] = toneFor(item.slug || item.name);
    const initial = escapeHtml(item.initial || String(item.name || '?').charAt(0).toUpperCase());

    return `<span class="${size} flex shrink-0 items-center justify-center rounded-md border-2 border-ink"
                  style="background: linear-gradient(135deg, ${from}, ${to});">
                <span class="font-display text-lg font-black text-white/70">${initial}</span>
            </span>`;
}

function summaryRow(item) {
    const subtotal = Number(item.price) * Number(item.qty);

    return `
        <div class="flex items-center gap-3 border-b border-line pb-3 last:border-0 last:pb-0">
            ${thumbHtml(item, 'h-12 w-12')}
            <div class="min-w-0 flex-1">
                <p class="product-name truncate text-[11px] leading-tight">${escapeHtml(item.name)}</p>
                <p class="tag mt-0.5 text-[8px] text-muted">${item.qty} &times; ${rupiah(item.price)}</p>
            </div>
            <p class="font-display text-sm font-bold whitespace-nowrap text-varya-dark">${rupiah(subtotal)}</p>
        </div>
    `;
}

function cartItemRow(item) {
    const subtotal = Number(item.price) * Number(item.qty);

    return `
        <article class="brut bg-surface p-4 sm:p-5" data-cart-item="${item.id}">
            <div class="flex gap-4">
                ${thumbHtml(item, 'h-24 w-24')}
                <div class="flex min-w-0 flex-1 flex-col">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="product-name text-sm leading-snug sm:text-base">${escapeHtml(item.name)}</h2>
                            <p class="tag mt-1 text-[9px] text-muted">${rupiah(item.price)} / item</p>
                        </div>
                        <button type="button" data-remove-item
                                class="grid h-8 w-8 shrink-0 place-items-center rounded-md border-2 border-line-strong bg-surface text-ink transition-colors hover:border-rust hover:text-rust"
                                aria-label="Hapus ${escapeHtml(item.name)} dari keranjang">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        </button>
                    </div>

                    <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-4">
                        <div class="flex items-stretch overflow-hidden rounded-md border-2 border-ink bg-surface">
                            <button type="button" data-cart-minus
                                    class="grid w-9 place-items-center border-r-2 border-ink text-lg font-bold transition-colors hover:bg-varya-light"
                                    aria-label="Kurangi jumlah ${escapeHtml(item.name)}">&minus;</button>
                            <span class="tag flex w-10 items-center justify-center text-xs font-bold">${item.qty}</span>
                            <button type="button" data-cart-plus
                                    class="grid w-9 place-items-center border-l-2 border-ink bg-varya-dark text-lg font-bold text-white transition-colors hover:bg-varya"
                                    aria-label="Tambah jumlah ${escapeHtml(item.name)}">+</button>
                        </div>

                        <p class="font-display text-lg font-black text-varya-dark">${rupiah(subtotal)}</p>
                    </div>
                </div>
            </div>
        </article>
    `;
}

function ticketItemRow(item) {
    const subtotal = Number(item.price) * Number(item.qty);

    return `
        <div class="flex items-center gap-3 border-b border-line pb-3 last:border-0 last:pb-0">
            ${thumbHtml(item, 'h-14 w-14')}
            <div class="min-w-0 flex-1">
                <p class="product-name truncate text-[11px] leading-tight">${escapeHtml(item.name)}</p>
                <p class="tag mt-0.5 text-[8px] text-muted">${item.qty} &times; ${rupiah(item.price)}</p>
            </div>
            <p class="font-display text-sm font-bold whitespace-nowrap text-varya-dark">${rupiah(subtotal)}</p>
        </div>
    `;
}

/* -------------------------------------------------------------------------- */
/* Sinkronisasi tampilan                                                      */
/* -------------------------------------------------------------------------- */

function syncAll() {
    const cart = getCart();

    updateCartBadges(cart);
    syncMenuCards(cart);
    renderCartPage(cart);
    renderSummary('cart', cart);
    renderSummary('pending');
    renderSummary('last');
    syncCheckoutState(cart);
    syncPaymentMeta();
    updateProductNote(cart);
}

function updateCartBadges(cart = getCart()) {
    const count = cartCount(cart);

    document.querySelectorAll('[data-cart-count]').forEach((element) => {
        element.textContent = String(count);
    });
}

function syncMenuCards(cart = getCart()) {
    document.querySelectorAll('[data-menu-card]').forEach((card) => {
        const addButton = card.querySelector('[data-add-to-cart]');
        const stepper = card.querySelector('[data-qty-stepper]');
        const value = card.querySelector('[data-qty-value]');

        if (!addButton || !stepper) return;

        const item = cart.find((entry) => entry.id === Number(card.dataset.menuId));
        const inCart = Boolean(item);

        addButton.hidden = inCart;
        stepper.hidden = !inCart;

        if (value) value.textContent = String(item?.qty ?? 0);
    });
}

function renderCartPage(cart = getCart()) {
    const page = document.querySelector('[data-cart-page]');
    if (!page) return;

    const list = page.querySelector('[data-cart-items]');
    const empty = page.querySelector('[data-cart-empty]');

    if (list) {
        list.innerHTML = cart.map(cartItemRow).join('');
        list.hidden = cart.length === 0;
    }

    if (empty) empty.hidden = cart.length > 0;

    const clearButton = page.querySelector('[data-clear-cart]');
    if (clearButton) clearButton.hidden = cart.length === 0;

    page.querySelectorAll('[data-cart-breadcrumb-count]').forEach((element) => {
        element.textContent = String(cartCount(cart));
    });
}

function renderSummary(source, providedItems = null) {
    const root = document.querySelector(`[data-order-summary][data-summary-source="${source}"]`);
    if (!root) return;

    let items = providedItems;

    if (!items) {
        if (source === 'cart') items = getCart();
        else if (source === 'pending') items = getPendingOrder()?.items ?? [];
        else items = getLastOrder()?.items ?? [];
    }

    const count = items.reduce((total, item) => total + Number(item.qty || 0), 0);
    const subtotal = items.reduce(
        (total, item) => total + Number(item.price || 0) * Number(item.qty || 0),
        0,
    );

    const list = root.querySelector('[data-summary-items]');
    const empty = root.querySelector('[data-summary-empty]');

    if (list) list.innerHTML = items.map(summaryRow).join('');
    if (empty) empty.hidden = items.length > 0;

    root.querySelectorAll('[data-summary-count]').forEach((element) => {
        element.textContent = String(count);
    });

    const subtotalEl = root.querySelector('[data-summary-subtotal]');
    if (subtotalEl) subtotalEl.textContent = rupiah(subtotal);

    const totalEl = root.querySelector('[data-summary-total]');
    if (totalEl) totalEl.textContent = rupiah(subtotal);

    root.querySelectorAll('[data-summary-cta]').forEach((cta) => {
        const disabled = items.length === 0;

        cta.classList.toggle('pointer-events-none', disabled);
        cta.classList.toggle('opacity-50', disabled);
        cta.setAttribute('aria-disabled', String(disabled));

        if (cta.tagName === 'BUTTON') cta.disabled = disabled;
        if (cta.tagName === 'A') cta.tabIndex = disabled ? -1 : 0;
    });
}

function syncCheckoutState(cart = getCart()) {
    const page = document.querySelector('[data-checkout-page]');
    if (!page) return;

    const warning = page.querySelector('[data-checkout-empty]');
    if (warning) warning.hidden = cart.length > 0;
}

function syncPaymentMeta() {
    const pending = getPendingOrder();

    document.querySelectorAll('[data-payment-code]').forEach((element) => {
        element.textContent = pending?.code ?? '—';
    });

    document.querySelectorAll('[data-payment-total]').forEach((element) => {
        element.textContent = rupiah(pending?.total ?? 0);
    });
}

function updateProductNote(cart = getCart()) {
    const root = document.querySelector('[data-product-detail]');
    if (!root) return;

    const item = cart.find((entry) => entry.id === Number(root.dataset.menuId));
    const note = root.querySelector('[data-product-cart-note]');

    if (!note) return;

    note.textContent = item
        ? `Sudah ada ${item.qty} di keranjang.`
        : 'Belum ada di keranjang. Pilih jumlah lalu tambahkan.';
}

/* -------------------------------------------------------------------------- */
/* Notifikasi kecil                                                           */
/* -------------------------------------------------------------------------- */

function showToast(message) {
    const toast = document.querySelector('[data-toast]');
    if (!toast) return;

    toast.textContent = message;
    toast.hidden = false;

    if (toastTimer) window.clearTimeout(toastTimer);

    toastTimer = window.setTimeout(() => {
        toast.hidden = true;
        toastTimer = null;
    }, 2200);
}

/* -------------------------------------------------------------------------- */
/* Detail produk                                                              */
/* -------------------------------------------------------------------------- */

function initProductDetail() {
    const root = document.querySelector('[data-product-detail]');
    if (!root) return;

    productQty = 1;
    updateProductQty();
}

function updateProductQty() {
    const value = document.querySelector('[data-product-qty]');
    if (value) value.textContent = String(productQty);
}

function productFromRoot(root) {
    if (!root) return null;

    return {
        id: Number(root.dataset.menuId),
        slug: root.dataset.menuSlug ?? '',
        name: root.dataset.menuName ?? 'Menu',
        price: Number(root.dataset.menuPrice) || 0,
        image: root.dataset.menuImage || null,
        initial: root.dataset.menuInitial || String(root.dataset.menuName ?? '?').charAt(0).toUpperCase(),
    };
}

/* -------------------------------------------------------------------------- */
/* Checkout                                                                   */
/* -------------------------------------------------------------------------- */

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function firstErrorMessage(body) {
    const errors = body?.errors;

    if (!errors || typeof errors !== 'object') return null;

    const first = Object.values(errors)[0];

    if (Array.isArray(first)) return first[0] ?? null;

    return first ? String(first) : null;
}

function showCheckoutError(message) {
    const errorBox = document.querySelector('[data-checkout-error]');
    if (!errorBox) {
        showToast(message);
        return;
    }

    errorBox.textContent = message;
    errorBox.hidden = false;
    errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function hideCheckoutError() {
    const errorBox = document.querySelector('[data-checkout-error]');
    if (!errorBox) return;

    errorBox.hidden = true;
    errorBox.textContent = '';
}

function initCheckout() {
    const form = document.querySelector('#checkout-form');
    if (!form) return;

    const nameInput = form.querySelector('[data-customer-name]');
    const nameError = form.querySelector('[data-name-error]');
    const submitButton = form.querySelector('[data-summary-cta]');
    const submitLabel = submitButton?.innerHTML ?? '';

    const setPending = (pending) => {
        if (!submitButton) return;

        submitButton.disabled = pending;
        submitButton.classList.toggle('opacity-60', pending);
        submitButton.classList.toggle('pointer-events-none', pending);
        submitButton.setAttribute('aria-busy', String(pending));
        submitButton.innerHTML = pending ? 'Memproses pembayaran…' : submitLabel;
    };

    nameInput?.addEventListener('input', () => {
        if (nameError) nameError.hidden = true;
        nameInput.removeAttribute('aria-invalid');
        hideCheckoutError();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        hideCheckoutError();

        const name = (nameInput?.value ?? '').trim();
        const cart = getCart();

        if (!name) {
            if (nameError) nameError.hidden = false;
            nameInput?.setAttribute('aria-invalid', 'true');
            nameInput?.focus();
            return;
        }

        if (cart.length === 0) {
            syncCheckoutState(cart);
            showToast('Keranjang masih kosong');
            return;
        }

        const payload = {
            nama_pelanggan: name,
            tipe_pesanan: form.querySelector('[data-order-type]:checked')?.value ?? 'dine_in',
            catatan: (form.querySelector('[data-catatan]')?.value ?? '').trim(),
            item: cart.map((item) => ({ menu_id: item.id, jumlah: item.qty })),
        };

        setPending(true);

        try {
            const response = await fetch(ROUTES.checkoutStore, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify(payload),
            });

            const body = await response.json().catch(() => ({}));

            if (response.ok && body.data?.snap_redirect_url) {
                // Order tersimpan: keranjang tidak dipakai lagi.
                storage.remove(CART_KEY);
                window.location.href = body.data.snap_redirect_url;
                return;
            }

            // Order sudah tersimpan tetapi halaman pembayaran gagal dibuat.
            // Keranjang dikosongkan agar pelanggan tidak membuat pesanan ganda.
            if (response.status === 500 && body.data?.kode_pesanan) {
                storage.remove(CART_KEY);
                syncAll();
                showCheckoutError(
                    `Pesanan ${body.data.kode_pesanan} sudah tersimpan, tetapi halaman pembayaran gagal dibuat. Hubungi kasir dengan kode tersebut.`,
                );

                return;
            } else if (response.status === 429) {
                showCheckoutError('Terlalu banyak percobaan checkout. Tunggu sebentar lalu coba lagi.');
            } else if (response.status === 422) {
                showCheckoutError(
                    firstErrorMessage(body) ?? 'Pesanan tidak bisa diproses. Periksa kembali data pesanan.',
                );
            } else {
                showCheckoutError(body.message ?? 'Pesanan gagal diproses. Coba lagi.');
            }
        } catch {
            showCheckoutError('Tidak bisa menghubungi server. Periksa koneksi lalu coba lagi.');
        }

        setPending(false);
    });
}

/* -------------------------------------------------------------------------- */
/* Pembayaran QRIS (menunggu integrasi Midtrans)                              */
/* -------------------------------------------------------------------------- */

function initPaymentPage() {
    const page = document.querySelector('[data-payment-page]');
    if (!page) return;

    const pending = getPendingOrder();
    const content = page.querySelector('[data-payment-content]');
    const empty = page.querySelector('[data-payment-empty]');

    if (!pending) {
        if (content) content.hidden = true;
        if (empty) empty.hidden = false;
        return;
    }

    if (content) content.hidden = false;
    if (empty) empty.hidden = true;

    syncPaymentMeta();
    startPaymentCountdown(page, pending);
}

function startPaymentCountdown(page, pending) {
    if (paymentTimer) window.clearInterval(paymentTimer);

    const countdown = page.querySelector('[data-payment-countdown]');
    const status = page.querySelector('[data-payment-status]');
    const statusText = page.querySelector('[data-payment-status-text]');
    const dot = page.querySelector('[data-payment-status-dot]');

    const tick = () => {
        const remaining = Number(pending.expiresAt) - Date.now();

        if (remaining <= 0) {
            if (countdown) countdown.textContent = '00:00';
            if (statusText) statusText.textContent = 'Pembayaran kedaluwarsa';
            if (status) {
                status.classList.remove('text-varya-dark');
                status.classList.add('text-rust');
            }
            if (dot) {
                dot.classList.remove('bg-varya');
                dot.classList.add('bg-rust');
            }

            window.clearInterval(paymentTimer);
            paymentTimer = null;
            return;
        }

        const minutes = Math.floor(remaining / 60000);
        const seconds = Math.floor((remaining % 60000) / 1000);

        if (countdown) countdown.textContent = `${pad(minutes)}:${pad(seconds)}`;
    };

    tick();
    paymentTimer = window.setInterval(tick, 1000);
}

/* -------------------------------------------------------------------------- */
/* Tiket pesanan                                                              */
/* -------------------------------------------------------------------------- */

function initSuccessPage() {
    const content = document.querySelector('[data-success-content]');
    if (!content) return;

    const banner = document.querySelector('[data-success-banner]');
    const empty = document.querySelector('[data-success-empty]');
    const order = getLastOrder();

    if (!order) {
        content.hidden = true;
        if (empty) empty.hidden = false;
        if (banner) banner.hidden = true;
        return;
    }

    const items = Array.isArray(order.items) ? order.items : [];
    const count = items.reduce((total, item) => total + Number(item.qty || 0), 0);
    const subtotal = items.reduce(
        (total, item) => total + Number(item.price || 0) * Number(item.qty || 0),
        0,
    );
    const queue = formatQueue(order.queueNumber);
    const type = order.type || 'Makan di Tempat';

    if (banner) banner.hidden = false;
    if (empty) empty.hidden = true;
    content.hidden = false;

    document.querySelectorAll('[data-success-name]').forEach((element) => {
        element.textContent = order.name || 'Pelanggan';
    });

    document.querySelectorAll('[data-ticket-number]').forEach((element) => {
        element.textContent = queue;
    });

    document.querySelectorAll('[data-ticket-number-inline]').forEach((element) => {
        element.textContent = queue;
    });

    document.querySelectorAll('[data-ticket-type-short]').forEach((element) => {
        element.textContent = `/ ${type.toUpperCase()}`;
    });

    document.querySelectorAll('[data-ticket-code]').forEach((element) => {
        element.textContent = order.code || '—';
    });

    document.querySelectorAll('[data-ticket-date]').forEach((element) => {
        element.textContent = formatDate(order.paidAt || order.createdAt);
    });

    document.querySelectorAll('[data-ticket-name]').forEach((element) => {
        element.textContent = order.name || 'Pelanggan';
    });

    document.querySelectorAll('[data-ticket-type]').forEach((element) => {
        element.textContent = type;
    });

    const catatanWrap = document.querySelector('[data-ticket-catatan-wrap]');
    const catatan = document.querySelector('[data-ticket-catatan]');

    if (catatanWrap) catatanWrap.hidden = !order.catatan;
    if (catatan) catatan.textContent = order.catatan || '';

    const itemList = document.querySelector('[data-ticket-items]');
    if (itemList) itemList.innerHTML = items.map(ticketItemRow).join('');

    document.querySelectorAll('[data-ticket-item-count]').forEach((element) => {
        element.textContent = String(count);
    });

    document.querySelectorAll('[data-ticket-subtotal]').forEach((element) => {
        element.textContent = rupiah(subtotal);
    });

    document.querySelectorAll('[data-ticket-total]').forEach((element) => {
        element.textContent = rupiah(order.total ?? subtotal);
    });

    document.title = `Pesanan ${queue} — Kedai Kopi Varya`;
}

/* -------------------------------------------------------------------------- */
/* Event delegation                                                           */
/* -------------------------------------------------------------------------- */

function handleClick(event) {
    if (!(event.target instanceof Element)) return;

    const addButton = event.target.closest('[data-add-to-cart]');

    if (addButton) {
        const card = addButton.closest('[data-menu-card]');
        if (!card) return;

        addToCart({
            id: Number(card.dataset.menuId),
            slug: card.dataset.menuSlug ?? '',
            name: card.dataset.menuName ?? 'Menu',
            price: Number(card.dataset.menuPrice) || 0,
            image: card.dataset.menuImage || null,
            initial: card.dataset.menuInitial || String(card.dataset.menuName ?? '?').charAt(0).toUpperCase(),
        });

        return;
    }

    const cardPlus = event.target.closest('[data-qty-plus]');

    if (cardPlus) {
        const card = cardPlus.closest('[data-menu-card]');
        if (!card) return;

        const item = getCart().find((entry) => entry.id === Number(card.dataset.menuId));
        setQty(Number(card.dataset.menuId), (item?.qty ?? 0) + 1);

        return;
    }

    const cardMinus = event.target.closest('[data-qty-minus]');

    if (cardMinus) {
        const card = cardMinus.closest('[data-menu-card]');
        if (!card) return;

        const item = getCart().find((entry) => entry.id === Number(card.dataset.menuId));
        setQty(Number(card.dataset.menuId), (item?.qty ?? 0) - 1);

        return;
    }

    const cartPlus = event.target.closest('[data-cart-plus]');

    if (cartPlus) {
        const card = cartPlus.closest('[data-cart-item]');
        if (!card) return;

        const item = getCart().find((entry) => entry.id === Number(card.dataset.cartItem));
        setQty(Number(card.dataset.cartItem), (item?.qty ?? 0) + 1);

        return;
    }

    const cartMinus = event.target.closest('[data-cart-minus]');

    if (cartMinus) {
        const card = cartMinus.closest('[data-cart-item]');
        if (!card) return;

        const item = getCart().find((entry) => entry.id === Number(card.dataset.cartItem));
        setQty(Number(card.dataset.cartItem), (item?.qty ?? 0) - 1);

        return;
    }

    const removeButton = event.target.closest('[data-remove-item]');

    if (removeButton) {
        const card = removeButton.closest('[data-cart-item]');
        if (card) removeItem(Number(card.dataset.cartItem));

        return;
    }

    if (event.target.closest('[data-clear-cart]')) {
        if (getCart().length > 0 && window.confirm('Kosongkan semua item di keranjang?')) {
            clearCart();
        }

        return;
    }

    const productPlus = event.target.closest('[data-product-plus]');

    if (productPlus) {
        productQty = clamp(productQty + 1, 1, 99);
        updateProductQty();

        return;
    }

    const productMinus = event.target.closest('[data-product-minus]');

    if (productMinus) {
        productQty = clamp(productQty - 1, 1, 99);
        updateProductQty();

        return;
    }

    const productAdd = event.target.closest('[data-product-add]');

    if (productAdd) {
        const root = document.querySelector('[data-product-detail]');
        const item = productFromRoot(root);

        if (!item) return;

        addToCart(item, productQty);
        productQty = 1;
        updateProductQty();

        return;
    }

    if (event.target.closest('[data-check-payment]')) {
        const pending = getPendingOrder();

        if (!pending) {
            showToast('Tidak ada transaksi aktif');
        } else if (Date.now() >= Number(pending.expiresAt)) {
            showToast('Status: pembayaran kedaluwarsa');
        } else {
            showToast('Status: menunggu pembayaran QRIS');
        }
    }
}

/* -------------------------------------------------------------------------- */
/* Inisialisasi                                                               */
/* -------------------------------------------------------------------------- */

document.addEventListener('DOMContentLoaded', () => {
    syncAll();
    initProductDetail();
    initCheckout();
    initPaymentPage();
    initSuccessPage();
});

document.addEventListener('click', handleClick);
