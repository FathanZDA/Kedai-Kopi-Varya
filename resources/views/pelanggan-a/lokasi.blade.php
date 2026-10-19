<x-layouts.public title="Lokasi"
                  description="Alamat dan lokasi Kedai Kopi Varya di Jl. Raya Wonokromo No.16, Surabaya.">
    <x-public.ticker :store="$store" />

    <div class="pb-4">
        <x-pelanggan-a.partials.lokasi :store="$store" />
    </div>
</x-layouts.public>