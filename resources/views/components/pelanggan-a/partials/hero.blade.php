@props([
    'promos' => [],
])

@php
    $slides = $promos->isEmpty() ? collect() : $promos->values();
@endphp

@if ($slides->isNotEmpty())
    <section class="mx-auto max-w-[1280px] px-5 pt-4 sm:px-8" aria-label="Promo kedai" data-reveal>
        <div class="brut relative overflow-hidden bg-varya-dark" data-hero-root>
            {{-- Panggung slide: semua slide ditumpuk, crossfade lewat .is-active --}}
            <div class="relative h-[360px] sm:h-[480px] lg:h-[540px]">
                @foreach ($slides as $index => $promo)
                    <div data-hero-slide="{{ $index }}"
                         @class([
                             'absolute inset-0',
                             'is-active' => $index === 0,
                         ])
                         aria-hidden="{{ $index === 0 ? 'false' : 'true' }}">
                        @if ($promo->gambar && file_exists(public_path($promo->gambar)))
                            {{-- Foto hanya latar: sedikit dilembutkan agar teks fokus, tapi tetap terlihat. --}}
                            <img src="{{ asset($promo->gambar) }}" alt="{{ $promo->judul }}"
                                 class="absolute inset-0 h-full w-full scale-105 object-cover blur-[1.5px] saturate-[0.95]">
                        @else
                            {{-- Placeholder sampai admin mengunggah foto promo [AD-04] --}}
                            <div class="absolute inset-0 bg-gradient-to-br from-[#2f4f3f] via-[#1f3b2d] to-[#14261d]"></div>
                            <div class="absolute inset-0 opacity-[0.07]"
                                 style="background-image:repeating-linear-gradient(45deg,#fff 0 2px,transparent 2px 14px)"></div>
                        @endif

                        {{-- Selubung terang tipis: foto tetap terbaca, bukan tenggelam (mengikuti desain). --}}
                        <div class="absolute inset-0 bg-[#eef3ea]/50"></div>

                        <div class="relative flex min-h-[360px] flex-col items-center justify-center gap-2.5 px-6 py-10 text-center sm:min-h-[480px] sm:gap-3.5 sm:px-16 sm:py-14 lg:min-h-[540px]">
                            @if ($index === 0)
                                {{-- Label "Wajib Coba" menempel di samping chip jam buka. --}}
                                <div class="flex flex-wrap items-center justify-center">
                                    <p class="tag flex items-center gap-2 rounded-full bg-varya-dark px-5 py-2 text-[11px] text-white shadow-[3px_3px_0_0_#1b1c1d]">
                                        <span class="inline-block h-2 w-2 rounded-full bg-varya-light"></span>
                                        Buka <span class="text-white/80">//</span> 08.00 - 23.00 WIB
                                    </p>

                                    <span class="tag ml-3 -rotate-[5deg] translate-y-1 rounded-[6px] bg-gradient-to-b from-[#c25c2c] to-rust px-3 py-1.5 text-[11px] font-bold tracking-wide text-white uppercase shadow-[2px_2px_0_0_#1b1c1d]">
                                        Wajib Coba! 🔥
                                    </span>
                                </div>
                            @endif

                            <h1 class="title-xl text-5xl text-ink drop-shadow-[0_2px_6px_rgba(255,255,255,0.65)] sm:text-7xl lg:text-8xl">
                                {{ $promo->judul }}
                            </h1>

                            @if ($promo->deskripsi)
                                <p class="max-w-2xl rounded-md border-2 border-ink bg-varya-light px-4 py-2 font-serif text-[14px] leading-relaxed font-semibold text-varya-dark shadow-[3px_3px_0_0_#1b1c1d] sm:text-base">
                                    {{ $promo->deskripsi }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Tombol navigasi slide --}}
            @if ($slides->count() > 1)
                <button type="button" data-hero-prev
                        class="absolute top-1/2 left-4 z-20 inline-flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-lg border-[3px] border-ink bg-[#efedee] shadow-[4px_4px_0_0_#1b1c1d] transition-transform hover:-translate-y-1/2 hover:scale-105 sm:left-8"
                        aria-label="Slide sebelumnya">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m0 0 6 6m-6-6 6-6"/></svg>
                </button>

                <button type="button" data-hero-next
                        class="absolute top-1/2 right-4 z-20 inline-flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-lg border-[3px] border-ink bg-[#efedee] shadow-[4px_4px_0_0_#1b1c1d] transition-transform hover:-translate-y-1/2 hover:scale-105 sm:right-8"
                        aria-label="Slide berikutnya">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m0 0-6-6m6 6-6 6"/></svg>
                </button>

                {{-- Indikator --}}
                <div class="absolute bottom-4 left-1/2 z-20 flex -translate-x-1/2 items-center gap-2">
                    @foreach ($slides as $index => $promo)
                        <button type="button" data-hero-dot="{{ $index }}"
                                @class([
                                    'h-2.5 rounded-full border-2 border-ink/25 transition-all',
                                    'w-7 bg-varya-dark' => $index === 0,
                                    'w-2.5 bg-white/80 hover:bg-white' => $index !== 0,
                                ])
                                aria-label="Ke slide {{ $index + 1 }}"
                                @if ($index === 0) aria-current="true" @endif></button>
                    @endforeach
                </div>

                {{-- Progress autoplay 6 detik --}}
                <div data-hero-progress
                     aria-hidden="true"
                     class="pointer-events-none absolute inset-x-0 bottom-0 z-20 h-1 bg-white/25">
                    <span data-hero-progress-bar></span>
                </div>
            @endif
        </div>
    </section>
@endif
