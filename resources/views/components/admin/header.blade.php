@props([
    'admin' => ['name' => 'Bagas Pratama', 'initial' => 'BP', 'role' => 'Admin / Owner'],
])

<header class="sticky top-0 z-30 border-b border-line-strong/70 bg-admin-panel/95 backdrop-blur">
    <div class="flex h-[68px] items-center gap-3 px-4 sm:px-6 xl:px-7">
        <button type="button"
                data-admin-sidebar-open
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border-2 border-ink bg-admin-panel shadow-[2px_2px_0_0_#1b1c1d] lg:hidden"
                aria-label="Buka menu samping"
                aria-controls="admin-sidebar">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        </button>

        <form action="{{ route('admin.menu.index') }}"
              method="GET"
              role="search"
              class="flex min-w-0 max-w-[520px] flex-1 items-center gap-2 rounded-md border-2 border-ink bg-admin-panel px-3 py-2 shadow-[3px_3px_0_0_#1b1c1d] sm:px-4">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2.6" stroke-linecap="round" class="shrink-0 text-ink" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
            </svg>

            <label for="admin-global-search" class="sr-only">Cari pesanan, menu, atau promo</label>
            <input id="admin-global-search"
                   type="search"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari pesanan, SKU menu, promo..."
                   autocomplete="off"
                   class="min-w-0 flex-1 bg-transparent font-serif text-[12px] text-ink placeholder:text-muted focus:outline-none">
        </form>

        <div class="ml-auto flex shrink-0 items-center gap-3">
            <div class="hidden text-right xl:block">
                <p data-admin-clock class="tag text-[9px] font-bold text-ink">—</p>
                <p class="tag mt-0.5 text-[7px] text-muted">Asia/Jakarta</p>
            </div>

            <span class="hidden h-8 w-px bg-line-strong/70 xl:block" aria-hidden="true"></span>

            <a href="{{ route('admin.store') }}"
               class="inline-flex items-center gap-2 rounded-md border-2 border-ink bg-admin-panel px-3 py-2 shadow-[3px_3px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5 sm:px-4">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="8" r="3.5"/><path d="M5 21c.7-4 3-6 7-6s6.3 2 7 6"/>
                </svg>
                <span class="tag text-[9px] font-bold">Admin</span>
            </a>
        </div>
    </div>
</header>
