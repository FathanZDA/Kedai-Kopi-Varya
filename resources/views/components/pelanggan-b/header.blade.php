@php
    $isBeranda = request()->routeIs('pelanggan-b.beranda');
    $isOrderFlow = request()->routeIs([
        'pelanggan-b.produk',
        'pelanggan-b.keranjang',
        'pelanggan-b.checkout',
        'pelanggan-b.pembayaran',
        'pelanggan-b.berhasil',
    ]);

    $links = [
        ['href' => route('pelanggan-b.beranda'), 'label' => 'Beranda', 'active' => $isBeranda],
        ['href' => route('pelanggan-b.beranda').'#tentang', 'label' => 'Tentang Kami', 'active' => false],
        ['href' => route('pelanggan-b.beranda').'#menu', 'label' => 'Menu', 'active' => $isOrderFlow],
        ['href' => route('pelanggan-b.beranda').'#lokasi', 'label' => 'Lokasi', 'active' => false],
    ];
@endphp

<header class="sticky top-0 z-50">
    <div class="border-b border-line bg-header/95 backdrop-blur">
        <div class="mx-auto flex h-[76px] max-w-[1280px] items-center justify-between gap-3 px-5 sm:gap-6 sm:px-8">
            <x-public.brand :href="route('pelanggan-b.beranda')" />

            {{-- Navigasi desktop --}}
            <nav class="hidden items-center gap-1 lg:flex" aria-label="Navigasi utama">
                @foreach ($links as $link)
                    <a href="{{ $link['href'] }}"
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

            <div class="flex items-center gap-2.5">
                {{-- Keranjang selalu terlihat, termasuk di layar kecil --}}
                <a href="{{ route('pelanggan-b.keranjang') }}"
                   class="inline-flex h-11 items-center gap-2 rounded-full border-2 border-ink bg-varya-dark px-3 text-white shadow-[3px_3px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5 sm:px-5"
                   aria-label="Buka keranjang">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 7h12l1 13H5L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/>
                    </svg>
                    <span class="tag hidden text-[10px] font-bold sm:inline">Keranjang</span>
                    <span data-cart-count
                          class="tag inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-rust px-1.5 text-[10px] font-bold text-white">0</span>
                </a>

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
                           @class([
                               'block rounded-md px-4 py-3 text-sm font-semibold tracking-wide uppercase',
                               'bg-varya-dark text-white' => $link['active'],
                               'text-ink' => ! $link['active'],
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
