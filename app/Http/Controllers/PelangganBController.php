<?php

namespace App\Http\Controllers;

use App\Exceptions\MidtransException;
use App\Http\Requests\PelangganB\StoreCheckoutRequest;
use App\Http\Resources\CheckoutResource;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Promo;
use App\Models\StoreHour;
use App\Models\StoreSetting;
use App\Services\MidtransService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

class PelangganBController extends Controller
{
    public function beranda()
    {
        return view('pelanggan-b.beranda', [
            ...$this->publicData(),
            'promos' => Promo::query()->active()->ordered()->get(),
        ]);
    }

    public function produk(Menu $menu)
    {
        $menu->loadMissing('category');

        return view('pelanggan-b.produk', [
            ...$this->publicData(),
            'menu' => $menu,
        ]);
    }

    public function keranjang()
    {
        return view('pelanggan-b.keranjang', $this->publicData());
    }

    public function checkout()
    {
        return view('pelanggan-b.checkout', $this->publicData());
    }

    /**
     * Simpan order QRIS dan minta halaman pembayaran Snap.
     *
     * Order disimpan sebagai `menunggu_pembayaran` tanpa nomor antrean.
     * Nomor antrean baru dibuat oleh webhook setelah pembayaran lunas.
     *
     * Bila kredensial Midtrans belum diatur, checkout ditolak `503` sebelum
     * order dibuat supaya tidak ada order yatim yang tidak bisa dibayar.
     * Bila Snap gagal, order tetap tersimpan dan `kode_pesanan` dikembalikan
     * agar pelanggan bisa dibantu kasir.
     */
    public function storeCheckout(
        StoreCheckoutRequest $request,
        OrderService $orderService,
        MidtransService $midtrans,
    ): JsonResponse {
        if (! $midtrans->popupTerkonfigurasi()) {
            return response()->json([
                'message' => 'Pembayaran QRIS belum tersedia. Silakan hubungi kasir untuk memesan.',
            ], 503);
        }

        try {
            $order = $orderService->buatOrderQris($request->validated());
        } catch (MidtransException $exception) {
            return response()->json([
                'message' => 'Pesanan sudah tersimpan, tetapi halaman pembayaran QRIS gagal dibuat. Silakan hubungi kasir dengan kode pesanan ini.',
                'data' => $exception->order
                    ? ['kode_pesanan' => $exception->order->kode_pesanan]
                    : null,
            ], 500);
        }

        return (new CheckoutResource($order))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Data yang dipakai hampir semua halaman Pelanggan B.
     */
    private function publicData(): array
    {
        return [
            'store' => StoreSetting::instance(),
            'hours' => StoreHour::query()->ordered()->get(),
            'categories' => Category::query()->ordered()->get(),
            'menus' => Menu::query()->with('category')->ordered()->get(),
        ];
    }
}
