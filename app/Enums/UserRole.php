<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Kasir = 'kasir';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin / Owner',
            self::Kasir => 'Kasir',
        };
    }
}
