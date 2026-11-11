@props([
    'menus' => [],
    // 0 berarti semua kartu langsung tampil.
    'previewLimit' => 0,
])

<section id="menu" class="mx-auto max-w-[1280px] px-5 pt-16 sm:px-8 sm:pt-20">
    <x-section-heading title="Menu" align="center" />

    <p class="mx-auto mt-3 max-w-xl text-center font-serif text-sm text-ink-soft">
        Pilih menu favoritmu, atur jumlahnya, lalu lanjutkan ke pembayaran QRIS.
    </p>

    {{-- Pencarian --}}
    <form data-menu-search
          class="mx-auto mt-7 flex max-w-2xl items-center gap-2 rounded-full border-[3px] border-ink bg-surface p-1.5 pl-5 shadow-[4px_4px_0_0_#1b1c1d]"
          role="search"
          onsubmit="return false">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.5" stroke-linecap="round" class="shrink-0 text-ink" aria-hidden="true">
            <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
        </svg>

        <label for="menu-search-b" class="sr-only">Cari produk atau layanan</label>
        <input id="menu-search-b"
               type="search"
               data-menu-search-input
               placeholder="Cari produk atau layanan..."
               class="min-w-0 flex-1 bg-transparent font-serif text-sm text-ink uppercase placeholder:text-muted focus:outline-none">
        <button type="submit" class="tag shrink-0 rounded-full bg-varya-dark px-5 py-2.5 text-[11px] font-bold text-white sm:px-6 sm:text-[11px]">
            Cari
        </button>
    </form>

    {{-- Filter kategori --}}
    <div data-menu-filters class="mt-6 flex flex-wrap justify-center gap-2.5">
        <button type="button"
                data-filter="all"
                class="brut-sm tag bg-varya-dark px-5 py-2.5 text-[11px] font-bold text-white transition-transform hover:-translate-y-0.5 sm:px-6 sm:text-[11px]">
            Semua
        </button>

        @foreach (\App\Support\MenuGroup::all() as $group)
            <button type="button"
                    data-filter="{{ $group['slug'] }}"
                    class="brut-sm tag bg-surface px-5 py-2.5 text-[11px] font-bold text-ink transition-transform hover:-translate-y-0.5 sm:px-6 sm:text-[11px]">
                {{ $group['label'] }}
            </button>
        @endforeach
    </div>

    {{-- Kartu produk --}}
    <div id="menu-grid"
         data-menu-grid
         data-menu-preview-limit="{{ $previewLimit }}"
         class="mx-auto mt-9 flex max-w-6xl flex-wrap justify-center gap-4 sm:gap-5 lg:gap-6">
        @forelse ($menus as $menu)
            <div data-menu-item
                 data-menu-index="{{ $loop->index }}"
                 data-category="{{ \App\Support\MenuGroup::forSlug($menu->category?->slug) }}"
                 data-name="{{ \Illuminate\Support\Str::lower($menu->nama.' '.$menu->deskripsi) }}"
                 class="w-[calc(50%_-_0.5rem)] sm:w-[calc(50%_-_0.625rem)] lg:w-[calc(25%_-_1.125rem)]">
                <x-menu-card
                    :menu="$menu"
                    :detail-url="route('pelanggan-b.produk', $menu)"
                    orderable />
            </div>
        @empty
            <p class="w-full py-12 text-center font-serif text-muted">Belum ada menu yang tersedia.</p>
        @endforelse
    </div>

    {{-- Status kosong setelah pencarian --}}
    <p data-menu-empty hidden class="mt-9 rounded-xl border-2 border-dashed border-line-strong py-14 text-center font-serif text-muted">
        Produk yang kamu cari belum ada. Coba kata kunci lain.
    </p>

    @if ($previewLimit > 0)
        {{-- Tanpa JS tombol tetap tersembunyi dan semua menu tampil (progressive enhancement). --}}
        <div class="mt-10 flex justify-center">
            <button type="button"
                    data-menu-more
                    aria-expanded="false"
                    aria-controls="menu-grid"
                    hidden
                    class="brut-sm tag inline-flex items-center gap-2 bg-surface px-8 py-3.5 text-[11px] font-bold text-ink transition-transform hover:-translate-y-0.5">
                <span data-menu-more-label>Lihat Semua Menu</span>
                <svg data-menu-more-icon width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                     class="transition-transform"><path d="m6 9 6 6 6-6"/></svg>
            </button>
        </div>
    @endif
</section>
