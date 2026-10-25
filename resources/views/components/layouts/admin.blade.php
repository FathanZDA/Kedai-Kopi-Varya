@props([
    'title' => null,
    'active' => 'dashboard',
    'description' => 'Antarmuka admin Kedai Kopi Varya.',
    'admin' => ['name' => 'Bagas Pratama', 'initial' => 'BP', 'role' => 'Admin / Owner'],
    'config' => [],
])

@php
    $adminConfig = array_merge([
        'routes' => [
            'dashboard' => route('admin.dashboard'),
            'menus' => route('admin.menu.index'),
            'menuCreate' => route('admin.menu.create'),
            'menuEditBase' => url('/admin/menu'),
            'reports' => route('admin.reports'),
            'promos' => route('admin.promos'),
            'store' => route('admin.store'),
            'situs' => route('pelanggan-a.beranda'),
        ],
        'admin' => $admin,
    ], $config);
@endphp

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — Admin Kedai Kopi Varya' : 'Admin — Kedai Kopi Varya' }}</title>
    <meta name="description" content="{{ $description }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..600&family=Playfair+Display:wght@700;800;900&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

    <script>
        window.ADMIN_CONFIG = @json($adminConfig);
    </script>

    @vite([
        'resources/css/app.css',
        'resources/js/admin.js',
    ])
</head>
<body class="admin-shell min-h-screen font-serif text-ink antialiased">
    <div class="min-h-screen lg:flex">
        <x-admin.sidebar :active="$active" :admin="$admin" />

        {{-- Latar gelap saat sidebar dibuka di layar kecil. --}}
        <div data-admin-sidebar-backdrop hidden class="fixed inset-0 z-40 bg-ink/50 backdrop-blur-[2px] lg:hidden"></div>

        <div class="min-w-0 flex-1">
            <x-admin.header :admin="$admin" />

            <main class="mx-auto w-full max-w-[1480px] px-4 pt-6 pb-14 sm:px-6 xl:px-7">
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

    {{-- Dialog konfirmasi generik (hapus menu, promosi, foto, dll.). --}}
    <dialog data-admin-confirm-dialog class="admin-dialog">
        <div class="admin-dialog-card">
            <div class="flex items-start justify-between gap-4 border-b-2 border-ink bg-[#f1eee4] px-5 py-4">
                <div>
                    <p class="tag text-[8px] font-bold text-rust">// Konfirmasi</p>
                    <h2 data-confirm-title class="mt-1 font-display text-xl leading-tight font-black text-ink uppercase">
                        Konfirmasi tindakan
                    </h2>
                </div>

                <button type="button"
                        data-confirm-cancel
                        class="grid h-8 w-8 shrink-0 place-items-center rounded border-2 border-ink bg-white transition-colors hover:bg-varya-light"
                        aria-label="Tutup dialog">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>

            <div class="px-5 py-5">
                <p data-confirm-message class="font-serif text-sm leading-relaxed text-ink-soft"></p>

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button"
                            data-confirm-cancel
                            class="admin-btn-secondary">
                        Batal
                    </button>

                    <button type="button"
                            data-confirm-accept
                            class="admin-btn-danger">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </dialog>

    @stack('scripts')
</body>
</html>
