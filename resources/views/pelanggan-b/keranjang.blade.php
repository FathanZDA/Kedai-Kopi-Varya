<x-layouts.pelanggan-b title="Keranjang"
                       description="Keranjang pesanan Kedai Kopi Varya. Periksa item dan lanjutkan ke pembayaran QRIS.">
    <div data-cart-page>
        <div class="mx-auto max-w-[1280px] px-5 pt-6 sm:px-8">
            <nav class="flex flex-wrap items-center gap-2" aria-label="Breadcrumb">
                <a href="{{ route('pelanggan-b.beranda') }}#menu"
                   class="tag inline-flex items-center gap-2 border-2 border-line-strong bg-surface px-4 py-2 text-[9px] font-bold text-ink transition-colors hover:border-ink">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m0 0 6 6m-6-6 6-6"/></svg>
                    Katalog Menu
                </a>
                <span class="tag text-[9px] text-muted">//</span>
                <span class="tag text-[9px] text-muted">
                    Keranjang Belanja (<span data-cart-breadcrumb-count>0</span> Item)
                </span>
            </nav>
        </div>

        <div class="mx-auto max-w-[1280px] px-5 pt-5 pb-10 sm:px-8">
            <div class="grid gap-6 lg:grid-cols-[1.35fr_1fr] lg:items-start">
                <section aria-labelledby="cart-title">
                    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p class="tag text-[10px] text-varya">Pesananmu</p>
                            <h1 id="cart-title" class="title-xl mt-1 text-3xl sm:text-4xl">Keranjang</h1>
                        </div>

                        <button type="button" data-clear-cart
                                class="tag text-[9px] font-bold text-rust underline underline-offset-4 transition-colors hover:text-ink">
                            Kosongkan Keranjang
                        </button>
                    </div>

                    {{-- Item keranjang dirender oleh pelanggan-b.js --}}
                    <div data-cart-items class="space-y-4"></div>

                    {{-- Tampilan saat keranjang kosong --}}
                    <div data-cart-empty hidden class="brut bg-surface px-6 py-14 text-center">
                        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border-[3px] border-ink bg-varya-light">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6 7h12l1 13H5L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/>
                            </svg>
                        </span>
                        <h2 class="product-name mt-4 text-xl">Keranjang masih kosong</h2>
                        <p class="mx-auto mt-2 max-w-sm font-serif text-sm text-ink-soft">
                            Pilih menu favoritmu dulu, lalu atur jumlahnya di sini.
                        </p>
                        <a href="{{ route('pelanggan-b.beranda') }}#menu"
                           class="tag mt-6 inline-flex items-center justify-center gap-2 rounded-md border-[3px] border-ink bg-varya-dark px-6 py-3.5 text-[10px] font-bold text-white shadow-[3px_3px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5">
                            Lihat Menu
                        </a>
                    </div>
                </section>

                <x-pelanggan-b.order-summary source="cart" title="Ringkasan Pesanan" :store="$store">
                    <x-slot:cta>
                        <a data-summary-cta href="{{ route('pelanggan-b.checkout') }}"
                           class="tag flex w-full items-center justify-center gap-2 rounded-md border-[3px] border-ink bg-ink px-5 py-4 text-[10px] font-bold text-white shadow-[3px_3px_0_0_#115231] transition-transform hover:-translate-y-0.5">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 6h18v12H3z"/><path d="M3 10h18"/>
                            </svg>
                            Lanjut ke Pembayaran
                            <span class="rounded-full bg-white/15 px-2 py-0.5"><span data-summary-count>0</span> Item</span>
                        </a>

                        <p class="mt-3 flex items-start gap-2 font-serif text-[11px] leading-relaxed text-muted">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0" aria-hidden="true">
                                <path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3Z"/><path d="m9 12 2 2 4-4"/>
                            </svg>
                            Pembayaran QRIS diproses aman melalui Midtrans (Sandbox).
                        </p>
                    </x-slot:cta>
                </x-pelanggan-b.order-summary>
            </div>
        </div>
    </div>
</x-layouts.pelanggan-b>
