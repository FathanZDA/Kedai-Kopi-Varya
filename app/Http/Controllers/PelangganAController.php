<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Promo;
use App\Models\StoreSetting;

class PelangganAController extends Controller
{
    public function beranda()
    {
        return view('pelanggan-a.beranda', [
            ...$this->publicData(),
            'promos' => Promo::query()->active()->ordered()->get(),
        ]);
    }

    public function tentang()
    {
        return view('pelanggan-a.tentang', $this->publicData());
    }

    public function menu()
    {
        return view('pelanggan-a.menu', $this->publicData());
    }

    public function lokasi()
    {
        return view('pelanggan-a.lokasi', $this->publicData());
    }

    /**
     * Data yang dipakai hampir semua halaman publik.
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