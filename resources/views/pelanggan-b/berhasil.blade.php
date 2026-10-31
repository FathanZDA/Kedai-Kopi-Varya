@php
    $status = $order->status_pembayaran;
    $lunas = $status === \App\Enums\StatusPembayaran::Lunas;
    $pending = $status === \App\Enums\StatusPembayaran::Menunggu;
    $gagal = in_array($status, [
        \App\Enums\StatusPembayaran::Gagal,
        \App\Enums\StatusPembayaran::Kedaluwarsa,
    ], true);
    $perluDiperiksa = $status === \App\Enums\StatusPembayaran::PerluDiperiksa;
    $dibatalkan = $status === \App\Enums\StatusPembayaran::Dibatalkan;

    $queue = $lunas && $order->nomor_antrean !== null
        ? str_pad((string) $order->nomor_antrean, 3, '0', STR_PAD_LEFT)
        : '--';

    $tipeLabel = $order->tipe_pesanan === \App\Enums\TipePesanan::DineIn
        ? 'Makan di Tempat'
        : 'Bawa Pulang';

    $waktu = ($order->waktu_lunas ?? $order->created_at)
        ->copy()
        ->locale('id')
        ->translatedFormat('d M Y, H:i').' WIB';

    $jumlahItem = $order->items->sum('jumlah');

    $judul = match ($status) {
        \App\Enums\StatusPembayaran::Lunas => 'Pesanan Berhasil Dibuat!',
        \App\Enums\StatusPembayaran::Menunggu => 'Menunggu Pembayaran',
        \App\Enums\StatusPembayaran::Gagal => 'Pembayaran Belum Berhasil',
        \App\Enums\StatusPembayaran::Kedaluwarsa => 'Pembayaran Kedaluwarsa',
        \App\Enums\StatusPembayaran::Dibatalkan => 'Pesanan Dibatalkan',
        \App\Enums\StatusPembayaran::PerluDiperiksa => 'Pesanan Perlu Diperiksa',
    };

    $subjudul = match ($status) {
        \App\Enums\StatusPembayaran::Lunas => 'Pembayaran QRIS berhasil. Pesananmu sudah masuk antrean dan akan dipanggil saat selesai.',
        \App\Enums\StatusPembayaran::Menunggu => 'Selesaikan pembayaran QRIS untuk mendapatkan nomor antrean.',
        \App\Enums\StatusPembayaran::Gagal => 'Pesananmu belum dibayar. Kamu bisa mencoba bayar lagi dari halaman ini.',
        \App\Enums\StatusPembayaran::Kedaluwarsa => 'Batas waktu pembayaran sudah lewat. Kamu bisa mencoba bayar lagi.',
        \App\Enums\StatusPembayaran::Dibatalkan => 'Pesanan ini sudah dibatalkan. Silakan buat pesanan baru.',
        \App\Enums\StatusPembayaran::PerluDiperiksa => 'Pembayaran perlu diperiksa kasir. Tunjukkan kode pesanan ini.',
    };

    $bannerClass = match (true) {
        $lunas => 'bg-varya-dark',
        $pending => 'bg-[#7a4f24]',
        $gagal => 'bg-rust',
        $perluDiperiksa => 'bg-rust',
        default => 'bg-ink-soft',
    };

    $statusLabel = match ($status) {
        \App\Enums\StatusPembayaran::Lunas => 'Lunas via QRIS',
        \App\Enums\StatusPembayaran::Menunggu => 'Menunggu pembayaran',
        \App\Enums\StatusPembayaran::Gagal => 'Pembayaran gagal',
        \App\Enums\StatusPembayaran::Kedaluwarsa => 'Pembayaran kedaluwarsa',
        \App\Enums\StatusPembayaran::Dibatalkan => 'Dibatalkan',
        \App\Enums\StatusPembayaran::PerluDiperiksa => 'Perlu diperiksa',
    };

    $statusBadge = match ($status) {
        \App\Enums\StatusPembayaran::Lunas => 'bg-varya-light text-varya-dark',
        \App\Enums\StatusPembayaran::Menunggu => 'bg-[#f7e9c8] text-[#7a4f24]',
        \App\Enums\StatusPembayaran::Gagal => 'bg-[#f7dcd2] text-rust',
        \App\Enums\StatusPembayaran::Kedaluwarsa => 'bg-[#f7dcd2] text-rust',
        \App\Enums\StatusPembayaran::PerluDiperiksa => 'bg-[#f7dcd2] text-rust',
        \App\Enums\StatusPembayaran::Dibatalkan => 'bg-line text-ink-soft',
    };
@endphp

<x-layouts.pelanggan-b :title="$lunas ? 'Pesanan Berhasil' : 'Status Pesanan'"
                       description="Status pembayaran, tiket antrean, dan rincian pesanan Kedai Kopi Varya.">
    <div data-pesanan-status
         data-status-url="{{ route('pesanan.status.json', ['order' => $order->kode_pesanan]) }}"
         data-current-status="{{ $status->value }}"
         data-current-order-status="{{ $order->status_pesanan->value }}"
         data-order-code="{{ $order->kode_pesanan }}"
         data-batas-waktu="{{ $order->batas_waktu_pembayaran?->toIso8601String() }}"
         class="pb-10">
        <section data-status-banner class="{{ $bannerClass }} text-white" aria-live="polite">
            <div class="mx-auto flex max-w-[1280px] flex-col gap-5 px-5 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg border-[3px] border-white bg-white text-varya-dark shadow-[4px_4px_0_0_#1b1c1d]">
                        @if ($lunas)
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                        @elseif ($pending)
                            <svg width="27" height="27" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                            </svg>
                        @else
                            <svg width="27" height="27" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 9v5M12 17h.01"/>
                            </svg>
                        @endif
                    </span>
                    <div>
                        <h1 class="title-xl text-3xl sm:text-4xl">{{ $judul }}</h1>
                        <p class="mt-1 max-w-2xl font-serif text-sm text-white/85">
                            Terima kasih, <span class="font-bold text-white">{{ $order->nama_pelanggan }}</span>.
                            {{ $subjudul }}
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-3 rounded-lg border-2 border-white/30 bg-white/10 px-4 py-3">
                    @if ($lunas)
                        <span class="flex h-9 w-9 items-center justify-center rounded-full border border-white/40">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                            </svg>
                        </span>
                        <div>
                            <p class="tag text-[8px] text-white/70">Estimasi Tunggu</p>
                            <p class="font-display text-lg leading-tight font-bold">8 - 12 Menit</p>
                        </div>
                    @elseif ($pending && $order->batas_waktu_pembayaran)
                        <span class="flex h-9 w-9 items-center justify-center rounded-full border border-white/40">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                            </svg>
                        </span>
                        <div>
                            <p class="tag text-[8px] text-white/70">Sisa Waktu</p>
                            <p data-status-countdown class="font-display text-lg leading-tight font-bold">--:--</p>
                        </div>
                    @else
                        <div>
                            <p class="tag text-[8px] text-white/70">Kode Pesanan</p>
                            <p class="font-display text-lg leading-tight font-bold">{{ $order->kode_pesanan }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <div class="mx-auto max-w-[1280px] px-5 py-8 sm:px-8">
            <div class="grid gap-6 lg:grid-cols-[1.25fr_1fr] lg:items-start">
                {{-- Tiket antrean --}}
                <article class="brut overflow-hidden bg-surface" aria-labelledby="ticket-title">
                    <div class="flex items-center gap-2 border-b-[3px] border-ink bg-[#f1f0f1] px-5 py-3.5">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-varya-dark" aria-hidden="true">
                            <path d="M3 9a2 2 0 0 0 0 6v3h18v-3a2 2 0 0 1 0-6V6H3v3Z"/><path d="M13 6v12" stroke-dasharray="2 3"/>
                        </svg>
                        <h2 id="ticket-title" class="product-name text-sm">Tiket Antrean Digital</h2>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-5 border-b-2 border-dashed border-line-strong pb-5">
                            <div>
                                <p class="tag text-[9px] text-muted">Nomor Panggilan</p>
                                <p class="mt-1 flex flex-wrap items-end gap-2">
                                    <span class="font-display text-5xl leading-none font-black text-varya-dark sm:text-6xl">#{{ $queue }}</span>
                                    <span class="tag mb-1 text-[9px] font-bold text-ink">/ {{ strtoupper($tipeLabel) }}</span>
                                </p>
                            </div>

                            <div class="text-left sm:text-right">
                                <p class="tag text-[9px] text-muted">Kode ID Pesanan</p>
                                <p class="tag mt-1 inline-block border-2 border-ink bg-surface px-3 py-1.5 text-[10px] font-bold text-ink">
                                    {{ $order->kode_pesanan }}
                                </p>
                                <p class="tag mt-1.5 text-[8px] text-muted">{{ $waktu }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-5 border-b-2 border-dashed border-line-strong py-5">
                            <div>
                                <p class="tag text-[9px] text-muted">Identitas Pemesan</p>
                                <p class="mt-1 flex items-center gap-2 font-display text-base font-bold">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-varya-dark" aria-hidden="true">
                                        <circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>
                                    </svg>
                                    <span>{{ $order->nama_pelanggan }}</span>
                                </p>
                            </div>

                            <div>
                                <p class="tag text-[9px] text-muted">Tipe Pesanan</p>
                                <p class="mt-1 flex items-center gap-2 font-display text-base font-bold">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-varya-dark" aria-hidden="true">
                                        <path d="M4 8h16M6 8v12h12V8"/><path d="M9 8V5h6v3"/>
                                    </svg>
                                    <span>{{ $tipeLabel }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 rounded-lg border-2 border-varya-muted/40 bg-varya-light px-4 py-3.5">
                            <p class="tag flex items-center gap-2 text-[9px] font-bold text-varya-dark">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>
                                </svg>
                                Pemberitahuan Pengambilan Pesanan
                            </p>
                            <p class="mt-1.5 font-serif text-[12px] leading-relaxed text-ink-soft">
                                @if ($lunas)
                                    Harap perhatikan nomor panggilan <strong class="text-ink">#{{ $queue }}</strong>.
                                    Saat pesanan selesai, nomor antrean atau namamu akan dipanggil oleh barista.
                                @else
                                    Nomor antrean baru akan muncul setelah pembayaran QRIS lunas. Halaman ini
                                    memperbarui status secara otomatis.
                                @endif
                            </p>
                        </div>

                        @if (filled($order->catatan))
                            <div class="mt-4 rounded-lg border-2 border-line-strong bg-[#f7f6f7] px-4 py-3.5">
                                <p class="tag flex items-center gap-2 text-[9px] font-bold text-ink">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M4 4h16v12l-4 4H4z"/><path d="M8 9h8M8 13h5"/>
                                    </svg>
                                    Catatan Khusus untuk Barista
                                </p>
                                <p class="mt-1.5 font-serif text-[12px] leading-relaxed text-ink-soft">{{ $order->catatan }}</p>
                            </div>
                        @endif
                    </div>
                </article>

                {{-- Rincian pesanan --}}
                <article class="brut overflow-hidden bg-surface" aria-labelledby="ticket-summary-title">
                    <div class="flex items-center justify-between gap-3 border-b-[3px] border-ink bg-ink px-5 py-3.5 text-white">
                        <div class="flex items-center gap-2">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h8"/>
                            </svg>
                            <h2 id="ticket-summary-title" class="product-name text-sm">Rincian Item Pesanan</h2>
                        </div>
                        <span class="tag rounded-full bg-varya px-3 py-1 text-[8px] font-bold text-white">
                            {{ $jumlahItem }} Item
                        </span>
                    </div>

                    <div class="p-5">
                        <div class="space-y-3">
                            @foreach ($order->items as $item)
                                <div class="flex items-center gap-3 border-b border-line pb-3 last:border-0 last:pb-0">
                                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-md border-2 border-ink bg-varya-light font-display text-lg font-black text-varya-dark">
                                        {{ mb_strtoupper(mb_substr($item->nama_menu, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="product-name truncate text-[11px] leading-tight">{{ $item->nama_menu }}</p>
                                        <p class="tag mt-0.5 text-[8px] text-muted">{{ $item->jumlah }} &times; Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</p>
                                    </div>
                                    <p class="font-display text-sm font-bold whitespace-nowrap text-varya-dark">
                                        Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-5 border-t-2 border-dashed border-line-strong pt-4">
                            <div class="tag flex items-center justify-between text-[9px] text-ink-soft">
                                <span>Subtotal</span>
                                <span>Rp {{ number_format($order->items->sum('subtotal'), 0, ',', '.') }}</span>
                            </div>
                            <div class="mt-2 flex items-end justify-between gap-3">
                                <span class="tag text-[10px] text-ink">Total Pembayaran</span>
                                <span class="font-display text-2xl leading-none font-black text-varya-dark">
                                    Rp {{ number_format($order->total, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
                                <span class="tag text-[8px] text-muted">Status</span>
                                <span class="tag inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[8px] font-bold {{ $statusBadge }}">
                                    @if ($lunas)
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>
                                    @elseif ($pending)
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
                                        </svg>
                                    @else
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                             stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 9v5M12 17h.01"/>
                                        </svg>
                                    @endif
                                    {{ $statusLabel }}
                                </span>
                            </div>
                        </div>

                        @if (! $lunas && $percobaanAktif?->snap_token)
                            <button type="button"
                                    data-buka-pembayaran
                                    data-snap-token="{{ $percobaanAktif->snap_token }}"
                                    class="tag mt-5 flex w-full items-center justify-center gap-2 rounded-md border-[3px] border-ink bg-varya-dark px-5 py-4 text-[10px] font-bold text-white shadow-[3px_3px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19"/>
                                </svg>
                                Buka Pembayaran QRIS
                            </button>
                        @endif

                        @if ($dapatBayarUlang)
                            <button type="button"
                                    data-bayar-lagi
                                    data-bayar-lagi-url="{{ route('pesanan.bayar-lagi', ['order' => $order->kode_pesanan]) }}"
                                    @class([
                                        'tag mt-5 flex w-full items-center justify-center gap-2 rounded-md border-[3px] border-ink bg-varya-dark px-5 py-4 text-[10px] font-bold text-white shadow-[3px_3px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5 disabled:pointer-events-none disabled:opacity-60',
                                        'hidden' => $percobaanAktif?->snap_token !== null,
                                    ])>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 4v7h-7"/>
                                </svg>
                                Coba Bayar Lagi
                            </button>
                        @endif

                        @if ($lunas || (! $percobaanAktif?->snap_token && ! $dapatBayarUlang))
                            <div class="mt-5 rounded-lg border-2 border-dashed border-line-strong bg-[#f7f6f7] px-4 py-3.5">
                                <p class="font-serif text-[12px] leading-relaxed text-ink-soft">
                                    @if ($lunas)
                                        Pembayaran sudah diterima. Silakan tunggu nomor antrean dipanggil.
                                    @else
                                        Tunjukkan kode pesanan ini kepada kasir bila membutuhkan bantuan.
                                    @endif
                                </p>
                            </div>
                        @endif

                        <a href="{{ route('pelanggan-b.beranda') }}#menu"
                           class="tag mt-3 flex w-full items-center justify-center gap-2 rounded-md border-[3px] border-ink bg-surface px-5 py-4 text-[10px] font-bold text-ink shadow-[3px_3px_0_0_#1b1c1d] transition-transform hover:-translate-y-0.5">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 7h16v13H4z"/><path d="M8 7V4h8v3"/><path d="M8 12h8"/>
                            </svg>
                            Pesan Menu Lain
                        </a>
                    </div>
                </article>
            </div>
        </div>
    </div>
</x-layouts.pelanggan-b>
