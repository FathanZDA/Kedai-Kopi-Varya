<?php

namespace App\Services;

use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Enums\SumberPesanan;
use App\Exceptions\MidtransException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Rekonsiliasi pembayaran QRIS dan penutupan pesanan otomatis.
 *
 * Proses ini dijalankan terjadwal setiap 5 menit (PRD §11.7):
 * - memeriksa pembayaran `pending` yang berumur lebih dari 2 menit ke Midtrans;
 * - menutup pesanan yang belum dibayar lebih dari 24 jam tanpa percobaan aktif;
 * - menutup pesanan gagal/kedaluwarsa yang sudah lebih dari 24 jam.
 *
 * Status pembayaran tetap hanya berubah lewat MidtransNotificationService.
 * Service ini tidak pernah membuat nomor antrean sendiri dan tidak menandai
 * pembayaran lunas secara manual.
 */
class QrisReconciliationService
{
    /**
     * Pembayaran pending baru diperiksa setelah umur ini (PRD §11.7).
     */
    private const BATAS_REKONSILIASI_MENIT = 2;

    /**
     * Pesanan ditutup otomatis setelah umur ini (PRD §8.4.6).
     */
    private const BATAS_TUTUP_JAM = 24;

    public function __construct(private readonly OrderService $orders) {}

    /**
     * Jalankan satu putaran rekonsiliasi.
     *
     * @return array{diperiksa: int, ditutup: int, gagal: int}
     */
    public function jalankan(): array
    {
        $hasil = [
            'diperiksa' => 0,
            'ditutup' => 0,
            'gagal' => 0,
        ];

        $this->periksaPembayaranTertunda($hasil);
        $this->tutupPesananKedaluwarsa($hasil);

        Log::info('Rekonsiliasi QRIS selesai.', $hasil);

        return $hasil;
    }

    /**
     * @param  array{diperiksa: int, ditutup: int, gagal: int}  $hasil
     */
    private function periksaPembayaranTertunda(array &$hasil): void
    {
        Order::query()
            ->where('sumber', SumberPesanan::Qr)
            ->where('status_pesanan', StatusPesanan::MenungguPembayaran)
            ->whereHas('payments', fn ($query) => $query->where('status_transaksi', 'pending'))
            ->orderBy('id')
            ->chunkById(50, function ($orders) use (&$hasil): void {
                foreach ($orders as $order) {
                    $payment = $order->payments()->latest('id')->first();

                    if (! $payment || $payment->status_transaksi !== 'pending') {
                        continue;
                    }

                    $waktuDibuat = $payment->waktu_dibuat ?? $payment->created_at;

                    if (! $waktuDibuat
                        || $waktuDibuat->gt(now()->subMinutes(self::BATAS_REKONSILIASI_MENIT))) {
                        continue;
                    }

                    $hasil['diperiksa']++;

                    try {
                        $this->orders->periksaStatusPembayaran($order);
                    } catch (MidtransException $exception) {
                        $hasil['gagal']++;

                        Log::warning('Rekonsiliasi QRIS gagal memeriksa status Midtrans.', [
                            'kode_pesanan' => $order->kode_pesanan,
                            'midtrans_order_id' => $payment->midtrans_order_id,
                            'status_code' => $exception->statusCode,
                            'pesan' => $exception->getMessage(),
                        ]);
                    } catch (ValidationException $exception) {
                        // Order berubah bersamaan dengan proses lain; putaran
                        // berikutnya akan memeriksa keadaan terbaru.
                        $hasil['gagal']++;

                        Log::warning('Rekonsiliasi QRIS melewati order yang berubah.', [
                            'kode_pesanan' => $order->kode_pesanan,
                            'pesan' => $exception->getMessage(),
                        ]);
                    }
                }
            });
    }

    /**
     * @param  array{diperiksa: int, ditutup: int, gagal: int}  $hasil
     */
    private function tutupPesananKedaluwarsa(array &$hasil): void
    {
        Order::query()
            ->where('sumber', SumberPesanan::Qr)
            ->where('status_pesanan', StatusPesanan::MenungguPembayaran)
            ->whereIn('status_pembayaran', [
                StatusPembayaran::Menunggu,
                StatusPembayaran::Gagal,
                StatusPembayaran::Kedaluwarsa,
            ])
            ->where('created_at', '<=', now()->subHours(self::BATAS_TUTUP_JAM))
            ->whereDoesntHave('payments', function ($query): void {
                $query->where('status_transaksi', 'pending')
                    ->where(function ($query): void {
                        $query->where('waktu_dibuat', '>', now()->subMinutes(MidtransService::BATAS_PEMBAYARAN_MENIT))
                            ->orWhere(function ($query): void {
                                $query->whereNull('waktu_dibuat')
                                    ->where('created_at', '>', now()->subMinutes(MidtransService::BATAS_PEMBAYARAN_MENIT));
                            });
                    });
            })
            ->orderBy('id')
            ->chunkById(50, function ($orders) use (&$hasil): void {
                foreach ($orders as $order) {
                    try {
                        if ($this->tutupOrder($order)) {
                            $hasil['ditutup']++;
                        }
                    } catch (Throwable $exception) {
                        $hasil['gagal']++;

                        Log::error('Rekonsiliasi QRIS gagal menutup pesanan.', [
                            'kode_pesanan' => $order->kode_pesanan,
                            'pesan' => $exception->getMessage(),
                        ]);
                    }
                }
            });
    }

    /**
     * Tutup satu order dengan lock agar tidak menimpa pembayaran yang masuk
     * bersamaan. Nomor antrean tidak pernah dibuat di sini.
     */
    private function tutupOrder(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $terkunci = Order::query()->lockForUpdate()->find($order->id);

            if (! $terkunci) {
                return false;
            }

            if ($terkunci->sumber !== SumberPesanan::Qr
                || $terkunci->status_pesanan !== StatusPesanan::MenungguPembayaran) {
                return false;
            }

            if ($terkunci->created_at->gt(now()->subHours(self::BATAS_TUTUP_JAM))) {
                return false;
            }

            if (! in_array($terkunci->status_pembayaran, [
                StatusPembayaran::Menunggu,
                StatusPembayaran::Gagal,
                StatusPembayaran::Kedaluwarsa,
            ], true)) {
                return false;
            }

            // Jangan pernah menutup order yang sudah punya bukti pembayaran
            // lunas, meskipun status order sempat tidak sinkron.
            if ($terkunci->payments()
                ->whereIn('status_transaksi', ['settlement', 'capture'])
                ->exists()) {
                return false;
            }

            if ($this->adaPercobaanAktif($terkunci)) {
                return false;
            }

            $statusSebelumnya = $terkunci->status_pembayaran->value;

            $terkunci->update([
                'status_pembayaran' => StatusPembayaran::Dibatalkan,
                'status_pesanan' => StatusPesanan::Dibatalkan,
            ]);

            Log::info('Pesanan QRIS ditutup otomatis setelah 24 jam.', [
                'kode_pesanan' => $terkunci->kode_pesanan,
                'status_pembayaran_sebelumnya' => $statusSebelumnya,
            ]);

            return true;
        }, 3);
    }

    /**
     * Percobaan aktif = pembayaran pending yang belum melewati 15 menit.
     */
    private function adaPercobaanAktif(Order $order): bool
    {
        return $order->payments()
            ->where('status_transaksi', 'pending')
            ->where(function ($query): void {
                $query->where('waktu_dibuat', '>', now()->subMinutes(MidtransService::BATAS_PEMBAYARAN_MENIT))
                    ->orWhere(function ($query): void {
                        $query->whereNull('waktu_dibuat')
                            ->where('created_at', '>', now()->subMinutes(MidtransService::BATAS_PEMBAYARAN_MENIT));
                    });
            })
            ->exists();
    }
}
