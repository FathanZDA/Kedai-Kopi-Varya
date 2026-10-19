<x-layouts.public title="Tentang Kami"
                  description="Sejarah dan cerita Kedai Kopi Varya — ruang nongkrong dan kolaborasi di Wonokromo, Surabaya.">
    <x-public.ticker :store="$store" />

    <div class="pb-4">
        <x-pelanggan-a.partials.tentang :store="$store" />
    </div>
</x-layouts.public>