<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\MidtransNotificationController;
use App\Http\Controllers\PelangganAController;
use App\Http\Controllers\PelangganBController;
use App\Http\Controllers\PesananController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autentikasi — login bersama kasir dan admin
|--------------------------------------------------------------------------
| Tidak ada registrasi publik. Akun dibuat lewat seeder.
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Pelanggan A — pengunjung tanpa login
|--------------------------------------------------------------------------
| Landing page, menu, tentang toko, dan lokasi.
*/

Route::get('/', [PelangganAController::class, 'beranda'])->name('pelanggan-a.beranda');

Route::get('/tentang', [PelangganAController::class, 'tentang'])->name('pelanggan-a.tentang');

Route::redirect('/tentang-kami', '/tentang', 301);

Route::get('/menu', [PelangganAController::class, 'menu'])->name('pelanggan-a.menu');

Route::get('/lokasi', [PelangganAController::class, 'lokasi'])->name('pelanggan-a.lokasi');

/*
|--------------------------------------------------------------------------
| Pelanggan B — pemesanan mandiri di kedai (scan QR)
|--------------------------------------------------------------------------
| Keranjang masih di sisi klien. Checkout menyimpan order QRIS lewat
| POST /pesan/checkout dan membuka popup Snap di halaman checkout.
| Status lunas hanya ditentukan oleh webhook (lihat grup di bawah).
*/

Route::prefix('pesan')->name('pelanggan-b.')->group(function () {
    Route::get('/', [PelangganBController::class, 'beranda'])->name('beranda');

    Route::get('/menu/{menu:slug}', [PelangganBController::class, 'produk'])->name('produk');

    Route::get('/keranjang', [PelangganBController::class, 'keranjang'])->name('keranjang');

    Route::get('/checkout', [PelangganBController::class, 'checkout'])->name('checkout');

    Route::post('/checkout', [PelangganBController::class, 'storeCheckout'])
        ->middleware('throttle:checkout')
        ->name('checkout.store');
});

/*
|--------------------------------------------------------------------------
| Konfirmasi dan status pesanan QRIS
|--------------------------------------------------------------------------
| Halaman publik dengan kode pesanan acak. Polling hanya membaca status;
| perubahan lunas tetap berasal dari webhook Midtrans.
*/

Route::get('/pesanan/{order:kode_pesanan}/status', [PesananController::class, 'status'])
    ->where('order', '[A-Z0-9]{8}')
    ->middleware('throttle:order-status')
    ->name('pesanan.status.json');

Route::post('/pesanan/{order:kode_pesanan}/bayar-lagi', [PesananController::class, 'bayarLagi'])
    ->where('order', '[A-Z0-9]{8}')
    ->middleware('throttle:checkout')
    ->name('pesanan.bayar-lagi');

Route::get('/pesanan/{order:kode_pesanan}', [PesananController::class, 'show'])
    ->where('order', '[A-Z0-9]{8}')
    ->name('pesanan.status');

/*
|--------------------------------------------------------------------------
| Webhook Midtrans — publik, tanpa auth dan tanpa CSRF
|--------------------------------------------------------------------------
| Satu-satunya jalur yang boleh menandai pembayaran QRIS lunas. Signature
| diverifikasi di controller; path ini dikecualikan dari CSRF di
| bootstrap/app.php. Jangan menambahkan throttle ketat di sini karena
| Midtrans dapat mengirim ulang notifikasi.
*/

Route::post('/api/midtrans/notification', [MidtransNotificationController::class, 'handle'])
    ->name('midtrans.notification');

/*
|--------------------------------------------------------------------------
| Kasir — wajib login dengan role kasir
|--------------------------------------------------------------------------
| Menu dan kategori dibaca dari database. Transaksi tunai disimpan lewat
| POST /kasir/pesanan, statusnya diubah lewat PATCH .../status, dan
| ketersediaan menu lewat PATCH /kasir/menu/{menu}/availability.
*/

Route::middleware(['auth', 'role:kasir'])->group(function () {
    Route::redirect('/kasir', '/kasir/pesanan/baru', 301);

    Route::prefix('kasir')->name('kasir.')->group(function () {
        Route::get('/pesanan/baru', [KasirController::class, 'buatPesanan'])->name('pesanan.baru');

        // Didaftarkan sebelum route lain yang memakai {order} agar "poll"
        // tidak dianggap sebagai id pesanan.
        Route::get('/pesanan/poll', [KasirController::class, 'pollOrders'])
            ->middleware('throttle:120,1')
            ->name('pesanan.poll');

        Route::post('/pesanan', [KasirController::class, 'storeOrder'])->name('pesanan.store');

        Route::patch('/pesanan/{order}/status', [KasirController::class, 'updateStatus'])->name('pesanan.status');

        Route::get('/pesanan', [KasirController::class, 'pesananMasuk'])->name('pesanan.index');

        Route::get('/menu', [KasirController::class, 'kelolaMenu'])->name('menu.index');

        Route::patch('/menu/{menu}/availability', [KasirController::class, 'updateAvailability'])->name('menu.availability');
    });
});

/*
|--------------------------------------------------------------------------
| Admin / Owner — wajib login dengan role admin
|--------------------------------------------------------------------------
| Menu, kategori, promosi, dan informasi toko sudah dibaca dari database.
| Transaksi, laporan, dan aksi CRUD masih data contoh/localStorage.
*/

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::redirect('/admin', '/admin/dashboard', 301);

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::get('/menu', [AdminController::class, 'menus'])->name('menu.index');

        Route::get('/menu/create', [AdminController::class, 'createMenu'])->name('menu.create');

        Route::get('/menu/{menu}/edit', [AdminController::class, 'editMenu'])->name('menu.edit');

        Route::get('/laporan', [AdminController::class, 'reports'])->name('reports');

        Route::get('/promosi', [AdminController::class, 'promos'])->name('promos');

        Route::get('/toko', [AdminController::class, 'store'])->name('store');
    });
});
