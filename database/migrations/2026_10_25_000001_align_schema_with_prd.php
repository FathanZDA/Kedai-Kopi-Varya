<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyelaraskan skema lama (English) dengan kontrak docs/DATABASE.md.
     *
     * Migration ini tidak mengubah migration yang sudah dijalankan. Semua
     * perubahan dibuat sebagai migration baru agar data yang sudah ada tetap
     * dapat dimigrasikan.
     */
    public function up(): void
    {
        $this->tambahRolePengguna();
        $this->ubahKategori();
        $this->ubahMenu();
        $this->ubahPromo();
        $this->buatJamToko();
        $this->ubahPengaturanToko();
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->renameColumn('nama_toko', 'name');
            $table->renameColumn('sejarah', 'about_text');
            $table->renameColumn('alamat', 'address');
            $table->dropColumn(['maps_embed', 'telepon', 'whatsapp', 'fasilitas']);
            $table->string('landmark')->nullable()->after('address');
            $table->json('hours')->nullable()->after('about_text');
        });

        Schema::dropIfExists('store_hours');

        Schema::table('promos', function (Blueprint $table) {
            $table->renameColumn('judul', 'title');
            $table->renameColumn('deskripsi', 'description');
            $table->renameColumn('gambar', 'image');
            $table->renameColumn('aktif', 'is_active');
            $table->renameColumn('urutan', 'sort_order');
            $table->string('subtitle')->nullable()->after('title');
            $table->string('badge_text')->nullable()->after('image');
            $table->dropColumn(['mulai', 'berakhir']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['category_id', 'tersedia']);
            $table->dropSoftDeletes();
            $table->renameColumn('nama', 'name');
            $table->renameColumn('harga', 'price');
            $table->renameColumn('deskripsi', 'description');
            $table->renameColumn('foto', 'image');
            $table->renameColumn('tersedia', 'is_available');
            $table->renameColumn('urutan', 'sort_order');
            $table->boolean('is_featured')->default(false)->after('image');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->index(['category_id', 'is_available']);
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->renameColumn('nama', 'name');
            $table->renameColumn('urutan', 'sort_order');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }

    private function tambahRolePengguna(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'kasir'])->nullable()->after('password');
        });

        // Akun yang sudah ada sebelumnya adalah akun demo/admin.
        DB::table('users')->whereNull('role')->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'kasir'])->nullable(false)->change();
            $table->index('role');
        });
    }

    private function ubahKategori(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->renameColumn('name', 'nama');
            $table->renameColumn('sort_order', 'urutan');
        });
    }

    private function ubahMenu(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['category_id', 'is_available']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->renameColumn('name', 'nama');
            $table->renameColumn('price', 'harga');
            $table->renameColumn('description', 'deskripsi');
            $table->renameColumn('image', 'foto');
            $table->renameColumn('is_available', 'tersedia');
            $table->renameColumn('sort_order', 'urutan');
            $table->dropColumn('is_featured');
            $table->softDeletes();
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->index(['category_id', 'tersedia']);
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
        });
    }

    private function ubahPromo(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->renameColumn('title', 'judul');
            $table->renameColumn('description', 'deskripsi');
            $table->renameColumn('image', 'gambar');
            $table->renameColumn('is_active', 'aktif');
            $table->renameColumn('sort_order', 'urutan');
            $table->dropColumn(['subtitle', 'badge_text']);
            $table->date('mulai')->nullable()->after('aktif');
            $table->date('berakhir')->nullable()->after('mulai');
        });
    }

    private function buatJamToko(): void
    {
        Schema::create('store_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('hari')->unique();
            $table->time('jam_buka')->nullable();
            $table->time('jam_tutup')->nullable();
            $table->boolean('tutup')->default(false);
            $table->timestamps();
        });

        $store = Schema::hasTable('store_settings')
            ? DB::table('store_settings')->first()
            : null;

        $jamTersimpan = [];

        if ($store && isset($store->hours)) {
            $decoded = json_decode((string) $store->hours, true);
            $jamTersimpan = is_array($decoded) ? $decoded : [];
        }

        $namaHari = [
            0 => ['minggu', 'Minggu'],
            1 => ['senin', 'Senin'],
            2 => ['selasa', 'Selasa'],
            3 => ['rabu', 'Rabu'],
            4 => ['kamis', 'Kamis'],
            5 => ['jumat', 'Jumat'],
            6 => ['sabtu', 'Sabtu'],
        ];

        foreach ($namaHari as $hari => [$key, $label]) {
            $rentang = $jamTersimpan[$label] ?? $jamTersimpan[$key] ?? '08.00 - 23.00';
            [$buka, $tutup] = $this->parseRentangJam((string) $rentang);

            DB::table('store_hours')->updateOrInsert(
                ['hari' => $hari],
                [
                    'jam_buka' => $buka,
                    'jam_tutup' => $tutup,
                    'tutup' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function ubahPengaturanToko(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->renameColumn('name', 'nama_toko');
            $table->renameColumn('about_text', 'sejarah');
            $table->renameColumn('address', 'alamat');
            $table->text('alamat')->nullable()->change();
            $table->text('maps_url')->nullable()->change();
            $table->dropColumn(['landmark', 'hours']);
            $table->text('maps_embed')->nullable()->after('maps_url');
            $table->string('telepon', 30)->nullable()->after('maps_embed');
            $table->string('whatsapp', 30)->nullable()->after('telepon');
            $table->text('fasilitas')->nullable()->after('instagram');
        });
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function parseRentangJam(string $rentang): array
    {
        if (! preg_match('/(\d{1,2})[.:](\d{2})\s*[-–]\s*(\d{1,2})[.:](\d{2})/', $rentang, $matches)) {
            return ['08:00:00', '23:00:00'];
        }

        return [
            sprintf('%02d:%02d:00', (int) $matches[1], (int) $matches[2]),
            sprintf('%02d:%02d:00', (int) $matches[3], (int) $matches[4]),
        ];
    }
};
