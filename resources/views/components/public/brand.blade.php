@props([
    'variant' => 'dark', // dark = untuk header, light = untuk footer
    'href' => route('pelanggan-a.beranda'),
])

@php
    $markClasses = $variant === 'light'
        ? 'bg-footer text-white border-white/30'
        : 'bg-white text-ink border-ink';
    $nameClasses = $variant === 'light' ? 'text-white' : 'text-ink';
@endphp

<a href="{{ $href }}" class="group inline-flex items-center gap-3">
    {{-- Logo mark. Ganti dengan <img> bila file logo asli sudah tersedia. --}}
    <span class="flex h-12 w-12 flex-col items-center justify-center rounded-md border-2 {{ $markClasses }}">
        <span class="font-display text-xl leading-none font-black">V</span>
        <span class="tag text-[6px] leading-tight opacity-70">Varya</span>
    </span>

    <span class="font-display text-xl font-black tracking-tight uppercase {{ $nameClasses }}">
        Kedai Varya
    </span>
</a>