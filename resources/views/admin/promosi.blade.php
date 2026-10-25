@php
    $aboutImage = $store['about_image'];
@endphp

<x-layouts.admin title="Iklan & Promosi"
                 active="promos"
                 :admin="$admin"
                 :config="$config"
                 description="Kelola slider banner dan konten promosi Kedai Kopi Varya.">
    <div data-admin-promotions>
        <div class="max-w-3xl">
            <p class="tag text-[9px] font-bold text-varya-dark">// Konten Landing Page</p>
            <h1 class="admin-title mt-2 text-[34px] sm:text-[44px]">
                Manajemen Iklan &amp; Promosi<br class="hidden sm:block"> Toko
            </h1>
            <p class="mt-2 font-serif text-[12px] leading-relaxed text-ink-soft">
                Atur banner utama dan foto tentang kami. Satu gambar dipakai otomatis untuk tampilan desktop dan mobile.
            </p>
        </div>

        <div class="mt-5 grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_390px]">
            <div class="min-w-0 space-y-5">
                {{-- Slider banner --}}
                <section class="admin-card overflow-hidden" aria-labelledby="promo-slider-title">
                    <div class="flex flex-wrap items-center gap-3 border-b-2 border-ink bg-varya-light px-4 py-3">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-sm border-2 border-ink bg-varya-dark text-white">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 15 5-5 4 4 3-3 6 6"/>
                            </svg>
                        </span>
                        <h2 id="promo-slider-title" class="font-display text-[18px] leading-tight font-black uppercase">
                            Slider Banner Utama Beranda
                        </h2>
                        <span class="tag rounded border-2 border-varya-dark bg-varya-dark px-2.5 py-1 text-[7px] font-bold text-white">
                            Aktif di Website
                        </span>
                        <span class="tag text-[8px] font-bold text-varya-dark">Penempatan: Slider Hero Landing Page</span>
                    </div>

                    <div class="p-4 sm:p-5">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line-strong pb-3">
                            <div>
                                <p class="font-display text-[16px] leading-tight font-extrabold">Kelola Slide Foto Banner Utama</p>
                                <p class="mt-1 font-serif text-[10px] text-muted">Urutan tampil mengikuti nomor slide. Promosi nonaktif tidak tampil di landing page.</p>
                            </div>

                            <button type="button" data-promo-new
                                    class="inline-flex items-center gap-1.5 rounded border-2 border-ink bg-admin-panel px-3 py-2 font-mono text-[8px] font-bold tracking-wide uppercase shadow-[2px_2px_0_0_#1b1c1d] transition-colors hover:bg-varya-light">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.7" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                Tambah Slide
                            </button>
                        </div>

                        <div data-promo-list class="mt-4 space-y-3">
                            <p class="rounded-md border-2 border-dashed border-line-strong bg-admin-soft px-4 py-8 text-center font-serif text-sm text-muted">
                                Memuat daftar promosi...
                            </p>
                        </div>

                        <p data-promo-empty hidden class="mt-3 rounded-md border-2 border-dashed border-line-strong bg-admin-soft px-4 py-8 text-center font-serif text-sm text-muted">
                            Belum ada promosi. Tambahkan slide pertama dari editor di samping.
                        </p>
                    </div>
                </section>

                {{-- Foto tentang kami --}}
                <section class="admin-card overflow-hidden" aria-labelledby="about-photo-title">
                    <div class="flex flex-wrap items-center gap-3 border-b-2 border-ink bg-varya-light px-4 py-3">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-sm border-2 border-ink bg-varya-dark text-white">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/>
                            </svg>
                        </span>
                        <h2 id="about-photo-title" class="font-display text-[18px] leading-tight font-black uppercase">
                            Foto Tentang Kami
                        </h2>
                        <span class="tag rounded border-2 border-varya-dark bg-varya-dark px-2.5 py-1 text-[7px] font-bold text-white">
                            Aktif di Website
                        </span>
                        <span class="tag text-[8px] font-bold text-varya-dark">Penempatan: Sebelah Tentang Kami</span>
                    </div>

                    <div class="p-4 sm:p-5">
                        <p class="font-display text-[16px] leading-tight font-extrabold">Galeri Foto Tentang Kami</p>
                        <p class="mt-1 font-serif text-[10px] text-muted">Satu foto akan dipotong otomatis mengikuti bingkai desktop dan mobile.</p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-[1.2fr_1fr]">
                            <figure>
                                <div class="flex items-center justify-between gap-2 border-2 border-b-0 border-ink bg-[#303031] px-3 py-1.5 text-white">
                                    <span class="tag text-[7px] font-bold">Versi Desktop</span>
                                    <span class="tag rounded bg-varya-dark px-1.5 py-0.5 text-[7px] font-bold">3:2 &bull; 1200 x 800 px</span>
                                </div>
                                <div class="admin-preview-frame aspect-[3/2] rounded-b-md">
                                    <img data-about-desktop
                                         src="{{ $aboutImage }}"
                                         alt="Foto tentang kami versi desktop"
                                         class="h-full w-full object-cover">
                                </div>
                            </figure>

                            <figure>
                                <div class="flex items-center justify-between gap-2 border-2 border-b-0 border-ink bg-[#303031] px-3 py-1.5 text-white">
                                    <span class="tag text-[7px] font-bold">Versi Mobile</span>
                                    <span class="tag rounded bg-varya-light px-1.5 py-0.5 text-[7px] font-bold text-ink">16:9 &bull; 1080 x 608 px</span>
                                </div>
                                <div class="admin-preview-frame aspect-video rounded-b-md">
                                    <img data-about-mobile
                                         src="{{ $aboutImage }}"
                                         alt="Foto tentang kami versi mobile"
                                         class="h-full w-full object-cover">
                                </div>
                            </figure>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <button type="button" data-about-edit
                                    class="inline-flex items-center gap-1.5 rounded border-2 border-ink bg-admin-panel px-3 py-2 font-mono text-[8px] font-bold tracking-wide uppercase shadow-[2px_2px_0_0_#1b1c1d] transition-colors hover:bg-varya-light">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m4 20 4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20Z"/><path d="m13.5 7.5 3 3"/>
                                </svg>
                                Ubah Foto
                            </button>

                            <button type="button" data-about-delete
                                    class="inline-flex items-center gap-1.5 rounded border-2 border-ink bg-admin-panel px-3 py-2 font-mono text-[8px] font-bold tracking-wide uppercase shadow-[2px_2px_0_0_#1b1c1d] transition-colors hover:bg-[#fbe1de] hover:text-rust">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 11v5M14 11v5"/>
                                </svg>
                                Hapus
                            </button>

                            <span class="tag ml-auto text-[8px] text-muted">Status: <span data-about-status class="text-varya-dark">Tampil</span></span>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Editor promosi --}}
            <aside class="xl:sticky xl:top-24">
                <form data-promo-form class="admin-card overflow-hidden" novalidate>
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b-2 border-ink bg-[#f3f1e7] px-4 py-3">
                        <div class="flex items-center gap-2">
                            <span class="grid h-7 w-7 place-items-center rounded-sm border-2 border-ink bg-admin-panel">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m4 20 4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20Z"/><path d="m13.5 7.5 3 3"/>
                                </svg>
                            </span>
                            <h2 data-promo-editor-title class="font-display text-[17px] leading-tight font-black uppercase">
                                Editor Promosi Baru
                            </h2>
                        </div>
                        <span class="tag rounded border border-line-strong bg-white px-2 py-1 text-[7px] font-bold text-ink-soft">Formulir Input</span>
                    </div>

                    <div class="space-y-4 p-4">
                        <div>
                            <label for="promo-title" class="admin-label">Judul Promosi <span class="text-rust">*</span></label>
                            <input id="promo-title"
                                   type="text"
                                   data-promo-field="title"
                                   placeholder="Contoh: Ngopi Sore Varya"
                                   class="admin-input mt-2"
                                   maxlength="80"
                                   autocomplete="off">
                            <p data-promo-error="title" hidden class="mt-1.5 font-mono text-[8px] font-bold text-rust"></p>
                        </div>

                        <div>
                            <label for="promo-description" class="admin-label">Deskripsi Singkat</label>
                            <textarea id="promo-description"
                                      data-promo-field="description"
                                      rows="3"
                                      maxlength="220"
                                      placeholder="Jelaskan penawaran atau suasana promo..."
                                      class="admin-textarea mt-2 resize-y"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="promo-order" class="admin-label">Urutan Tampil</label>
                                <input id="promo-order"
                                       type="number"
                                       data-promo-field="order"
                                       min="1"
                                       value="1"
                                       class="admin-input mt-2">
                                <p data-promo-error="order" hidden class="mt-1.5 font-mono text-[8px] font-bold text-rust"></p>
                            </div>

                            <label class="mt-6 flex cursor-pointer items-center gap-2 rounded-md border-2 border-line-strong bg-admin-soft px-3 py-2.5">
                                <input type="checkbox" data-promo-field="active" class="h-4 w-4 accent-varya-dark" checked>
                                <span class="tag text-[8px] font-bold">Tampilkan</span>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="promo-start" class="admin-label">Periode Mulai</label>
                                <input id="promo-start" type="date" data-promo-field="period_start" class="admin-input mt-2">
                            </div>
                            <div>
                                <label for="promo-end" class="admin-label">Periode Selesai</label>
                                <input id="promo-end" type="date" data-promo-field="period_end" class="admin-input mt-2">
                                <p data-promo-error="period" hidden class="mt-1.5 font-mono text-[8px] font-bold text-rust"></p>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <p class="admin-label">Upload Gambar</p>
                                <span class="tag text-[7px] text-muted">Maks. 3 MB</span>
                            </div>

                            <div data-promo-dropzone
                                 class="mt-2 cursor-pointer rounded-md border-2 border-dashed border-line-strong bg-admin-soft px-3 py-4 text-center transition-colors hover:border-varya-dark hover:bg-varya-light/30">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="mx-auto text-varya-dark" aria-hidden="true">
                                    <path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 15v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4"/>
                                </svg>
                                <p class="mt-1.5 font-display text-[12px] leading-tight font-black uppercase">Tarik &amp; lepas foto di sini</p>
                                <p class="mt-1 font-serif text-[9px] text-muted">Format JPG, PNG, WEBP</p>
                                <label for="promo-image-input"
                                       class="mt-2 inline-flex cursor-pointer items-center justify-center rounded border-2 border-ink bg-white px-3 py-1.5 font-mono text-[8px] font-bold uppercase shadow-[2px_2px_0_0_#1b1c1d]">
                                    Pilih dari Komputer
                                </label>
                                <input id="promo-image-input" type="file" data-promo-image-input accept="image/jpeg,image/png,image/webp" class="sr-only">
                                <p data-promo-image-name class="mt-1.5 max-w-full truncate font-mono text-[8px] text-muted">Belum ada file dipilih</p>
                            </div>
                            <p data-promo-error="image" hidden class="mt-1.5 font-mono text-[8px] font-bold text-rust"></p>
                        </div>

                        <div>
                            <p class="admin-label">Pratinjau Responsif</p>
                            <div class="mt-2 grid grid-cols-[1.3fr_1fr] gap-2">
                                <figure>
                                    <div class="flex items-center justify-between border-2 border-b-0 border-ink bg-[#303031] px-2 py-1 text-white">
                                        <span class="tag text-[6px] font-bold">Desktop</span>
                                        <span class="tag rounded bg-varya-dark px-1 py-0.5 text-[6px] font-bold">16:9</span>
                                    </div>
                                    <div class="admin-preview-frame aspect-video rounded-b-md">
                                        <img data-promo-editor-desktop src="{{ $promos[0]['image'] }}" alt="Pratinjau banner desktop" class="h-full w-full object-cover">
                                    </div>
                                </figure>

                                <figure>
                                    <div class="flex items-center justify-between border-2 border-b-0 border-ink bg-[#303031] px-2 py-1 text-white">
                                        <span class="tag text-[6px] font-bold">Mobile</span>
                                        <span class="tag rounded bg-varya-light px-1 py-0.5 text-[6px] font-bold text-ink">1:1</span>
                                    </div>
                                    <div class="admin-preview-frame aspect-square rounded-b-md">
                                        <img data-promo-editor-mobile src="{{ $promos[0]['image'] }}" alt="Pratinjau banner mobile" class="h-full w-full object-cover">
                                    </div>
                                </figure>
                            </div>
                            <p class="mt-1.5 font-serif text-[9px] leading-relaxed text-muted">
                                Satu gambar dipakai untuk desktop dan mobile. Area tengah otomatis menjadi fokus.
                            </p>
                        </div>

                        <div class="border-t border-line-strong pt-3">
                            <button type="submit" class="admin-btn-primary w-full">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 15v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4"/>
                                </svg>
                                Simpan &amp; Terapkan Foto
                            </button>

                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <button type="button" data-promo-reset class="admin-btn-secondary">Promosi Baru</button>
                                <button type="button" data-promo-cancel-edit class="admin-btn-secondary" hidden>Batal Edit</button>
                            </div>
                        </div>
                    </div>
                </form>
            </aside>
        </div>
    </div>

    {{-- Dialog foto tentang kami --}}
    <dialog data-about-dialog class="admin-dialog !max-w-xl">
        <div class="admin-dialog-card">
            <div class="flex items-start justify-between gap-4 border-b-2 border-ink bg-[#f3f1e7] px-5 py-4">
                <div>
                    <p class="tag text-[8px] font-bold text-varya-dark">// Foto Tentang Kami</p>
                    <h2 class="mt-1 font-display text-xl leading-tight font-black uppercase">Ubah Foto Halaman Tentang</h2>
                </div>

                <button type="button" data-about-close
                        class="grid h-8 w-8 shrink-0 place-items-center rounded border-2 border-ink bg-white transition-colors hover:bg-varya-light"
                        aria-label="Tutup dialog">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>

            <div class="p-5">
                <div data-about-dropzone class="cursor-pointer rounded-md border-2 border-dashed border-line-strong bg-admin-soft px-4 py-5 text-center transition-colors hover:border-varya-dark">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="mx-auto text-varya-dark" aria-hidden="true">
                        <path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 15v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4"/>
                    </svg>
                    <p class="mt-2 font-display text-[14px] font-black uppercase">Tarik &amp; lepas foto di sini</p>
                    <p class="mt-1 font-serif text-[10px] text-muted">Format JPG, PNG, WEBP (Maks. 3 MB)</p>
                    <label for="about-image-input"
                           class="mt-3 inline-flex cursor-pointer items-center justify-center rounded border-2 border-ink bg-white px-3 py-1.5 font-mono text-[8px] font-bold uppercase shadow-[2px_2px_0_0_#1b1c1d]">
                        Pilih dari Komputer
                    </label>
                    <input id="about-image-input" type="file" data-about-image-input accept="image/jpeg,image/png,image/webp" class="sr-only">
                    <p data-about-image-name class="mt-1.5 truncate font-mono text-[8px] text-muted">Belum ada file dipilih</p>
                </div>

                <div class="mt-4 grid grid-cols-[1.2fr_1fr] gap-3">
                    <figure>
                        <div class="border-2 border-b-0 border-ink bg-[#303031] px-2 py-1 text-center">
                            <span class="tag text-[7px] font-bold text-white">Desktop 3:2</span>
                        </div>
                        <div class="admin-preview-frame aspect-[3/2] rounded-b-md">
                            <img data-about-dialog-desktop src="{{ $aboutImage }}" alt="Pratinjau foto tentang desktop" class="h-full w-full object-cover">
                        </div>
                    </figure>
                    <figure>
                        <div class="border-2 border-b-0 border-ink bg-[#303031] px-2 py-1 text-center">
                            <span class="tag text-[7px] font-bold text-white">Mobile 16:9</span>
                        </div>
                        <div class="admin-preview-frame aspect-video rounded-b-md">
                            <img data-about-dialog-mobile src="{{ $aboutImage }}" alt="Pratinjau foto tentang mobile" class="h-full w-full object-cover">
                        </div>
                    </figure>
                </div>

                <p data-about-error hidden class="mt-2 font-mono text-[8px] font-bold text-rust"></p>

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" data-about-close class="admin-btn-secondary">Batal</button>
                    <button type="button" data-about-save class="admin-btn-primary">Simpan Foto</button>
                </div>
            </div>
        </div>
    </dialog>
</x-layouts.admin>
