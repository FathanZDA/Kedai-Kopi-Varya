<div class="mt-16 sm:mt-20">
    {{-- Garis aksen --}}
    <div class="h-2.5 bg-varya-light"></div>
    <div class="h-1.5 bg-varya-dark"></div>

    {{-- Bar tengah --}}
    <div class="bg-footer-bar">
        <p class="tag mx-auto max-w-[1280px] px-5 py-3 text-center text-[10px] tracking-[0.2em] text-ink sm:text-[11px]">
            Kedai Kopi Varya <span class="mx-1 opacity-50">//</span> Sejak. 2024
        </p>
    </div>

    {{-- Footer utama --}}
    <footer class="bg-footer text-white">
        <div class="mx-auto flex max-w-[1280px] flex-col gap-6 px-5 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-8">
            <x-public.brand variant="light" />

            <p class="font-serif text-sm text-white/70">
                &copy; {{ date('Y') }} Kedai Kopi Varya. Hak cipta dilindungi undang-undang.
            </p>
        </div>
    </footer>
</div>