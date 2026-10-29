@props([
    'title' => null,
    'active' => 'pesanan-baru',
    'description' => 'Antarmuka kasir Kedai Kopi Varya.',
    'kasir' => ['name' => 'Dimas Pratama', 'initial' => 'DP', 'role' => 'Kasir'],
])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — Kasir Kedai Kopi Varya' : 'Kasir — Kedai Kopi Varya' }}</title>
    <meta name="description" content="{{ $description }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..600&family=Playfair+Display:wght@700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

    <script>
        window.KASIR_CONFIG = {
            routes: {
                pesananBaru: @json(route('kasir.pesanan.baru')),
                pesananStore: @json(route('kasir.pesanan.store')),
                pesananStatus: @json(route('kasir.pesanan.status', ['order' => '__ID__'])),
                pesananMasuk: @json(route('kasir.pesanan.index')),
                pesananPoll: @json(route('kasir.pesanan.poll')),
                menuAvailability: @json(route('kasir.menu.availability', ['menu' => '__ID__'])),
                kelolaMenu: @json(route('kasir.menu.index')),
                situs: @json(route('pelanggan-a.beranda')),
            },
            kasir: @json($kasir),
        };
    </script>

    @vite([
        'resources/css/app.css',
        'resources/js/kasir.js',
    ])
</head>
<body class="kasir-shell min-h-screen font-serif text-ink antialiased">
    <div class="min-h-screen lg:flex">
        <x-kasir.sidebar :active="$active" :kasir="$kasir" />

        {{-- Latar gelap saat sidebar dibuka di layar kecil. --}}
        <div data-kasir-sidebar-backdrop hidden class="fixed inset-0 z-40 bg-ink/50 backdrop-blur-[2px] lg:hidden"></div>

        <div class="min-w-0 flex-1">
            <x-kasir.header :kasir="$kasir" />

            <main class="mx-auto w-full max-w-[1560px] px-4 pt-8 pb-14 sm:px-6 xl:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Notifikasi kecil untuk aksi di sisi klien. --}}
    <div data-toast
         hidden
         role="status"
         aria-live="polite"
         class="fixed bottom-5 left-1/2 z-[80] w-[calc(100%-2.5rem)] max-w-md -translate-x-1/2 rounded-lg border-[3px] border-ink bg-varya-dark px-5 py-3.5 text-center font-mono text-[10px] font-bold tracking-wide text-white uppercase shadow-[5px_5px_0_0_#1b1c1d]"></div>

    @stack('scripts')
</body>
</html>
