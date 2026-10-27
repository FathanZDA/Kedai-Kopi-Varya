@props([
    'title' => 'Ringkasan Pesanan',
    'source' => 'cart', // cart | pending | last
    'badgeLabel' => 'Menu Terpilih',
    'showStore' => true,
    'store' => null,
])

@php
    $store = $store ?? \App\Models\StoreSetting::instance();
@endphp

<aside data-order-summary data-summary-source="{{ $source }}" class="brut bg-surface">
    <div class="flex items-center justify-between gap-3 border-b-[3px] border-ink bg-[#f1f0f1] px-5 py-3.5">
        <h2 class="product-name text-lg">{{ $title }}</h2>
        <span class="tag shrink-0 rounded-full bg-rust px-3 py-1 text-[9px] font-bold text-white">
            <span data-summary-count>0</span> {{ $badgeLabel }}
        </span>
    </div>

    <div class="p-5">
        <div data-summary-items class="space-y-3"></div>

        <p data-summary-empty
           class="rounded-lg border-2 border-dashed border-line-strong py-8 text-center font-serif text-sm text-muted">
            Keranjang masih kosong.
        </p>

        <div class="mt-5 border-t-2 border-dashed border-line-strong pt-4">
            <div class="tag flex items-center justify-between text-[10px] text-ink-soft">
                <span>Subtotal</span>
                <span data-summary-subtotal>Rp 0</span>
            </div>

            <div class="mt-2 flex items-end justify-between gap-3">
                <span class="tag text-[10px] text-ink">Total Pembayaran</span>
                <span data-summary-total class="font-display text-2xl leading-none font-black text-varya-dark">Rp 0</span>
            </div>
        </div>

        @if (isset($cta))
            <div class="mt-5">
                {{ $cta }}
            </div>
        @endif

        @if ($showStore)
            <div class="mt-5 rounded-lg border-2 border-line-strong bg-[#f7f6f7] p-4">
                <div class="flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border-2 border-ink bg-surface">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 9l1.5-5h15L21 9"/><path d="M4 9v11h16V9"/><path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/><path d="M9 20v-6h6v6"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="product-name text-xs">{{ $store->nama_toko ?? 'Kedai Kopi Varya' }}</p>
                        <p class="mt-1 font-serif text-[12px] leading-relaxed text-ink-soft">
                            {{ $store->alamat ?? 'Jl. Raya Wonokromo No.16, Wonokromo, Surabaya.' }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</aside>
