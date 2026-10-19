@props([
    'store' => null,
])

@php
    $stats = [
        ['value' => '100%', 'label' => 'Kopi Lokal Pilihan'],
        ['value' => '15+', 'label' => 'Kreasi Kopi & Nonkopi'],
        ['value' => 'Setiap Hari', 'label' => 'Siap Melayani'],
    ];
@endphp

<section id="tentang" class="mx-auto max-w-[1280px] px-5 pt-16 sm:px-8 sm:pt-20">
    <x-section-heading title="Tentang Kami" />

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.15fr_1fr] lg:gap-8">
        {{-- Kolom teks --}}
        <div class="brut flex flex-col justify-between gap-8 bg-surface p-6 sm:p-8">
            <div>
                <p class="font-display text-sm font-bold tracking-wide text-varya-dark uppercase">
                    Sejak. 2024 <span class="mx-1 opacity-50">•</span> Kedai Varya
                </p>

                <hr class="mt-4 mb-6 border-line-strong">

                <p class="font-serif text-[15px] leading-[1.9] text-ink-soft sm:text-base">
                    {{ $store?->about_text }}
                </p>
            </div>

            <dl class="grid grid-cols-3 divide-x divide-line-strong rounded-xl border-2 border-line-strong px-2 py-5">
                @foreach ($stats as $stat)
                    <div class="px-3 text-center">
                        <dt class="font-display text-xl font-black text-varya-dark sm:text-2xl">{{ $stat['value'] }}</dt>
                        <dd class="tag mt-1.5 text-[9px] leading-tight text-muted sm:text-[10px]">{{ $stat['label'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- Kolom foto --}}
        <div class="brut relative min-h-[280px] overflow-hidden bg-surface p-2 lg:min-h-0">
            <span class="tag absolute top-6 left-6 z-10 bg-ink/70 px-4 py-1.5 text-[10px] text-white">
                <span class="opacity-50">//</span> Kedai Varya
            </span>

            {{-- Placeholder sampai foto kedai tersedia di public/images --}}
            <div class="flex h-full min-h-[264px] w-full items-center justify-center rounded-lg bg-gradient-to-br from-[#7f8f6a] via-[#5d7a63] to-[#33463a] lg:min-h-full">
                <div class="h-full w-full opacity-15"
                     style="background-image:repeating-linear-gradient(135deg,#fff 0 3px,transparent 3px 22px)"></div>
            </div>
        </div>
    </div>
</section>