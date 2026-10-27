@props([
    'categories' => [],
    'menus' => [],
    'showAllLink' => false,
])

<section id="menu" class="mx-auto max-w-[1280px] px-5 pt-16 sm:px-8 sm:pt-20">
    <x-section-heading title="Menu" align="center" />

    {{-- Pencarian --}}
    <form data-menu-search class="mx-auto mt-7 flex max-w-2xl items-center gap-3"
          role="search" onsubmit="return false">
        <div class="brut flex flex-1 items-center gap-3 rounded-full bg-surface px-5 py-2.5">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.5" stroke-linecap="round" class="shrink-0 text-ink" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
            </svg>

            <label for="menu-search" class="sr-only">Cari produk atau layanan</label>
            <input id="menu-search"
                   type="search"
                   data-menu-search-input
                   placeholder="Cari produk atau layanan..."
                   class="w-full bg-transparent font-serif text-sm text-ink uppercase placeholder:text-muted focus:outline-none">
        </div>

        <button type="submit" class="tag shrink-0 rounded-full border-2 border-ink bg-varya-dark px-6 py-3 text-[11px] font-bold text-white shadow-[3px_3px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5">
            Cari
        </button>
    </form>

    {{-- Filter kategori --}}
    <div data-menu-filters class="mt-6 flex flex-wrap justify-center gap-2.5">
        <button type="button"
                data-filter="all"
                class="brut-sm tag bg-varya-dark px-6 py-2.5 text-[11px] font-bold text-white transition-transform hover:-translate-y-0.5">
            Semua
        </button>

        @foreach ($categories as $category)
            <button type="button"
                    data-filter="{{ $category->slug }}"
                    class="brut-sm tag bg-surface px-6 py-2.5 text-[11px] font-bold text-ink transition-transform hover:-translate-y-0.5">
                {{ $category->nama }}
            </button>
        @endforeach
    </div>

    {{-- Kartu produk --}}
    <div data-menu-grid class="mt-9 grid grid-cols-2 gap-4 sm:gap-5 md:grid-cols-3 xl:grid-cols-6">
        @forelse ($menus as $menu)
            <div data-menu-item
                 data-category="{{ $menu->category->slug }}"
                 data-name="{{ \Illuminate\Support\Str::lower($menu->nama.' '.$menu->deskripsi) }}">
                <x-menu-card :menu="$menu" />
            </div>
        @empty
            <p class="col-span-full py-12 text-center font-serif text-muted">Belum ada menu yang tersedia.</p>
        @endforelse
    </div>

    {{-- Status kosong setelah pencarian --}}
    <p data-menu-empty hidden class="mt-9 rounded-xl border-2 border-dashed border-line-strong py-14 text-center font-serif text-muted">
        Produk yang kamu cari belum ada. Coba kata kunci lain.
    </p>

    @if ($showAllLink)
        <div class="mt-10 flex justify-center">
            <a href="{{ route('pelanggan-a.menu') }}"
               class="brut-sm tag inline-flex items-center gap-2 bg-surface px-8 py-3.5 text-[11px] font-bold text-ink transition-transform hover:-translate-y-0.5">
                Lihat Semua Menu
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </a>
        </div>
    @endif
</section>