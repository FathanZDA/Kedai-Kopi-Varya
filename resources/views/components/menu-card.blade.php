@props([
    'menu',
    /** true bila kartu tidak punya tombol pesan (halaman pelanggan A) */
    'readonly' => true,
    /** URL halaman detail produk; null berarti kartu tidak bisa diklik */
    'detailUrl' => null,
    /** true untuk menampilkan kontrol pesan/stepper (mode Pelanggan B) */
    'orderable' => false,
])

@php
    // Placeholder dipakai selama foto produk belum diunggah lewat admin [AD-02].
    // Begitu file asli ada di public/images, komponen ini otomatis memakainya.
    $tones = [
        'from-[#8a6a4f] to-[#5d4633]', 'from-[#7d5a3c] to-[#4e3826]',
        'from-[#6b8f71] to-[#3f5c46]', 'from-[#b07a3c] to-[#7a4f24]',
        'from-[#9c8a5a] to-[#6b5c34]', 'from-[#8f5b4a] to-[#5d382c]',
        'from-[#a3835f] to-[#6f563a]', 'from-[#7f6b52] to-[#54453a]',
        'from-[#96805c] to-[#635537]', 'from-[#b5916a] to-[#7d6041]',
        'from-[#8c8577] to-[#5b574d]', 'from-[#6f6357] to-[#453d34]',
    ];
    $tone = $tones[crc32($menu->slug) % count($tones)];
    $initial = mb_strtoupper(mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $menu->nama), 0, 1));
@endphp

<article @class([
    'group relative flex h-full flex-col overflow-hidden bg-surface transition-transform duration-200 hover:-translate-y-1',
    'brut',
    'opacity-70' => ! $menu->tersedia,
])
data-menu-card
data-menu-id="{{ $menu->id }}"
data-menu-slug="{{ $menu->slug }}"
data-menu-name="{{ $menu->nama }}"
data-menu-price="{{ $menu->harga }}"
data-menu-image="{{ $menu->foto ? asset($menu->foto) : '' }}"
data-menu-initial="{{ $initial }}">

    {{-- Foto produk --}}
    @if ($detailUrl)
        <a href="{{ $detailUrl }}"
           class="relative block aspect-[4/3] w-full overflow-hidden border-b-[3px] border-ink"
           aria-label="Lihat detail {{ $menu->nama }}">
    @else
        <div class="relative aspect-[4/3] w-full overflow-hidden border-b-[3px] border-ink">
    @endif
        @if ($menu->foto)
            <img src="{{ asset($menu->foto) }}"
                 alt="{{ $menu->nama }}"
                 loading="lazy"
                 class="h-full w-full object-cover {{ $menu->tersedia ? '' : 'grayscale' }}">
        @else
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br {{ $tone }} {{ $menu->tersedia ? '' : 'grayscale' }}">
                <span class="font-display text-6xl font-black text-white/25 select-none">{{ $initial }}</span>
            </div>
        @endif

        {{-- Badge status --}}
        @if (! $menu->tersedia)
            <span class="tag absolute top-3 left-3 rounded-full bg-brown px-3 py-1 text-[10px] font-bold text-white shadow-[2px_2px_0_0_#1b1c1d] -rotate-6">
                Stok Habis
            </span>
        @endif
    @if ($detailUrl)
        </a>
    @else
        </div>
    @endif

    {{-- Informasi produk --}}
    <div class="flex flex-1 flex-col gap-1.5 p-4">
        @if ($detailUrl)
            <a href="{{ $detailUrl }}" class="focus:outline-none focus-visible:underline">
                <h3 class="product-name text-sm leading-snug">{{ $menu->nama }}</h3>
            </a>
        @else
            <h3 class="product-name text-sm leading-snug">{{ $menu->nama }}</h3>
        @endif

        <p class="font-display text-lg font-bold text-varya-dark">
            {{ $menu->formatted_price }}
        </p>

        @if ($menu->deskripsi)
            <p class="font-serif text-[13px] leading-relaxed text-ink-soft">{{ $menu->deskripsi }}</p>
        @endif

        @if ($orderable)
            <div class="mt-auto pt-3" data-order-control>
                @if ($menu->tersedia)
                    <button type="button"
                            data-add-to-cart
                            class="tag flex w-full items-center justify-center gap-1.5 rounded-md border-2 border-ink bg-varya-dark px-3 py-2.5 text-[10px] font-bold text-white shadow-[2px_2px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5">
                        <span class="text-sm leading-none" aria-hidden="true">+</span>
                        Pesan
                    </button>

                    <div data-qty-stepper hidden class="flex items-stretch overflow-hidden rounded-md border-2 border-ink bg-surface shadow-[2px_2px_0_0_#1b1c1d]">
                        <button type="button"
                                data-qty-minus
                                class="grid w-10 place-items-center border-r-2 border-ink text-lg font-bold transition-colors hover:bg-varya-light"
                                aria-label="Kurangi jumlah {{ $menu->nama }}">−</button>
                        <span data-qty-value class="tag flex flex-1 items-center justify-center text-xs font-bold">0</span>
                        <button type="button"
                                data-qty-plus
                                class="grid w-10 place-items-center border-l-2 border-ink bg-varya-dark text-lg font-bold text-white transition-colors hover:bg-varya"
                                aria-label="Tambah jumlah {{ $menu->nama }}">+</button>
                    </div>
                @else
                    <button type="button"
                            disabled
                            class="tag flex w-full items-center justify-center rounded-md border-2 border-line-strong bg-[#efedee] px-3 py-2.5 text-[10px] font-bold text-muted">
                        Habis Terjual
                    </button>
                @endif
            </div>
        @endif
    </div>
</article>
