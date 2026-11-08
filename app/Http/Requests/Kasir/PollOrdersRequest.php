<?php

namespace App\Http\Requests\Kasir;

use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Validasi parameter polling Pesanan Masuk.
 *
 * `sejak` opsional: kirim `waktu_server` dari respons sebelumnya untuk
 * menerima hanya pesanan yang berubah sejak saat itu.
 */
class PollOrdersRequest extends FormRequest
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
            'sejak' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sejak.date' => 'Parameter sejak harus berupa tanggal yang valid.',
        ];
    }

    /**
     * Batas waktu perubahan, atau null untuk mengambil seluruh pesanan hari ini.
     */
    public function sejak(): ?CarbonInterface
    {
        $nilai = $this->validated('sejak');

        if ($nilai === null || $nilai === '') {
            return null;
        }

        return Carbon::parse($nilai)->setTimezone(config('app.timezone'));
    }
}
