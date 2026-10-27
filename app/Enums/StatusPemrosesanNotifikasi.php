<?php

namespace App\Enums;

enum StatusPemrosesanNotifikasi: string
{
    case Diterima = 'diterima';
    case Diproses = 'diproses';
    case Diabaikan = 'diabaikan';
    case Gagal = 'gagal';

    public function label(): string
    {
        return match ($this) {
            self::Diterima => 'Diterima',
            self::Diproses => 'Diproses',
            self::Diabaikan => 'Diabaikan',
            self::Gagal => 'Gagal',
        };
    }
}
