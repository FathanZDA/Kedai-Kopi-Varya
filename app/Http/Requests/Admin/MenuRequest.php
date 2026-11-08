<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi tambah dan ubah menu oleh admin (PRD AD-02b/AD-02c, §8.6).
 *
 * Kategori memakai daftar tetap yang sudah di-seed, sehingga hanya
 * `category_id` yang sudah ada yang diterima. Otorisasi role ditangani
 * middleware `auth` + `role:admin`.
 */
class MenuRequest extends FormRequest
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
            'nama' => ['required', 'string', 'max:80'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'harga' => ['required', 'integer', 'min:1', 'max:100000000'],
            'deskripsi' => ['nullable', 'string', 'max:300'],
            'tersedia' => ['required', 'boolean'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama menu wajib diisi.',
            'nama.max' => 'Nama menu maksimal 80 karakter.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak tersedia.',
            'harga.required' => 'Harga wajib diisi.',
            'harga.integer' => 'Harga harus berupa bilangan bulat dalam Rupiah.',
            'harga.min' => 'Harga harus lebih besar dari nol.',
            'harga.max' => 'Harga terlalu besar.',
            'deskripsi.max' => 'Deskripsi maksimal 300 karakter.',
            'tersedia.required' => 'Status ketersediaan wajib dipilih.',
            'tersedia.boolean' => 'Status ketersediaan harus bernilai tersedia atau habis.',
            'foto.image' => 'Foto harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataMenu(): array
    {
        $data = $this->validated();
        $data['tersedia'] = $this->boolean('tersedia');

        return $data;
    }
}
