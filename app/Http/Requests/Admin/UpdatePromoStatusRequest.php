<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi perubahan cepat status aktif promosi dari daftar Iklan & Promosi.
 *
 * Hanya `promos.aktif` yang berubah; data lain tetap lewat form ubah promosi.
 * Otorisasi role ditangani middleware `auth` + `role:admin`.
 */
class UpdatePromoStatusRequest extends FormRequest
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
            'aktif' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'aktif.required' => 'Status tampil wajib diisi.',
            'aktif.boolean' => 'Status tampil harus bernilai aktif atau nonaktif.',
        ];
    }

    public function aktif(): bool
    {
        return (bool) $this->validated('aktif');
    }
}
