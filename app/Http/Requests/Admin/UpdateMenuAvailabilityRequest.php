<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi perubahan cepat ketersediaan menu dari tabel Kelola Menu.
 *
 * Hanya `menus.tersedia` yang boleh berubah; data lain tetap lewat form ubah
 * menu. Otorisasi role ditangani middleware `auth` + `role:admin`.
 */
class UpdateMenuAvailabilityRequest extends FormRequest
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
            'tersedia' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tersedia.required' => 'Status ketersediaan wajib diisi.',
            'tersedia.boolean' => 'Status ketersediaan harus bernilai tersedia atau habis.',
        ];
    }

    public function tersedia(): bool
    {
        return (bool) $this->validated('tersedia');
    }
}
