@php
    $unpaidCount = collect($orders)->where('status', 'menunggu_pembayaran')->count();
    $queueCount = collect($orders)->where('status', 'antre')->count();
    $processCount = collect($orders)->where('status', 'diproses')->count();
    $doneCount = collect($orders)->where('status', 'selesai')->count();
@endphp

<x-layouts.kasir title="Pesanan Masuk"
                 active="pesanan-masuk"
                 :kasir="$kasir"
                 :next-queue="$nextQueue"
                 description="Pantau pesanan QRIS dan tunai yang masuk ke kasir Kedai Kopi Varya.">
    <div data-kasir-pesanan-masuk>
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="tag text-[9px] font-bold text-varya-dark">// Antrean Produksi</p>
                <h1 class="kasir-title mt-1 text-3xl sm:text-[38px]">Pesanan Masuk</h1>
            </div>

            <div class="flex items-center gap-2 rounded-full border-2 border-line-strong bg-kasir-panel px-3 py-2">
                <span class="h-2 w-2 animate-pulse rounded-full bg-varya" aria-hidden="true"></span>
                <p class="tag text-[8px] font-bold text-ink-soft">Status QRIS diperbarui otomatis</p>
            </div>
        </div>

        <div class="kasir-stripe-divider mt-3 mb-5" aria-hidden="true"></div>

        {{-- Toolbar --}}
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div data-order-tabs
                 class="flex flex-wrap items-center gap-1 rounded-full border-2 border-ink bg-kasir-panel p-1.5 shadow-[2px_2px_0_0_#1b1c1d]"
                 role="tablist"
                 aria-label="Filter status pesanan">
                @php
                    $tabs = [
                        ['key' => 'all', 'label' => 'Semua', 'count' => count($orders)],
                        ['key' => 'menunggu_pembayaran', 'label' => 'Belum Dibayar', 'count' => $unpaidCount],
                        ['key' => 'antre', 'label' => 'Antrean', 'count' => $queueCount],
                        ['key' => 'diproses', 'label' => 'Diproses', 'count' => $processCount],
                        ['key' => 'selesai', 'label' => 'Selesai', 'count' => $doneCount],
                    ];
                @endphp

                @foreach ($tabs as $tab)
                    <button type="button"
                            data-order-tab="{{ $tab['key'] }}"
                            role="tab"
                            @class([
                                'tag flex items-center gap-1.5 rounded-full px-3 py-2 text-[8px] font-bold transition-colors sm:px-4',
                                'bg-varya-dark text-white' => $loop->first,
                                'text-ink-soft hover:bg-varya-light/50' => ! $loop->first,
                            ])
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                        {{ $tab['label'] }}
                        <span data-tab-count="{{ $tab['key'] }}"
                              @class([
                                  'inline-flex min-w-5 items-center justify-center rounded-full border px-1.5 py-0.5 text-[7px]',
                                  'border-white/40 bg-white/15 text-white' => $loop->first,
                                  'border-line-strong bg-white text-ink-soft' => ! $loop->first,
                              ])>
                            {{ $tab['count'] }}
                        </span>
                    </button>
                @endforeach
            </div>

            <form data-order-search
                  role="search"
                  onsubmit="return false"
                  class="flex items-center gap-2 rounded-full border-2 border-ink bg-kasir-panel p-1.5 pl-4 shadow-[2px_2px_0_0_#1b1c1d] xl:w-[330px]">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.6" stroke-linecap="round" class="shrink-0 text-ink" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                </svg>

                <label for="kasir-order-search" class="sr-only">Cari pesanan</label>
                <input id="kasir-order-search"
                       type="search"
                       data-order-search-input
                       placeholder="CARI PESANAN..."
                       autocomplete="off"
                       class="min-w-0 flex-1 bg-transparent font-serif text-[11px] font-semibold text-ink uppercase placeholder:text-muted focus:outline-none">

                <button type="submit"
                        class="tag shrink-0 rounded-full bg-varya-dark px-4 py-2 text-[8px] font-bold text-white transition-colors hover:bg-varya">
                    Cari
                </button>
            </form>
        </div>

        {{-- Tabel pesanan --}}
        <div class="kasir-card mt-4 overflow-hidden">
            <div class="kasir-stripes h-3" aria-hidden="true"></div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[940px] border-collapse text-left">
                    <thead class="bg-kasir-head">
                        <tr class="border-b-2 border-ink">
                            <th scope="col" class="tag w-[80px] px-4 py-3 text-[8px] font-bold text-ink-soft">// No. Urut</th>
                            <th scope="col" class="tag w-[120px] px-3 py-3 text-[8px] font-bold text-ink-soft">// Status Order</th>
                            <th scope="col" class="tag w-[150px] px-3 py-3 text-[8px] font-bold text-ink-soft">// Pelanggan</th>
                            <th scope="col" class="tag px-3 py-3 text-[8px] font-bold text-ink-soft">// Item Pesanan</th>
                            <th scope="col" class="tag w-[150px] px-3 py-3 text-[8px] font-bold text-ink-soft">// Pembayaran</th>
                            <th scope="col" class="tag w-[240px] px-4 py-3 text-right text-[8px] font-bold text-ink-soft">// Aksi Kasir</th>
                        </tr>
                    </thead>

                    <tbody data-orders-body class="divide-y-2 divide-line">
                        {{-- Baris pesanan dirender oleh kasir.js --}}
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t-2 border-ink bg-[#f7f5ec] px-4 py-3">
                <p data-order-record class="tag text-[8px] font-bold text-muted">// Record: Menampilkan 0 dari 0 pesanan</p>
                <div data-order-pagination class="flex flex-wrap items-center gap-1.5"></div>
            </div>
        </div>

        <noscript>
            <p class="mt-4 rounded-md border-2 border-rust bg-[#fbe9e2] px-4 py-3 font-serif text-sm text-rust">
                Aktifkan JavaScript untuk melihat dan memproses daftar pesanan.
            </p>
        </noscript>

        {{-- Modal detail pesanan --}}
        <dialog data-order-detail-dialog class="kasir-dialog !max-w-[700px]" aria-labelledby="order-detail-title">
            <div class="kasir-dialog-card">
                <div class="kasir-stripes h-3" aria-hidden="true"></div>

                <div class="flex items-center justify-between gap-3 border-b-2 border-ink px-5 py-3.5">
                    <h2 id="order-detail-title" class="font-display text-lg font-black uppercase">Detail Pesanan Masuk</h2>
                    <button type="button" data-order-detail-close
                            class="grid h-8 w-8 place-items-center rounded border-2 border-ink bg-kasir-panel transition-colors hover:bg-varya-light"
                            aria-label="Tutup detail pesanan">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </div>

                <div class="kasir-dialog-scroll">
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-b border-line bg-[#f1f0e6] px-5 py-3">
                        <p class="tag text-[8px] text-muted">No. Urut: <span data-detail-queue class="ml-1 inline-block rounded border-2 border-ink bg-kasir-panel px-2 py-0.5 text-[9px] font-bold text-ink">—</span></p>
                        <span data-detail-type class="tag inline-block rounded border-2 border-ink bg-ink px-2.5 py-1 text-[8px] font-bold text-white">Dine In</span>
                        <p class="tag text-[8px] text-muted">Pelanggan: <span data-detail-customer class="ml-1 font-bold text-ink">—</span></p>
                        <span data-detail-status class="tag ml-auto inline-block rounded border-2 border-ink bg-varya-light px-2.5 py-1 text-[8px] font-bold text-varya-dark">Antrean</span>
                    </div>

                    <div class="px-5 py-4">
                        <div class="flex items-center justify-between gap-2">
                            <p class="tag text-[8px] font-bold text-muted">// Daftar Item Pesanan (<span data-detail-item-count>0</span> Item)</p>
                            <span data-detail-time class="tag text-[8px] text-muted">—</span>
                        </div>

                        <div data-detail-items class="mt-3 space-y-2.5"></div>

                        <div data-detail-notes-wrap hidden class="mt-3 rounded-md border-2 border-dashed border-line-strong bg-[#f7f5ec] px-3 py-2.5">
                            <p class="tag text-[8px] font-bold text-muted">// Catatan Pesanan</p>
                            <p data-detail-notes class="mt-1 font-serif text-[12px] text-ink-soft">—</p>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-md border-2 border-ink bg-[#f7f5ec] px-3 py-2.5">
                                <p class="tag text-[8px] font-bold text-muted">Pembayaran</p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <span data-detail-payment class="tag inline-flex items-center gap-1 rounded border-2 border-ink bg-kasir-panel px-2 py-1 text-[8px] font-bold">QRIS BCA</span>
                                    <span data-detail-payment-status class="tag rounded border border-varya/30 bg-varya-light px-2 py-1 text-[7px] font-bold text-varya-dark">Lunas / Verified</span>
                                </div>
                            </div>

                            <div class="rounded-md border-2 border-ink bg-[#f7f5ec] px-3 py-2.5">
                                <p class="tag text-[8px] font-bold text-muted">Total Bayar</p>
                                <p data-detail-total class="mt-1 font-display text-[20px] leading-none font-black text-varya-dark">Rp 0</p>
                            </div>
                        </div>

                        <div data-detail-cash-wrap hidden class="mt-3 grid gap-2 rounded-md border-2 border-line-strong bg-[#f7f5ec] px-3 py-2.5 sm:grid-cols-2">
                            <p class="tag text-[8px] text-muted">Uang Diterima: <span data-detail-cash class="ml-1 font-bold text-ink">—</span></p>
                            <p class="tag text-[8px] text-muted sm:text-right">Kembalian: <span data-detail-change class="ml-1 font-bold text-varya-dark">—</span></p>
                        </div>

                        <div class="mt-3 grid gap-2 rounded-md border border-line-strong bg-white px-3 py-2.5 sm:grid-cols-2">
                            <p class="tag text-[8px] text-muted">Kode Pesanan: <span data-detail-code class="ml-1 font-bold text-ink">—</span></p>
                            <p class="tag text-[8px] text-muted sm:text-right">Sumber: <span data-detail-source class="ml-1 font-bold text-ink">—</span></p>
                            <p data-detail-midtrans-wrap class="tag text-[8px] text-muted sm:col-span-2">ID Midtrans: <span data-detail-midtrans class="ml-1 font-mono text-[8px] font-bold break-all text-ink">—</span></p>
                            <p data-detail-expires-wrap hidden class="tag text-[8px] text-muted sm:col-span-2">Batas Pembayaran: <span data-detail-expires class="ml-1 font-bold text-rust">—</span></p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 border-t-2 border-ink bg-[#f7f5ec] px-5 py-3.5">
                    <button type="button" data-order-detail-close
                            class="tag rounded border-2 border-ink bg-kasir-panel px-4 py-2.5 text-[9px] font-bold shadow-[2px_2px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5">
                        Tutup
                    </button>

                    <div data-detail-actions class="flex flex-wrap items-center gap-2"></div>
                </div>
            </div>
        </dialog>

        {{-- Konfirmasi pembatalan --}}
        <dialog data-confirm-dialog class="kasir-dialog !max-w-[430px]" aria-labelledby="confirm-dialog-title">
            <div class="kasir-dialog-card">
                <div class="kasir-stripes h-3" aria-hidden="true"></div>

                <div class="p-5">
                    <span class="grid h-11 w-11 place-items-center rounded-full border-2 border-ink bg-[#f7d7d3] text-rust">
                        <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 9v4M12 17h.01"/><path d="M10.3 3.9 2.5 18a2 2 0 0 0 1.8 3h15.4a2 2 0 0 0 1.8-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
                        </svg>
                    </span>

                    <h2 id="confirm-dialog-title" class="mt-3 font-display text-xl font-black uppercase">Batalkan Pesanan?</h2>
                    <p data-confirm-message class="mt-2 font-serif text-[13px] leading-relaxed text-ink-soft">
                        Pesanan yang belum dibayar akan dibatalkan dan tidak masuk antrean produksi.
                    </p>

                    <div class="mt-5 grid grid-cols-2 gap-2">
                        <button type="button" data-confirm-cancel
                                class="tag rounded border-2 border-ink bg-kasir-panel px-4 py-3 text-[9px] font-bold shadow-[2px_2px_0_0_#1b1c1d]">
                            Kembali
                        </button>
                        <button type="button" data-confirm-ok
                                class="tag rounded border-2 border-ink bg-rust px-4 py-3 text-[9px] font-bold text-white shadow-[2px_2px_0_0_#1b1c1d]">
                            Ya, Batalkan
                        </button>
                    </div>
                </div>
            </div>
        </dialog>

        <script type="application/json" data-orders-data>@json($orders)</script>
    </div>
</x-layouts.kasir>
