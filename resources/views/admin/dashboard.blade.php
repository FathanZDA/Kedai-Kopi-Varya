@php
    $formatRupiah = static fn (int $value): string => 'Rp '.number_format($value, 0, ',', '.');
    $availableMenus = collect($menus)->where('available', true)->count();
    $activeByCategory = collect($menus)
        ->where('available', true)
        ->groupBy('category_label')
        ->map->count();
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
                <p class="tag text-[12px] font-bold text-varya-dark">// Ringkasan Aktivitas Toko</p>
                <p class="tag flex items-center gap-1.5 text-[12px] text-muted">
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

                <nav class="flex shrink-0 items-stretch overflow-hidden rounded-md border-2 border-ink bg-admin-soft shadow-[3px_3px_0_0_#1b1c1d]"
                     aria-label="Pilih periode ringkasan">
                    @foreach (['hari', 'minggu', 'bulan'] as $periode)
                        @php
                            $aktif = $periode === $periodeAktif;
                            $ringkasanKotak = $ringkasanPeriode[$periode];
                        @endphp
                        <a href="{{ route('admin.dashboard', ['periode' => $periode]) }}"
                           data-periode="{{ $periode }}"
                           @if ($aktif) aria-current="page" @endif
                           class="flex min-w-[78px] flex-col justify-center px-3 py-3 text-center transition-colors {{ $loop->first ? '' : 'border-l border-ink' }} {{ $aktif ? 'bg-ink text-white' : 'bg-admin-soft text-ink hover:bg-varya-light' }}">
                            <p class="tag text-[12px] leading-tight font-bold {{ $aktif ? 'text-white/70' : 'text-muted' }}">{{ $ringkasanKotak['label'] }}</p>
                            <p class="font-display text-[24px] leading-none font-black">{{ $ringkasanKotak['orders'] }}</p>
                            <p class="tag mt-1 text-[12px] font-bold {{ $aktif ? 'text-white/70' : 'text-muted' }}">Pesanan</p>
                        </a>
                    @endforeach
                </nav>
            </div>
        </section>

        {{-- Kartu statistik --}}
        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="admin-card flex flex-col p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="tag text-[12px] font-bold text-ink">Total Pesanan</p>
                        <p class="mt-1 font-serif text-[13px] text-muted">Semua saluran transaksi &bull; {{ $ringkasan['label'] }}</p>
                    </div>
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md border-2 border-ink bg-admin-panel">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>
                        </svg>
                    </span>
                </div>

                <div class="mt-5 flex items-end gap-2">
                    <span class="font-display text-[44px] leading-none font-black">{{ $ringkasan['orders'] }}</span>
                    <span class="font-display pb-1 text-[18px] leading-none font-bold uppercase text-ink">Pesanan</span>
                </div>

                <div class="mt-4 flex items-center gap-2 border-t border-line-strong/70 pt-3">
                    @if ($ringkasan['change_percent'] !== null)
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 {{ $ringkasan['change_percent'] >= 0 ? 'text-varya-dark' : 'text-rust' }}" aria-hidden="true">
                            @if ($ringkasan['change_percent'] >= 0)
                                <path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>
                            @else
                                <path d="m3 7 6 6 4-4 8 8"/><path d="M15 17h6v-6"/>
                            @endif
                        </svg>
                        <p class="font-mono text-[12px] leading-relaxed font-bold text-ink-soft">
                            <strong class="{{ $ringkasan['change_percent'] >= 0 ? 'text-varya-dark' : 'text-rust' }}">{{ $ringkasan['change_percent'] >= 0 ? '+' : '-' }}{{ number_format(abs($ringkasan['change_percent']), 1, ',', '.') }}%</strong>
                            dibanding {{ $ringkasan['pembanding_label'] }} ({{ $ringkasan['pembanding'] }} pesanan)
                        </p>
                    @else
                        <p class="font-mono text-[12px] leading-relaxed font-bold text-muted">
                            Belum ada pesanan {{ $ringkasan['pembanding_label'] }} sebagai pembanding
                        </p>
                    @endif
                </div>
            </article>

            <article class="admin-card flex flex-col p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="tag text-[12px] font-bold text-ink">Total Pendapatan</p>
                        <p class="mt-1 font-serif text-[13px] text-muted">Hanya transaksi lunas &bull; {{ $ringkasan['label'] }}</p>
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
                        {{ number_format($ringkasan['revenue'], 0, ',', '.') }}
                    </span>
                    <span class="font-display pb-1 text-[18px] leading-none font-bold uppercase text-ink">IDR</span>
                </div>

                <div class="mt-4 border-t border-line-strong/70 pt-3">
                    <p class="font-mono text-[12px] leading-relaxed font-bold text-ink-soft">
                        {{ $ringkasan['count'] }} transaksi lunas &bull; QRIS {{ number_format($ringkasan['qris_percent'], 1, ',', '.') }}% &bull; Tunai {{ number_format($ringkasan['cash_percent'], 1, ',', '.') }}%
                    </p>
                </div>
            </article>

            <article class="admin-card flex flex-col p-4 sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="tag text-[12px] font-bold text-ink">Daftar Menu Aktif</p>
                        <p class="mt-1 font-serif text-[13px] text-muted">Katalog kasir &amp; pesanan web</p>
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
                    <p class="font-mono text-[12px] leading-relaxed font-bold text-ink-soft">
                        @foreach ($activeByCategory as $category => $total)
                            {{ $total }} {{ $category }}@if (! $loop->last) &bull; @endif
                        @endforeach
                    </p>
                </div>
            </article>

            <article class="admin-card flex flex-col p-4 sm:p-5"
                     data-rotator
                     data-rotator-interval="2000">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="tag text-[12px] font-bold text-ink">Paling Banyak Terjual</p>
                        <p class="mt-1 font-serif text-[13px] text-muted">Transaksi lunas &bull; Bulan ini</p>
                    </div>
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-md border-2 border-ink bg-varya-dark text-white">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2l1.1-6.2L3 9.6l6.2-.9L12 3Z"/>
                        </svg>
                    </span>
                </div>

                @if (count($popularMenus) === 0)
                    <p class="mt-5 font-serif text-[13px] leading-relaxed text-ink-soft">
                        Belum ada transaksi lunas bulan ini.
                    </p>
                @else
                    <div class="relative mt-5 min-h-[72px]">
                        @foreach ($popularMenus as $index => $menu)
                            <div data-rotator-slide
                                 data-nama="{{ $menu['name'] }}"
                                 data-terjual="{{ $menu['sales'] }}"
                                 data-aktif="{{ $index === 0 ? 'true' : 'false' }}"
                                 class="popular-slide {{ $index === 0 ? 'is-aktif' : '' }}">
                                <p class="font-display text-[21px] leading-tight font-black text-varya-dark line-clamp-2">
                                    {{ $menu['name'] }}
                                </p>
                                <p class="mt-1 font-mono text-[12px] leading-none font-bold text-ink-soft">
                                    {{ $menu['sales'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>

                    @if (count($popularMenus) > 1)
                        <div class="mt-auto flex items-center justify-between gap-2 border-t border-line-strong/70 pt-3">
                            <div class="flex items-center gap-1.5" aria-hidden="true">
                                @foreach ($popularMenus as $index => $menu)
                                    <span data-rotator-dot
                                          data-aktif="{{ $index === 0 ? 'true' : 'false' }}"
                                          class="popular-dot"></span>
                                @endforeach
                            </div>

                            <button type="button"
                                    data-rotator-toggle
                                    aria-pressed="true"
                                    class="tag rounded border-2 border-ink bg-admin-soft px-2 py-1 text-[12px] font-bold text-ink transition-colors hover:bg-varya-light focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-varya-dark">
                                Jeda
                            </button>
                        </div>

                        <p class="sr-only" aria-live="polite" aria-atomic="true" data-rotator-status>
                            Menyorot {{ $popularMenus[0]['name'] }}, {{ $popularMenus[0]['sales'] }}.
                        </p>
                    @endif
                @endif
            </article>
        </div>

        {{-- Grafik penjualan (lebar penuh) --}}
        <div class="mt-5">
            <section class="admin-card overflow-hidden"
                     aria-labelledby="grafik-title"
                     data-grafik
                     data-grafik-total-label="Total {{ $ringkasan['label'] }}"
                     data-grafik-total-revenue="{{ $ringkasan['revenue'] }}"
                     data-grafik-total-orders="{{ $ringkasan['orders'] }}">
                <div class="border-b-2 border-ink bg-[#f3f1e7] px-4 py-5">
                    <div class="flex flex-wrap items-start gap-3">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-sm bg-varya-dark text-white">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 20h16"/><path d="M7 20v-6M12 20V9M17 20V4"/>
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <h2 id="grafik-title" class="font-display text-[20px] leading-tight font-black uppercase">
                                Grafik Penjualan
                            </h2>
                            <p class="mt-1 font-serif text-[13px] leading-relaxed text-ink-soft">
                                Pendapatan transaksi lunas {{ $grafik['satuan'] }} &bull; {{ $ringkasan['label'] }}
                            </p>
                            <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-[12px] font-bold">
                                <span class="text-rust">&#9679; {{ $pendingPayments }} menunggu bayar hari ini</span>
                                <span class="text-[#8a6415]">&#9679; {{ $processingCount }} diproses hari ini</span>
                            </p>
                        </div>

                        <div class="shrink-0 border-l-2 border-ink pl-3 sm:pl-4">
                            <p class="tag text-[12px] font-bold text-muted">Total Pendapatan</p>
                            <p class="font-display text-[24px] leading-none font-black text-varya-dark">
                                Rp {{ number_format($ringkasan['revenue'], 0, ',', '.') }}
                            </p>
                            <p class="tag mt-1 text-[12px] font-bold text-ink-soft">
                                {{ $ringkasan['orders'] }} pesanan &bull; {{ $ringkasan['label'] }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-3 pt-4 pb-2 sm:px-4">
                    @if ($grafik['maks'] === 0)
                        <p class="mb-2 rounded border border-line-strong bg-admin-soft px-3 py-2 font-serif text-[13px] text-ink-soft">
                            Belum ada transaksi lunas {{ mb_strtolower($grafik['label']) }}. Grafik akan terisi setelah pembayaran pertama.
                        </p>
                    @endif

                    <div class="overflow-x-auto pb-1">
                        <div class="flex items-stretch gap-[3px]"
                             style="min-width: {{ max(260, count($grafik['batang']) * 26) }}px;">
                            @foreach ($grafik['batang'] as $batang)
                                <button type="button"
                                        data-grafik-bar
                                        aria-pressed="false"
                                        aria-label="{{ $batang['judul'] }}: Rp {{ number_format($batang['pendapatan'], 0, ',', '.') }}, {{ $batang['pesanan'] }} pesanan"
                                        data-label="{{ $batang['judul'] }}"
                                        data-revenue="{{ $batang['pendapatan'] }}"
                                        data-orders="{{ $batang['pesanan'] }}"
                                        class="group flex h-[200px] min-w-0 flex-1 flex-col justify-end rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-varya-dark">
                                    <span class="relative flex-1">
                                        <span class="grafik-bar absolute inset-x-0 bottom-0 rounded-t-[3px] border border-ink bg-varya/60 transition-colors group-hover:bg-varya-dark"
                                              style="height: {{ $batang['tinggi'] }}%"></span>
                                    </span>
                                    <span class="mt-1 h-4 shrink-0 text-center font-mono text-[12px] leading-none font-bold text-muted">
                                        {{ $batang['label'] }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-auto flex flex-wrap items-end justify-between gap-3 border-t-2 border-ink bg-[#f7f5ec] px-4 py-3">
                    <div class="min-w-0" aria-live="polite">
                        <p class="tag text-[12px] font-bold text-muted" data-grafik-caption>Total {{ $ringkasan['label'] }}</p>
                        <p class="font-display text-[22px] leading-none font-black text-varya-dark" data-grafik-revenue>
                            Rp {{ number_format($ringkasan['revenue'], 0, ',', '.') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                        <p class="font-mono text-[12px] font-bold text-ink-soft">
                            <span data-grafik-orders>{{ $ringkasan['orders'] }}</span> pesanan tercatat
                        </p>
                        <p class="tag max-w-[190px] text-[12px] text-muted" data-grafik-hint>
                            Klik batang untuk rincian
                        </p>
                        <a href="{{ route('admin.reports') }}"
                           class="tag rounded border-2 border-ink bg-admin-panel px-3 py-1.5 text-[12px] font-bold text-ink transition-colors hover:bg-varya-light">
                            Laporan Transaksi
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-layouts.admin>
