@php
    $availableCount = collect($menus)->where('available', true)->count();
    $unavailableCount = collect($menus)->where('available', false)->count();
    $formatRupiah = static fn (int $value): string => 'Rp '.number_format($value, 0, ',', '.');
@endphp

<x-layouts.kasir title="Kelola Menu"
                 active="kelola-menu"
                 :kasir="$kasir"
                 description="Ubah status ketersediaan menu Kedai Kopi Varya.">
    <div data-kasir-kelola-menu>
        <div>
            <p class="tag text-[9px] font-bold text-varya-dark">// Ketersediaan Menu</p>
            <h1 class="kasir-title mt-1 text-3xl sm:text-[38px]">Kelola Menu</h1>
        </div>

        <div class="kasir-stripe-divider mt-3 mb-5" aria-hidden="true"></div>

        {{-- Toolbar --}}
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div data-menu-tabs
                 class="flex flex-wrap items-center gap-1 rounded-full border-2 border-ink bg-kasir-panel p-1.5 shadow-[2px_2px_0_0_#1b1c1d]"
                 role="tablist"
                 aria-label="Filter ketersediaan menu">
                @php
                    $tabs = [
                        ['key' => 'all', 'label' => 'Semua', 'count' => count($menus)],
                        ['key' => 'available', 'label' => 'Tersedia', 'count' => $availableCount],
                        ['key' => 'unavailable', 'label' => 'Stok Habis', 'count' => $unavailableCount],
                    ];
                @endphp

                @foreach ($tabs as $tab)
                    <button type="button"
                            data-menu-tab="{{ $tab['key'] }}"
                            role="tab"
                            @class([
                                'tag flex items-center gap-1.5 rounded-full px-3 py-2 text-[8px] font-bold transition-colors sm:px-4',
                                'bg-varya-dark text-white' => $loop->first,
                                'text-ink-soft hover:bg-varya-light/50' => ! $loop->first,
                            ])
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                        {{ $tab['label'] }}
                        <span data-menu-tab-count="{{ $tab['key'] }}"
                              @class([
                                  'inline-flex min-w-5 items-center justify-center rounded-full border px-1.5 py-0.5 text-[7px]',
                                  'border-white/40 bg-white/15 text-white' => $loop->first,
                                  'border-line-strong bg-white text-ink-soft' => ! $loop->first,
                              ])>
                            {{ $tab['count'] }}
                        </span>
                    </button>
                @endforeach
            </div>

            <form data-menu-search-form
                  role="search"
                  onsubmit="return false"
                  class="flex items-center gap-2 rounded-full border-2 border-ink bg-kasir-panel p-1.5 pl-4 shadow-[2px_2px_0_0_#1b1c1d] xl:w-[330px]">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.6" stroke-linecap="round" class="shrink-0 text-ink" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                </svg>

                <label for="kasir-menu-list-search" class="sr-only">Cari menu</label>
                <input id="kasir-menu-list-search"
                       type="search"
                       data-menu-list-search-input
                       placeholder="CARI MENU..."
                       autocomplete="off"
                       class="min-w-0 flex-1 bg-transparent font-serif text-[11px] font-semibold text-ink uppercase placeholder:text-muted focus:outline-none">

                <button type="submit"
                        class="tag shrink-0 rounded-full bg-varya-dark px-4 py-2 text-[8px] font-bold text-white transition-colors hover:bg-varya">
                    Cari
                </button>
            </form>
        </div>

        <div class="mt-3 flex items-start gap-2 rounded-md border-2 border-line-strong bg-[#f8f5e9] px-3 py-2.5">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0 text-varya-dark" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>
            </svg>
            <p class="font-serif text-[11px] leading-relaxed text-ink-soft">
                Kasir hanya dapat mengubah status <strong class="text-ink">Tersedia / Habis</strong>. Nama, harga, deskripsi, foto, dan penghapusan menu hanya dapat dilakukan admin.
            </p>
        </div>

        {{-- Tabel menu --}}
        <div class="kasir-card mt-4 overflow-hidden">
            <div class="kasir-stripes h-3" aria-hidden="true"></div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] border-collapse text-left">
                    <thead class="bg-kasir-head">
                        <tr class="border-b-2 border-ink">
                            <th scope="col" class="tag w-[70px] px-4 py-3 text-[8px] font-bold text-ink-soft">// No</th>
                            <th scope="col" class="tag px-3 py-3 text-[8px] font-bold text-ink-soft">// Menu &amp; Spesifikasi</th>
                            <th scope="col" class="tag w-[130px] px-3 py-3 text-[8px] font-bold text-ink-soft">// Kategori</th>
                            <th scope="col" class="tag w-[130px] px-3 py-3 text-[8px] font-bold text-ink-soft">// Harga</th>
                            <th scope="col" class="tag w-[135px] px-3 py-3 text-[8px] font-bold text-ink-soft">// Status</th>
                            <th scope="col" class="tag w-[150px] px-4 py-3 text-right text-[8px] font-bold text-ink-soft">// Aksi Kasir</th>
                        </tr>
                    </thead>

                    <tbody data-menu-table class="divide-y-2 divide-line">
                        @foreach ($menus as $menu)
                            <tr data-menu-row
                                data-menu-id="{{ $menu['id'] }}"
                                data-menu-name="{{ $menu['name'] }}"
                                data-menu-spec="{{ $menu['spec'] }}"
                                data-menu-category="{{ $menu['category_label'] }}"
                                data-menu-search="{{ \Illuminate\Support\Str::lower($menu['name'].' '.$menu['spec'].' '.$menu['category_label']) }}"
                                data-available="{{ $menu['available'] ? '1' : '0' }}">
                                <td class="px-4 py-3.5">
                                    <span class="tag inline-flex min-w-9 items-center justify-center rounded border-2 border-ink bg-varya-light px-2 py-1 text-[9px] font-bold text-varya-dark">
                                        #{{ str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                </td>

                                <td class="px-3 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ asset($menu['image']) }}"
                                             alt="{{ $menu['name'] }}"
                                             loading="lazy"
                                             @class([
                                                 'h-12 w-12 shrink-0 rounded border-2 border-ink object-cover',
                                                 'grayscale' => ! $menu['available'],
                                             ])
                                             data-menu-thumb>

                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <p class="font-display text-[14px] leading-tight font-extrabold">{{ $menu['name'] }}</p>
                                                <span data-row-unavailable
                                                      @class([
                                                          'tag rounded bg-brown px-1.5 py-0.5 text-[7px] font-bold text-white',
                                                          'hidden' => $menu['available'],
                                                      ])>
                                                    Habis
                                                </span>
                                            </div>
                                            <p class="mt-1 font-serif text-[11px] leading-relaxed text-ink-soft">{{ $menu['spec'] }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 py-3.5">
                                    <span class="tag inline-block rounded border-2 border-line-strong bg-[#f0eee3] px-2.5 py-1 text-[8px] font-bold text-ink-soft">
                                        {{ $menu['category_label'] }}
                                    </span>
                                </td>

                                <td class="px-3 py-3.5">
                                    <span class="font-display text-[14px] font-bold text-ink">{{ $formatRupiah($menu['price']) }}</span>
                                </td>

                                <td class="px-3 py-3.5">
                                    <span data-menu-status
                                          @class([
                                              'tag inline-flex items-center gap-1.5 rounded border-2 px-2.5 py-1 text-[8px] font-bold',
                                              'border-varya/30 bg-varya-light text-varya-dark' => $menu['available'],
                                              'border-rust/30 bg-[#fbe1de] text-rust' => ! $menu['available'],
                                          ])>
                                        <span data-menu-status-dot @class([
                                            'h-1.5 w-1.5 rounded-full',
                                            'bg-varya' => $menu['available'],
                                            'bg-rust' => ! $menu['available'],
                                        ])></span>
                                        <span data-menu-status-label>{{ $menu['available'] ? 'Tersedia' : 'Habis' }}</span>
                                    </span>
                                </td>

                                <td class="px-4 py-3.5 text-right">
                                    <button type="button"
                                            data-toggle-menu
                                            @class([
                                                'tag rounded border-2 border-ink px-3 py-2 text-[8px] font-bold shadow-[2px_2px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60',
                                                'bg-kasir-panel text-rust' => $menu['available'],
                                                'bg-varya-dark text-white' => ! $menu['available'],
                                            ])>
                                        {{ $menu['available'] ? 'Set Habis' : 'Set Tersedia' }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t-2 border-ink bg-[#f7f5ec] px-4 py-3">
                <p data-menu-record class="tag text-[8px] font-bold text-muted">// Record: Menampilkan 0 dari 0 menu</p>
                <div data-menu-pagination class="flex flex-wrap items-center gap-1.5"></div>
            </div>
        </div>

        <p data-menu-empty hidden class="mt-4 rounded-md border-2 border-dashed border-line-strong bg-kasir-panel py-10 text-center font-serif text-sm text-muted">
            Menu yang kamu cari tidak ditemukan.
        </p>
    </div>
</x-layouts.kasir>
