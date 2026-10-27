<x-layouts.pelanggan-b title="Pesan di Kedai"
                       description="Pesan menu Kedai Kopi Varya langsung dari meja. Pilih menu, atur jumlah, dan bayar dengan QRIS.">
    <span id="beranda" class="block"></span>

    <x-public.ticker :store="$store" />

    <x-pelanggan-a.partials.hero :promos="$promos" />

    <x-pelanggan-a.partials.tentang :store="$store" />

    <x-pelanggan-b.menu-section :categories="$categories" :menus="$menus" />

    <div class="pb-4">
        <x-pelanggan-a.partials.lokasi :store="$store" :hours="$hours" />
    </div>
</x-layouts.pelanggan-b>
