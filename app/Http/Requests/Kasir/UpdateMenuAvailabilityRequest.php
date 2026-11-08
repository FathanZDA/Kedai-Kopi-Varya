<?php

namespace App\Http\Requests\Kasir;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi perubahan ketersediaan menu oleh kasir.
 *
 * Kasir hanya boleh mengubah `menus.tersedia`; nama, harga, kategori, dan
 * deskripsi tetap milik admin. Otorisasi role ditangani middleware
 * `auth` + `role:kasir`.
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
