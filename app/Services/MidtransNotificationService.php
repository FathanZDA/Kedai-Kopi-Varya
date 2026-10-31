<?php

namespace App\Services;

use App\Enums\StatusPembayaran;
use App\Enums\StatusPemrosesanNotifikasi;
use App\Enums\StatusPesanan;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pemrosesan notifikasi pembayaran Midtrans.
 *
 * Webhook adalah satu-satunya sumber status lunas (PRD §8.1.5). Seluruh
 * perubahan status berjalan idempoten di dalam satu transaksi database dan
 * mengunci baris `payments` + `orders` agar notifikasi ganda tidak membuat
 * nomor antrean ganda (PRD §11.4-11.5).
 */
class MidtransNotificationService
{
    /**
     * Jumlah percobaan ulang bila MySQL melaporkan deadlock.
     */
    private const PERCOBAAN_TRANSAKSI = 3;

    public function __construct(private readonly QueueNumberService $nomorAntrean) {}

    /**
     * Apakah payload memuat field minimum yang dibutuhkan.
     *
     * @param  array<string, mixed>  $payload
     */
    public function payloadLengkap(array $payload): bool
    {
        foreach (['order_id', 'status_code', 'gross_amount', 'transaction_status', 'signature_key'] as $kunci) {
            if (! isset($payload[$kunci]) || ! is_scalar($payload[$kunci]) || (string) $payload[$kunci] === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifikasi `SHA512(order_id + status_code + gross_amount + server_key)`.
     *
     * Perbandingan memakai `hash_equals` agar waktu eksekusi tidak membocorkan
     * isi signature (PRD §11.4.4).
     *
     * @param  array<string, mixed>  $payload
     */
    public function signatureValid(array $payload): bool
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            return false;
        }

        $diharapkan = hash('sha512',
            (string) $payload['order_id'].
            (string) $payload['status_code'].
            (string) $payload['gross_amount'].
            $serverKey
        );

        return hash_equals($diharapkan, (string) $payload['signature_key']);
    }

    /**
     * Catat payload notifikasi yang lolos verifikasi signature.
     *
     * Payload dengan signature tidak valid hanya ditulis ke log dan tidak
     * disimpan, supaya request palsu tidak bisa membanjiri tabel audit.
     *
     * @param  array<string, mixed>  $payload
     */
    public function catat(
        array $payload,
        StatusPemrosesanNotifikasi $status,
        ?string $catatan = null,
        ?int $orderId = null,
    ): PaymentNotification {
        return PaymentNotification::create([
            'midtrans_order_id' => Str::limit((string) $payload['order_id'], 50, ''),
            'order_id' => $orderId,
            'transaction_status' => Str::limit((string) $payload['transaction_status'], 50, ''),
            'status_code' => Str::limit((string) $payload['status_code'], 10, ''),
            'gross_amount' => $this->nominalDesimal($payload['gross_amount']),
            'signature_valid' => true,
            'payload' => $payload,
            'status_pemrosesan' => $status,
            'catatan' => $catatan,
            'received_at' => now(),
            'processed_at' => now(),
        ]);
    }

    /**
     * Terapkan satu notifikasi ke `payments` dan `orders`.
     *
     * Pemanggil wajib sudah memverifikasi signature. Order yang tidak dikenal
     * tetap dicatat sebagai `gagal` dan tidak melempar exception, sehingga
     * endpoint dapat membalas `200` (PRD §11.4.5).
     *
     * @param  array<string, mixed>  $payload
     */
    public function proses(array $payload): PaymentNotification
    {
        return DB::transaction(function () use ($payload): PaymentNotification {
            $payment = Payment::query()
                ->where('midtrans_order_id', $this->batasi((string) $payload['order_id'], 50))
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                Log::warning('Notifikasi Midtrans untuk pembayaran yang tidak dikenal.', [
                    'midtrans_order_id' => $payload['order_id'],
                    'transaction_status' => $payload['transaction_status'],
                ]);

                return $this->catat(
                    $payload,
                    StatusPemrosesanNotifikasi::Gagal,
                    'payments.midtrans_order_id tidak ditemukan.',
                );
            }

            $order = Order::query()->lockForUpdate()->find($payment->order_id);

            if (! $order) {
                return $this->catat(
                    $payload,
                    StatusPemrosesanNotifikasi::Gagal,
                    'Order untuk pembayaran ini tidak ditemukan.',
                    $payment->order_id,
                );
            }

            // Percobaan yang sebelumnya sudah berstatus lunas menandakan
            // notifikasi ini adalah pengulangan, bukan pembayaran baru.
            $duplikat = in_array($payment->status_transaksi, ['settlement', 'capture'], true);

            $payment->update([
                'transaction_id' => $this->batasiNullable($payload['transaction_id'] ?? null, 100),
                'status_transaksi' => $this->batasi((string) $payload['transaction_status'], 50),
                'status_code' => $this->batasi((string) $payload['status_code'], 10),
                'fraud_status' => $this->batasiNullable($payload['fraud_status'] ?? null, 20),
                'payment_type' => $this->batasiNullable($payload['payment_type'] ?? null, 50),
                'waktu_diproses' => now(),
            ]);

            if (! $this->nominalCocok($payload['gross_amount'], $order->total)) {
                $this->tandaiPerluDiperiksa($order);

                Log::warning('Nominal notifikasi Midtrans tidak cocok dengan total pesanan.', [
                    'midtrans_order_id' => $payment->midtrans_order_id,
                    'kode_pesanan' => $order->kode_pesanan,
                    'gross_amount' => $payload['gross_amount'],
                    'total' => $order->total,
                ]);

                return $this->catat(
                    $payload,
                    StatusPemrosesanNotifikasi::Diabaikan,
                    'Nominal tidak cocok dengan orders.total; pesanan ditandai perlu diperiksa.',
                    $order->id,
                );
            }

            $catatan = $this->terapkanKeOrder($order, $this->petakanStatus($payload), $duplikat);

            return $this->catat($payload, StatusPemrosesanNotifikasi::Diproses, $catatan, $order->id);
        }, self::PERCOBAAN_TRANSAKSI);
    }

    /**
     * Pemetaan status Midtrans ke status pembayaran aplikasi (PRD §11.3).
     *
     * @param  array<string, mixed>  $payload
     */
    private function petakanStatus(array $payload): StatusPembayaran
    {
        $status = (string) $payload['transaction_status'];
        $fraud = isset($payload['fraud_status']) ? (string) $payload['fraud_status'] : null;

        return match ($status) {
            'settlement' => StatusPembayaran::Lunas,
            'capture' => match ($fraud) {
                'accept' => StatusPembayaran::Lunas,
                'challenge' => StatusPembayaran::PerluDiperiksa,
                'deny' => StatusPembayaran::Gagal,
                default => StatusPembayaran::PerluDiperiksa,
            },
            'pending' => StatusPembayaran::Menunggu,
            'deny', 'failure', 'cancel' => StatusPembayaran::Gagal,
            'expire' => StatusPembayaran::Kedaluwarsa,
            'refund', 'partial_refund', 'chargeback', 'partial_chargeback' => StatusPembayaran::PerluDiperiksa,
            default => StatusPembayaran::PerluDiperiksa,
        };
    }

    /**
     * Ubah status order sesuai aturan transisi.
     *
     * @return string|null catatan audit untuk `payment_notifications`
     */
    private function terapkanKeOrder(Order $order, StatusPembayaran $tujuan, bool $duplikat): ?string
    {
        $sekarang = $order->status_pembayaran;

        // Pembatalan lokal menang atas notifikasi selain settlement. Settlement
        // yang tetap masuk (race condition) ditandai perlu diperiksa tanpa
        // nomor antrean (PRD §8.4.10).
        if ($sekarang === StatusPembayaran::Dibatalkan) {
            if ($tujuan === StatusPembayaran::Lunas) {
                $order->status_pembayaran = StatusPembayaran::PerluDiperiksa;
                $order->save();

                return 'Settlement diterima setelah pembatalan lokal; tanpa nomor antrean.';
            }

            return 'Pesanan sudah dibatalkan lokal; status pembayaran dipertahankan.';
        }

        if ($sekarang === StatusPembayaran::Lunas) {
            if ($tujuan !== StatusPembayaran::Lunas) {
                $order->status_pembayaran = StatusPembayaran::PerluDiperiksa;
                $order->save();

                return "Pesanan sudah lunas tetapi notifikasi menyatakan {$tujuan->value}; ditandai perlu diperiksa.";
            }

            if (! $duplikat) {
                $order->status_pembayaran = StatusPembayaran::PerluDiperiksa;
                $order->save();

                return 'Pembayaran kedua untuk pesanan yang sudah lunas; ditandai perlu diperiksa.';
            }

            if ($order->nomor_antrean === null) {
                $this->beriNomorAntrean($order);
                $order->save();

                return 'Nomor antrean dilengkapi untuk pesanan yang sudah lunas.';
            }

            return 'Idempoten: pesanan sudah lunas dan sudah bernomor antrean.';
        }

        // Notifikasi pending yang terlambat tidak boleh menurunkan status
        // gagal/kedaluwarsa/perlu diperiksa yang sudah tercatat.
        if ($tujuan === StatusPembayaran::Menunggu) {
            return $sekarang === StatusPembayaran::Menunggu
                ? null
                : "Notifikasi pending diabaikan karena status sudah {$sekarang->value}.";
        }

        if ($tujuan === StatusPembayaran::Lunas) {
            $order->status_pembayaran = StatusPembayaran::Lunas;
            $order->waktu_lunas = now();
            $order->status_pesanan = StatusPesanan::Antre;
            $this->beriNomorAntrean($order);
            $order->save();

            return null;
        }

        $order->status_pembayaran = $tujuan;
        $order->save();

        return null;
    }

    /**
     * Beri nomor antrean bila belum ada; tidak pernah menimpa nomor lama.
     */
    private function beriNomorAntrean(Order $order): void
    {
        if ($order->nomor_antrean !== null) {
            return;
        }

        $tanggal = now()->toDateString();

        $order->nomor_antrean = $this->nomorAntrean->berikutnya($tanggal);
        $order->tanggal_antrean = $tanggal;
    }

    /**
     * Tandai order perlu diperiksa, kecuali sudah lunas.
     *
     * Order yang sudah lunas tidak diturunkan statusnya hanya karena ada
     * notifikasi nominal tidak cocok dari percobaan lain; kasus tersebut
     * cukup dicatat di log dan tabel audit.
     */
    private function tandaiPerluDiperiksa(Order $order): void
    {
        if ($order->status_pembayaran === StatusPembayaran::Lunas) {
            return;
        }

        $order->status_pembayaran = StatusPembayaran::PerluDiperiksa;
        $order->save();
    }

    /**
     * Bandingkan nominal Midtrans (`"45000.00"`) dengan total integer.
     */
    private function nominalCocok(mixed $grossAmount, int $total): bool
    {
        if (! is_numeric($grossAmount)) {
            return false;
        }

        return $this->nominalDesimal($grossAmount) === $this->nominalDesimal($total);
    }

    private function nominalDesimal(mixed $nilai): string
    {
        return number_format((float) $nilai, 2, '.', '');
    }

    private function batasi(string $nilai, int $panjang): string
    {
        return Str::limit($nilai, $panjang, '');
    }

    private function batasiNullable(mixed $nilai, int $panjang): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return $this->batasi((string) $nilai, $panjang);
    }
}
