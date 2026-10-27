@props([
    'store' => null,
    'hours' => collect(),
])

@php
    // Titik tengah Kedai Kopi Varya, Wonokromo, Surabaya.
    // Pakai OpenStreetMap supaya tidak butuh API key.
    $lat = -7.3015;
    $lon = 112.7390;
    $mapUrl = $store?->maps_embed ?: 'https://www.openstreetmap.org/export/embed.html'
        .'?bbox='.($lon - 0.006).'%2C'.($lat - 0.0045).'%2C'.($lon + 0.006).'%2C'.($lat + 0.0045)
        .'&layer=mapnik&marker='.$lat.'%2C'.$lon;
    $fasilitas = $store?->fasilitas ?? [];
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
                    {{ $store?->nama_toko ?? 'Kedai Kopi Varya' }} <span class="opacity-50">•</span> Lokasi
                </p>
                <p class="tag hidden text-[10px] opacity-70 sm:block">Peta Terverifikasi</p>
            </div>

            <iframe src="{{ $mapUrl }}"
                    title="Peta lokasi {{ $store?->nama_toko ?? 'Kedai Kopi Varya' }}"
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
                <p class="mt-2 font-serif text-[15px] leading-relaxed text-ink-soft">{{ $store?->alamat }}</p>
            </div>

            @if ($hours->isNotEmpty())
                <div>
                    <h3 class="flex items-center gap-2.5 font-display text-lg font-bold">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-varya-dark" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                        </svg>
                        Jam Operasional
                    </h3>
                    <dl class="mt-2 grid gap-1.5 font-serif text-[14px] text-ink-soft">
                        @foreach ($hours as $hour)
                            <div class="flex items-center justify-between gap-4 border-b border-line/60 pb-1.5 last:border-0 last:pb-0">
                                <dt>{{ $hour->hari_label }}</dt>
                                <dd class="font-mono text-[12px] text-ink">{{ $hour->rentang }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            @if ($fasilitas !== [])
                <div>
                    <h3 class="flex items-center gap-2.5 font-display text-lg font-bold">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-varya-dark" aria-hidden="true">
                            <path d="M3 9l1.5-5h15L21 9"/><path d="M4 9v11h16V9"/><path d="M9 20v-6h6v6"/>
                        </svg>
                        Fasilitas
                    </h3>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($fasilitas as $item)
                            <span class="tag rounded-full border-2 border-line-strong bg-canvas px-3 py-1.5 text-[9px] font-bold text-ink-soft">
                                {{ $item }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid gap-3 sm:grid-cols-2">
                @if ($store?->telepon)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $store->telepon) }}"
                       class="tag inline-flex items-center gap-2 rounded-md border-2 border-line-strong bg-surface px-3 py-2 text-[10px] font-bold text-ink transition-colors hover:border-ink">
                        Telepon
                    </a>
                @endif

                @if ($store?->whatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $store->whatsapp) }}"
                       target="_blank" rel="noopener noreferrer"
                       class="tag inline-flex items-center gap-2 rounded-md border-2 border-line-strong bg-surface px-3 py-2 text-[10px] font-bold text-ink transition-colors hover:border-ink">
                        WhatsApp
                    </a>
                @endif

                @if ($store?->instagram)
                    <a href="https://instagram.com/{{ ltrim($store->instagram, '@') }}"
                       target="_blank" rel="noopener noreferrer"
                       class="tag inline-flex items-center gap-2 rounded-md border-2 border-line-strong bg-surface px-3 py-2 text-[10px] font-bold text-ink transition-colors hover:border-ink">
                        {{ $store->instagram }}
                    </a>
                @endif
            </div>

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
