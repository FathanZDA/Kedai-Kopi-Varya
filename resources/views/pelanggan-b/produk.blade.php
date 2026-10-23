@php
    $tones = [
        ['#8a6a4f', '#5d4633'], ['#7d5a3c', '#4e3826'],
        ['#6b8f71', '#3f5c46'], ['#b07a3c', '#7a4f24'],
        ['#9c8a5a', '#6b5c34'], ['#8f5b4a', '#5d382c'],
        ['#a3835f', '#6f563a'], ['#7f6b52', '#54453a'],
        ['#96805c', '#635537'], ['#b5916a', '#7d6041'],
        ['#8c8577', '#5b574d'], ['#6f6357', '#453d34'],
    ];
    $tone = $tones[crc32($menu->slug) % count($tones)];
    $initial = mb_strtoupper(mb_substr(preg_replace('/[^A-Za-z0-9]/', '', $menu->name), 0, 1));
@endphp

<x-layouts.pelanggan-b :title="$menu->name"
                       :description="$menu->description ?: 'Detail menu Kedai Kopi Varya.'">
    <div class="mx-auto max-w-[1280px] px-5 pt-6 sm:px-8">
        <nav class="flex flex-wrap items-center gap-2" aria-label="Breadcrumb">
            <a href="{{ route('pelanggan-b.beranda') }}#menu"
               class="tag inline-flex items-center gap-2 border-2 border-line-strong bg-surface px-4 py-2 text-[9px] font-bold text-ink transition-colors hover:border-ink">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m0 0 6 6m-6-6 6-6"/></svg>
                Katalog Menu
            </a>
            <span class="tag text-[9px] text-muted">//</span>
            <span class="tag text-[9px] text-muted">Detail Produk</span>
        </nav>
    </div>

    <div class="mx-auto max-w-[1280px] px-5 pt-5 pb-10 sm:px-8">
        <div class="grid gap-6 lg:grid-cols-[1.35fr_1fr] lg:items-start">
            {{-- Detail produk --}}
            <article class="brut overflow-hidden bg-surface">
                <div class="p-3 sm:p-4">
                    <div class="relative aspect-[4/3] w-full overflow-hidden border-[3px] border-ink bg-canvas">
                        @if ($menu->image)
                            <img src="{{ asset($menu->image) }}"
                                 alt="{{ $menu->name }}"
                                 class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center"
                                 style="background: linear-gradient(135deg, {{ $tone[0] }}, {{ $tone[1] }});">
                                <span class="font-display text-8xl font-black text-white/25 select-none">{{ $initial }}</span>
                            </div>
                        @endif

                        <a href="{{ route('pelanggan-b.beranda') }}#menu"
                           class="absolute top-3 left-3 inline-flex h-10 w-10 items-center justify-center rounded-md border-2 border-ink bg-white text-ink shadow-[3px_3px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5"
                           aria-label="Tutup detail produk">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        </a>

                        @if ($menu->is_featured && $menu->is_available)
                            <span class="tag absolute top-3 right-3 -rotate-6 rounded-full bg-varya-dark px-3 py-1 text-[10px] font-bold text-white shadow-[2px_2px_0_0_#1b1c1d]">
                                Terlaris
                            </span>
                        @endif
                    </div>
                </div>

                <div class="px-5 pb-6 sm:px-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="tag text-[10px] text-varya">{{ $menu->category->name }}</p>
                            <h1 class="product-name mt-1 text-3xl leading-tight sm:text-4xl">{{ $menu->name }}</h1>
                        </div>

                        <p class="font-display text-2xl font-black text-varya-dark sm:text-3xl">
                            {{ $menu->formatted_price }}
                        </p>
                    </div>

                    <p class="mt-4 rounded-lg bg-canvas p-4 font-serif text-[14px] leading-relaxed text-ink-soft sm:text-[15px]">
                        {{ $menu->description ?: 'Menu pilihan Kedai Kopi Varya.' }}
                    </p>

                    <div data-product-detail
                         data-menu-id="{{ $menu->id }}"
                         data-menu-slug="{{ $menu->slug }}"
                         data-menu-name="{{ $menu->name }}"
                         data-menu-price="{{ $menu->price }}"
                         data-menu-image="{{ $menu->image ? asset($menu->image) : '' }}"
                         data-menu-initial="{{ $initial }}"
                         class="mt-6 border-t-2 border-dashed border-line-strong pt-5">
                        @if ($menu->is_available)
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-stretch">
                                <div class="flex shrink-0 items-stretch overflow-hidden rounded-md border-[3px] border-ink bg-surface shadow-[3px_3px_0_0_#1b1c1d]">
                                    <button type="button" data-product-minus
                                            class="grid w-12 place-items-center border-r-2 border-ink text-xl font-bold transition-colors hover:bg-varya-light"
                                            aria-label="Kurangi jumlah">−</button>
                                    <span data-product-qty class="tag flex w-14 items-center justify-center text-sm font-bold">1</span>
                                    <button type="button" data-product-plus
                                            class="grid w-12 place-items-center border-l-2 border-ink bg-varya-dark text-xl font-bold text-white transition-colors hover:bg-varya"
                                            aria-label="Tambah jumlah">+</button>
                                </div>

                                <button type="button" data-product-add
                                        class="tag flex flex-1 items-center justify-center gap-2 rounded-md border-[3px] border-ink bg-varya-dark px-6 py-4 text-[11px] font-bold text-white shadow-[3px_3px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5">
                                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M6 7h12l1 13H5L6 7Z"/><path d="M9 7a3 3 0 0 1 6 0"/><path d="M12 11v5M9.5 13.5h5"/>
                                    </svg>
                                    Tambah ke Keranjang
                                </button>
                            </div>

                            <p data-product-cart-note class="tag mt-3 text-[9px] text-muted"></p>
                        @else
                            <div class="rounded-md border-2 border-line-strong bg-[#efedee] px-5 py-4 text-center">
                                <p class="tag text-[11px] font-bold text-brown">Stok Habis</p>
                                <p class="mt-1 font-serif text-sm text-ink-soft">Menu ini sedang tidak tersedia. Silakan pilih menu lain.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </article>

            {{-- Ringkasan keranjang --}}
            <x-pelanggan-b.order-summary source="cart" title="Ringkasan Pesanan" :store="$store">
                <x-slot:cta>
                    <a data-summary-cta href="{{ route('pelanggan-b.keranjang') }}"
                       class="tag flex w-full items-center justify-center gap-2 rounded-md border-[3px] border-ink bg-ink px-5 py-4 text-[10px] font-bold text-white shadow-[3px_3px_0_0_#115231] transition-transform hover:-translate-y-0.5">
                        Lihat Keranjang
                        <span class="rounded-full bg-white/15 px-2 py-0.5"><span data-summary-count>0</span></span>
                    </a>
                </x-slot:cta>
            </x-pelanggan-b.order-summary>
        </div>
    </div>
</x-layouts.pelanggan-b>
