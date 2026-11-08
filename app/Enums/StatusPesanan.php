<?php

namespace App\Enums;

enum StatusPesanan: string
{
    case MenungguPembayaran = 'menunggu_pembayaran';
    case Antre = 'antre';
    case Diproses = 'diproses';
    case Selesai = 'selesai';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::MenungguPembayaran => 'Menunggu Pembayaran',
            self::Antre => 'Antre',
            self::Diproses => 'Diproses',
            self::Selesai => 'Selesai',
            self::Dibatalkan => 'Dibatalkan',
        };
    }
}
