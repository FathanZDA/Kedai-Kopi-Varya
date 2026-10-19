@php
    $links = [
        ['route' => 'pelanggan-a.beranda', 'label' => 'Beranda'],
        ['route' => 'pelanggan-a.tentang', 'label' => 'Tentang Kami'],
        ['route' => 'pelanggan-a.menu', 'label' => 'Menu'],
        ['route' => 'pelanggan-a.lokasi', 'label' => 'Lokasi'],
    ];
@endphp

<header class="sticky top-0 z-50">
    <div class="border-b border-line bg-header/95 backdrop-blur">
        <div class="mx-auto flex h-[76px] max-w-[1280px] items-center justify-between gap-6 px-5 sm:px-8">
            <x-public.brand />

            {{-- Navigasi desktop --}}
            <nav class="hidden items-center gap-1 md:flex" aria-label="Navigasi utama">
                @foreach ($links as $link)
                    @php $active = request()->routeIs($link['route']); @endphp
                    <a href="{{ route($link['route']) }}"
                       @class([
                           'rounded-full px-5 py-2 text-sm font-semibold tracking-wide uppercase transition-colors',
                           'bg-varya-dark text-white' => $active,
                           'text-ink hover:text-varya' => ! $active,
                       ])
                       @if ($active) aria-current="page" @endif>
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            {{-- Tombol menu untuk layar kecil --}}
            <button type="button"
                    data-nav-toggle
                    aria-expanded="false"
                    aria-controls="nav-mobile"
                    class="md:hidden inline-flex h-11 w-11 items-center justify-center rounded-md border-2 border-ink bg-white shadow-[3px_3px_0_0_#1b1c1d]">
                <span class="sr-only">Buka menu navigasi</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                    <path d="M3 6h18M3 12h18M3 18h18"/>
                </svg>
            </button>
        </div>

        {{-- Navigasi mobile --}}
        <nav id="nav-mobile" data-nav-panel hidden class="border-t border-line bg-header md:hidden">
            <ul class="mx-auto max-w-[1280px] px-5 py-3 sm:px-8">
                @foreach ($links as $link)
                    @php $active = request()->routeIs($link['route']); @endphp
                    <li>
                        <a href="{{ route($link['route']) }}"
                           @class([
                               'block rounded-md px-4 py-3 text-sm font-semibold tracking-wide uppercase',
                               'bg-varya-dark text-white' => $active,
                               'text-ink' => ! $active,
                           ])>
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