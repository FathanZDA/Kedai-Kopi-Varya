@php
    $formatRupiah = static fn (int $value): string => 'Rp '.number_format($value, 0, ',', '.');
    $availableMenus = collect($menus)->where('available', true)->count();
    $activeByCategory = collect($menus)
        ->where('available', true)
        ->groupBy('category_label')
        ->map->count();
    $processingCount = collect($orders)->where('status', 'diproses')->count();
@endphp

<x-layouts.admin title="Dashboard"
                 active="dashboard"
                 :admin="$admin"
                 :config="$config"
                 description="Ringkasan penjualan dan aktivitas toko Kedai Kopi Varya.">
    <div data-admin-dashboard>
        {{-- Hero ringkasan --}}
        <section class="admin-card overflow-hidden">
            <div class="flex flex-col gap-1 border-b-2 border-ink px-5 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="tag text-[9px] font-bold text-varya-dark">// Ringkasan Aktivitas Toko</p>
                <p class="tag flex items-center gap-1.5 text-[8px] text-muted">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>
                    </svg>
                    {{ $todayLabel }}
                </p>
            </div>

            <div class="flex flex-col gap-5 px-5 py-4 lg:flex-row lg:items-end lg:justify-between lg:px-6 lg:py-5">
                <div class="max-w-2xl">
                    <h1 class="admin-title text-[34px] text-ink sm:text-[42px] lg:text-[46px]">
                        Ringkasan Dashboard<br class="hidden sm:block"> Operasional
                    </h1>
                    <p class="mt-2 max-w-xl font-serif text-[12px] leading-relaxed text-ink-soft sm:text-[13px]">
                        Pantau performa penjualan, pesanan terbaru, dan menu favorit pelanggan dalam satu tampilan.
                    </p>
                </div>

                <div class="flex shrink-0 items-stretch overflow-hidden rounded-md border-2 border-ink bg-admin-soft shadow-[3px_3px_0_0_#1b1c1d]">
                    <div class="flex min-w-[72px] flex-col justify-center bg-ink px-3 py-3 text-center text-white">
                        <p class="tag text-[7px] leading-tight font-bold text-white/70">Hari<br>Ini</p>
                    </div>
                    <div class="flex min-w-[68px] flex-col justify-center border-l border-ink px-3 py-3 text-center">
                        <p class="font-display text-[24px] leading-none font-black">{{ count($orders) }}</p>
                        <p class="tag mt-1 text-[7px] font-bold text-muted">Hari</p>
                    </div>
                    <div class="flex min-w-[86px] flex-col justify-center border-l border-ink px-3 py-3 text-center">
                        <p class="font-display text-[24px] leading-none font-black">{{ $summary['count'] }}</p>
                        <p class="tag mt-1 text-[7px] font-bold text-muted">Bulan Ini</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Kartu statistik --}}
        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <article class="admin-card flex flex-col p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="tag text-[9px] font-bold text-ink">Total Pesanan Hari Ini</p>
                        <p class="mt-1 font-serif text-[11px] text-muted">Semua saluran transaksi</p>
                    </div>
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md border-2 border-ink bg-admin-panel">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>
                        </svg>
                    </span>
                </div>

                <div class="mt-5 flex items-end gap-2">
                    <span class="font-display text-[44px] leading-none font-black">{{ count($orders) }}</span>
                    <span class="font-display pb-1 text-[18px] leading-none font-bold uppercase text-ink">Pesanan</span>
                </div>

                <div class="mt-4 flex items-center gap-2 border-t border-line-strong/70 pt-3">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-varya-dark" aria-hidden="true">
                        <path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>
                    </svg>
                    <p class="font-mono text-[8px] leading-relaxed font-bold text-ink-soft">
                        <strong class="text-varya-dark">+14%</strong> dibanding kemarin ({{ $yesterdayOrders }} pesanan)
                    </p>
                </div>
            </article>

            <article class="admin-card flex flex-col p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="tag text-[9px] font-bold text-ink">Total Pendapatan</p>
                        <p class="mt-1 font-serif text-[11px] text-muted">Omzet kotor kasir bulan ini</p>
                    </div>
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md border-2 border-ink bg-varya-dark text-white">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 2v20M17 6.5c0-1.9-2.2-3.5-5-3.5S7 4.6 7 6.5 9.2 10 12 10s5 1.6 5 3.5S14.8 17 12 17s-5-1.6-5-3.5"/>
                        </svg>
                    </span>
                </div>

                <div class="mt-5 flex flex-wrap items-end gap-2">
                    <span class="font-display text-[37px] leading-none font-black text-varya-dark sm:text-[40px]">
                        {{ number_format($summary['revenue'], 0, ',', '.') }}
                    </span>
                    <span class="font-display pb-1 text-[18px] leading-none font-bold uppercase text-ink">IDR</span>
                </div>

                <div class="mt-4 border-t border-line-strong/70 pt-3">
                    <p class="font-mono text-[8px] leading-relaxed font-bold text-ink-soft">
                        Hari ini {{ $formatRupiah($todayRevenue) }} &bull; QRIS {{ number_format($summary['qris_percent'], 1, ',', '.') }}% &bull; Tunai {{ number_format($summary['cash_percent'], 1, ',', '.') }}%
                    </p>
                </div>
            </article>

            <article class="admin-card flex flex-col p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="tag text-[9px] font-bold text-ink">Daftar Menu Aktif</p>
                        <p class="mt-1 font-serif text-[11px] text-muted">Katalog kasir &amp; pesanan web</p>
                    </div>
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md border-2 border-ink bg-admin-panel">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 8h14v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8Z"/>
                            <path d="M8 8V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3M9 13h6"/>
                        </svg>
                    </span>
                </div>

                <div class="mt-5 flex items-end gap-2">
                    <span class="font-display text-[44px] leading-none font-black">{{ $availableMenus }}</span>
                    <span class="font-display pb-1 text-[18px] leading-none font-bold uppercase text-ink">Menu</span>
                </div>

                <div class="mt-4 border-t border-line-strong/70 pt-3">
                    <p class="font-mono text-[8px] leading-relaxed font-bold text-ink-soft">
                        @foreach ($activeByCategory as $category => $total)
                            {{ $total }} {{ $category }}@if (! $loop->last) &bull; @endif
                        @endforeach
                    </p>
                </div>
            </article>
        </div>

        {{-- Pesanan terbaru + kolom kanan --}}
        <div class="mt-5 grid items-stretch gap-4 xl:grid-cols-[minmax(0,1fr)_300px] 2xl:grid-cols-[minmax(0,1fr)_360px]">
            <section class="admin-card overflow-hidden xl:flex xl:flex-col" aria-labelledby="recent-orders-title">
                <div class="border-b-2 border-ink bg-[#f3f1e7] px-4 py-5">
                    <div class="flex items-start gap-3">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-sm bg-varya-dark text-white">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6M9 16h3"/>
                            </svg>
                        </span>
                        <div>
                            <h2 id="recent-orders-title" class="font-display text-[20px] leading-tight font-black uppercase">
                                Pesanan Masuk Terbaru
                            </h2>
                            <p class="mt-1 font-serif text-[11px] leading-relaxed text-ink-soft">
                                Pencatatan otomatis sinkron dari QR Meja &amp; Kasir Depan
                            </p>
                            <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-[8px] font-bold">
                                <span class="text-rust">&#9679; {{ $pendingPayments }} menunggu bayar</span>
                                <span class="text-[#8a6415]">&#9679; {{ $processingCount }} diproses</span>
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-1.5" data-dashboard-order-tabs role="tablist" aria-label="Filter tipe pesanan">
                        <button type="button" data-order-tab="all" role="tab" aria-selected="true"
                                class="tag rounded border-2 border-ink bg-varya-dark px-3 py-1.5 text-[8px] font-bold text-white">
                            Semua (<span data-order-count="all">{{ count($orders) }}</span>)
                        </button>
                        <button type="button" data-order-tab="dine_in" role="tab" aria-selected="false"
                                class="tag rounded border-2 border-ink bg-admin-panel px-3 py-1.5 text-[8px] font-bold text-ink hover:bg-varya-light">
                            Makan di Tempat (<span data-order-count="dine_in">{{ collect($orders)->where('type', 'dine_in')->count() }}</span>)
                        </button>
                        <button type="button" data-order-tab="take_away" role="tab" aria-selected="false"
                                class="tag rounded border-2 border-ink bg-admin-panel px-3 py-1.5 text-[8px] font-bold text-ink hover:bg-varya-light">
                            Bawa Pulang (<span data-order-count="take_away">{{ collect($orders)->where('type', 'take_away')->count() }}</span>)
                        </button>
                        <a href="{{ route('admin.reports') }}"
                           class="tag ml-auto rounded border-2 border-ink bg-admin-panel px-3 py-1.5 text-[8px] font-bold text-ink transition-colors hover:bg-varya-light">
                            Lihat Semua
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto xl:flex-1">
                    <table class="w-full min-w-[620px] border-collapse text-left xl:h-full">
                        <thead class="bg-admin-head">
                            <tr class="border-b-2 border-ink">
                                <th scope="col" class="tag px-4 py-2.5 text-[8px] font-bold text-ink-soft">Kode &amp; Waktu</th>
                                <th scope="col" class="tag px-3 py-2.5 text-[8px] font-bold text-ink-soft">Pemesan / Lokasi</th>
                                <th scope="col" class="tag px-3 py-2.5 text-[8px] font-bold text-ink-soft">Tipe</th>
                                <th scope="col" class="tag px-3 py-2.5 text-[8px] font-bold text-ink-soft">Item</th>
                                <th scope="col" class="tag px-3 py-2.5 text-[8px] font-bold text-ink-soft">Total Harga</th>
                                <th scope="col" class="tag px-4 py-2.5 text-[8px] font-bold text-ink-soft">Status</th>
                            </tr>
                        </thead>
                        <tbody data-dashboard-orders class="divide-y-2 divide-line"></tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t-2 border-ink bg-[#f7f5ec] px-4 py-3">
                    <p data-dashboard-orders-record class="tag text-[8px] font-bold text-muted">Menampilkan 0 dari 0 pesanan tercatat hari ini</p>
                    <div data-dashboard-orders-pagination class="flex flex-wrap items-center gap-1.5"></div>
                </div>
            </section>

            <div class="space-y-4">
                <section class="admin-card overflow-hidden" aria-labelledby="popular-menus-title">
                    <div class="flex items-center gap-2 border-b-2 border-ink bg-[#f3f1e7] px-4 py-3">
                        <span class="grid h-6 w-6 place-items-center rounded-sm bg-varya-dark text-white">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M5 8h14v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8Z"/>
                                <path d="M8 8V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3M9 13h6"/>
                            </svg>
                        </span>
                        <h2 id="popular-menus-title" class="font-display text-[17px] leading-tight font-black uppercase">
                            Menu Populer Hari Ini
                        </h2>
                    </div>

                    <div class="divide-y-2 divide-line">
                        @foreach ($popularMenus as $menu)
                            <div class="flex items-center gap-2.5 px-4 py-2.5">
                                <img src="{{ $menu['image'] }}"
                                     alt="{{ $menu['name'] }}"
                                     loading="lazy"
                                     class="h-12 w-12 shrink-0 rounded border-2 border-ink object-cover">

                                <div class="min-w-0 flex-1">
                                    <p class="font-display text-[12px] leading-tight font-extrabold">{{ $menu['name'] }}</p>
                                    <p class="mt-0.5 font-serif text-[9px] leading-snug text-ink-soft">
                                        {{ $formatRupiah($menu['price']) }} &bull; {{ $menu['note'] }}
                                    </p>
                                    <p class="tag mt-0.5 text-[6.5px] text-muted">{{ $menu['tag'] }}</p>
                                </div>

                                <span class="tag shrink-0 rounded bg-varya-dark px-1.5 py-1 text-[6.5px] font-bold text-white">
                                    {{ $menu['sales'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>

            </div>
        </div>
    </div>
</x-layouts.admin>
