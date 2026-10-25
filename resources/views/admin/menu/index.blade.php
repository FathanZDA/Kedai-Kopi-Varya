@php
    $totalMenus = count($menus);
    $unavailableMenus = collect($menus)->where('available', false)->count();
@endphp

<x-layouts.admin title="Kelola Menu"
                 active="menu"
                 :admin="$admin"
                 :config="$config"
                 description="Kelola daftar menu dan ketersediaan stok Kedai Kopi Varya.">
    <div data-admin-menu-list>
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-2xl">
                <h1 class="admin-title text-[32px] sm:text-[36px]">
                    Kelola Daftar Menu &amp;<br class="hidden sm:block"> Stok
                </h1>
            </div>

            <div class="flex w-full flex-col gap-2 xl:w-[310px]">
                <div class="grid grid-cols-2 gap-2">
                    <div class="flex min-w-0 items-center gap-2.5 rounded-md border-2 border-ink bg-admin-panel px-3 py-2.5 shadow-[3px_3px_0_0_#1b1c1d]">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-sm bg-varya-light text-varya-dark">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 9h8M8 13h8M8 17h5"/>
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="tag text-[7px] font-bold text-muted">Total Varian</p>
                            <p class="mt-1 font-display text-[16px] leading-none font-black">
                                <span data-admin-menu-total>{{ $totalMenus }}</span>
                                <span class="tag text-[7px] text-ink-soft">Menu</span>
                            </p>
                        </div>
                    </div>

                    <div class="flex min-w-0 items-center gap-2.5 rounded-md border-2 border-ink bg-admin-panel px-3 py-2.5 shadow-[3px_3px_0_0_#1b1c1d]">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-sm bg-[#fbe1de] text-rust">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 9v4M12 17h.01"/><path d="M10.3 3.5 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.5a2 2 0 0 0-3.4 0Z"/>
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <p class="tag text-[7px] font-bold text-muted">Perlu Isi Ulang</p>
                            <p class="mt-1 font-display text-[16px] leading-none font-black">
                                <span data-admin-menu-unavailable>{{ $unavailableMenus }}</span>
                                <span class="tag text-[7px] text-rust">Habis</span>
                            </p>
                        </div>
                    </div>
                </div>

                <a href="{{ route('admin.menu.create') }}"
                   class="admin-btn-primary w-full justify-center !px-5 sm:w-[262px] sm:self-start">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.7" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Tambah Menu Baru
                </a>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="admin-card mt-6 p-3 sm:p-4">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <form data-admin-menu-search-form
                      role="search"
                      onsubmit="return false"
                      class="flex items-center gap-2 rounded-md border-2 border-ink bg-white p-1.5 pl-3 shadow-[2px_2px_0_0_#1b1c1d] xl:w-[360px]">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.6" stroke-linecap="round" class="shrink-0 text-ink" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                    </svg>

                    <label for="admin-menu-search" class="sr-only">Cari menu</label>
                    <input id="admin-menu-search"
                           type="search"
                           data-admin-menu-search
                           placeholder="Cari nama menu / deskripsi..."
                           autocomplete="off"
                           class="min-w-0 flex-1 bg-transparent font-serif text-[12px] text-ink placeholder:text-muted focus:outline-none">
                </form>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div data-admin-menu-categories
                         class="flex flex-wrap items-center gap-1.5"
                         role="tablist"
                         aria-label="Filter kategori menu">
                        @php
                            $categoryTabs = [
                                ['key' => 'all', 'label' => 'Semua'],
                                ['key' => 'kopi', 'label' => 'Kopi'],
                                ['key' => 'non-kopi', 'label' => 'Non-Kopi'],
                                ['key' => 'makanan', 'label' => 'Makanan'],
                                ['key' => 'camilan', 'label' => 'Camilan'],
                            ];
                        @endphp

                        @foreach ($categoryTabs as $tab)
                            <button type="button"
                                    data-admin-menu-category="{{ $tab['key'] }}"
                                    role="tab"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                    @class([
                                        'tag rounded-full border-2 border-ink px-3.5 py-2 text-[8px] font-bold transition-colors',
                                        'bg-varya-dark text-white shadow-[2px_2px_0_0_#1b1c1d]' => $loop->first,
                                        'bg-admin-panel text-ink hover:bg-varya-light' => ! $loop->first,
                                    ])>
                                {{ $tab['label'] }}
                            </button>
                        @endforeach
                    </div>

                    <label class="sr-only" for="admin-menu-availability">Filter ketersediaan</label>
                    <select id="admin-menu-availability"
                            data-admin-menu-availability
                            class="admin-select w-full sm:w-[154px]">
                        <option value="all">Semua Stok</option>
                        <option value="available">Tersedia</option>
                        <option value="unavailable">Habis</option>
                    </select>
                </div>
            </div>

            <div class="mt-3 flex items-start gap-2 rounded-md border border-line-strong bg-admin-soft px-3 py-2.5">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0 text-varya-dark" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>
                </svg>
                <p class="font-serif text-[11px] leading-relaxed text-ink-soft">
                    Klik status <strong class="text-ink">Tersedia / Habis</strong> untuk mengubah ketersediaan. Menu yang dihapus tidak tampil untuk penjualan baru, tetapi riwayat pesanan lama tetap utuh.
                </p>
            </div>
        </div>

        {{-- Tabel menu --}}
        <div class="admin-card mt-4 overflow-hidden">
            <div class="admin-stripes h-3" aria-hidden="true"></div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px] border-collapse text-left">
                    <thead class="bg-admin-head">
                        <tr class="border-b-2 border-ink">
                            <th scope="col" class="tag w-[76px] px-4 py-3 text-[8px] font-bold text-ink-soft">Foto</th>
                            <th scope="col" class="tag px-3 py-3 text-[8px] font-bold text-ink-soft">Nama Menu &amp; Racikan</th>
                            <th scope="col" class="tag w-[120px] px-3 py-3 text-[8px] font-bold text-ink-soft">Kategori</th>
                            <th scope="col" class="tag w-[125px] px-3 py-3 text-[8px] font-bold text-ink-soft">Harga (Rp)</th>
                            <th scope="col" class="tag w-[140px] px-3 py-3 text-[8px] font-bold text-ink-soft">Status Stok</th>
                            <th scope="col" class="tag w-[170px] px-4 py-3 text-right text-[8px] font-bold text-ink-soft">Aksi Admin</th>
                        </tr>
                    </thead>

                    <tbody data-admin-menu-rows class="divide-y-2 divide-line">
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center font-serif text-sm text-muted">Memuat daftar menu...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t-2 border-ink bg-[#f7f5ec] px-4 py-3">
                <p data-admin-menu-record class="tag text-[8px] font-bold text-muted">// Menampilkan 0 dari 0 menu</p>
                <div data-admin-menu-pagination class="flex flex-wrap items-center gap-1.5"></div>
            </div>
        </div>

        <p data-admin-menu-empty hidden class="mt-4 rounded-md border-2 border-dashed border-line-strong bg-admin-panel py-10 text-center font-serif text-sm text-muted">
            Menu yang kamu cari tidak ditemukan.
        </p>
    </div>
</x-layouts.admin>
