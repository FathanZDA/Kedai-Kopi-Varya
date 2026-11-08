<?php

namespace App\Http\Requests\Kasir;

use App\Enums\StatusPesanan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi perubahan status pesanan oleh kasir.
 *
 * Hanya `antre → diproses → selesai` yang diizinkan; aturan transisinya
 * diperiksa di App\Services\OrderService::ubahStatus().
 *
 * Otorisasi role kasir sudah ditangani middleware `auth` + `role:kasir`.
 */
class UpdateOrderStatusRequest extends FormRequest
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
            'status' => ['required', Rule::enum(StatusPesanan::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status pesanan wajib diisi.',
            'status.enum' => 'Status pesanan tidak dikenal.',
        ];
    }

    public function statusPesanan(): StatusPesanan
    {
        return StatusPesanan::from((string) $this->validated('status'));
    }
}
