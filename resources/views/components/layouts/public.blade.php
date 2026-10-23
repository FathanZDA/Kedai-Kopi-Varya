@props([
    'title' => null,
    'description' => 'Kedai Kopi Varya — tempat nongkrong, komunitas, dan ruang kreasi di Wonokromo, Surabaya.',
])

<!DOCTYPE html>
<html lang="id" class="scroll-pt-24">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — Kedai Kopi Varya' : 'Kedai Kopi Varya — Tempat Nongkrong // Komunitas // Ruang Kreasi' }}</title>
    <meta name="description" content="{{ $description }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..600&family=Playfair+Display:wght@700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/pelanggan-a.js'])
</head>
<body class="min-h-screen bg-canvas font-serif text-ink antialiased">

    <x-public.header />

    <main>
        {{ $slot }}
    </main>

    <x-public.footer />

    @stack('scripts')
</body>
</html>