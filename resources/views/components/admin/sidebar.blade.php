@props([
    'active' => 'dashboard',
    'admin' => ['name' => 'Bagas Pratama', 'initial' => 'BP', 'role' => 'Admin / Owner'],
])

@php
    $groups = [
        [
            'label' => 'Menu Utama',
            'items' => [
                [
                    'key' => 'dashboard',
                    'label' => 'Dashboard',
                    'href' => route('admin.dashboard'),
                    'icon' => 'grid',
                ],
                [
                    'key' => 'menu',
                    'label' => 'Kelola Menu',
                    'href' => route('admin.menu.index'),
                    'icon' => 'scissors',
                ],
                [
                    'key' => 'reports',
                    'label' => 'Laporan Transaksi',
                    'href' => route('admin.reports'),
                    'icon' => 'receipt',
                ],
            ],
        ],
        [
            'label' => 'Konten & Operasional',
            'items' => [
                [
                    'key' => 'promos',
                    'label' => 'Iklan & Promosi',
                    'href' => route('admin.promos'),
                    'icon' => 'megaphone',
                ],
                [
                    'key' => 'store',
                    'label' => 'Informasi Toko',
                    'href' => route('admin.store'),
                    'icon' => 'store',
                ],
            ],
        ],
    ];
@endphp

<aside id="admin-sidebar"
       data-admin-sidebar
       class="fixed inset-y-0 left-0 z-50 flex w-[274px] -translate-x-full flex-col border-r-2 border-ink bg-admin-panel transition-transform duration-200 lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:translate-x-0">

    {{-- Logo --}}
    <div class="flex h-[92px] shrink-0 items-center gap-2.5 border-b-2 border-ink px-4 lg:gap-3">
        <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden lg:h-12 lg:w-12">
            <img src="{{ asset('images/brand/varya-mark.png') }}"
                 alt="Logo Kedai Kopi Varya"
                 class="h-11 w-11 object-contain lg:h-12 lg:w-12">
        </span>

        <span class="min-w-0">
            <span class="block font-display text-[16px] leading-none font-black tracking-[0.015em] whitespace-nowrap text-ink uppercase [word-spacing:0.16em] lg:text-[18px]">
                Kedai Kopi Varya
            </span>
            <span class="tag mt-1.5 block text-[8px] font-bold tracking-[0.2em] text-muted">Admin</span>
        </span>

        <button type="button"
                data-admin-sidebar-close
                class="ml-auto inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border-2 border-ink bg-admin-panel text-ink lg:hidden"
                aria-label="Tutup menu samping">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
    </div>

    {{-- Navigasi --}}
    <div class="admin-scrollbar flex-1 overflow-y-auto px-4 py-5">
        @foreach ($groups as $group)
            <div class="{{ $loop->first ? '' : 'mt-7' }}">
                <p class="tag px-2 text-[9px] font-bold text-muted">{{ $group['label'] }}</p>

                <nav class="mt-3 space-y-2.5" aria-label="{{ $group['label'] }}">
                    @foreach ($group['items'] as $item)
                        @php $isActive = $active === $item['key']; @endphp

                        <a href="{{ $item['href'] }}"
                           @class([
                               'flex items-center gap-3 rounded-md border-2 border-ink px-4 py-3 transition-transform duration-150',
                               'bg-varya-dark text-white shadow-[3px_3px_0_0_#1b1c1d]' => $isActive,
                               'bg-admin-panel text-ink shadow-[3px_3px_0_0_#c9c8c9] hover:-translate-y-0.5 hover:bg-varya-light/40' => ! $isActive,
                           ])
                           @if ($isActive) aria-current="page" @endif>
                            <span class="grid h-6 w-6 shrink-0 place-items-center">
                                @switch($item['icon'])
                                    @case('grid')
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="3" width="7" height="7" rx="1"/>
                                            <rect x="14" y="3" width="7" height="7" rx="1"/>
                                            <rect x="3" y="14" width="7" height="7" rx="1"/>
                                            <rect x="14" y="14" width="7" height="7" rx="1"/>
                                        </svg>
                                        @break
                                    @case('scissors')
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="6" cy="6" r="2.6"/><circle cx="6" cy="18" r="2.6"/>
                                            <path d="M8.2 7.8 20 18M8.2 16.2 20 6"/>
                                        </svg>
                                        @break
                                    @case('receipt')
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/>
                                            <path d="M9 8h6M9 12h6M9 16h3"/>
                                        </svg>
                                        @break
                                    @case('megaphone')
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m3 11 18-6v14L3 13v-2Z"/>
                                            <path d="m7 13 1.5 6h4L11 14"/>
                                        </svg>
                                        @break
                                    @default
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M4 10h16v10H4z"/>
                                            <path d="M3 6h18v4H3z"/>
                                            <path d="M8 20v-6M16 20v-6"/>
                                        </svg>
                                @endswitch
                            </span>

                            <span class="tag text-[10px] font-bold tracking-[0.04em]">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>
        @endforeach
    </div>

    {{-- Bawah sidebar --}}
    <div class="shrink-0 space-y-3 border-t-2 border-ink p-4">
        <a href="{{ route('pelanggan-a.beranda') }}"
           class="flex items-center justify-between gap-2 rounded-md border-2 border-ink bg-varya-light px-4 py-3 shadow-[3px_3px_0_0_#c9c8c9] transition-transform hover:-translate-y-0.5">
            <span class="flex items-center gap-2">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.2 2.4 3.4 5.4 3.4 9S14.2 18.6 12 21c-2.2-2.4-3.4-5.4-3.4-9S9.8 5.4 12 3Z"/>
                </svg>
                <span class="tag text-[9px] font-bold">Kunjungi Situs Web</span>
            </span>

            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M14 3h7v7M21 3l-9 9"/><path d="M19 14v6H4V5h6"/>
            </svg>
        </a>

        <div class="flex items-center gap-2 rounded-md border-2 border-ink bg-admin-panel p-2 shadow-[3px_3px_0_0_#c9c8c9]">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full border-2 border-ink bg-varya-dark font-mono text-[10px] font-bold text-white">
                {{ $admin['initial'] }}
            </span>

            <span class="min-w-0 flex-1">
                <span class="block truncate font-display text-[12px] leading-tight font-extrabold">{{ $admin['name'] }}</span>
                <span class="tag mt-0.5 block text-[7px] text-muted">{{ $admin['role'] }}</span>
            </span>

            <a href="{{ route('pelanggan-a.beranda') }}"
               class="tag inline-flex shrink-0 items-center gap-1 rounded border-2 border-ink bg-admin-panel px-1.5 py-1.5 text-[8px] font-bold transition-colors hover:bg-varya-light"
               title="Keluar dari area admin (demo)">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>
                </svg>
                Keluar
            </a>
        </div>
    </div>
</aside>
