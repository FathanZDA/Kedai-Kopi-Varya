@props([
    'variant' => 'dark', // dark = untuk header, light = untuk footer
    'href' => route('pelanggan-a.beranda'),
])

@php
    $markClasses = $variant === 'light' ? 'brightness-0 invert' : '';
    $nameClasses = $variant === 'light' ? 'text-white' : 'text-ink';
@endphp

<a href="{{ $href }}" class="group inline-flex items-center gap-3">
    <img src="{{ asset('images/brand/varya-mark.png') }}"
         alt=""
         width="228"
         height="240"
         class="h-11 w-auto shrink-0 object-contain sm:h-12 {{ $markClasses }}">

    <span class="font-display text-xl font-black tracking-tight uppercase {{ $nameClasses }}">
        Kedai Varya
    </span>
</a>
