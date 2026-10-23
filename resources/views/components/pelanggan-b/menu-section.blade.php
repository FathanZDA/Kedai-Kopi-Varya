@props([
    'categories' => [],
    'menus' => [],
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
        <button type="submit" class="tag shrink-0 rounded-full bg-varya-dark px-5 py-2.5 text-[10px] font-bold text-white sm:px-6 sm:text-[11px]">
            Cari
        </button>
    </form>

    {{-- Filter kategori --}}
    <div data-menu-filters class="mt-6 flex flex-wrap justify-center gap-2.5">
        <button type="button"
                data-filter="all"
                class="brut-sm tag bg-varya-dark px-5 py-2.5 text-[10px] font-bold text-white transition-transform hover:-translate-y-0.5 sm:px-6 sm:text-[11px]">
            Semua
        </button>

        @foreach ($categories as $category)
            <button type="button"
                    data-filter="{{ $category->slug }}"
                    class="brut-sm tag bg-surface px-5 py-2.5 text-[10px] font-bold text-ink transition-transform hover:-translate-y-0.5 sm:px-6 sm:text-[11px]">
                {{ $category->name }}
            </button>
        @endforeach
    </div>

    {{-- Kartu produk --}}
    <div data-menu-grid class="mt-9 grid grid-cols-2 gap-4 sm:gap-5 md:grid-cols-3 xl:grid-cols-6">
        @forelse ($menus as $menu)
            <div data-menu-item
                 data-category="{{ $menu->category->slug }}"
                 data-name="{{ \Illuminate\Support\Str::lower($menu->name.' '.$menu->description) }}">
                <x-menu-card
                    :menu="$menu"
                    :detail-url="route('pelanggan-b.produk', $menu)"
                    orderable />
            </div>
        @empty
            <p class="col-span-full py-12 text-center font-serif text-muted">Belum ada menu yang tersedia.</p>
        @endforelse
    </div>

    {{-- Status kosong setelah pencarian --}}
    <p data-menu-empty hidden class="mt-9 rounded-xl border-2 border-dashed border-line-strong py-14 text-center font-serif text-muted">
        Produk yang kamu cari belum ada. Coba kata kunci lain.
    </p>

    <div class="mt-10 flex justify-center">
        <a href="#menu"
           class="brut-sm tag inline-flex items-center gap-2 bg-surface px-8 py-3.5 text-[11px] font-bold text-ink transition-transform hover:-translate-y-0.5">
            Lihat Semua Menu
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </a>
    </div>
</section>
