<?php

namespace App\Exceptions;

use App\Models\Order;
use RuntimeException;

/**
 * Kegagalan saat berkomunikasi dengan Midtrans.
 *
 * Order yang sedang diproses ikut dibawa agar controller tetap dapat
 * mengembalikan `kode_pesanan` ketika halaman pembayaran gagal dibuat.
 */
class MidtransException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $rawResponse
     */
    public function __construct(
        string $message,
        public readonly ?Order $order = null,
        public readonly ?string $statusCode = null,
        public readonly ?array $rawResponse = null,
    ) {
        parent::__construct($message);
    }
}
