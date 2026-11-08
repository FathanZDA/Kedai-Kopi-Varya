@props([
    'store' => null,
])

@php
    $hours = $store?->hours_summary ?? '08.00 - 23.00';
    $items = ['SEJAK. 2024', 'KEDAI VARYA', 'SURABAYA', 'TEMPAT NONGKRONG'];
@endphp

<div class="mx-auto max-w-[1280px] px-5 pt-5 sm:px-8">
    <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 rounded-full bg-varya-dark px-5 py-2.5 text-white">
        <p class="tag flex flex-wrap items-center gap-x-3 text-[10px] sm:text-[11px]">
            @foreach ($items as $item)
                <span>{{ $item }}</span>
                <span class="opacity-50" aria-hidden="true">//</span>
            @endforeach
        </p>

        <p class="tag flex items-center gap-2 text-[10px] sm:text-[11px]">
            <span class="inline-block h-2 w-2 rounded-full bg-varya-light"></span>
            <span>Buka {{ $hours }}</span>
        </p>
    </div>
</div>