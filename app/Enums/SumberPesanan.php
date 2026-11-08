<?php

namespace App\Enums;

enum SumberPesanan: string
{
    case Qr = 'qr';
    case Kasir = 'kasir';

    public function label(): string
    {
        return match ($this) {
            self::Qr => 'Pesanan QR',
            self::Kasir => 'Kasir',
        };
    }
}
