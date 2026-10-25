<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\KasirController;
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

/*
|--------------------------------------------------------------------------
| Kasir — tampilan front-end
|--------------------------------------------------------------------------
| Tahap ini hanya menyiapkan tampilan. Data menu dan pesanan masih berupa
| data contoh di KasirController; autentikasi dan database menyusul.
*/

Route::prefix('kasir')->name('kasir.')->group(function () {
    Route::redirect('/', '/kasir/pesanan/baru');

    Route::get('/pesanan/baru', [KasirController::class, 'buatPesanan'])->name('pesanan.baru');

    Route::get('/pesanan', [KasirController::class, 'pesananMasuk'])->name('pesanan.index');

    Route::get('/menu', [KasirController::class, 'kelolaMenu'])->name('menu.index');
});

/*
|--------------------------------------------------------------------------
| Admin / Owner — tampilan front-end
|--------------------------------------------------------------------------
| Dashboard, kelola menu, laporan transaksi, iklan & promosi, dan informasi
| toko. Data masih berupa contoh di AdminController dan localStorage browser.
*/

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/menu', [AdminController::class, 'menus'])->name('menu.index');

    Route::get('/menu/create', [AdminController::class, 'createMenu'])->name('menu.create');

    Route::get('/menu/{menu}/edit', [AdminController::class, 'editMenu'])->name('menu.edit');

    Route::get('/laporan', [AdminController::class, 'reports'])->name('reports');

    Route::get('/promosi', [AdminController::class, 'promos'])->name('promos');

    Route::get('/toko', [AdminController::class, 'store'])->name('store');
});