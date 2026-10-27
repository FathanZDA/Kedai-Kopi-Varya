<x-layouts.pelanggan-b title="Informasi Pemesanan"
                       description="Lengkapi data pesanan Kedai Kopi Varya dan lanjutkan pembayaran QRIS.">
    <div data-checkout-page class="pb-10">
        <div class="mx-auto max-w-[1280px] px-5 pt-6 sm:px-8">
            <a href="{{ route('pelanggan-b.keranjang') }}"
               class="tag inline-flex items-center gap-2 border-2 border-line-strong bg-surface px-4 py-2 text-[9px] font-bold text-ink transition-colors hover:border-ink">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m0 0 6 6m-6-6 6-6"/></svg>
                Kembali ke Keranjang
            </a>

            <div class="brut mt-5 bg-surface px-5 py-5 sm:px-8 sm:py-6">
                <p class="tag text-[10px] text-varya">Langkah 2 dari 3</p>
                <h1 class="title-xl mt-1 text-4xl sm:text-5xl">Informasi Pemesanan</h1>
            </div>
        </div>

        <div class="mx-auto max-w-[1280px] px-5 pt-6 sm:px-8">
            <div data-checkout-empty
                 hidden
                 class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-lg border-2 border-dashed border-rust/50 bg-[#fff5f0] px-4 py-3">
                <p class="font-serif text-sm text-rust">Keranjang masih kosong. Pilih menu dulu sebelum checkout.</p>
                <a href="{{ route('pelanggan-b.beranda') }}#menu" class="tag text-[9px] font-bold text-rust underline underline-offset-4">
                    Lihat Menu
                </a>
            </div>

            <form id="checkout-form"
                  action="{{ route('pelanggan-b.pembayaran') }}"
                  method="GET"
                  class="grid gap-6 lg:grid-cols-[1.35fr_1fr] lg:items-start"
                  novalidate>
                {{-- Data pemesanan --}}
                <section class="brut bg-surface" aria-labelledby="data-pemesanan-title">
                    <div class="flex items-center gap-2 border-b-[3px] border-ink bg-[#f1f0f1] px-5 py-3">
                        <span class="h-3 w-3 rounded-full border border-ink bg-rust"></span>
                        <span class="h-3 w-3 rounded-full border border-ink bg-[#e0b64a]"></span>
                        <span class="h-3 w-3 rounded-full border border-ink bg-varya"></span>
                        <h2 id="data-pemesanan-title" class="product-name ml-1 text-sm">Data Pemesanan</h2>
                    </div>

                    <div class="space-y-6 p-5 sm:p-6">
                        {{-- Nama --}}
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <label for="customer-name" class="tag text-[10px] font-bold text-ink">
                                    Nama Lengkap <span class="text-rust">*</span>
                                </label>
                                <span class="tag rounded bg-[#efedee] px-2 py-1 text-[8px] text-muted">Wajib Diisi</span>
                            </div>
                            <input id="customer-name"
                                   type="text"
                                   name="customer_name"
                                   data-customer-name
                                   autocomplete="name"
                                   placeholder="Contoh: Fathan Al-Ghifari"
                                   class="mt-2 w-full rounded-md border-[3px] border-ink bg-surface px-4 py-3 font-serif text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-varya/30">
                            <p data-name-error hidden class="mt-1.5 font-serif text-[11px] text-rust">
                                Nama wajib diisi sebelum melanjutkan pembayaran.
                            </p>
                        </div>

                        {{-- Tipe pesanan --}}
                        <fieldset>
                            <legend class="tag text-[10px] font-bold text-ink">
                                Tipe Pesanan <span class="text-rust">*</span>
                            </legend>

                            <div class="mt-2 grid grid-cols-2 gap-3">
                                <label class="relative cursor-pointer">
                                    <input type="radio" name="order_type" value="Makan di Tempat" data-order-type checked class="peer sr-only">
                                    <span class="flex items-center gap-2.5 rounded-md border-[3px] border-ink bg-surface px-4 py-3 transition-all peer-checked:border-varya-dark peer-checked:bg-varya-light peer-checked:shadow-[3px_3px_0_0_#115231] peer-focus-visible:ring-2 peer-focus-visible:ring-varya/40 [&_.radio-dot]:bg-transparent peer-checked:[&_.radio-dot]:bg-varya-dark">
                                        <span class="grid h-4 w-4 shrink-0 place-items-center rounded-full border-2 border-ink">
                                            <span class="radio-dot h-1.5 w-1.5 rounded-full bg-transparent"></span>
                                        </span>
                                        <span class="tag text-[9px] leading-tight font-bold sm:text-[10px]">Makan di Tempat</span>
                                    </span>
                                </label>

                                <label class="relative cursor-pointer">
                                    <input type="radio" name="order_type" value="Bawa Pulang" data-order-type class="peer sr-only">
                                    <span class="flex items-center gap-2.5 rounded-md border-[3px] border-ink bg-surface px-4 py-3 transition-all peer-checked:border-varya-dark peer-checked:bg-varya-light peer-checked:shadow-[3px_3px_0_0_#115231] peer-focus-visible:ring-2 peer-focus-visible:ring-varya/40 [&_.radio-dot]:bg-transparent peer-checked:[&_.radio-dot]:bg-varya-dark">
                                        <span class="grid h-4 w-4 shrink-0 place-items-center rounded-full border-2 border-ink">
                                            <span class="radio-dot h-1.5 w-1.5 rounded-full bg-transparent"></span>
                                        </span>
                                        <span class="tag text-[9px] leading-tight font-bold sm:text-[10px]">Bawa Pulang</span>
                                    </span>
                                </label>
                            </div>
                        </fieldset>

                        {{-- Metode pembayaran --}}
                        <fieldset>
                            <legend class="tag text-[10px] font-bold text-ink">
                                Metode Pembayaran <span class="text-rust">*</span>
                            </legend>

                            <div class="mt-2 flex items-start gap-3 rounded-md border-[3px] border-varya-dark bg-[#eef4ea] p-4 shadow-[3px_3px_0_0_#115231]">
                                <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full border-2 border-varya-dark bg-varya-dark text-white">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="product-name text-sm">QRIS</p>
                                        <span class="tag rounded bg-varya-dark px-2 py-0.5 text-[8px] font-bold text-white">Midtrans Sandbox</span>
                                    </div>
                                    <p class="mt-1 font-serif text-[12px] leading-relaxed text-ink-soft">
                                        BCA, GoPay, OVO, ShopeePay &amp; aplikasi QRIS lainnya.
                                    </p>
                                </div>

                                <input type="hidden" name="payment_method" value="QRIS">
                            </div>

                            <div class="mt-3 flex items-start gap-3 rounded-md border-2 border-dashed border-line-strong bg-canvas p-3.5">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0 text-varya-dark" aria-hidden="true">
                                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM20 14v3M14 20h3M20 19v2"/>
                                </svg>
                                <p class="font-serif text-[12px] leading-relaxed text-ink-soft">
                                    QRIS dinamis akan muncul di halaman pembayaran Midtrans setelah kamu menekan tombol
                                    <strong class="font-bold text-ink">Bayar dengan QRIS</strong>. Status lunas diperbarui otomatis.
                                </p>
                            </div>
                        </fieldset>

                        {{-- Catatan --}}
                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <label for="order-catatan" class="tag text-[10px] font-bold text-ink">Catatan</label>
                                <span class="tag text-[8px] text-muted">(Opsional)</span>
                            </div>
                            <textarea id="order-catatan"
                                      name="catatan"
                                      data-catatan
                                      rows="3"
                                      placeholder="Contoh: Meja No. 05, gula sedikit saja / less ice &amp; sedotan kertas."
                                      class="mt-2 w-full resize-none rounded-md border-[3px] border-ink bg-surface px-4 py-3 font-serif text-sm text-ink placeholder:text-muted focus:outline-none focus:ring-2 focus:ring-varya/30"></textarea>
                        </div>
                    </div>
                </section>

                {{-- Ringkasan --}}
                <x-pelanggan-b.order-summary source="cart" title="Pesanan Anda" :store="$store">
                    <x-slot:cta>
                        <button type="submit" data-summary-cta
                                class="tag flex w-full items-center justify-center gap-2 rounded-md border-[3px] border-ink bg-ink px-5 py-4 text-[10px] font-bold text-white shadow-[3px_3px_0_0_#115231] transition-transform hover:-translate-y-0.5">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19"/>
                            </svg>
                            Bayar dengan QRIS
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m0 0-6-6m6 6-6 6"/></svg>
                        </button>

                        <p class="mt-3 flex items-start gap-2 font-serif text-[11px] leading-relaxed text-muted">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0" aria-hidden="true">
                                <path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3Z"/><path d="m9 12 2 2 4-4"/>
                            </svg>
                            Kamu akan diarahkan ke halaman pembayaran QRIS Midtrans. Status pesanan diperbarui otomatis.
                        </p>
                    </x-slot:cta>
                </x-pelanggan-b.order-summary>
            </form>
        </div>
    </div>
</x-layouts.pelanggan-b>
