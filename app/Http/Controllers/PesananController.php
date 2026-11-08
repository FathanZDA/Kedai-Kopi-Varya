<?php

namespace App\Http\Controllers;

use App\Enums\SumberPesanan;
use App\Exceptions\MidtransException;
use App\Http\Resources\PembayaranUlangResource;
use App\Http\Resources\PesananStatusResource;
use App\Models\Order;
use App\Services\MidtransService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Halaman konfirmasi dan status pesanan QRIS Pelanggan B.
 *
 * Halaman ini hanya dapat dibuka dengan kode pesanan acak 8 karakter dan
 * tidak pernah menandai pembayaran lunas. Status lunas tetap berasal dari
 * webhook Midtrans.
 */
class PesananController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly MidtransService $midtrans,
    ) {}

    public function show(Order $order): View
    {
        $this->pastikanPesananQr($order);

        $order->load([
            'items',
            'payments' => fn ($query) => $query->latest('id'),
        ]);

        $popupTersedia = $this->midtrans->popupTerkonfigurasi();

        return view('pelanggan-b.berhasil', [
            'order' => $order,
            'percobaanAktif' => $popupTersedia
                ? $this->orderService->percobaanAktif($order)
                : null,
            'dapatBayarUlang' => $popupTersedia
                && $this->orderService->dapatBayarUlang($order),
        ]);
    }

    public function status(Order $order): JsonResponse
    {
        $this->pastikanPesananQr($order);

        return (new PesananStatusResource(
            $order,
            $this->midtrans->popupTerkonfigurasi()
                && $this->orderService->dapatBayarUlang($order),
        ))->response();
    }

    public function bayarLagi(Order $order): JsonResponse
    {
        $this->pastikanPesananQr($order);

        if (! $this->midtrans->popupTerkonfigurasi()) {
            return response()->json([
                'message' => 'Pembayaran QRIS belum tersedia. Silakan hubungi kasir.',
            ], 503);
        }

        try {
            $hasil = $this->orderService->bayarLagi($order);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first()
                    ?? 'Pesanan ini tidak dapat dibayar ulang.',
            ], 409);
        } catch (MidtransException $exception) {
            return response()->json([
                'message' => 'Pembayaran QRIS gagal dibuat. Silakan coba lagi.',
                'data' => [
                    'kode_pesanan' => $order->kode_pesanan,
                ],
            ], 500);
        }

        return (new PembayaranUlangResource($hasil['payment'], $order->refresh()))
            ->response()
            ->setStatusCode(200);
    }

    private function pastikanPesananQr(Order $order): void
    {
        abort_unless($order->sumber === SumberPesanan::Qr, 404);
    }
}
