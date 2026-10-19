@props([
    'store' => null,
])

@php
    // Titik tengah Kedai Kopi Varya, Wonokromo, Surabaya.
    // Pakai OpenStreetMap supaya tidak butuh API key.
    $lat = -7.3015;
    $lon = 112.7390;
    $mapUrl = 'https://www.openstreetmap.org/export/embed.html'
        .'?bbox='.($lon - 0.006).'%2C'.($lat - 0.0045).'%2C'.($lon + 0.006).'%2C'.($lat + 0.0045)
        .'&layer=mapnik&marker='.$lat.'%2C'.$lon;
@endphp

<section id="lokasi" class="mx-auto max-w-[1280px] px-5 pt-16 sm:px-8 sm:pt-20">
    <x-section-heading title="Lokasi" />

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.15fr_1fr] lg:gap-8">
        {{-- Peta --}}
        <div class="brut flex flex-col overflow-hidden bg-surface">
            <div class="flex items-center justify-between gap-3 bg-ink px-4 py-2.5 text-white">
                <p class="tag flex items-center gap-2 text-[10px]">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    Kedai Kopi Varya <span class="opacity-50">•</span> Live GPS Pin
                </p>
                <p class="tag hidden text-[10px] opacity-70 sm:block">Peta Terverifikasi</p>
            </div>

            <iframe src="{{ $mapUrl }}"
                    title="Peta lokasi Kedai Kopi Varya"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    class="h-[320px] w-full border-0 lg:h-full lg:min-h-[400px]"></iframe>
        </div>

        {{-- Informasi lokasi --}}
        <div class="brut flex flex-col gap-6 bg-surface p-6 sm:p-8">
            <div>
                <h3 class="flex items-center gap-2.5 font-display text-lg font-bold">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-varya-dark" aria-hidden="true">
                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    Alamat Kedai
                </h3>
                <p class="mt-2 font-serif text-[15px] leading-relaxed text-ink-soft">{{ $store?->address }}</p>
            </div>

            @if ($store?->landmark)
                <div>
                    <h3 class="flex items-center gap-2.5 font-display text-lg font-bold">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-varya-dark" aria-hidden="true">
                            <path d="M3 6l6-3 6 3 6-3v15l-6 3-6-3-6 3V6Z"/><path d="M9 3v15M15 6v15"/>
                        </svg>
                        Petunjuk Lokasi
                    </h3>
                    <p class="mt-2 font-serif text-[15px] leading-relaxed text-ink-soft">{{ $store->landmark }}</p>
                </div>
            @endif

            @if ($store?->instagram)
                <div>
                    <h3 class="flex items-center gap-2.5 font-display text-lg font-bold">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-varya-dark" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/>
                        </svg>
                        Instagram
                    </h3>
                    <a href="https://instagram.com/{{ ltrim($store->instagram, '@') }}"
                       target="_blank" rel="noopener noreferrer"
                       class="mt-2 inline-flex items-center gap-2 rounded-md bg-varya-light px-3 py-1.5 font-mono text-sm text-varya-dark transition-colors hover:bg-varya-muted hover:text-white">
                        {{ $store->instagram }}
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M7 17 17 7M9 7h8v8"/>
                        </svg>
                    </a>
                </div>
            @endif

            <a href="{{ $store?->maps_url ?? '#' }}"
               target="_blank" rel="noopener noreferrer"
               class="brut-sm tag mt-auto inline-flex items-center justify-center gap-2 bg-varya px-6 py-4 text-[11px] font-bold text-white transition-transform hover:-translate-y-0.5">
                Buka di Google Maps
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M7 17 17 7M9 7h8v8"/>
                </svg>
            </a>
        </div>
    </div>
</section>