@php
    $formatRupiah = static fn (int $value): string => 'Rp '.number_format($value, 0, ',', '.');
@endphp

<x-layouts.kasir title="Buat Pesanan"
                 active="pesanan-baru"
                 :kasir="$kasir"
                 description="Buat pesanan tunai pelanggan di kasir Kedai Kopi Varya.">
    <div data-kasir-pesanan-baru>
        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
            {{-- Panel menu --}}
            <section class="min-w-0" aria-labelledby="menu-panel-title">
                <h1 id="menu-panel-title" class="sr-only">Buat Pesanan</h1>

                {{-- Pencarian --}}
                <form data-menu-search
                      role="search"
                      onsubmit="return false"
                      class="flex items-center gap-2 rounded-full border-2 border-ink bg-kasir-panel p-1.5 pl-4 shadow-[3px_3px_0_0_#1b1c1d]">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.6" stroke-linecap="round" class="shrink-0 text-ink" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                    </svg>

                    <label for="kasir-menu-search" class="sr-only">Cari produk atau layanan</label>
                    <input id="kasir-menu-search"
                           type="search"
                           data-menu-search-input
                           placeholder="CARI PRODUK ATAU LAYANAN..."
                           autocomplete="off"
                           class="min-w-0 flex-1 bg-transparent font-serif text-[12px] font-semibold text-ink uppercase placeholder:text-muted focus:outline-none">

                    <button type="submit"
                            class="tag shrink-0 rounded-full bg-varya-dark px-6 py-2.5 text-[9px] font-bold text-white transition-colors hover:bg-varya">
                        Cari
                    </button>
                </form>

                {{-- Filter kategori --}}
                <div data-menu-filters class="mt-4 flex flex-wrap gap-2" aria-label="Filter kategori menu">
                    @foreach ($categories as $category)
                        <button type="button"
                                data-menu-filter="{{ $category['slug'] }}"
                                @class([
                                    'tag rounded-full border-2 border-ink px-4 py-2 text-[9px] font-bold shadow-[2px_2px_0_0_#1b1c1d] transition-all duration-150 hover:-translate-y-0.5 sm:px-5',
                                    'bg-varya-dark text-white' => $loop->first,
                                    'bg-kasir-panel text-ink hover:bg-varya-light/60' => ! $loop->first,
                                ])
                                @if ($loop->first) aria-pressed="true" @else aria-pressed="false" @endif>
                            {{ $category['label'] }}
                        </button>
                    @endforeach
                </div>

                {{-- Grid menu --}}
                <div data-menu-grid class="mt-5 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($menus as $menu)
                        <article data-menu-card
                                 data-menu-id="{{ $menu['id'] }}"
                                 data-menu-name="{{ $menu['name'] }}"
                                 data-menu-price="{{ $menu['price'] }}"
                                 data-menu-category="{{ $menu['category'] }}"
                                 data-menu-available="{{ $menu['available'] ? '1' : '0' }}"
                                 data-menu-search="{{ \Illuminate\Support\Str::lower($menu['name'].' '.$menu['description'].' '.$menu['spec']) }}"
                                 @class([
                                     'group flex h-full flex-col overflow-hidden rounded-lg border-2 border-ink bg-kasir-panel shadow-[3px_3px_0_0_#1b1c1d] transition-transform duration-200 hover:-translate-y-0.5',
                                     'opacity-75' => ! $menu['available'],
                                 ])>
                            <div class="relative aspect-[4/3] w-full overflow-hidden border-b-2 border-ink bg-[#e8e2d4]">
                                <img src="{{ asset($menu['image']) }}"
                                     alt="{{ $menu['name'] }}"
                                     loading="lazy"
                                     @class([
                                         'h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]',
                                         'grayscale' => ! $menu['available'],
                                     ])>

                                @if ($menu['featured'])
                                    <span class="absolute top-2 left-2 grid h-7 w-7 -rotate-6 place-items-center rounded-full border-2 border-ink bg-[#f6c445] text-ink shadow-[2px_2px_0_0_#1b1c1d]"
                                          title="Menu terlaris">
                                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="m12 2.6 2.8 5.7 6.3.9-4.6 4.5 1.1 6.3-5.6-3-5.6 3 1.1-6.3L2.9 9.2l6.3-.9L12 2.6Z"/>
                                        </svg>
                                    </span>
                                @endif

                                <span data-menu-unavailable-badge
                                      @class([
                                          'tag absolute inset-x-0 top-1/2 -translate-y-1/2 border-y-2 border-ink bg-brown/95 py-1.5 text-center text-[9px] font-bold tracking-[0.2em] text-white',
                                          'hidden' => $menu['available'],
                                      ])>
                                    Habis
                                </span>

                                <button type="button"
                                        data-add-to-cart
                                        @disabled(! $menu['available'])
                                        class="absolute right-2 bottom-2 grid h-9 w-9 place-items-center rounded-full border-2 border-ink bg-varya-dark text-xl leading-none font-bold text-white shadow-[2px_2px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:border-line-strong disabled:bg-[#d8d5cc] disabled:text-muted disabled:shadow-none"
                                        aria-label="Tambah {{ $menu['name'] }} ke pesanan">
                                    +
                                </button>
                            </div>

                            <div class="flex flex-1 flex-col p-3">
                                <h2 class="font-display text-[14px] leading-tight font-extrabold text-ink">
                                    {{ $menu['name'] }}
                                </h2>
                                <p class="mt-1 line-clamp-2 font-serif text-[11px] leading-relaxed text-ink-soft">
                                    {{ $menu['description'] }}
                                </p>
                                <p class="mt-auto pt-2 font-display text-[15px] leading-none font-bold text-varya-dark">
                                    {{ $formatRupiah($menu['price']) }}
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>

                <p data-menu-empty hidden class="mt-5 rounded-lg border-2 border-dashed border-line-strong bg-kasir-panel py-12 text-center font-serif text-sm text-muted">
                    Menu yang kamu cari belum ada. Coba kata kunci atau kategori lain.
                </p>
            </section>

            {{-- Panel pesanan --}}
            <aside class="kasir-card xl:sticky xl:top-[92px]" aria-labelledby="order-panel-title">
                <div class="border-b-2 border-ink p-4">
                    <h2 id="order-panel-title" class="sr-only">Panel pesanan</h2>

                    <div class="grid grid-cols-2 divide-x-2 divide-line-strong overflow-hidden rounded-md border-2 border-line-strong bg-[#f0eee3]">
                        <label for="customer-name" class="block min-w-0 px-3 py-2.5">
                            <span class="tag block text-[8px] font-bold text-muted">Nama Pelanggan</span>
                            <input id="customer-name"
                                   type="text"
                                   data-customer-name
                                   maxlength="100"
                                   required
                                   placeholder="Nama pelanggan"
                                   autocomplete="off"
                                   class="mt-1 w-full bg-transparent font-display text-[13px] font-extrabold text-ink placeholder:text-muted/70 focus:outline-none">
                        </label>

                        <div class="min-w-0 px-3 py-2.5">
                            <span class="tag block text-[8px] font-bold text-muted">Kasir</span>
                            <p class="mt-1 truncate font-display text-[13px] font-extrabold text-ink">{{ $kasir['short_name'] }}</p>
                        </div>
                    </div>

                    <fieldset class="mt-3">
                        <legend class="sr-only">Tipe pesanan</legend>
                        <div class="grid grid-cols-2 gap-1 rounded-md border-2 border-ink bg-[#f0eee3] p-1">
                            <label class="cursor-pointer">
                                <input type="radio" name="tipe-pesanan" value="dine_in" data-order-type checked class="peer sr-only">
                                <span class="tag block rounded px-3 py-2.5 text-center text-[9px] font-bold text-ink-soft transition-colors peer-checked:bg-ink peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-varya">
                                    Dine In
                                </span>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" name="tipe-pesanan" value="take_away" data-order-type class="peer sr-only">
                                <span class="tag block rounded px-3 py-2.5 text-center text-[9px] font-bold text-ink-soft transition-colors peer-checked:bg-ink peer-checked:text-white peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-varya">
                                    Take Away
                                </span>
                            </label>
                        </div>
                    </fieldset>
                </div>

                <div class="p-4">
                    <div class="flex items-center justify-between gap-2">
                        <p class="tag text-[9px] font-bold text-muted">// Item Pesanan</p>
                        <button type="button" data-clear-cart
                                class="tag text-[8px] font-bold text-rust underline underline-offset-2 transition-colors hover:text-ink">
                            Kosongkan
                        </button>
                    </div>

                    <div data-cart-items class="kasir-scrollbar mt-3 max-h-[310px] space-y-3 overflow-y-auto pr-1"></div>

                    <div data-cart-empty class="mt-3 rounded-md border-2 border-dashed border-line-strong bg-[#f6f4ea] px-4 py-7 text-center">
                        <span class="mx-auto grid h-10 w-10 place-items-center rounded-full border-2 border-ink bg-varya-light text-varya-dark">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6 7h12l1 13H5L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/>
                            </svg>
                        </span>
                        <p class="mt-2 font-display text-sm font-extrabold">Pesanan masih kosong</p>
                        <p class="mt-1 font-serif text-[11px] leading-relaxed text-muted">
                            Klik tombol <span class="font-bold text-varya-dark">+</span> pada menu untuk menambahkan item.
                        </p>
                    </div>

                    <div class="mt-4 border-t-2 border-dashed border-line-strong pt-3">
                        <div class="tag flex items-center justify-between gap-2 text-[9px] text-ink-soft">
                            <span>Subtotal (<span data-cart-count>0</span> Item)</span>
                            <span data-cart-subtotal class="font-bold text-ink">Rp 0</span>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-3 rounded-md border-2 border-ink bg-[#f0eee3] px-3 py-2.5">
                            <span class="tag text-[9px] font-bold">Total Tagihan</span>
                            <span data-cart-total class="font-display text-[19px] leading-none font-black text-varya-dark">Rp 0</span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="tag text-[9px] font-bold text-muted">// Metode Pembayaran</p>

                        <div class="mt-2 flex items-center gap-3 rounded-md border-2 border-ink bg-varya-dark px-4 py-3 text-white">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded border-2 border-white/70 bg-white/10">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="2.5" y="6" width="19" height="12" rx="2"/>
                                    <circle cx="12" cy="12" r="2.6"/><path d="M6 12h.01M18 12h.01"/>
                                </svg>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="tag block text-[9px] font-bold">Tunai</span>
                                <span class="mt-0.5 block font-serif text-[10px] text-white/75">Pembayaran di kasir</span>
                            </span>

                            <span class="grid h-6 w-6 place-items-center rounded-full border-2 border-white/80 bg-white text-varya-dark">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                            </span>
                        </div>

                        <p class="mt-2 font-serif text-[10px] leading-relaxed text-muted">
                            Pesanan yang dibuat kasir hanya mendukung pembayaran tunai.
                        </p>
                    </div>

                    <p data-order-error hidden role="alert"
                       class="mt-3 rounded-md border-2 border-rust bg-[#fbe9e2] px-3 py-2 font-serif text-[11px] leading-relaxed text-rust"></p>

                    <button type="button" data-open-payment
                            class="tag mt-4 flex w-full items-center justify-center gap-2 rounded-md border-2 border-ink bg-varya-dark px-4 py-4 text-[10px] font-bold text-white shadow-[3px_3px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5 active:translate-y-0">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/><path d="M12 7v10M9.5 9.2h4a1.8 1.8 0 0 1 0 3.6h-3a1.8 1.8 0 0 0 0 3.6h4"/>
                        </svg>
                        Proses Bayar
                    </button>
                </div>
            </aside>
        </div>

        {{-- Modal catatan item --}}
        <dialog data-note-dialog class="kasir-dialog" aria-labelledby="note-dialog-title">
            <div class="kasir-dialog-card">
                <div class="kasir-stripes h-3" aria-hidden="true"></div>

                <div class="border-b-2 border-ink p-4">
                    <div class="flex items-start gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded border-2 border-ink bg-varya-dark text-white">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 4h16v13H9l-5 4V4Z"/><path d="M8 9h8M8 13h5"/>
                            </svg>
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="tag text-[8px] font-bold text-muted">// Catatan Pesanan</p>
                            <div class="mt-1 flex flex-wrap items-center gap-2">
                                <h2 id="note-dialog-title" data-note-item-name class="font-display text-lg leading-tight font-extrabold uppercase">
                                    Nama Menu
                                </h2>
                                <span data-note-item-price class="tag rounded border-2 border-ink bg-[#f8d8a6] px-2 py-1 text-[9px] font-bold">
                                    Rp 0
                                </span>
                            </div>
                        </div>

                        <button type="button" data-note-close
                                class="grid h-8 w-8 shrink-0 place-items-center rounded border-2 border-ink bg-kasir-panel transition-colors hover:bg-varya-light"
                                aria-label="Tutup catatan">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        </button>
                    </div>
                </div>

                <form data-note-form>
                    <div class="p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label for="item-note" class="tag text-[8px] font-bold text-muted">// Catatan Khusus Pelanggan</label>
                            <span class="tag rounded border-2 border-line-strong bg-[#f0eee3] px-2 py-1 text-[8px] font-bold text-ink-soft">
                                [ <span data-note-count>0</span> / 150 Karakter ]
                            </span>
                        </div>

                        <textarea id="item-note"
                                  data-note-textarea
                                  rows="5"
                                  maxlength="150"
                                  placeholder="Ketik catatan, misalnya: less sugar, tanpa es, extra shot..."
                                  class="mt-2 w-full resize-none rounded-md border-2 border-ink bg-white px-4 py-3 font-serif text-[13px] leading-relaxed text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-varya/30"></textarea>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t-2 border-ink p-4">
                        <button type="button" data-note-close
                                class="tag rounded border-2 border-ink bg-kasir-panel px-4 py-2.5 text-[9px] font-bold shadow-[2px_2px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5">
                            // Batal
                        </button>

                        <div class="flex flex-wrap items-center gap-2">
                            <span class="tag rounded border-2 border-ink bg-[#f8d8a6] px-3 py-2 text-[8px] font-bold">
                                // Total: <span data-note-total>Rp 0</span>
                            </span>
                            <button type="submit"
                                    class="tag flex items-center gap-1.5 rounded border-2 border-ink bg-varya-dark px-4 py-2.5 text-[9px] font-bold text-white shadow-[2px_2px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                                Simpan Catatan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </dialog>

        {{-- Modal pembayaran tunai --}}
        <dialog data-payment-dialog class="kasir-dialog !max-w-[740px]" aria-labelledby="payment-dialog-title">
            <div class="kasir-dialog-card">
                <div class="kasir-stripes h-3" aria-hidden="true"></div>

                <div class="flex items-center justify-between gap-3 border-b-2 border-ink px-5 py-3">
                    <h2 id="payment-dialog-title" class="font-display text-lg font-black uppercase">Kalkulator Pembayaran</h2>
                    <button type="button" data-payment-close
                            class="grid h-8 w-8 place-items-center rounded border-2 border-ink bg-kasir-panel transition-colors hover:bg-varya-light"
                            aria-label="Tutup kalkulator pembayaran">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </div>

                <div class="grid gap-4 p-4 sm:grid-cols-[1fr_1.08fr] sm:p-5">
                    <div class="space-y-3">
                        <div class="rounded-md border-2 border-ink bg-kasir-panel px-4 py-3">
                            <p class="tag text-[8px] font-bold text-muted">Total Tagihan</p>
                            <p class="mt-1 font-display text-[25px] leading-none font-black text-ink">
                                Rp <span data-payment-total>0</span>
                            </p>
                        </div>

                        <div class="rounded-md border-2 border-ink bg-kasir-panel px-4 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="tag text-[8px] font-bold text-muted">Uang Diterima</p>
                                <button type="button" data-payment-reset
                                        class="tag text-[8px] font-bold text-rust transition-colors hover:text-ink">
                                    Reset [C]
                                </button>
                            </div>
                            <p class="mt-1 font-mono text-[25px] leading-none font-bold text-varya-dark">
                                Rp <span data-payment-cash>0</span><span class="payment-caret" aria-hidden="true"></span>
                            </p>
                        </div>

                        <div class="rounded-md border-2 border-ink bg-[#e6f0e2] px-4 py-3">
                            <p class="tag text-[8px] font-bold text-varya-dark">Uang Kembalian</p>
                            <p data-payment-change class="mt-1 font-mono text-[25px] leading-none font-bold text-varya-dark">Rp 0</p>
                        </div>

                        <p data-payment-error hidden role="alert"
                           class="rounded-md border-2 border-rust bg-[#fbe9e2] px-3 py-2 font-serif text-[11px] leading-relaxed text-rust"></p>
                    </div>

                    <div class="rounded-md border-2 border-ink bg-kasir-panel p-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="tag text-[8px] font-bold text-muted">Keypad Tunai</p>
                            <button type="button" data-payment-backspace
                                    class="tag inline-flex items-center gap-1 rounded border-2 border-ink bg-kasir-panel px-2 py-1 text-[8px] font-bold transition-colors hover:bg-varya-light">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 6H9l-6 6 6 6h11V6Z"/><path d="m12 10 4 4M16 10l-4 4"/>
                                </svg>
                                Hapus
                            </button>
                        </div>

                        <div data-payment-keypad class="mt-3 grid grid-cols-3 gap-2">
                            @foreach (['7', '8', '9', '4', '5', '6', '1', '2', '3', 'C', '0', '000'] as $key)
                                <button type="button"
                                        data-payment-key="{{ $key }}"
                                        @class([
                                            'tag h-11 rounded border-2 border-ink font-mono text-[14px] font-bold shadow-[2px_2px_0_0_#1b1c1d] transition-transform active:translate-y-0.5 sm:h-12',
                                            'bg-[#f7d7d3] text-rust' => $key === 'C',
                                            'bg-[#dceaf7] text-ink' => $key === '000',
                                            'bg-white text-ink' => ! in_array($key, ['C', '000'], true),
                                        ])>
                                    {{ $key }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="border-t-2 border-ink p-4">
                    <button type="button" data-payment-confirm
                            class="tag flex w-full items-center justify-center gap-2 rounded-md border-2 border-ink bg-varya-dark px-4 py-3.5 text-[10px] font-bold text-white shadow-[3px_3px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                        <span data-payment-label>Selesai Bayar</span>
                    </button>
                </div>
            </div>
        </dialog>

        {{-- Modal konfirmasi berhasil --}}
        <dialog data-success-dialog class="kasir-dialog" aria-labelledby="success-dialog-title">
            <div class="kasir-dialog-card">
                <div class="kasir-stripes h-3" aria-hidden="true"></div>

                <div class="p-5 text-center sm:p-6">
                    <span class="mx-auto grid h-14 w-14 place-items-center rounded-full border-[3px] border-ink bg-varya-light text-varya-dark shadow-[3px_3px_0_0_#1b1c1d]">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                    </span>

                    <h2 id="success-dialog-title" class="mt-4 font-display text-2xl leading-tight font-black uppercase">
                        Pesanan Berhasil Dibuat!
                    </h2>

                    <div class="mt-5 rounded-md border-2 border-ink bg-kasir-panel p-4 text-left">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="tag text-[8px] font-bold text-muted">No. Antrian</p>
                                <p data-success-queue class="mt-1 font-mono text-[30px] leading-none font-bold text-varya-dark">#000</p>
                            </div>
                            <div class="text-right">
                                <p class="tag text-[8px] font-bold text-muted">Tipe Layanan</p>
                                <span data-success-type class="tag mt-1 inline-block rounded border-2 border-ink bg-ink px-2.5 py-1 text-[8px] font-bold text-white">
                                    Dine In
                                </span>
                            </div>
                        </div>

                        <div class="my-3 border-t-2 border-dashed border-line-strong" aria-hidden="true"></div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="min-w-0">
                                <p class="tag text-[8px] font-bold text-muted">Pelanggan</p>
                                <p data-success-customer class="mt-1 truncate font-display text-[13px] font-extrabold">—</p>
                            </div>
                            <div class="min-w-0 text-right">
                                <p class="tag text-[8px] font-bold text-muted">Kasir Bertugas</p>
                                <p class="mt-1 truncate font-display text-[13px] font-extrabold">{{ $kasir['name'] }}</p>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-3 rounded border-2 border-line-strong bg-[#f6f4ea] px-3 py-2.5">
                            <div>
                                <p class="tag text-[8px] font-bold text-muted">Metode</p>
                                <p class="mt-0.5 font-display text-[12px] font-extrabold">Tunai</p>
                            </div>
                            <div class="text-right">
                                <p class="tag text-[8px] font-bold text-muted">Total Tagihan</p>
                                <p data-success-total class="mt-0.5 font-display text-[17px] leading-none font-black text-varya-dark">Rp 0</p>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                            <span data-success-items class="tag text-[8px] text-muted">0 Menu</span>
                            <span data-success-time class="tag text-[8px] text-muted">—</span>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-2 sm:grid-cols-2">
                        <a data-success-orders href="{{ route('kasir.pesanan.index') }}"
                           class="tag flex items-center justify-center rounded-md border-2 border-ink bg-kasir-panel px-4 py-3 text-[9px] font-bold text-ink shadow-[2px_2px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5">
                            Lihat Pesanan Masuk
                        </a>
                        <button type="button" data-success-close
                                class="tag flex items-center justify-center rounded-md border-2 border-ink bg-varya-dark px-4 py-3 text-[9px] font-bold text-white shadow-[2px_2px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5">
                            Buat Pesanan Baru
                        </button>
                    </div>
                </div>
            </div>
        </dialog>
    </div>
</x-layouts.kasir>
