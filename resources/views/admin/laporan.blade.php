@php
    $formatRupiah = static fn (int $value): string => 'Rp '.number_format($value, 0, ',', '.');
@endphp

<x-layouts.admin title="Laporan Transaksi"
                 active="reports"
                 :admin="$admin"
                 :config="$config"
                 description="Laporan riwayat transaksi dan keuangan Kedai Kopi Varya.">
    <div data-admin-reports>
        <div class="max-w-4xl">
            <h1 class="admin-title text-[32px] sm:text-[36px]">
                Laporan Riwayat Transaksi &amp;<br class="hidden sm:block"> Keuangan
            </h1>
        </div>

        {{-- Ringkasan --}}
        <div class="mt-5 grid items-stretch gap-4 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,1fr)]">
            <section class="admin-card relative overflow-hidden p-4 sm:p-5" aria-labelledby="report-revenue-title">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full bg-varya-dark"></span>
                        <h2 id="report-revenue-title" class="tag text-[9px] font-bold text-ink">Total Pemasukan Berhasil</h2>
                    </div>
                    <span data-report-period-badge class="tag rounded border-2 border-line-strong bg-[#f1eee4] px-2.5 py-1 text-[8px] font-bold text-ink-soft">
                        Periode: Oktober 2026
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap items-end gap-3">
                    <p data-report-revenue class="font-display text-[46px] leading-none font-black text-ink sm:text-[54px]">
                        {{ $formatRupiah($summary['revenue']) }}
                    </p>
                    <span class="tag mb-1 rounded border-2 border-varya/30 bg-varya-light px-2.5 py-1.5 text-[8px] font-bold text-varya-dark">
                        / Pendapatan Bersih Kasir
                    </span>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1">
                    <p data-report-change class="flex items-center gap-1.5 font-serif text-[11px] text-varya-dark">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>
                        </svg>
                        <strong>+14,2%</strong> dibanding bulan lalu
                    </p>
                    <span class="h-1 w-1 rounded-full bg-muted" aria-hidden="true"></span>
                    <p class="font-serif text-[11px] text-ink-soft">
                        <strong data-report-count class="text-ink">{{ $summary['count'] }}</strong> transaksi selesai
                    </p>
                </div>

                <div class="mt-5 border-t border-line-strong/70 pt-3">
                    <p class="font-serif text-[10px] leading-relaxed text-ink-soft">
                        Semua pembayaran lunas (0 pengembalian) &bull; Rata-rata nilai transaksi:
                        <strong data-report-average class="text-ink">{{ $formatRupiah($summary['average']) }}</strong>
                    </p>
                </div>

                <div class="pointer-events-none absolute -right-8 -bottom-10 h-36 w-36 rounded-full bg-varya-light/60" aria-hidden="true"></div>
            </section>

            <section class="admin-card p-4 sm:p-5" aria-labelledby="report-method-title">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <h2 id="report-method-title" class="tag text-[9px] font-bold text-ink">Rincian Metode Pembayaran</h2>
                    <span class="tag text-[8px] font-bold text-varya-dark">2 Metode Aktif</span>
                </div>

                <div class="mt-4 flex h-3 overflow-hidden rounded-full border-2 border-ink bg-[#f4e1d5]">
                    <div data-report-qris-bar class="h-full bg-varya-dark" style="width: {{ $summary['qris_percent'] }}%"></div>
                    <div data-report-cash-bar class="h-full bg-[#b9562f]" style="width: {{ $summary['cash_percent'] }}%"></div>
                </div>

                <div class="mt-2 flex items-center justify-between gap-2">
                    <p class="tag text-[7px] font-bold text-varya-dark">QRIS <span data-report-qris-percent>{{ number_format($summary['qris_percent'], 1, ',', '.') }}%</span></p>
                    <p class="tag text-[7px] font-bold text-[#8a3b1d]">Tunai <span data-report-cash-percent>{{ number_format($summary['cash_percent'], 1, ',', '.') }}%</span></p>
                </div>

                <div class="mt-4 space-y-2.5">
                    <div class="flex items-center gap-3 rounded-md border-2 border-line-strong bg-admin-soft px-3 py-2.5">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded border-2 border-ink bg-varya-dark font-display text-[14px] font-black text-white">Q</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-display text-[13px] leading-tight font-extrabold">QRIS Digital</p>
                            <p class="mt-0.5 font-serif text-[9px] text-muted">Transaksi via GoPay / OVO / BCA</p>
                        </div>
                        <div class="text-right">
                            <p data-report-qris class="font-display text-[15px] leading-tight font-bold text-ink">{{ $formatRupiah($summary['qris']) }}</p>
                            <p class="tag mt-0.5 text-[7px] text-muted"><span data-report-qris-percent-small>{{ number_format($summary['qris_percent'], 1, ',', '.') }}%</span></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 rounded-md border-2 border-line-strong bg-admin-soft px-3 py-2.5">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded border-2 border-ink bg-[#b9562f] font-display text-[14px] font-black text-white">C</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-display text-[13px] leading-tight font-extrabold">Tunai / Kasir</p>
                            <p class="mt-0.5 font-serif text-[9px] text-muted">Pembayaran tunai meja / kasir</p>
                        </div>
                        <div class="text-right">
                            <p data-report-cash class="font-display text-[15px] leading-tight font-bold text-ink">{{ $formatRupiah($summary['cash']) }}</p>
                            <p class="tag mt-0.5 text-[7px] text-muted"><span data-report-cash-percent-small>{{ number_format($summary['cash_percent'], 1, ',', '.') }}%</span></p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        {{-- Filter --}}
        <div class="admin-card mt-5 p-3 sm:p-4">
            <div class="grid gap-2.5 md:grid-cols-2 xl:grid-cols-[minmax(220px,1fr)_180px_180px_190px_auto]">
                <div class="flex items-center gap-2 rounded-md border-2 border-ink bg-white px-3 py-2 shadow-[2px_2px_0_0_#1b1c1d]">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.6" stroke-linecap="round" class="shrink-0 text-ink" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                    </svg>
                    <label for="report-search" class="sr-only">Cari transaksi</label>
                    <input id="report-search"
                           type="search"
                           data-report-search
                           placeholder="Cari kode transaksi, pesanan, nama..."
                           autocomplete="off"
                           class="min-w-0 flex-1 bg-transparent font-serif text-[11px] text-ink placeholder:text-muted focus:outline-none">
                </div>

                <div>
                    <label for="report-method" class="sr-only">Filter metode</label>
                    <select id="report-method" data-report-method class="admin-select">
                        <option value="all">Semua Metode</option>
                        <option value="qris">QRIS</option>
                        <option value="tunai">Tunai</option>
                    </select>
                </div>

                <div>
                    <label for="report-status" class="sr-only">Filter status</label>
                    <select id="report-status" data-report-status class="admin-select">
                        <option value="all">Semua Status</option>
                        <option value="lunas">Lunas</option>
                        <option value="menunggu">Menunggu</option>
                        <option value="perlu_diperiksa">Perlu Diperiksa</option>
                        <option value="gagal">Gagal</option>
                    </select>
                </div>

                <div>
                    <label for="report-month" class="sr-only">Pilih bulan</label>
                    <select id="report-month" data-report-month class="admin-select">
                        @foreach ($months as $month)
                            <option value="{{ $month['value'] }}" @selected($month['value'] === $defaultMonth)>
                                {{ $month['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="button" data-report-reset class="admin-btn-secondary !px-4" title="Reset filter">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>
                    </svg>
                    Reset
                </button>
            </div>
        </div>

        {{-- Tabel transaksi --}}
        <div class="admin-card mt-4 overflow-hidden">
            <div class="admin-stripes h-3" aria-hidden="true"></div>

            <div class="flex flex-wrap items-center justify-between gap-2 border-b-2 border-ink bg-[#f3f1e7] px-4 py-3">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full bg-varya-dark"></span>
                    <h2 class="tag text-[9px] font-bold text-ink">
                        Audit Buku Kas: <span data-report-filtered-count>0</span> Transaksi Terfilter
                    </h2>
                </div>
                <p class="tag text-[8px] text-muted">Diurutkan: Tanggal Terbaru</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[920px] border-collapse text-left">
                    <thead class="bg-admin-head">
                        <tr class="border-b-2 border-ink">
                            <th scope="col" class="tag px-4 py-3 text-[8px] font-bold text-ink-soft">Kode Transaksi</th>
                            <th scope="col" class="tag px-3 py-3 text-[8px] font-bold text-ink-soft">Kode Pesanan</th>
                            <th scope="col" class="tag px-3 py-3 text-[8px] font-bold text-ink-soft">Pelanggan</th>
                            <th scope="col" class="tag px-3 py-3 text-[8px] font-bold text-ink-soft">Total</th>
                            <th scope="col" class="tag px-3 py-3 text-[8px] font-bold text-ink-soft">Metode</th>
                            <th scope="col" class="tag px-3 py-3 text-[8px] font-bold text-ink-soft">Status Bayar</th>
                            <th scope="col" class="tag px-4 py-3 text-[8px] font-bold text-ink-soft">Tanggal &amp; Waktu</th>
                        </tr>
                    </thead>
                    <tbody data-report-rows class="divide-y-2 divide-line">
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center font-serif text-sm text-muted">Memuat laporan transaksi...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t-2 border-ink bg-[#f7f5ec] px-4 py-3">
                <p data-report-record class="tag text-[8px] font-bold text-muted">// Menampilkan 0 dari 0 transaksi</p>
                <div data-report-pagination class="flex flex-wrap items-center gap-1.5"></div>
            </div>
        </div>

        <p data-report-empty hidden class="mt-4 rounded-md border-2 border-dashed border-line-strong bg-admin-panel py-10 text-center font-serif text-sm text-muted">
            Tidak ada transaksi yang sesuai dengan filter.
        </p>
    </div>

    {{-- Dialog rincian transaksi --}}
    <dialog data-report-dialog class="admin-dialog !max-w-3xl">
        <div class="admin-dialog-card">
            <div class="flex items-start justify-between gap-4 border-b-2 border-ink bg-[#f3f1e7] px-5 py-4">
                <div>
                    <p class="tag text-[8px] font-bold text-varya-dark">// Rincian Transaksi</p>
                    <h2 data-report-detail-title class="mt-1 font-display text-xl leading-tight font-black uppercase">
                        Detail Transaksi
                    </h2>
                    <p data-report-detail-subtitle class="mt-1 font-serif text-[10px] text-muted"></p>
                </div>

                <button type="button" data-report-close
                        class="grid h-8 w-8 shrink-0 place-items-center rounded border-2 border-ink bg-white transition-colors hover:bg-varya-light"
                        aria-label="Tutup rincian">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>

            <div data-report-detail class="p-5"></div>
        </div>
    </dialog>
</x-layouts.admin>
