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
    <x-section-heading title="Lokasi" data-reveal />

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.15fr_1fr] lg:gap-8">
        {{-- Peta --}}
        <div class="brut flex flex-col overflow-hidden bg-surface" data-reveal data-reveal-delay="80">
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
        <div class="brut flex flex-col gap-6 bg-surface p-6 sm:p-8" data-reveal data-reveal-delay="160">
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

            {{-- Kontak dan media sosial: ikon saja, sejajar menyamping. --}}
            <div class="flex flex-wrap items-center gap-2.5">
                @if ($store?->telepon)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $store->telepon) }}"
                       aria-label="Telepon {{ $store?->nama_toko ?? 'Kedai Kopi Varya' }}"
                       class="group relative grid h-11 w-11 place-items-center rounded-md border-2 border-line-strong bg-surface text-ink transition-colors hover:border-ink hover:bg-ink hover:text-white focus-visible:border-ink focus-visible:bg-ink focus-visible:text-white focus-visible:outline-none">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.4 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/>
                        </svg>
                        <span class="tag pointer-events-none absolute -top-9 left-1/2 -translate-x-1/2 rounded border-2 border-ink bg-ink px-2 py-1 text-[11px] whitespace-nowrap text-white opacity-0 shadow-[2px_2px_0_0_#115231] transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100">Telepon</span>
                    </a>
                @endif

                @if ($store?->whatsapp)
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $store->whatsapp) }}"
                       target="_blank" rel="noopener noreferrer"
                       aria-label="WhatsApp {{ $store?->nama_toko ?? 'Kedai Kopi Varya' }}"
                       class="group relative grid h-11 w-11 place-items-center rounded-md border-2 border-line-strong bg-surface text-ink transition-colors hover:border-ink hover:bg-ink hover:text-white focus-visible:border-ink focus-visible:bg-ink focus-visible:text-white focus-visible:outline-none">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12.04 2A9.9 9.9 0 0 0 2.13 11.9c0 1.75.46 3.46 1.33 4.96L2 22l5.27-1.38a9.9 9.9 0 0 0 4.77 1.22h.01a9.9 9.9 0 0 0 9.9-9.9A9.9 9.9 0 0 0 12.04 2Zm5.8 14.1c-.24.68-1.4 1.3-1.94 1.38-.5.08-1.13.11-1.82-.11-.42-.13-.96-.31-1.65-.6-2.9-1.25-4.8-4.17-4.94-4.36-.15-.2-1.18-1.57-1.18-3 0-1.42.75-2.12 1.02-2.41.27-.3.58-.37.78-.37h.56c.18 0 .42-.07.66.5.24.58.82 2 .89 2.15.07.15.12.32.02.51-.1.2-.15.32-.3.5-.15.17-.31.38-.44.51-.15.15-.3.31-.13.61.17.3.76 1.25 1.63 2.02 1.12 1 2.06 1.31 2.36 1.45.3.15.47.13.64-.08.17-.2.73-.85.93-1.14.2-.3.39-.25.66-.15.27.1 1.7.8 2 .95.3.15.49.22.56.35.08.13.08.75-.16 1.43Z"/>
                        </svg>
                        <span class="tag pointer-events-none absolute -top-9 left-1/2 -translate-x-1/2 rounded border-2 border-ink bg-ink px-2 py-1 text-[11px] whitespace-nowrap text-white opacity-0 shadow-[2px_2px_0_0_#115231] transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100">WhatsApp</span>
                    </a>
                @endif

                @if ($store?->instagram)
                    <a href="https://instagram.com/{{ ltrim($store->instagram, '@') }}"
                       target="_blank" rel="noopener noreferrer"
                       aria-label="Instagram {{ $store->instagram }}"
                       class="group relative grid h-11 w-11 place-items-center rounded-md border-2 border-line-strong bg-surface text-ink transition-colors hover:border-ink hover:bg-ink hover:text-white focus-visible:border-ink focus-visible:bg-ink focus-visible:text-white focus-visible:outline-none">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M12 2.16c3.2 0 3.58.01 4.85.07 3.25.15 4.77 1.69 4.92 4.92.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.15 3.23-1.66 4.77-4.92 4.92-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-3.26-.15-4.77-1.7-4.92-4.92C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85C2.38 3.92 3.9 2.38 7.15 2.23 8.42 2.17 8.8 2.16 12 2.16ZM12 0C8.74 0 8.33.01 7.05.07 2.7.27.27 2.69.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.2 4.36 2.62 6.78 6.98 6.98C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c4.35-.2 6.78-2.62 6.98-6.98.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.2-4.35-2.62-6.78-6.98-6.98C15.67.01 15.26 0 12 0Zm0 5.84A6.16 6.16 0 1 0 18.16 12 6.16 6.16 0 0 0 12 5.84Zm0 10.16A4 4 0 1 1 16 12a4 4 0 0 1-4 4Zm6.41-11.85a1.44 1.44 0 1 0 1.44 1.44 1.44 1.44 0 0 0-1.44-1.44Z"/>
                        </svg>
                        <span class="tag pointer-events-none absolute -top-9 left-1/2 -translate-x-1/2 rounded border-2 border-ink bg-ink px-2 py-1 text-[11px] whitespace-nowrap text-white opacity-0 shadow-[2px_2px_0_0_#115231] transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100">Instagram</span>
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
