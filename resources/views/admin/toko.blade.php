@php
    $todayKey = ['minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'][(int) now('Asia/Jakarta')->dayOfWeek];
    $todayHours = collect($store['hours'])->firstWhere('key', $todayKey) ?? $store['hours'][0];
@endphp

<x-layouts.admin title="Informasi Toko"
                 active="store"
                 :admin="$admin"
                 :config="$config"
                 description="Kelola profil, lokasi, kontak, jam operasional, dan fasilitas Kedai Kopi Varya.">
    <div data-admin-store>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-4xl">
                <h1 class="admin-title text-[32px] sm:text-[36px]">
                    Pengaturan Informasi Toko &amp;<br class="hidden sm:block"> Lokasi
                </h1>
            </div>

            <div class="flex items-center gap-3 rounded-md border-2 border-ink bg-admin-panel px-4 py-2.5 shadow-[3px_3px_0_0_#1b1c1d]">
                <div class="text-right">
                    <p class="tag text-[7px] text-muted">Terakhir diperbarui</p>
                    <p data-store-last-updated class="tag mt-1 text-[9px] font-bold text-ink">{{ $store['last_updated'] }}</p>
                </div>
                <span class="grid h-9 w-9 place-items-center rounded border-2 border-ink bg-varya-light text-varya-dark">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17.5 19a4.5 4.5 0 0 0 .5-8.97A6 6 0 0 0 6.2 9.2 4.5 4.5 0 0 0 6.5 19h11Z"/>
                        <path d="M12 12v7M9.5 14.5 12 12l2.5 2.5"/>
                    </svg>
                </span>
            </div>
        </div>

        <div class="mt-5 grid items-start gap-5 xl:grid-cols-[minmax(0,7fr)_minmax(340px,5fr)]">
            <form data-store-form class="min-w-0 space-y-4" novalidate>
                {{-- 01 Profil --}}
                <section class="admin-card overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line-strong bg-admin-panel px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-6 w-6 place-items-center rounded-sm bg-varya-dark font-mono text-[9px] font-bold text-white">01</span>
                            <h2 class="font-display text-[18px] leading-tight font-black uppercase">Profil &amp; Identitas Kedai</h2>
                        </div>
                        <span class="tag text-[8px] font-bold text-muted">Identitas Publik</span>
                    </div>

                    <div class="space-y-4 p-4 sm:p-5">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="store-name" class="admin-label">Nama Kedai Kopi <span class="text-rust">*</span></label>
                                <input id="store-name" type="text" data-store-field="name" value="{{ $store['name'] }}" class="admin-input mt-2" autocomplete="off">
                                <p data-store-error-for="name" hidden class="mt-1.5 font-mono text-[8px] font-bold text-rust"></p>
                            </div>

                            <div>
                                <label for="store-email" class="admin-label">Email Resmi <span class="text-rust">*</span></label>
                                <input id="store-email" type="email" data-store-field="email" value="{{ $store['email'] }}" class="admin-input mt-2" autocomplete="email">
                                <p data-store-error-for="email" hidden class="mt-1.5 font-mono text-[8px] font-bold text-rust"></p>
                            </div>
                        </div>

                        <div>
                            <label for="store-tagline" class="admin-label">Tagline</label>
                            <input id="store-tagline" type="text" data-store-field="tagline" value="{{ $store['tagline'] }}" class="admin-input mt-2" autocomplete="off">
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <label for="store-description" class="admin-label">Deskripsi Singkat / Tentang Kami</label>
                                <span class="tag text-[7px] text-muted"><span data-store-description-count>0</span> / 5000 Karakter</span>
                            </div>
                            <textarea id="store-description" data-store-field="description" rows="6" maxlength="5000" class="admin-textarea mt-2 resize-y">{{ $store['description'] }}</textarea>
                            <p class="mt-1.5 font-serif text-[10px] text-muted">Teks ini tampil di deskripsi tentang kami website.</p>
                            <p data-store-error-for="description" hidden class="mt-1.5 font-mono text-[8px] font-bold text-rust"></p>
                        </div>

                        <div class="border-t border-line-strong pt-4">
                            <p class="admin-label">3 Poin Statistik Tentang Kami</p>
                            <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                @foreach ($store['points'] as $index => $point)
                                    <div class="rounded-md border-2 border-line-strong bg-admin-soft p-3 shadow-[2px_2px_0_0_#1b1c1d]">
                                        <p class="tag text-[7px] text-muted">Poin 0{{ $index + 1 }}:</p>
                                        <input type="text"
                                               data-store-point-value="{{ $index }}"
                                               value="{{ $point['value'] }}"
                                               class="admin-input mt-1.5 !border-line-strong !px-2 !py-1.5 font-display !text-[16px] font-bold"
                                               aria-label="Nilai poin {{ $index + 1 }}">
                                        <p class="tag mt-2 text-[7px] text-muted">Label / Keterangan</p>
                                        <input type="text"
                                               data-store-point-label="{{ $index }}"
                                               value="{{ $point['label'] }}"
                                               class="admin-input mt-1 !border-line-strong !px-2 !py-1.5 !text-[10px]"
                                               aria-label="Label poin {{ $index + 1 }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 02 Alamat --}}
                <section class="admin-card overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line-strong bg-admin-panel px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-6 w-6 place-items-center rounded-sm bg-varya-dark font-mono text-[9px] font-bold text-white">02</span>
                            <h2 class="font-display text-[18px] leading-tight font-black uppercase">Alamat Fisik &amp; Titik Lokasi Google Maps</h2>
                        </div>
                    </div>

                    <div class="space-y-4 p-4 sm:p-5">
                        <div>
                            <label for="store-address" class="admin-label">Alamat Lengkap Kedai <span class="text-rust">*</span></label>
                            <textarea id="store-address" data-store-field="address" rows="3" class="admin-textarea mt-2 resize-y">{{ $store['address'] }}</textarea>
                            <p data-store-error-for="address" hidden class="mt-1.5 font-mono text-[8px] font-bold text-rust"></p>
                        </div>

                        <div>
                            <label for="store-maps-url" class="admin-label">Tautan Sematan Google Maps (URL / Bagikan)</label>
                            <div class="mt-2 flex flex-col gap-2 sm:flex-row">
                                <div class="flex min-w-0 flex-1 items-center gap-2 rounded-md border-2 border-ink bg-white px-3 py-2">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-varya-dark" aria-hidden="true">
                                        <path d="M10 13a5 5 0 0 0 7.5.5l2-2a5 5 0 0 0-7-7l-1.2 1.2"/>
                                        <path d="M14 11a5 5 0 0 0-7.5-.5l-2 2a5 5 0 0 0 7 7l1.2-1.2"/>
                                    </svg>
                                    <input id="store-maps-url" type="url" data-store-field="maps_url" value="{{ $store['maps_url'] }}" class="min-w-0 flex-1 bg-transparent font-serif text-[11px] text-ink placeholder:text-muted focus:outline-none" autocomplete="off">
                                </div>

                                <a href="{{ $store['maps_url'] }}" target="_blank" rel="noopener noreferrer" data-store-map-link class="admin-btn-secondary shrink-0">
                                    Buka Peta
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M14 3h7v7M21 3l-9 9"/><path d="M19 14v6H4V5h6"/>
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <div>
                            <label for="store-landmark" class="admin-label">Petunjuk Patokan Lokasi (Tanda Acuan)</label>
                            <input id="store-landmark" type="text" data-store-field="landmark" value="{{ $store['landmark'] }}" class="admin-input mt-2" autocomplete="off">
                        </div>
                    </div>
                </section>

                {{-- 03 Kontak & jam --}}
                <section class="admin-card overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line-strong bg-admin-panel px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-6 w-6 place-items-center rounded-sm bg-varya-dark font-mono text-[9px] font-bold text-white">03</span>
                            <h2 class="font-display text-[18px] leading-tight font-black uppercase">Media Sosial &amp; Jam Operasional</h2>
                        </div>
                    </div>

                    <div class="space-y-4 p-4 sm:p-5">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="store-phone" class="admin-label">Nomor Telepon / WhatsApp <span class="text-rust">*</span></label>
                                <input id="store-phone" type="tel" data-store-field="phone" value="{{ $store['phone'] }}" class="admin-input mt-2" autocomplete="tel">
                                <p data-store-error-for="phone" hidden class="mt-1.5 font-mono text-[8px] font-bold text-rust"></p>
                            </div>

                            <div>
                                <label for="store-instagram" class="admin-label">Akun Instagram Resmi</label>
                                <div class="mt-2 flex items-center gap-2 rounded-md border-2 border-ink bg-white px-3 py-2">
                                    <span class="font-mono text-[12px] font-bold text-varya-dark">@</span>
                                    <input id="store-instagram" type="text" data-store-field="instagram" value="{{ ltrim($store['instagram'], '@') }}" class="min-w-0 flex-1 bg-transparent font-serif text-[11px] text-ink placeholder:text-muted focus:outline-none" autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-line-strong pt-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="admin-label">Jam Operasional (Hari Ini)</p>
                                <p class="tag text-[7px] text-muted">Format waktu 24 jam</p>
                            </div>

                            <div class="mt-2 rounded-md border-2 border-ink bg-white px-3 py-3">
                                <p data-store-today-hours class="font-serif text-[12px] leading-relaxed text-ink">
                                    Hari Ini: {{ $todayHours['closed'] ? 'Tutup' : $todayHours['open'].' – '.$todayHours['close'].' WIB (Buka)' }}
                                </p>
                            </div>

                            <details class="mt-2.5">
                                <summary class="cursor-pointer font-mono text-[8px] font-bold text-varya-dark underline underline-offset-2">
                                    Atur jadwal per hari
                                </summary>

                                <div class="mt-2 space-y-2">
                                    @foreach ($store['hours'] as $hour)
                                        <div data-store-hour-row
                                             data-day-key="{{ $hour['key'] }}"
                                             data-day-label="{{ $hour['day'] }}"
                                             class="grid items-center gap-2 rounded-md border-2 border-line-strong bg-admin-soft px-3 py-2 sm:grid-cols-[90px_1fr_1fr_auto]">
                                            <p class="tag text-[9px] font-bold text-ink">{{ $hour['day'] }}</p>
                                            <input type="time"
                                                   data-hour-open
                                                   value="{{ $hour['open'] }}"
                                                   class="admin-input !border-line-strong !px-2 !py-1.5 !text-[11px]"
                                                   aria-label="Jam buka {{ $hour['day'] }}">
                                            <input type="time"
                                                   data-hour-close
                                                   value="{{ $hour['close'] }}"
                                                   class="admin-input !border-line-strong !px-2 !py-1.5 !text-[11px]"
                                                   aria-label="Jam tutup {{ $hour['day'] }}">
                                            <label class="admin-checkbox whitespace-nowrap">
                                                <input type="checkbox" data-hour-closed @checked($hour['closed'])>
                                                Tutup
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </details>

                            <p class="mt-2 font-serif text-[10px] leading-relaxed text-muted">
                                Format jam buka hari ini untuk ditampilkan pada informasi publik.
                            </p>
                        </div>
                    </div>
                </section>

                {{-- 04 Fasilitas --}}
                <section class="admin-card overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line-strong bg-admin-panel px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-6 w-6 place-items-center rounded-sm bg-varya-dark font-mono text-[9px] font-bold text-white">04</span>
                            <h2 class="font-display text-[18px] leading-tight font-black uppercase">Fasilitas Kedai</h2>
                        </div>
                        <span class="tag text-[8px] font-bold text-muted">Tampil di Publik</span>
                    </div>

                    <div class="p-4 sm:p-5">
                        <label for="store-facilities" class="admin-label">Daftar Fasilitas</label>
                        <textarea id="store-facilities" data-store-field="facilities" rows="3" class="admin-textarea mt-2 resize-y" placeholder="WiFi Cepat, Colokan Listrik, Area Outdoor">{{ implode(', ', $store['facilities']) }}</textarea>
                        <p class="mt-1.5 font-serif text-[10px] text-muted">Pisahkan setiap fasilitas dengan koma.</p>

                        <div data-store-facilities-preview class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ($store['facilities'] as $facility)
                                <span class="tag rounded border border-line-strong bg-white px-2 py-1 text-[8px] text-ink-soft">{{ $facility }}</span>
                            @endforeach
                        </div>
                    </div>
                </section>

                {{-- Aksi --}}
                <div class="admin-card flex flex-col gap-2 p-3 sm:flex-row sm:justify-end sm:p-4">
                    <button type="button" data-store-cancel class="admin-btn-secondary">Batal</button>
                    <button type="submit" class="admin-btn-primary">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/>
                            <path d="M7 3v6h9V3M8 17h8"/>
                        </svg>
                        Simpan Perubahan Toko
                    </button>
                </div>
            </form>

            {{-- Pratinjau langsung --}}
            <aside class="xl:sticky xl:top-24">
                <div class="admin-card overflow-hidden">
                    <div class="flex items-center justify-between gap-2 border-b-2 border-ink bg-[#f3f1e7] px-4 py-3">
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full border border-white/40 bg-[#ff6b6b]"></span>
                            <span class="h-2.5 w-2.5 rounded-full border border-white/40 bg-[#f2c66d]"></span>
                            <span class="h-2.5 w-2.5 rounded-full border border-white/40 bg-[#7fc08d]"></span>
                            <p class="tag ml-1 text-[8px] font-bold text-ink-soft">Pratinjau Langsung (Tampilan Publik)</p>
                        </div>
                        <span class="tag rounded border border-line-strong bg-white px-2 py-1 text-[7px] font-bold text-ink-soft">Tampilan Publik</span>
                    </div>

                    <div class="p-4">
                        <div class="rounded-md border-2 border-ink bg-white p-4">
                            <div class="flex items-center justify-between gap-2 border-b border-line-strong pb-2">
                                <p class="tag flex items-center gap-1.5 text-[8px] font-bold text-ink">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>
                                    </svg>
                                    Tentang Kami
                                </p>
                                <p class="tag text-[7px] text-muted">Landing Preview</p>
                            </div>

                            <p data-store-preview-description class="mt-3 line-clamp-[9] font-serif text-[10px] leading-relaxed text-ink-soft">
                                {{ $store['description'] }}
                            </p>

                            <div data-store-preview-points class="mt-3 grid grid-cols-3 gap-1.5">
                                @foreach ($store['points'] as $point)
                                    <div class="rounded border-2 border-line-strong bg-admin-soft px-2 py-2 text-center">
                                        <p class="font-display text-[15px] leading-none font-black">{{ $point['value'] }}</p>
                                        <p class="tag mt-1 text-[6px] leading-tight text-muted">{{ $point['label'] }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="relative mt-3 overflow-hidden rounded border-2 border-ink">
                                <img src="{{ asset('images/store/varya-map.png') }}"
                                     alt="Peta lokasi Kedai Kopi Varya di Wonokromo, Surabaya"
                                     class="h-[190px] w-full object-cover">
                                <div class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                                    <span class="inline-flex items-center gap-1.5 rounded border-2 border-ink bg-white px-2.5 py-1.5 font-mono text-[8px] font-bold shadow-[2px_2px_0_0_#1b1c1d]">
                                        <span class="h-2 w-2 rounded-full bg-varya-dark"></span>
                                        <span data-store-preview-map-name>{{ $store['name'] }}</span>
                                    </span>
                                </div>
                                <a href="https://www.openstreetmap.org/copyright"
                                   target="_blank"
                                   rel="noreferrer"
                                   class="absolute right-1 bottom-1 rounded bg-white/90 px-1 py-0.5 font-mono text-[6px] text-ink-soft hover:text-ink">
                                    © OpenStreetMap
                                </a>
                            </div>

                            <div class="mt-2 flex items-center justify-between gap-2">
                                <span class="tag text-[7px] font-bold text-muted">SURABAYA SELATAN / 60243</span>
                                <a href="{{ $store['maps_url'] }}" target="_blank" rel="noopener noreferrer" data-store-preview-map-link class="tag text-[7px] font-bold text-varya-dark underline underline-offset-2">Petunjuk Rute</a>
                            </div>

                            <div class="mt-3 border-t border-line-strong pt-2.5">
                                <div class="flex items-start gap-2">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0 text-varya-dark" aria-hidden="true">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <div>
                                        <p class="tag text-[7px] font-bold text-muted">Alamat Presisi</p>
                                        <p data-store-preview-address class="mt-1 font-serif text-[10px] leading-relaxed font-semibold text-ink">{{ $store['address'] }}</p>
                                        <p data-store-preview-landmark class="mt-1 font-serif text-[9px] leading-relaxed text-muted">{{ $store['landmark'] }}</p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.admin>
