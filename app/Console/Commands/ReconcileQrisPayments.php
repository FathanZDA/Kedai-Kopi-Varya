<?php

namespace App\Console\Commands;

use App\Services\QrisReconciliationService;
use Illuminate\Console\Command;

class ReconcileQrisPayments extends Command
{
    /**
     * @var string
     */
    protected $signature = 'qris:reconcile';

    /**
     * @var string
     */
    protected $description = 'Rekonsiliasi status pembayaran QRIS dan tutup pesanan yang sudah 24 jam';

    public function handle(QrisReconciliationService $reconciliation): int
    {
        $hasil = $reconciliation->jalankan();

        $this->info(sprintf(
            'Rekonsiliasi selesai: %d diperiksa, %d ditutup, %d gagal.',
            $hasil['diperiksa'],
            $hasil['ditutup'],
            $hasil['gagal'],
        ));

        return self::SUCCESS;
    }
}
