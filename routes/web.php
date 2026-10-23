<?php

use App\Http\Controllers\PelangganAController;
use App\Http\Controllers\PelangganBController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pelanggan A — pengunjung tanpa login
|--------------------------------------------------------------------------
| Landing page, menu, tentang toko, dan lokasi.
*/

Route::get('/', [PelangganAController::class, 'beranda'])->name('pelanggan-a.beranda');

Route::get('/tentang-kami', [PelangganAController::class, 'tentang'])->name('pelanggan-a.tentang');

Route::get('/menu', [PelangganAController::class, 'menu'])->name('pelanggan-a.menu');

Route::get('/lokasi', [PelangganAController::class, 'lokasi'])->name('pelanggan-a.lokasi');

/*
|--------------------------------------------------------------------------
| Pelanggan B — pemesanan mandiri di kedai (scan QR)
|--------------------------------------------------------------------------
| Tampilan UI memakai keranjang sisi klien. Tabel pesanan dan integrasi
| Midtrans akan ditambahkan pada tahap backend berikutnya.
*/

Route::prefix('pesan')->name('pelanggan-b.')->group(function () {
    Route::get('/', [PelangganBController::class, 'beranda'])->name('beranda');

    Route::get('/menu/{menu:slug}', [PelangganBController::class, 'produk'])->name('produk');

    Route::get('/keranjang', [PelangganBController::class, 'keranjang'])->name('keranjang');

    Route::get('/checkout', [PelangganBController::class, 'checkout'])->name('checkout');

    Route::get('/pembayaran', [PelangganBController::class, 'pembayaran'])->name('pembayaran');

    Route::get('/berhasil', [PelangganBController::class, 'berhasil'])->name('berhasil');
});