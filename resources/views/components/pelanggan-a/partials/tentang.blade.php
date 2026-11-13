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

    {{-- Dua panel landscape (lebar > tinggi) dengan tinggi sama, tanpa border dan hanya bayangan kanan-bawah (desain). --}}
    <div class="mt-8 grid gap-6 lg:grid-cols-2 lg:items-stretch lg:gap-8">
        {{-- Kolom teks --}}
        <div class="about-shadow flex flex-col justify-between gap-8 bg-surface p-6 sm:p-8" data-reveal data-reveal-delay="80">
            <div>
                <p class="font-display text-sm font-bold tracking-wide text-varya-dark uppercase">
                    Sejak. 2024 <span class="mx-1 opacity-50">•</span> Kedai Varya
                </p>

                <hr class="mt-4 mb-6 border-line-strong">

                <p class="font-serif text-[15px] leading-[1.9] text-ink-soft sm:text-base">
                    {{ $store?->sejarah }}
                </p>
            </div>

            <dl class="grid grid-cols-3 divide-x divide-line-strong rounded-xl border-2 border-line-strong px-2 py-5">
                @foreach ($stats as $stat)
                    <div class="px-3 text-center">
                        <dt class="font-display text-xl font-black text-varya-dark sm:text-2xl">{{ $stat['value'] }}</dt>
                        <dd class="tag mt-1.5 text-[11px] leading-tight text-muted sm:text-[11px]">{{ $stat['label'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- Kolom foto: rasio landscape, tinggi mengikuti panel teks --}}
        <div class="about-shadow relative h-[280px] overflow-hidden bg-surface sm:h-[340px] lg:h-auto lg:min-h-[380px]" data-reveal data-reveal-delay="160">
            <span class="tag absolute top-0 left-0 z-10 bg-ink/70 px-4 py-1.5 text-[11px] text-white">
                <span class="opacity-50">//</span> Kedai Varya
            </span>

            @if ($store?->foto_tentang)
                <img src="{{ asset($store->foto_tentang) }}"
                     alt="Suasana Kedai Kopi Varya"
                     class="absolute inset-0 h-full w-full object-cover">
            @else
                {{-- Placeholder selama admin belum mengunggah foto di halaman promosi --}}
                <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-[#7f8f6a] via-[#5d7a63] to-[#33463a]">
                    <div class="h-full w-full opacity-15"
                         style="background-image:repeating-linear-gradient(135deg,#fff 0 3px,transparent 3px 22px)"></div>
                </div>
            @endif
        </div>
    </div>
</section>
