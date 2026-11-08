@php
    $berandaUrl = route('pelanggan-a.beranda');

    $links = [
        [
            'section' => 'beranda',
            'href' => $berandaUrl.'#beranda',
            'label' => 'Beranda',
            'active' => request()->routeIs('pelanggan-a.beranda'),
        ],
        [
            'section' => 'tentang',
            'href' => $berandaUrl.'#tentang',
            'label' => 'Tentang Kami',
            'active' => request()->routeIs('pelanggan-a.tentang'),
        ],
        [
            'section' => 'menu',
            'href' => $berandaUrl.'#menu',
            'label' => 'Menu',
            'active' => request()->routeIs('pelanggan-a.menu'),
        ],
        [
            'section' => 'lokasi',
            'href' => $berandaUrl.'#lokasi',
            'label' => 'Lokasi',
            'active' => request()->routeIs('pelanggan-a.lokasi'),
        ],
    ];
@endphp

<header class="sticky top-0 z-50">
    <div class="border-b border-line bg-header/95 backdrop-blur">
        <div class="mx-auto flex h-[76px] max-w-[1280px] items-center justify-between gap-3 px-5 sm:gap-6 sm:px-8 lg:grid lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]">
            <div class="flex min-w-0 items-center justify-self-start">
                <x-public.brand />
            </div>

            {{-- Navigasi desktop --}}
            <nav class="hidden items-center gap-1 justify-self-center lg:flex" aria-label="Navigasi utama">
                @foreach ($links as $link)
                    <a href="{{ $link['href'] }}"
                       data-nav-link
                       data-nav-section="{{ $link['section'] }}"
                       data-nav-desktop
                       @class([
                           'rounded-full px-5 py-2 text-sm font-semibold tracking-wide uppercase transition-colors',
                           'bg-varya-dark text-white' => $link['active'],
                           'text-ink hover:text-varya' => ! $link['active'],
                       ])
                       @if ($link['active']) aria-current="page" @endif>
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2.5 justify-self-end">
                {{-- Tombol menu untuk layar kecil --}}
                <button type="button"
                        data-nav-toggle
                        aria-expanded="false"
                        aria-controls="nav-mobile"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-md border-2 border-ink bg-white shadow-[3px_3px_0_0_#1b1c1d] lg:hidden">
                    <span class="sr-only">Buka menu navigasi</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                        <path d="M3 6h18M3 12h18M3 18h18"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Navigasi mobile --}}
        <nav id="nav-mobile" data-nav-panel hidden class="border-t border-line bg-header lg:hidden">
            <ul class="mx-auto max-w-[1280px] px-5 py-3 sm:px-8">
                @foreach ($links as $link)
                    <li>
                        <a href="{{ $link['href'] }}"
                           data-nav-link
                           data-nav-section="{{ $link['section'] }}"
                           @class([
                               'block rounded-md px-4 py-3 text-sm font-semibold tracking-wide uppercase',
                               'bg-varya-dark text-white' => $link['active'],
                               'text-ink' => ! $link['active'],
                           ])
                           @if ($link['active']) aria-current="page" @endif>
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>

    {{-- Garis aksen hijau di bawah header --}}
    <div class="h-1.5 bg-varya-dark"></div>
    <div class="h-2.5 bg-varya-light"></div>
</header>
