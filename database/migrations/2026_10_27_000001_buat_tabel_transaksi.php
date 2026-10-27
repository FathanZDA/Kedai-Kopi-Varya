<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel transaksi sesuai kontrak docs/DATABASE.md §4.4-4.8.
     *
     * Hanya struktur tabel; aturan bisnis (pembuatan order, pembayaran, dan
     * nomor antrean) dijalankan oleh Service/Action pada slice backend.
     *
     * Urutan pembuatan mengikuti docs/DATABASE.md §11 agar foreign key selalu
     * menunjuk tabel yang sudah ada.
     */
    public function up(): void
    {
        $this->buatQueueCounters();
        $this->buatOrders();
        $this->buatOrderItems();
        $this->buatPayments();
        $this->buatPaymentNotifications();
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_notifications');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('queue_counters');
    }

    private function buatQueueCounters(): void
    {
        Schema::create('queue_counters', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->unsignedInteger('nomor_terakhir')->default(0);
            $table->timestamps();
        });
    }

    private function buatOrders(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->char('kode_pesanan', 8)->unique();
            $table->unsignedSmallInteger('nomor_antrean')->nullable();
            $table->date('tanggal_antrean')->nullable();
            $table->string('nama_pelanggan', 100);
            $table->enum('tipe_pesanan', ['dine_in', 'take_away']);
            $table->text('catatan')->nullable();
            $table->enum('sumber', ['qr', 'kasir']);
            $table->enum('status_pesanan', [
                'menunggu_pembayaran',
                'antre',
                'diproses',
                'selesai',
                'dibatalkan',
            ])->default('menunggu_pembayaran');
            $table->enum('metode_pembayaran', ['qris', 'tunai']);
            $table->enum('status_pembayaran', [
                'menunggu',
                'lunas',
                'gagal',
                'kedaluwarsa',
                'dibatalkan',
                'perlu_diperiksa',
            ])->default('menunggu');
            $table->unsignedInteger('total');
            $table->unsignedInteger('uang_diterima')->nullable();
            $table->unsignedInteger('kembalian')->nullable();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('waktu_lunas')->nullable();
            $table->timestamp('batas_waktu_pembayaran')->nullable();
            $table->timestamps();

            // Nomor antrean unik per hari. MySQL mengizinkan banyak NULL pada
            // unique index, sehingga order yang belum lunas tidak bentrok.
            $table->unique(['tanggal_antrean', 'nomor_antrean']);
            $table->index(['status_pembayaran', 'created_at']);
            $table->index(['status_pesanan', 'created_at']);
            $table->index(['cashier_id', 'created_at']);
        });
    }

    private function buatOrderItems(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('menu_id')->nullable()->constrained('menus')->nullOnDelete();
            $table->string('nama_menu');
            $table->unsignedInteger('harga_satuan');
            $table->unsignedSmallInteger('jumlah');
            $table->unsignedInteger('subtotal');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    private function buatPayments(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('midtrans_order_id', 50)->unique();
            $table->string('transaction_id', 100)->nullable();
            $table->string('status_transaksi', 50);
            $table->string('status_code', 10);
            $table->string('fraud_status', 20)->nullable();
            $table->decimal('gross_amount', 15, 2);
            $table->string('payment_type', 50)->nullable();
            $table->string('snap_token')->nullable();
            $table->text('snap_redirect_url')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('waktu_dibuat')->useCurrent();
            $table->timestamp('waktu_diproses')->nullable();
            $table->timestamps();
        });
    }

    private function buatPaymentNotifications(): void
    {
        Schema::create('payment_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('midtrans_order_id', 50);
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('transaction_status', 50);
            $table->string('status_code', 10);
            $table->decimal('gross_amount', 15, 2);
            $table->boolean('signature_valid')->default(false);
            $table->json('payload');
            $table->enum('status_pemrosesan', [
                'diterima',
                'diproses',
                'diabaikan',
                'gagal',
            ])->default('diterima');
            $table->text('catatan')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();

            $table->index('midtrans_order_id');
        });
    }
};
