<?php

namespace App\Http\Requests\Kasir;

use App\Enums\TipePesanan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi input "Buat Pesanan" kasir (tunai).
 *
 * Harga dan subtotal tidak diterima dari browser. Nilainya selalu dihitung
 * ulang dari database di App\Services\OrderService.
 *
 * Otorisasi role kasir sudah ditangani middleware `auth` + `role:kasir`
 * pada routes/web.php.
 */
class StoreOrderRequest extends FormRequest
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
            'uang_diterima' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'item' => ['required', 'array', 'min:1', 'max:50'],
            'item.*.menu_id' => ['required', 'integer', 'distinct'],
            'item.*.jumlah' => ['required', 'integer', 'min:1', 'max:99'],
            'item.*.catatan' => ['nullable', 'string', 'max:200'],
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
            'tipe_pesanan.enum' => 'Tipe pesanan harus Dine In atau Take Away.',
            'catatan.max' => 'Catatan pesanan maksimal 500 karakter.',
            'uang_diterima.required' => 'Uang diterima wajib diisi.',
            'uang_diterima.integer' => 'Uang diterima harus berupa angka.',
            'uang_diterima.min' => 'Uang diterima tidak boleh negatif.',
            'uang_diterima.max' => 'Uang diterima terlalu besar.',
            'item.required' => 'Pesanan harus berisi minimal satu menu.',
            'item.min' => 'Pesanan harus berisi minimal satu menu.',
            'item.max' => 'Satu pesanan maksimal berisi 50 baris menu.',
            'item.*.menu_id.required' => 'Menu pada baris pesanan tidak valid.',
            'item.*.menu_id.integer' => 'Menu pada baris pesanan tidak valid.',
            'item.*.menu_id.distinct' => 'Menu yang sama tidak boleh dikirim dua kali.',
            'item.*.jumlah.required' => 'Jumlah item wajib diisi.',
            'item.*.jumlah.integer' => 'Jumlah item harus berupa angka.',
            'item.*.jumlah.min' => 'Jumlah item minimal 1.',
            'item.*.jumlah.max' => 'Jumlah item maksimal 99.',
            'item.*.catatan.max' => 'Catatan item maksimal 200 karakter.',
        ];
    }
}
