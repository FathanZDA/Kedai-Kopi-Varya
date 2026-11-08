<x-layouts.pelanggan-b title="Pembayaran QRIS"
                       description="Halaman pembayaran QRIS Kedai Kopi Varya melalui Midtrans Sandbox.">
    <div data-payment-page class="pb-10">
        <div class="mx-auto max-w-[1280px] px-5 pt-6 sm:px-8">
            <nav class="flex flex-wrap items-center gap-2" aria-label="Breadcrumb">
                <a href="{{ route('pelanggan-b.checkout') }}"
                   class="tag inline-flex items-center gap-2 border-2 border-line-strong bg-surface px-4 py-2 text-[9px] font-bold text-ink transition-colors hover:border-ink">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m0 0 6 6m-6-6 6-6"/></svg>
                    Kembali ke Checkout
                </a>
                <span class="tag text-[9px] text-muted">//</span>
                <span class="tag text-[9px] text-muted">Pembayaran</span>
            </nav>

            {{-- Tampilan bila tidak ada transaksi aktif --}}
            <div data-payment-empty hidden class="brut mt-6 bg-surface px-6 py-14 text-center">
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border-[3px] border-ink bg-varya-light">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/>
                    </svg>
                </span>
                <h1 class="product-name mt-4 text-xl">Belum ada transaksi aktif</h1>
                <p class="mx-auto mt-2 max-w-md font-serif text-sm text-ink-soft">
                    Selesaikan data pemesanan dulu, lalu halaman pembayaran QRIS akan terbuka otomatis.
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('pelanggan-b.checkout') }}"
                       class="tag inline-flex items-center justify-center rounded-md border-[3px] border-ink bg-varya-dark px-6 py-3.5 text-[10px] font-bold text-white shadow-[3px_3px_0_0_#1b1c1d]">
                        Ke Checkout
                    </a>
                    <a href="{{ route('pelanggan-b.beranda') }}#menu"
                       class="tag inline-flex items-center justify-center rounded-md border-[3px] border-ink bg-surface px-6 py-3.5 text-[10px] font-bold text-ink">
                        Lihat Menu
                    </a>
                </div>
            </div>

            <div data-payment-content class="mt-5 grid gap-6 lg:grid-cols-[1.35fr_1fr] lg:items-start">
                {{-- Kartu QRIS --}}
                <section class="brut overflow-hidden bg-surface" aria-labelledby="payment-title">
                    <div class="flex items-center justify-between gap-3 border-b-[3px] border-ink bg-varya-dark px-5 py-4 text-white">
                        <div>
                            <p class="tag text-[9px] text-white/70">Pembayaran</p>
                            <h1 id="payment-title" class="product-name mt-0.5 text-xl">QRIS Midtrans</h1>
                        </div>
                        <span class="tag rounded-full border border-white/30 bg-white/10 px-3 py-1 text-[8px] font-bold">Belum Aktif</span>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div class="mx-auto max-w-md">
                            <div class="text-center">
                                <p class="tag text-[9px] text-muted">Merchant Resmi</p>
                                <p class="product-name mt-1 text-lg">Kedai Kopi Varya</p>
                                <p class="tag mt-1 text-[8px] text-muted">
                                    Kode Pesanan: <span data-payment-code class="font-bold text-ink">ABCD1234</span>
                                </p>
                            </div>

                            <div class="mt-4 rounded-lg border-2 border-ink bg-canvas px-4 py-3 text-center">
                                <p class="tag text-[9px] text-muted">Total Pembayaran</p>
                                <p data-payment-total class="font-display text-3xl leading-tight font-black text-varya-dark">Rp 0</p>
                            </div>

                            <div class="mt-5 rounded-xl border-[3px] border-dashed border-line-strong bg-canvas p-6 text-center">
                                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full border-2 border-ink bg-white">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/>
                                    </svg>
                                </span>
                                <p class="product-name mt-3 text-base">QRIS Midtrans belum terpasang</p>
                                <p class="mx-auto mt-2 max-w-sm font-serif text-[12px] leading-relaxed text-ink-soft">
                                    Integrasi pembayaran QRIS sedang disiapkan. Pesanan ini masih berupa draft dan
                                    belum memiliki nomor antrean. Status lunas hanya akan diperbarui oleh webhook Midtrans.
                                </p>
                            </div>

                            <p class="mx-auto mt-4 max-w-sm text-center font-serif text-[12px] leading-relaxed text-ink-soft">
                                Setelah integrasi aktif, halaman ini akan menampilkan QRIS resmi dari Midtrans Sandbox.
                            </p>

                            <div class="mt-4 flex flex-wrap justify-center gap-1.5">
                                @foreach (['GoPay', 'OVO', 'ShopeePay', 'DANA', 'BCA'] as $channel)
                                    <span class="tag rounded border border-line-strong bg-canvas px-2 py-1 text-[7px] font-bold text-ink-soft">{{ $channel }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Ringkasan + status --}}
                <x-pelanggan-b.order-summary source="pending" title="Ringkasan Pembayaran" badge-label="Item" :show-store="false">
                    <x-slot:cta>
                        <div class="rounded-lg border-2 border-dashed border-line-strong bg-canvas px-4 py-3 text-center">
                            <p class="tag text-[9px] text-muted">Selesaikan dalam</p>
                            <p data-payment-countdown class="font-display text-3xl leading-tight font-black text-ink">15:00</p>
                            <p data-payment-status class="tag mt-1 inline-flex items-center gap-1.5 text-[9px] font-bold text-varya-dark">
                                <span data-payment-status-dot class="inline-block h-2 w-2 rounded-full bg-varya"></span>
                                <span data-payment-status-text>Menunggu pembayaran</span>
                            </p>
                        </div>

                        <div class="mt-3 rounded-lg border-2 border-dashed border-line-strong bg-canvas p-3.5">
                            <p class="tag text-[8px] font-bold text-muted">Menunggu Integrasi</p>
                            <p class="mt-1 font-serif text-[11px] leading-relaxed text-ink-soft">
                                Pembayaran QRIS belum dapat diproses. Draft pesanan ini tidak akan ditandai lunas
                                dan tidak mendapat nomor antrean sampai webhook Midtrans aktif.
                            </p>
                        </div>

                        <button type="button" data-check-payment
                                class="tag mt-3 flex w-full items-center justify-center gap-2 rounded-md border-2 border-ink bg-surface px-4 py-3 text-[9px] font-bold text-ink transition-colors hover:bg-varya-light">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v6h-6"/>
                            </svg>
                            Cek Status Pembayaran
                        </button>

                        <a href="{{ route('pelanggan-b.checkout') }}"
                           class="tag mt-3 block text-center text-[8px] text-muted underline underline-offset-4 hover:text-rust">
                            Batalkan &amp; kembali ke checkout
                        </a>
                    </x-slot:cta>
                </x-pelanggan-b.order-summary>
            </div>
        </div>
    </div>
</x-layouts.pelanggan-b>
