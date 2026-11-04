<?php

namespace App\Exceptions;

use Exception;

/**
 * Harga menu berubah sejak pelanggan memasukkan item ke keranjang (PRD B-10).
 *
 * Dibedakan dari `ValidationException` supaya controller dapat menjawab `409`:
 * data pesanan sah, hanya harga acuan di keranjang sudah usang, sehingga
 * pelanggan perlu menyesuaikan keranjang sebelum membayar.
 */
class HargaMenuBerubahException extends Exception
{
    /**
     * @param  list<array{menu_id: int, nama: string, harga_lama: int, harga_baru: int}>  $perubahan
     */
    public function __construct(public readonly array $perubahan)
    {
        parent::__construct(self::susunPesan($perubahan));
    }

    /**
     * @param  list<array{menu_id: int, nama: string, harga_lama: int, harga_baru: int}>  $perubahan
     */
    private static function susunPesan(array $perubahan): string
    {
        $daftar = collect($perubahan)
            ->map(fn (array $item): string => sprintf(
                '%s (Rp %s → Rp %s)',
                $item['nama'],
                number_format($item['harga_lama'], 0, ',', '.'),
                number_format($item['harga_baru'], 0, ',', '.'),
            ))
            ->implode(', ');

        return "Harga menu berikut berubah: {$daftar}. Hapus item tersebut dari keranjang, lalu tambahkan lagi dari menu terbaru.";
    }
}
