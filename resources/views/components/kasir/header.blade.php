@props([
    'kasir' => ['name' => 'Dimas Pratama', 'initial' => 'DP', 'role' => 'Kasir'],
])

<header class="sticky top-0 z-30 border-b border-line-strong/70 bg-kasir-canvas/95 backdrop-blur">
    <div class="flex h-[68px] items-center justify-between gap-3 px-4 sm:px-6 xl:px-8">
        <div class="flex min-w-0 items-center gap-3">
            <button type="button"
                    data-kasir-sidebar-open
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border-2 border-ink bg-kasir-panel shadow-[2px_2px_0_0_#1b1c1d] lg:hidden"
                    aria-label="Buka menu samping"
                    aria-controls="kasir-sidebar">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>

            <div class="flex min-w-0 items-center gap-2.5">
                <span class="hidden h-9 w-9 shrink-0 place-items-center rounded-md border-2 border-line-strong bg-white/70 text-varya-dark sm:grid">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 8h16v11H4z"/><path d="M7 8V5h10v3"/><path d="M9 13h6"/>
                    </svg>
                </span>

                <p class="tag truncate text-[10px] font-bold tracking-[0.04em] text-ink">
                    Kasir: <span class="text-varya-dark">{{ $kasir['name'] }}</span>
                </p>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            <div class="hidden text-right sm:block">
                <p data-kasir-clock class="tag text-[9px] font-bold text-ink">—</p>
                <p class="tag mt-0.5 text-[7px] text-muted">Asia/Jakarta</p>
            </div>

            <span class="hidden h-8 w-px bg-line-strong/70 sm:block" aria-hidden="true"></span>

            <div class="flex items-center gap-2">
                <span class="hidden text-right md:block">
                    <span class="block font-display text-[12px] leading-tight font-extrabold">{{ $kasir['name'] }}</span>
                    <span class="tag mt-0.5 block text-[7px] text-muted">{{ $kasir['role'] }}</span>
                </span>

                <span class="grid h-10 w-10 place-items-center rounded-full border-2 border-ink bg-varya-dark font-mono text-[11px] font-bold text-white shadow-[2px_2px_0_0_#1b1c1d]"
                      title="{{ $kasir['name'] }}">
                    {{ $kasir['initial'] }}
                </span>
            </div>
        </div>
    </div>
</header>
