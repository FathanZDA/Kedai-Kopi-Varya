<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Promo;
use App\Models\StoreSetting;

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

    public function pembayaran()
    {
        return view('pelanggan-b.pembayaran', $this->publicData());
    }

    public function berhasil()
    {
        return view('pelanggan-b.berhasil', $this->publicData());
    }

    /**
     * Data yang dipakai hampir semua halaman Pelanggan B.
     */
    private function publicData(): array
    {
        return [
            'store' => StoreSetting::instance(),
            'categories' => Category::query()->ordered()->get(),
            'menus' => Menu::query()->with('category')->ordered()->get(),
        ];
    }
}
