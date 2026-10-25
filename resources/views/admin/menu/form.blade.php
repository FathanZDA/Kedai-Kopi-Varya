@php
    $isEdit = $menu !== null;
    $pageTitle = $isEdit ? 'Ubah Data Menu: '.$menu['name'] : 'Tambah Menu Baru';
    $formTitle = $isEdit ? 'Formulir Data Menu' : 'Formulir Menu Baru';
    $previewImage = $menu['image'] ?? asset('images/menus/varya-signature.jpg');
@endphp

<x-layouts.admin :title="$pageTitle"
                 active="menu"
                 :admin="$admin"
                 :config="$config"
                 :description="$isEdit ? 'Ubah data menu Kedai Kopi Varya.' : 'Tambahkan menu baru ke katalog Kedai Kopi Varya.'">
    <div data-admin-menu-form
         data-mode="{{ $isEdit ? 'edit' : 'create' }}"
         data-menu-id="{{ $menu['id'] ?? '' }}">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.menu.index') }}"
               class="inline-flex items-center gap-2 rounded-md border-2 border-ink bg-admin-panel px-3.5 py-2 shadow-[2px_2px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 12H5M11 18l-6-6 6-6"/>
                </svg>
                <span class="tag text-[8px] font-bold">Kembali ke Daftar Menu</span>
            </a>

            <p class="tag text-[8px] text-muted">
                {{ $isEdit ? 'Terakhir diperbarui: Hari ini, 14:20 WIB' : 'Menu baru akan tampil di halaman pelanggan' }}
            </p>
        </div>

        <div class="mt-4 border-t-2 border-ink pt-5">
            <p class="tag text-[9px] font-bold text-varya-dark">// {{ $isEdit ? 'Perbarui Katalog' : 'Katalog Baru' }}</p>
            <h1 class="admin-title mt-2 text-[32px] sm:text-[44px] lg:text-[48px]">{{ $pageTitle }}</h1>
        </div>

        <div class="mt-5 grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_370px]">
            <form data-menu-form class="min-w-0" novalidate>
                {{-- 01 Data pokok --}}
                <section class="admin-card overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b-2 border-ink bg-[#f3f1e7] px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <span class="grid h-6 w-6 place-items-center rounded-sm bg-varya-dark font-mono text-[9px] font-bold text-white">01</span>
                            <h2 class="font-display text-[18px] leading-tight font-black uppercase">{{ $formTitle }}</h2>
                        </div>
                        <span class="tag rounded border border-line-strong bg-white px-2.5 py-1 text-[7px] font-bold text-ink-soft">
                            Wajib diisi (*)
                        </span>
                    </div>

                    <div class="space-y-4 p-4 sm:p-5">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="admin-menu-name" class="admin-label">Nama Menu <span class="text-rust">*</span></label>
                                <input id="admin-menu-name"
                                       type="text"
                                       data-field="name"
                                       value="{{ $menu['name'] ?? '' }}"
                                       placeholder="Contoh: Caramel Macchiato Street"
                                       class="admin-input mt-2"
                                       maxlength="80"
                                       autocomplete="off">
                                <p data-error-for="name" hidden class="mt-1.5 font-mono text-[8px] font-bold text-rust"></p>
                            </div>

                            <div>
                                <label for="admin-menu-category" class="admin-label">Kategori <span class="text-rust">*</span></label>
                                <select id="admin-menu-category" data-field="category" class="admin-select mt-2">
                                    <option value="">Pilih Kategori</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category['slug'] }}"
                                                @selected(($menu['category'] ?? '') === $category['slug'])>
                                            {{ $category['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <p data-error-for="category" hidden class="mt-1.5 font-mono text-[8px] font-bold text-rust"></p>
                            </div>

                            <div>
                                <label for="admin-menu-price" class="admin-label">Harga (Rp) <span class="text-rust">*</span></label>
                                <div class="relative mt-2">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center border-r-2 border-ink px-3 font-mono text-[10px] font-bold text-ink-soft">
                                        Rp
                                    </span>
                                    <input id="admin-menu-price"
                                           type="number"
                                           data-field="price"
                                           value="{{ $menu['price'] ?? '' }}"
                                           placeholder="Contoh: 28000"
                                           min="1"
                                           step="500"
                                           class="admin-input !pl-12"
                                           inputmode="numeric">
                                </div>
                                <p data-error-for="price" hidden class="mt-1.5 font-mono text-[8px] font-bold text-rust"></p>
                            </div>

                            <div>
                                <label for="admin-menu-available" class="admin-label">Status Ketersediaan <span class="text-rust">*</span></label>
                                <select id="admin-menu-available" data-field="available" class="admin-select mt-2">
                                    <option value="1" @selected(($menu['available'] ?? true) === true)>Tersedia</option>
                                    <option value="0" @selected(($menu['available'] ?? true) === false)>Habis</option>
                                </select>
                                <p data-error-for="available" hidden class="mt-1.5 font-mono text-[8px] font-bold text-rust"></p>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <label for="admin-menu-description" class="admin-label">Deskripsi Menu &amp; Profil Rasa</label>
                                <span class="tag text-[7px] text-muted"><span data-description-count>0</span> / 300 karakter</span>
                            </div>
                            <textarea id="admin-menu-description"
                                      data-field="description"
                                      rows="5"
                                      maxlength="300"
                                      placeholder="Jelaskan aroma, rasa, biji kopi asal, profil roasting, dan racikan menu ini..."
                                      class="admin-textarea mt-2 resize-y">{{ $menu['description'] ?? '' }}</textarea>
                            <p class="mt-1.5 font-serif text-[10px] text-muted">Teks ini tampil sebagai deskripsi singkat di katalog pelanggan.</p>
                        </div>
                    </div>
                </section>

                {{-- 02 Media --}}
                <section class="admin-card mt-4 overflow-hidden">
                    <div class="flex items-center gap-2.5 border-b-2 border-ink bg-[#f3f1e7] px-4 py-3">
                        <span class="grid h-6 w-6 place-items-center rounded-sm bg-varya-dark font-mono text-[9px] font-bold text-white">02</span>
                        <h2 class="font-display text-[18px] leading-tight font-black uppercase">Media &amp; Foto Visual Menu</h2>
                    </div>

                    <div class="grid gap-4 p-4 sm:grid-cols-[180px_minmax(0,1fr)] sm:p-5">
                        <div>
                            <p class="admin-label">{{ $isEdit ? 'Foto saat ini' : 'Pratinjau foto' }}</p>
                            <div class="mt-2 overflow-hidden rounded-md border-2 border-ink bg-[#e9e5d9]">
                                <img data-menu-image-preview
                                     src="{{ $previewImage }}"
                                     alt="Pratinjau foto menu"
                                     class="aspect-square w-full object-cover">
                            </div>
                        </div>

                        <div>
                            <p class="admin-label">Ganti File Foto</p>

                            <div data-menu-dropzone
                                 class="mt-2 flex min-h-[150px] cursor-pointer flex-col items-center justify-center rounded-md border-2 border-dashed border-line-strong bg-admin-soft px-4 py-5 text-center transition-colors hover:border-varya-dark hover:bg-varya-light/30">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="text-varya-dark" aria-hidden="true">
                                    <path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 15v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4"/>
                                </svg>
                                <p class="mt-2 font-display text-[13px] leading-tight font-black uppercase">
                                    Tarik &amp; lepas foto di sini
                                </p>
                                <p class="mt-1 font-serif text-[10px] text-muted">Format JPG, PNG, WEBP (Maks. 2 MB)</p>

                                <label for="admin-menu-image-input"
                                       class="mt-3 inline-flex cursor-pointer items-center justify-center rounded border-2 border-ink bg-white px-3 py-1.5 font-mono text-[8px] font-bold tracking-wide uppercase shadow-[2px_2px_0_0_#1b1c1d] transition-colors hover:bg-varya-light">
                                    Pilih File
                                </label>
                                <input id="admin-menu-image-input"
                                       type="file"
                                       data-menu-image-input
                                       accept="image/jpeg,image/png,image/webp"
                                       class="sr-only">
                                <p data-menu-image-name class="mt-2 max-w-full truncate font-mono text-[8px] text-muted">
                                    {{ $isEdit ? 'Foto saat ini digunakan' : 'Belum ada file dipilih' }}
                                </p>
                            </div>
                            <p data-menu-image-error hidden class="mt-2 font-mono text-[8px] font-bold text-rust"></p>
                        </div>
                    </div>
                </section>

                {{-- 03 Atribut --}}
                <section class="admin-card mt-4 overflow-hidden">
                    <div class="flex items-center gap-2.5 border-b-2 border-ink bg-[#f3f1e7] px-4 py-3">
                        <span class="grid h-6 w-6 place-items-center rounded-sm bg-varya-dark font-mono text-[9px] font-bold text-white">03</span>
                        <h2 class="font-display text-[18px] leading-tight font-black uppercase">Atribut Penjualan</h2>
                    </div>

                    <div class="p-4 sm:p-5">
                        <label class="flex max-w-md cursor-pointer items-start gap-3 rounded-md border-2 border-line-strong bg-admin-soft px-4 py-3">
                            <input type="checkbox"
                                   data-field="featured"
                                   class="mt-0.5 h-4 w-4 accent-varya-dark"
                                   @checked($menu['featured'] ?? false)>
                            <span>
                                <span class="block font-display text-[14px] leading-tight font-extrabold">Menu Terlaris</span>
                                <span class="mt-1 block font-serif text-[10px] leading-relaxed text-ink-soft">
                                    Tampilkan badge <strong>Best Seller</strong> di katalog pelanggan dan kasir.
                                </span>
                            </span>
                        </label>
                    </div>
                </section>

                {{-- Aksi --}}
                <div class="admin-card mt-4 flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between sm:p-4">
                    @if ($isEdit)
                        <button type="button" data-menu-delete class="admin-btn-danger sm:order-1">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6M10 11v5M14 11v5"/>
                            </svg>
                            Hapus Menu Ini
                        </button>
                    @endif

                    <div class="flex flex-col-reverse gap-2 sm:ml-auto sm:flex-row">
                        <a href="{{ route('admin.menu.index') }}" class="admin-btn-secondary">Batal</a>
                        <button type="submit" class="admin-btn-primary">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/>
                                <path d="M7 3v6h9V3M8 17h8"/>
                            </svg>
                            {{ $isEdit ? 'Simpan Perubahan Menu' : 'Simpan Menu Baru' }}
                        </button>
                    </div>
                </div>
            </form>

            {{-- Live preview --}}
            <aside class="xl:sticky xl:top-24">
                <div class="admin-card overflow-hidden bg-[#303031] text-white">
                    <div class="flex items-center justify-between gap-2 border-b-2 border-ink px-4 py-2.5">
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full border border-white/30 bg-[#ff6b6b]"></span>
                            <span class="h-2.5 w-2.5 rounded-full border border-white/30 bg-[#f2c66d]"></span>
                            <span class="h-2.5 w-2.5 rounded-full border border-white/30 bg-[#7fc08d]"></span>
                        </div>
                        <p class="tag text-[8px] font-bold tracking-[0.12em] text-white/80">Live Catalog Preview</p>
                    </div>

                    <div class="bg-admin-soft p-4 text-ink">
                        <div class="overflow-hidden rounded-lg border-2 border-ink bg-white shadow-[3px_3px_0_0_#1b1c1d]">
                            <div class="relative aspect-[4/3] overflow-hidden border-b-2 border-ink bg-[#e8e2d4]">
                                <img data-preview-image
                                     src="{{ $previewImage }}"
                                     alt="Pratinjau menu"
                                     class="h-full w-full object-cover">

                                <span data-preview-featured
                                      @class([
                                          'absolute top-2 left-2 rounded bg-varya-dark px-2 py-1 font-mono text-[7px] font-bold tracking-wide text-white uppercase',
                                          'hidden' => ! ($menu['featured'] ?? false),
                                      ])>
                                    Terlaris
                                </span>

                                <span data-preview-unavailable
                                      @class([
                                          'tag absolute inset-x-0 top-1/2 -translate-y-1/2 border-y-2 border-ink bg-brown/95 py-1.5 text-center text-[9px] font-bold tracking-[0.2em] text-white',
                                          'hidden' => $menu['available'] ?? true,
                                      ])>
                                    Habis
                                </span>
                            </div>

                            <div class="p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <p data-preview-category class="tag text-[7px] font-bold text-varya-dark">
                                        {{ $menu['category_label'] ?? 'Kategori' }}
                                    </p>
                                    <p data-preview-sku class="tag text-[7px] text-muted">{{ $menu['sku'] ?? 'SKU-BARU' }}</p>
                                </div>

                                <h3 data-preview-name class="mt-1 font-display text-[16px] leading-tight font-extrabold">
                                    {{ $menu['name'] ?? 'Nama Menu Baru' }}
                                </h3>
                                <p data-preview-description class="mt-1 line-clamp-3 font-serif text-[10px] leading-relaxed text-ink-soft">
                                    {{ $menu['description'] ?? 'Deskripsi menu akan tampil di sini.' }}
                                </p>

                                <div class="mt-3 flex items-center justify-between gap-2 border-t border-line-strong/70 pt-2.5">
                                    <p data-preview-price class="font-display text-[18px] leading-none font-bold text-varya-dark">
                                        Rp 0
                                    </p>
                                    <span class="grid h-7 w-7 place-items-center rounded-full border-2 border-ink bg-varya-dark text-sm font-bold text-white">+</span>
                                </div>
                            </div>
                        </div>

                        <p class="mt-3 text-center font-serif text-[9px] leading-relaxed text-muted">
                            Pratinjau ini mengikuti data yang kamu isi. Perubahan akan tampil setelah disimpan.
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-layouts.admin>
