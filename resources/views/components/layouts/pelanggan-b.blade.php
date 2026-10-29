@props([
    'title' => null,
    'description' => 'Pesan menu Kedai Kopi Varya langsung dari meja — tanpa antre, bayar dengan QRIS.',
])

<!DOCTYPE html>
<html lang="id" class="scroll-pt-28">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — Kedai Kopi Varya' : 'Pesan di Kedai — Kedai Kopi Varya' }}</title>
    <meta name="description" content="{{ $description }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..600&family=Playfair+Display:wght@700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

    <script>
        window.VARYA_ROUTES = {
            menu: @json(route('pelanggan-b.beranda')),
            cart: @json(route('pelanggan-b.keranjang')),
            checkout: @json(route('pelanggan-b.checkout')),
            checkoutStore: @json(route('pelanggan-b.checkout.store')),
            payment: @json(route('pelanggan-b.pembayaran')),
            success: @json(route('pelanggan-b.berhasil')),
        };
    </script>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/js/pelanggan-a.js',
        'resources/js/pelanggan-b.js',
    ])
</head>
<body class="min-h-screen bg-canvas font-serif text-ink antialiased">

    <x-pelanggan-b.header />

    <main>
        {{ $slot }}
    </main>

    <x-public.footer :brand-href="route('pelanggan-b.beranda')" />

    {{-- Notifikasi kecil saat item masuk keranjang. --}}
    <div data-toast
         hidden
         role="status"
         aria-live="polite"
         class="fixed bottom-5 left-1/2 z-[70] w-[calc(100%-2.5rem)] max-w-sm -translate-x-1/2 rounded-lg border-[3px] border-ink bg-ink px-4 py-3 text-center font-mono text-[11px] tracking-wide text-white uppercase shadow-[5px_5px_0_0_#115231]"></div>

    @stack('scripts')
</body>
</html>
