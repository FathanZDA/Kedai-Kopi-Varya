@props([
    'menu',
    /** true bila kartu tidak punya tombol pesan (halaman pelanggan A) */
    'readonly' => true,
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
    $initial = mb_strtoupper(mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $menu->name), 0, 1));
@endphp

<article @class([
    'group relative flex flex-col overflow-hidden bg-surface transition-transform duration-200 hover:-translate-y-1',
    'brut',
    'opacity-70' => ! $menu->is_available,
])>
    {{-- Foto produk --}}
    <div class="relative aspect-[4/3] w-full overflow-hidden border-b-[3px] border-ink">
        @if ($menu->image)
            <img src="{{ asset($menu->image) }}"
                 alt="{{ $menu->name }}"
                 loading="lazy"
                 class="h-full w-full object-cover {{ $menu->is_available ? '' : 'grayscale' }}">
        @else
            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br {{ $tone }} {{ $menu->is_available ? '' : 'grayscale' }}">
                <span class="font-display text-6xl font-black text-white/25 select-none">{{ $initial }}</span>
            </div>
        @endif

        {{-- Badge status --}}
        @if ($menu->is_featured && $menu->is_available)
            <span class="tag absolute top-3 left-3 rounded-full bg-varya-dark px-3 py-1 text-[10px] font-bold text-white shadow-[2px_2px_0_0_#1b1c1d] -rotate-6">
                Terlaris
            </span>
        @elseif (! $menu->is_available)
            <span class="tag absolute top-3 left-3 rounded-full bg-brown px-3 py-1 text-[10px] font-bold text-white shadow-[2px_2px_0_0_#1b1c1d] -rotate-6">
                Stok Habis
            </span>
        @endif
    </div>

    {{-- Informasi produk --}}
    <div class="flex flex-1 flex-col gap-1.5 p-4">
        <h3 class="product-name text-sm leading-snug">{{ $menu->name }}</h3>

        <p class="font-display text-lg font-bold text-varya-dark">
            {{ $menu->formatted_price }}
        </p>

        @if ($menu->description)
            <p class="font-serif text-[13px] leading-relaxed text-ink-soft">{{ $menu->description }}</p>
        @endif
    </div>
</article>