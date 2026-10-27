<x-layouts.pelanggan-b title="Pesanan Berhasil"
                       description="Tiket antrean digital dan rincian pesanan Kedai Kopi Varya.">
    <section data-success-banner hidden class="bg-varya-dark text-white">
        <div class="mx-auto flex max-w-[1280px] flex-col gap-5 px-5 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-8">
            <div class="flex items-center gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg border-[3px] border-white bg-white text-varya-dark shadow-[4px_4px_0_0_#1b1c1d]">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                </span>
                <div>
                    <h1 class="title-xl text-3xl sm:text-4xl">Pesanan Berhasil Dibuat!</h1>
                    <p class="mt-1 font-serif text-sm text-white/80">
                        Terima kasih, <span data-success-name class="font-bold text-white">Pelanggan</span>.
                        Pesananmu sudah diteruskan ke barista &amp; kasir Kedai Kopi Varya.
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-3 rounded-lg border-2 border-white/30 bg-white/10 px-4 py-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-full border border-white/40">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                    </svg>
                </span>
                <div>
                    <p class="tag text-[8px] text-white/70">Estimasi Tunggu</p>
                    <p class="font-display text-lg leading-tight font-bold">8 - 12 Menit</p>
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto max-w-[1280px] px-5 py-8 sm:px-8">
        {{-- Tampilan bila belum ada pesanan --}}
        <div data-success-empty hidden class="brut mx-auto max-w-2xl bg-surface px-6 py-14 text-center">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border-[3px] border-ink bg-varya-light">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h5"/>
                </svg>
            </span>
            <h2 class="product-name mt-4 text-xl">Belum ada pesanan</h2>
            <p class="mx-auto mt-2 max-w-md font-serif text-sm text-ink-soft">
                Tiket antrean akan muncul di sini setelah kamu menyelesaikan pembayaran.
            </p>
            <a href="{{ route('pelanggan-b.beranda') }}#menu"
               class="tag mt-6 inline-flex items-center justify-center gap-2 rounded-md border-[3px] border-ink bg-varya-dark px-6 py-3.5 text-[10px] font-bold text-white shadow-[3px_3px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5">
                Pesan Menu
            </a>
        </div>

        <div data-success-content hidden class="grid gap-6 lg:grid-cols-[1.25fr_1fr] lg:items-start">
            {{-- Tiket antrean --}}
            <article class="brut overflow-hidden bg-surface" aria-labelledby="ticket-title">
                <div class="flex items-center gap-2 border-b-[3px] border-ink bg-[#f1f0f1] px-5 py-3.5">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-varya-dark" aria-hidden="true">
                        <path d="M3 9a2 2 0 0 0 0 6v3h18v-3a2 2 0 0 1 0-6V6H3v3Z"/><path d="M13 6v12" stroke-dasharray="2 3"/>
                    </svg>
                    <h2 id="ticket-title" class="product-name text-sm">Tiket Antrean Digital</h2>
                </div>

                <div class="p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-5 border-b-2 border-dashed border-line-strong pb-5">
                        <div>
                            <p class="tag text-[9px] text-muted">Nomor Panggilan</p>
                            <p class="mt-1 flex flex-wrap items-end gap-2">
                                <span data-ticket-number class="font-display text-5xl leading-none font-black text-varya-dark sm:text-6xl">#00</span>
                                <span data-ticket-type-short class="tag mb-1 text-[9px] font-bold text-ink">/ MAKAN DI TEMPAT</span>
                            </p>
                        </div>

                        <div class="text-left sm:text-right">
                            <p class="tag text-[9px] text-muted">Kode ID Pesanan</p>
                            <p data-ticket-code class="tag mt-1 inline-block border-2 border-ink bg-surface px-3 py-1.5 text-[10px] font-bold text-ink">
                                ABCD1234
                            </p>
                            <p data-ticket-date class="tag mt-1.5 text-[8px] text-muted">20 Okt 2026, 17:18 WIB</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-5 border-b-2 border-dashed border-line-strong py-5">
                        <div>
                            <p class="tag text-[9px] text-muted">Identitas Pemesan</p>
                            <p class="mt-1 flex items-center gap-2 font-display text-base font-bold">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-varya-dark" aria-hidden="true">
                                    <circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>
                                </svg>
                                <span data-ticket-name>Pelanggan</span>
                            </p>
                        </div>

                        <div>
                            <p class="tag text-[9px] text-muted">Tipe Pesanan</p>
                            <p class="mt-1 flex items-center gap-2 font-display text-base font-bold">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-varya-dark" aria-hidden="true">
                                    <path d="M4 8h16M6 8v12h12V8"/><path d="M9 8V5h6v3"/>
                                </svg>
                                <span data-ticket-type>Makan di Tempat</span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 rounded-lg border-2 border-varya-muted/40 bg-varya-light px-4 py-3.5">
                        <p class="tag flex items-center gap-2 text-[9px] font-bold text-varya-dark">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>
                            </svg>
                            Pemberitahuan Pengambilan Pesanan
                        </p>
                        <p class="mt-1.5 font-serif text-[12px] leading-relaxed text-ink-soft">
                            Harap perhatikan nomor panggilan <strong class="text-ink" data-ticket-number-inline>#00</strong>.
                            Saat pesanan selesai, nomor antrean atau namamu akan dipanggil oleh barista.
                        </p>
                    </div>

                    <div data-ticket-catatan-wrap class="mt-4 rounded-lg border-2 border-line-strong bg-[#f7f6f7] px-4 py-3.5">
                        <p class="tag flex items-center gap-2 text-[9px] font-bold text-ink">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 4h16v12l-4 4H4z"/><path d="M8 9h8M8 13h5"/>
                            </svg>
                            Catatan Khusus untuk Barista
                        </p>
                        <p data-ticket-catatan class="mt-1.5 font-serif text-[12px] leading-relaxed text-ink-soft">
                            Catatan pesanan.
                        </p>
                    </div>
                </div>
            </article>

            {{-- Rincian pesanan --}}
            <article class="brut overflow-hidden bg-surface" aria-labelledby="ticket-summary-title">
                <div class="flex items-center justify-between gap-3 border-b-[3px] border-ink bg-ink px-5 py-3.5 text-white">
                    <div class="flex items-center gap-2">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h8"/>
                        </svg>
                        <h2 id="ticket-summary-title" class="product-name text-sm">Rincian Item Pesanan</h2>
                    </div>
                    <span class="tag rounded-full bg-varya px-3 py-1 text-[8px] font-bold text-white">
                        <span data-ticket-item-count>0</span> Item
                    </span>
                </div>

                <div class="p-5">
                    <div data-ticket-items class="space-y-3"></div>

                    <div class="mt-5 border-t-2 border-dashed border-line-strong pt-4">
                        <div class="tag flex items-center justify-between text-[9px] text-ink-soft">
                            <span>Subtotal</span>
                            <span data-ticket-subtotal>Rp 0</span>
                        </div>
                        <div class="mt-2 flex items-end justify-between gap-3">
                            <span class="tag text-[10px] text-ink">Total Pembayaran</span>
                            <span data-ticket-total class="font-display text-2xl leading-none font-black text-varya-dark">Rp 0</span>
                        </div>
                        <div class="mt-3 flex items-center justify-end gap-2">
                            <span class="tag text-[8px] text-muted">Status</span>
                            <span class="tag inline-flex items-center gap-1.5 rounded-full bg-varya-light px-3 py-1 text-[8px] font-bold text-varya-dark">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                                Lunas via QRIS
                            </span>
                        </div>
                    </div>

                    <a href="{{ route('pelanggan-b.beranda') }}#menu"
                       class="tag mt-5 flex w-full items-center justify-center gap-2 rounded-md border-[3px] border-ink bg-surface px-5 py-4 text-[10px] font-bold text-ink shadow-[3px_3px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 7h16v13H4z"/><path d="M8 7V4h8v3"/><path d="M8 12h8"/>
                        </svg>
                        Pesan Menu Lain
                    </a>
                </div>
            </article>
        </div>
    </div>
</x-layouts.pelanggan-b>
