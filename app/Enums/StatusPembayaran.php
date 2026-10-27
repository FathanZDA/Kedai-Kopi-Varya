<?php

namespace App\Enums;

enum StatusPembayaran: string
{
    case Menunggu = 'menunggu';
    case Lunas = 'lunas';
    case Gagal = 'gagal';
    case Kedaluwarsa = 'kedaluwarsa';
    case Dibatalkan = 'dibatalkan';
    case PerluDiperiksa = 'perlu_diperiksa';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu',
            self::Lunas => 'Lunas',
            self::Gagal => 'Gagal',
            self::Kedaluwarsa => 'Kedaluwarsa',
            self::Dibatalkan => 'Dibatalkan',
            self::PerluDiperiksa => 'Perlu Diperiksa',
        };
    }
}
