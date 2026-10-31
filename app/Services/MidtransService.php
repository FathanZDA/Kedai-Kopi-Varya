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
}
