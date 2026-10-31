<?php

namespace App\Services;

use App\Models\QueueCounter;
use Illuminate\Support\Facades\DB;

/**
 * Penomoran antrean harian.
 *
 * Dipakai dua alur yang berbeda: order tunai kasir (langsung lunas) dan
 * order QRIS yang baru lunas setelah webhook Midtrans. Keduanya wajib
 * memanggil method ini di dalam `DB::transaction` agar baris penghitung
 * terkunci dan nomor tidak pernah ganda (PRD §11.5).
 *
 * @see docs/DATABASE.md §7.1
 */
class QueueNumberService
{
    /**
     * Ambil nomor antrean berikutnya untuk tanggal tertentu.
     *
     * `insertOrIgnore` menangani hari pertama saat baris penghitung belum
     * ada. `lockForUpdate()` menahan transaksi lain sampai nomor tersimpan.
     *
     * @param  string  $tanggal  format `Y-m-d`
     */
    public function berikutnya(string $tanggal): int
    {
        QueueCounter::query()->insertOrIgnore([
            'tanggal' => $tanggal,
            'nomor_terakhir' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counter = QueueCounter::query()
            ->where('tanggal', $tanggal)
            ->lockForUpdate()
            ->firstOrFail();

        $counter->nomor_terakhir += 1;
        $counter->save();

        return $counter->nomor_terakhir;
    }
}
