<?php

use App\Http\Controllers\PelangganAController;
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