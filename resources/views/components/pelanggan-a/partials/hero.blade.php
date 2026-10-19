@props([
    'promos' => [],
])

@php
    $slides = $promos->isEmpty() ? collect() : $promos->values();
@endphp

@if ($slides->isNotEmpty())
    <section class="mx-auto max-w-[1280px] px-5 pt-4 sm:px-8" aria-label="Promo kedai">
        <div class="brut relative overflow-hidden bg-varya-dark">
            {{-- Slide aktif --}}
            @foreach ($slides as $index => $promo)
                <div data-hero-slide="{{ $index }}"
                     @class([
                         'relative',
                         'block' => $index === 0,
                         'hidden' => $index !== 0,
                     ])>
                    <div class="relative h-[360px] sm:h-[480px] lg:h-[540px]">
                        @if ($promo->image && file_exists(public_path($promo->image)))
                            <img src="{{ asset($promo->image) }}" alt="{{ $promo->title }}"
                                 class="absolute inset-0 h-full w-full object-cover">
                        @else
                            {{-- Placeholder sampai admin mengunggah foto promo [AD-04] --}}
                            <div class="absolute inset-0 bg-gradient-to-br from-[#2f4f3f] via-[#1f3b2d] to-[#14261d]"></div>
                            <div class="absolute inset-0 opacity-[0.07]"
                                 style="background-image:repeating-linear-gradient(45deg,#fff 0 2px,transparent 2px 14px)"></div>
                        @endif

                        {{-- Overlay supaya teks tetap terbaca di atas foto --}}
                        <div class="absolute inset-0 bg-ink/35"></div>

                        <div class="relative flex h-full flex-col items-center justify-center gap-5 px-6 text-center sm:px-16">
                            @if ($index === 0)
                                <p class="tag flex items-center gap-2 rounded-full bg-varya px-5 py-2 text-[10px] text-white shadow-[3px_3px_0_0_#1b1c1d] sm:text-[11px]">
                                    <span class="inline-block h-2 w-2 rounded-full bg-varya-light"></span>
                                    Buka <span class="opacity-60">//</span> 08.00 - 23.00 WIB
                                </p>
                            @endif

                            <h1 class="title-xl text-5xl text-white sm:text-7xl lg:text-8xl">
                                {{ $promo->title }}
                            </h1>

                            @if ($promo->subtitle)
                                <p class="max-w-3xl bg-varya-light px-5 py-2 font-display text-sm font-bold tracking-wide text-varya-dark uppercase sm:text-base">
                                    {{ $promo->subtitle }}
                                </p>
                            @endif

                            @if ($promo->description)
                                <p class="max-w-2xl font-serif text-sm text-white/85 sm:text-base">{{ $promo->description }}</p>
                            @endif
                        </div>

                        {{-- Stiker miring --}}
                        @if ($promo->badge_text && $index === 0)
                            <span class="tag absolute top-8 right-6 rotate-6 border-2 border-ink bg-rust px-3 py-1.5 text-[10px] font-bold text-white shadow-[3px_3px_0_0_#1b1c1d] sm:top-14 sm:right-24">
                                {{ $promo->badge_text }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- Tombol navigasi slide --}}
            @if ($slides->count() > 1)
                <button type="button" data-hero-prev
                        class="absolute top-1/2 left-4 inline-flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-lg border-[3px] border-ink bg-[#efedee] shadow-[4px_4px_0_0_#1b1c1d] transition-transform hover:-translate-y-1/2 hover:scale-105 sm:left-8"
                        aria-label="Slide sebelumnya">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m0 0 6 6m-6-6 6-6"/></svg>
                </button>

                <button type="button" data-hero-next
                        class="absolute top-1/2 right-4 inline-flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-lg border-[3px] border-ink bg-[#efedee] shadow-[4px_4px_0_0_#1b1c1d] transition-transform hover:-translate-y-1/2 hover:scale-105 sm:right-8"
                        aria-label="Slide berikutnya">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m0 0-6-6m6 6-6 6"/></svg>
                </button>

                {{-- Indikator --}}
                <div class="absolute bottom-4 left-1/2 flex -translate-x-1/2 items-center gap-2">
                    @foreach ($slides as $index => $promo)
                        <button type="button" data-hero-dot="{{ $index }}"
                                @class([
                                    'h-2.5 rounded-full transition-all',
                                    'w-7 bg-varya' => $index === 0,
                                    'w-2.5 bg-white/60 hover:bg-white/90' => $index !== 0,
                                ])
                                aria-label="Ke slide {{ $index + 1 }}"></button>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif