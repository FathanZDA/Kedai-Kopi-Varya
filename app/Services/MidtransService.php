<?php

namespace App\Services;

use App\Exceptions\MidtransException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Integrasi Snap Midtrans lewat HTTP client Laravel.
 *
 * Tidak memakai SDK `midtrans/midtrans-php` (docs/DECISIONS.md F-41).
 * `server_key` hanya dibaca dari konfigurasi backend dan tidak pernah dikirim
 * ke browser. Kontrak payload: PRD §11.2.
 */
class MidtransService
{
    /**
     * Batas waktu pembayaran QRIS dalam menit (PRD §8.4.2).
     */
    public const BATAS_PEMBAYARAN_MENIT = 15;

    /**
     * Apakah kredensial backend sudah tersedia.
     */
    public function terkonfigurasi(): bool
    {
        return filled(config('services.midtrans.server_key'));
    }

    /**
     * Apakah Snap.js bisa dibuka di browser.
     *
     * `client_key` bersifat publik dan dipakai Snap.js; `server_key` tetap
     * hanya dipakai backend.
     */
    public function popupTerkonfigurasi(): bool
    {
        return $this->terkonfigurasi()
            && filled(config('services.midtrans.client_key'));
    }

    /**
     * Buat transaksi Snap untuk satu percobaan pembayaran.
     *
     * @return array<string, mixed> respons Snap (`token`, `redirect_url`, ...)
     *
     * @throws MidtransException
     */
    public function buatTransaksiSnap(Order $order, Payment $payment): array
    {
        if (! $this->terkonfigurasi()) {
            throw new MidtransException('Kredensial Midtrans belum diatur di server.', $order, null, null);
        }

        try {
            $response = Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
                ->acceptJson()
                ->asJson()
                ->connectTimeout(10)
                ->timeout(20)
                ->post($this->urlSnap(), $this->payload($order, $payment));
        } catch (ConnectionException $exception) {
            // Gangguan jaringan tidak boleh menjadi error 500 tanpa pesan jelas.
            throw new MidtransException(
                'Tidak bisa menghubungi Midtrans.',
                $order,
                '500',
                ['error' => $exception->getMessage()],
            );
        }

        return $this->bacaRespons($response, $order);
    }

    /**
     * Ambil status transaksi terbaru langsung dari API Midtrans.
     *
     * Dipakai tombol **Cek Status** kasir. Respons tidak pernah menandai
     * pesanan lunas di sini; `MidtransNotificationService` yang memetakan
     * status ke order.
     *
     * @return array<string, mixed>
     *
     * @throws MidtransException
     */
    public function ambilStatus(Payment $payment, Order $order): array
    {
        if (! $this->terkonfigurasi()) {
            throw new MidtransException('Kredensial Midtrans belum diatur di server.', $order, null, null);
        }

        try {
            $response = Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout(20)
                ->get($this->urlApi().'/'.rawurlencode($payment->midtrans_order_id).'/status');
        } catch (ConnectionException $exception) {
            throw new MidtransException(
                'Tidak bisa menghubungi Midtrans.',
                $order,
                '500',
                ['error' => $exception->getMessage()],
            );
        }

        $data = $this->bacaResponsApi($response, $order, 'status pembayaran');
        $this->pastikanOrderId($data, $payment, $order);

        if (blank($data['transaction_status'] ?? null)) {
            throw new MidtransException(
                'Respons status Midtrans tidak lengkap.',
                $order,
                (string) ($data['status_code'] ?? $response->status()),
                $data,
            );
        }

        $data['order_id'] = $payment->midtrans_order_id;
        $data['status_code'] = (string) ($data['status_code'] ?? $response->status());

        return $data;
    }

    /**
     * Batalkan transaksi Midtrans yang masih aktif.
     *
     * Bila Midtrans menolak pembatalan (mis. transaksi sudah kedaluwarsa),
     * pemanggil harus memeriksa status lewat `ambilStatus()` sebelum
     * mengubah status lokal.
     *
     * @return array<string, mixed>
     *
     * @throws MidtransException
     */
    public function batalkanTransaksi(Payment $payment, Order $order): array
    {
        if (! $this->terkonfigurasi()) {
            throw new MidtransException('Kredensial Midtrans belum diatur di server.', $order, null, null);
        }

        try {
            $response = Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout(20)
                ->post($this->urlApi().'/'.rawurlencode($payment->midtrans_order_id).'/cancel');
        } catch (ConnectionException $exception) {
            throw new MidtransException(
                'Tidak bisa menghubungi Midtrans.',
                $order,
                '500',
                ['error' => $exception->getMessage()],
            );
        }

        $data = $this->bacaResponsApi($response, $order, 'pembatalan');
        $this->pastikanOrderId($data, $payment, $order);

        if (blank($data['transaction_status'] ?? null)) {
            throw new MidtransException(
                'Respons pembatalan Midtrans tidak lengkap.',
                $order,
                (string) ($data['status_code'] ?? $response->status()),
                $data,
            );
        }

        $data['order_id'] = $payment->midtrans_order_id;
        $data['status_code'] = (string) ($data['status_code'] ?? $response->status());

        return $data;
    }

    /**
     * Tolak respons yang bukan milik percobaan pembayaran yang diminta.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws MidtransException
     */
    private function pastikanOrderId(array $data, Payment $payment, Order $order): void
    {
        if ((string) ($data['order_id'] ?? '') !== $payment->midtrans_order_id) {
            throw new MidtransException(
                'Respons Midtrans tidak cocok dengan percobaan pembayaran.',
                $order,
                (string) ($data['status_code'] ?? ''),
                $data,
            );
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws MidtransException
     */
    private function bacaResponsApi(Response $response, Order $order, string $aksi): array
    {
        $data = $response->json();
        $data = is_array($data) ? $data : [];

        $statusCode = (string) ($data['status_code'] ?? $response->status());

        if ($response->failed() || ! in_array($statusCode, ['200', '201'], true)) {
            throw new MidtransException(
                "Permintaan {$aksi} ke Midtrans gagal.",
                $order,
                $statusCode,
                $data !== [] ? $data : ['body' => $response->body()],
            );
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws MidtransException
     */
    private function bacaRespons(Response $response, Order $order): array
    {
        $data = $response->json();
        $data = is_array($data) ? $data : [];

        if ($response->failed() || blank($data['token'] ?? null) || blank($data['redirect_url'] ?? null)) {
            throw new MidtransException(
                'Transaksi Snap gagal dibuat.',
                $order,
                (string) ($data['status_code'] ?? $response->status()),
                $data !== [] ? $data : ['body' => $response->body()],
            );
        }

        return $data;
    }

    /**
     * Payload Snap sesuai PRD §11.2.
     *
     * `item_details` memakai snapshot `order_items`, sehingga jumlah
     * `price * quantity` selalu sama dengan `gross_amount`.
     *
     * @return array<string, mixed>
     */
    private function payload(Order $order, Payment $payment): array
    {
        return [
            'transaction_details' => [
                'order_id' => $payment->midtrans_order_id,
                'gross_amount' => $order->total,
            ],
            'item_details' => $order->items
                ->map(fn (OrderItem $item): array => [
                    'id' => (string) ($item->menu_id ?? 'item-'.$item->id),
                    'price' => $item->harga_satuan,
                    'quantity' => $item->jumlah,
                    'name' => Str::limit($item->nama_menu, 50, ''),
                ])
                ->values()
                ->all(),
            'customer_details' => [
                'first_name' => Str::limit($order->nama_pelanggan, 50, ''),
            ],
            'enabled_payments' => ['other_qris'],
            'expiry' => [
                'unit' => 'minutes',
                'duration' => self::BATAS_PEMBAYARAN_MENIT,
            ],
            'callbacks' => [
                'finish' => route('pesanan.status', ['order' => $order->kode_pesanan]),
            ],
        ];
    }

    /**
     * Mode produksi belum dipakai pada MVP; nilai ini hanya memilih host.
     */
    private function urlSnap(): string
    {
        if (config('services.midtrans.is_production')) {
            return 'https://app.midtrans.com/snap/v1/transactions';
        }

        return (string) config('services.midtrans.snap_sandbox_url');
    }

    /**
     * Basis URL API status/pembatalan Midtrans (`/v2`).
     */
    private function urlApi(): string
    {
        if (config('services.midtrans.is_production')) {
            return 'https://api.midtrans.com/v2';
        }

        return rtrim((string) config('services.midtrans.api_sandbox_url'), '/');
    }
}
