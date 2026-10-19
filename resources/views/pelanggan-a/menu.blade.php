<x-layouts.public title="Menu"
                  description="Daftar menu Kedai Kopi Varya — kopi, teh, makanan, dan cemilan dengan harga terbaru.">
    <x-public.ticker :store="$store" />

    <div class="pb-4">
        <x-pelanggan-a.partials.menu-section
            :categories="$categories"
            :menus="$menus" />
    </div>
</x-layouts.public>

@push('scripts')
    <script type="module" src="{{ Vite::asset('resources/js/pelanggan-a.js') }}"></script>
@endpush