<x-layouts.public title="Menu"
                  description="Daftar menu Kedai Kopi Varya — kopi, bukan kopi, makanan & camilan dengan harga terbaru.">
    <x-public.ticker :store="$store" />

    <div class="pb-4">
        <x-pelanggan-a.partials.menu-section
            :menus="$menus" />
    </div>
</x-layouts.public>

@push('scripts')
    <script type="module" src="{{ Vite::asset('resources/js/pelanggan-a.js') }}"></script>
@endpush