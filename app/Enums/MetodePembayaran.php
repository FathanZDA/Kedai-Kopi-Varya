<?php

namespace App\Enums;

enum MetodePembayaran: string
{
    case Qris = 'qris';
    case Tunai = 'tunai';

    public function label(): string
    {
        return match ($this) {
            self::Qris => 'QRIS',
            self::Tunai => 'Tunai',
        };
    }
}
