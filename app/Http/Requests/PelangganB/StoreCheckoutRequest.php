<?php

namespace App\Http\Requests\PelangganB;

use App\Enums\TipePesanan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi checkout Pelanggan B (QRIS).
 *
 * Harga, subtotal, dan total tidak diterima dari browser. Nilainya selalu
 * dihitung ulang dari database di App\Services\OrderService.
 *
 * Kontrak: docs/ROUTES.md §4.1. Catatan per item hanya dipakai alur kasir,
 * sehingga checkout memakai `orders.catatan` untuk catatan pesanan.
 */
class StoreCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'tipe_pesanan' => ['required', Rule::enum(TipePesanan::class)],
            'catatan' => ['nullable', 'string', 'max:500'],
            'item' => ['required', 'array', 'min:1', 'max:50'],
            'item.*.menu_id' => ['required', 'integer', 'distinct'],
            'item.*.jumlah' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_pelanggan.required' => 'Nama pelanggan wajib diisi.',
            'nama_pelanggan.max' => 'Nama pelanggan maksimal 100 karakter.',
            'tipe_pesanan.required' => 'Tipe pesanan wajib dipilih.',
            'tipe_pesanan.enum' => 'Tipe pesanan harus Makan di Tempat atau Bawa Pulang.',
            'catatan.max' => 'Catatan pesanan maksimal 500 karakter.',
            'item.required' => 'Keranjang tidak boleh kosong.',
            'item.array' => 'Keranjang tidak valid.',
            'item.min' => 'Keranjang tidak boleh kosong.',
            'item.max' => 'Satu pesanan maksimal berisi 50 baris menu.',
            'item.*.menu_id.required' => 'Menu pada keranjang tidak valid.',
            'item.*.menu_id.integer' => 'Menu pada keranjang tidak valid.',
            'item.*.menu_id.distinct' => 'Menu yang sama tidak boleh dikirim dua kali.',
            'item.*.jumlah.required' => 'Jumlah item wajib diisi.',
            'item.*.jumlah.integer' => 'Jumlah item harus berupa angka.',
            'item.*.jumlah.min' => 'Jumlah item minimal 1.',
            'item.*.jumlah.max' => 'Jumlah item maksimal 99.',
        ];
    }
}
