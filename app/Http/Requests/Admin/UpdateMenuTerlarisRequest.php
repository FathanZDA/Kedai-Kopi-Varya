<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi perubahan cepat penanda "Terlaris" dari tabel Kelola Menu (F-75).
 *
 * Hanya `menus.terlaris` yang boleh berubah; data lain tetap lewat form ubah
 * menu. Otorisasi role ditangani middleware `auth` + `role:admin`.
 */
class UpdateMenuTerlarisRequest extends FormRequest
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
            'terlaris' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'terlaris.required' => 'Status terlaris wajib diisi.',
            'terlaris.boolean' => 'Status terlaris harus bernilai ya atau tidak.',
        ];
    }

    public function terlaris(): bool
    {
        return (bool) $this->validated('terlaris');
    }
}
