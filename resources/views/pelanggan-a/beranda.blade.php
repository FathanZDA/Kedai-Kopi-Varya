<x-layouts.public>
    <x-public.ticker :store="$store" />

    <x-pelanggan-a.partials.hero :promos="$promos" />

    <x-pelanggan-a.partials.tentang :store="$store" />

    <x-pelanggan-a.partials.menu-section
        :categories="$categories"
        :menus="$menus"
        show-all-link />

    <div class="pb-4">
        <x-pelanggan-a.partials.lokasi :store="$store" />
    </div>
</x-layouts.public>

@push('scripts')
    <script type="module" src="{{ Vite::asset('resources/js/pelanggan-a.js') }}"></script>
@endpush