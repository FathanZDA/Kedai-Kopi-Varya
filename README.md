# Kedai Kopi Varya

Website UMKM Kedai Kopi Varya: etalase publik, pemesanan mandiri lewat QR dengan pembayaran QRIS Midtrans, antarmuka kasir, dan panel admin/owner.

## Fitur

| Area | Akses | Isi |
|---|---|---|
| Publik (Pelanggan A) | Tanpa login | Beranda, menu, tentang, dan lokasi — semua dari database |
| Pesan QR (Pelanggan B) | Tanpa login | Menu mode pesan, keranjang, checkout QRIS (popup Snap), halaman status pesanan dengan tiket antrean dan **Coba Bayar Lagi** |
| Kasir | Login `kasir` | Buat Pesanan tunai (halaman awal), Pesanan Masuk dengan polling, detail pesanan, pembatalan QRIS, Cek Status, Kelola Menu (ketersediaan) |
| Admin | Login `admin` | Dashboard, Kelola Menu (CRUD + foto), Laporan Transaksi (filter bulan + rincian), Iklan & Promosi, Informasi Toko (profil + jam 7 hari) |

Aturan bisnis lengkap ada di `PRD.md`. Ringkasan status pengerjaan dan slice berikutnya ada di `docs/HANDOFF.md`.

Catatan penting:

- Status pembayaran QRIS hanya berubah lewat webhook Midtrans (`POST /api/midtrans/notification`) atau Cek Status server-side kasir — tidak ada simulasi di browser.
- Nomor antrean dibuat setelah pembayaran lunas (QRIS) atau saat pesanan tunai disimpan.
- Keranjang Pelanggan B masih disimpan di `localStorage`; backend belum punya endpoint keranjang.
- Foto Tentang Kami masih demo karena `store_settings` belum punya kolom foto.

## Stack

- Laravel 13 (PHP 8.5)
- Blade + Tailwind CSS 4 + Vite 7
- JavaScript modular (tanpa framework)
- MySQL (`kedai_varya`); test memakai MySQL `kedai_varya_test`
- PHPUnit 12.5
- Midtrans Sandbox lewat HTTP client Laravel (tanpa SDK `midtrans/midtrans-php`)

## Kebutuhan

- PHP 8.5 + ekstensi umum Laravel (`pdo_mysql`, `mbstring`, `openssl`, `curl`)
- Composer
- MySQL / MariaDB (XAMPP cukup)
- Node.js + npm
- Untuk menguji webhook: akun Midtrans Sandbox dan tunnel (ngrok)

Di Windows, `npm.ps1` sering diblokir Execution Policy — gunakan `npm.cmd`.

## Setup Lokal

```bash
# 1. Dependensi
composer install
npm.cmd install

# 2. Konfigurasi
cp .env.example .env
php artisan key:generate

# 3. Buat database MySQL (contoh lewat mysql client)
#    CREATE DATABASE kedai_varya CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 4. Skema + data awal
php artisan migrate --seed

# 5. Jalankan
npm.cmd run dev        # atau `npm.cmd run build` untuk aset produksi
php artisan serve
```

`php artisan migrate:fresh --seed` hanya untuk database yang **tidak** berisi data nyata. Database `kedai_varya` di mesin pengembangan berisi data uji.

### Akun awal

`database/seeders/DatabaseSeeder.php` membuat satu akun `admin` dan satu akun `kasir`. Email dan kata sandinya ada di seeder tersebut; kata sandi tidak didokumentasikan di README demi keamanan. Tidak ada registrasi publik maupun UI manajemen akun — ganti kredensial bawaan sebelum dipakai di luar pengembangan.

Data katalog kanonik (5 kategori, 12 menu, 3 promosi, 7 baris jam toko) berasal dari `database/seeders/PelangganASeeder.php`.

### Folder unggahan

Foto menu dan gambar promosi disimpan di `public/images/menus` dan `public/images/promos`. Pastikan kedua folder dapat ditulis oleh proses PHP.

## Konfigurasi Midtrans (Sandbox)

Isi variabel berikut di `.env` (jangan commit nilainya):

```env
MIDTRANS_MERCHANT_ID=
MIDTRANS_CLIENT_KEY=
MIDTRANS_SERVER_KEY=
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SNAP_SANDBOX_URL=https://app.sandbox.midtrans.com/snap/v1/transactions
MIDTRANS_API_SANDBOX_URL=https://api.sandbox.midtrans.com/v2
```

- `MIDTRANS_SERVER_KEY` hanya dipakai backend; `MIDTRANS_CLIENT_KEY` dikirim ke browser untuk popup Snap.
- Daftarkan Notification URL `https://<domain-publik>/api/midtrans/notification` di dashboard Midtrans.
- Untuk pengembangan lokal, jalankan `ngrok http 127.0.0.1:8000` (pakai IP, bukan `localhost`, agar tidak kena `502`). URL gratis berubah setiap restart — perbarui Notification URL.
- Tanpa `MIDTRANS_SERVER_KEY`/`MIDTRANS_CLIENT_KEY`, checkout dan bayar-ulang menjawab `503`; tanpa `MIDTRANS_SERVER_KEY`, webhook menjawab `500`.
- Panduan lengkap (termasuk troubleshooting ngrok dan sertifikat CA): `docs/MIDTRANS-SETUP.md`.

## Proses Terjadwal

`php artisan qris:reconcile` dijadwalkan setiap 5 menit untuk menyamakan status pembayaran dengan Midtrans dan menutup otomatis pesanan QRIS yang belum dibayar setelah 24 jam.

Saat pengembangan jalankan scheduler:

```bash
php artisan schedule:work
```

Di server produksi gunakan cron `* * * * * php artisan schedule:run`.

## Perintah Pemeriksaan

```bash
php artisan test                                         # 200 test
php vendor/bin/pint --test                               # gaya kode PHP
npm.cmd run build                                        # aset frontend
php artisan route:list --except-vendor                   # daftar route
php artisan qris:reconcile                               # jalankan rekonsiliasi manual
```

### Database test

Test memakai MySQL dengan database terpisah, `kedai_varya_test` (lihat `phpunit.xml`). Buat database itu lebih dulu:

```sql
CREATE DATABASE kedai_varya_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

SQLite tidak dipakai untuk aplikasi maupun test. File `database/database.sqlite` sudah tidak digunakan; isinya hanya data seed lama tanpa tabel transaksi, sehingga aman dihapus setelah dipastikan tidak ada data yang dibutuhkan.

## Struktur Penting

| Path | Isi |
|---|---|
| `app/Http/Controllers` | `PelangganAController`, `PelangganBController`, `KasirController`, `AdminController`, `PesananController`, `MidtransNotificationController` |
| `app/Services` | `OrderService`, `MidtransService`, `MidtransNotificationService`, `QueueNumberService`, `QrisReconciliationService`, `MenuCatalogService`, `PromoService`, `StoreSettingsService`, `ReportService`, `DashboardService` |
| `app/Enums` | Status pesanan/pembayaran, metode, sumber, tipe pesanan, role |
| `database/seeders` | Akun awal + katalog kanonik |
| `resources/js` | Satu modul per area: `pelanggan-a.js`, `pelanggan-b.js`, `kasir.js`, `admin.js` |
| `resources/views` | Blade per area + komponen layout |
| `public/images` | Foto menu, gambar promosi, aset statis |

## Dokumen Acuan

| Dokumen | Isi |
|---|---|
| `PRD.md` | Aturan bisnis dan kriteria penerimaan (acuan utama) |
| `docs/DECISIONS.md` | Keputusan teknis berstatus FINAL/OPEN |
| `docs/DATABASE.md` | Kontrak skema dan aturan transaksi database |
| `docs/ROUTES.md` | Kontrak route dan JSON |
| `docs/MIDTRANS-SETUP.md` | Setup sandbox, ngrok, dan troubleshooting webhook |
| `docs/HANDOFF.md` | Status antar sesi, slice berikutnya, catatan lingkungan |
| `AGENTS.md` | Panduan kerja agent/kontributor |
| `kasir.md`, `admin.md`, `pelanggan-A.md`, `pelanggan-B.md` | Alur per role |

## Aturan Kerja Singkat

- MySQL saja; jangan menambahkan konfigurasi SQLite.
- Jangan memakai `localStorage`/`sessionStorage` untuk data nyata selain keranjang Pelanggan B yang belum punya backend.
- Validasi di server, bukan hanya di JavaScript; gunakan named route di Blade.
- Jangan menandai pembayaran QRIS lunas secara manual atau membuat nomor antrean sebelum lunas.
- Nominal Rupiah disimpan sebagai integer; waktu memakai `Asia/Jakarta`.
- Perubahan skema hanya lewat migration baru; jangan mengubah migration yang sudah dijalankan.
- Sertakan pembaruan dokumen pada commit yang sama dengan perubahan perilaku.
