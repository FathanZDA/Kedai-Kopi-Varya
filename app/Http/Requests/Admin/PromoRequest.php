<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi tambah dan ubah promosi oleh admin (PRD AD-04b, §8.9).
 *
 * Periode opsional; bila keduanya diisi, tanggal selesai tidak boleh lebih
 * awal dari tanggal mulai. Otorisasi role ditangani middleware
 * `auth` + `role:admin`.
 */
class PromoRequest extends FormRequest
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
            'judul' => ['required', 'string', 'max:80'],
            'deskripsi' => ['nullable', 'string', 'max:220'],
            'urutan' => ['required', 'integer', 'min:1', 'max:999'],
            'aktif' => ['required', 'boolean'],
            'mulai' => ['nullable', 'date'],
            'berakhir' => ['nullable', 'date', 'after_or_equal:mulai'],
            'gambar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judul.required' => 'Judul promosi wajib diisi.',
            'judul.max' => 'Judul promosi maksimal 80 karakter.',
            'deskripsi.max' => 'Deskripsi maksimal 220 karakter.',
            'urutan.required' => 'Urutan tampil wajib diisi.',
            'urutan.integer' => 'Urutan harus berupa angka bulat.',
            'urutan.min' => 'Urutan minimal 1.',
            'urutan.max' => 'Urutan maksimal 999.',
            'aktif.required' => 'Status tampil wajib dipilih.',
            'aktif.boolean' => 'Status tampil harus bernilai aktif atau nonaktif.',
            'mulai.date' => 'Tanggal mulai tidak valid.',
            'berakhir.date' => 'Tanggal selesai tidak valid.',
            'berakhir.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
            'gambar.image' => 'Gambar harus berupa file gambar.',
            'gambar.mimes' => 'Gambar harus berformat JPG, PNG, atau WEBP.',
            'gambar.max' => 'Ukuran gambar maksimal 3 MB.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataPromo(): array
    {
        $data = $this->validated();
        $data['aktif'] = $this->boolean('aktif');

        return $data;
    }
}
