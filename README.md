# Kedai Kopi Varya

Website UMKM Kedai Kopi Varya untuk katalog publik, pemesanan mandiri lewat QR, kasir, dan admin/owner.

## Status

Tahap saat ini adalah penyelarasan front-end dan data dengan kontrak produk. Yang sudah tersedia:

- Halaman publik: beranda, menu, tentang, dan lokasi.
- Katalog Pelanggan B dan keranjang sisi klien.
- Tampilan kasir dengan menu/kategori dari database.
- Tampilan admin dengan menu, kategori, promo, dan informasi toko dari database.

Yang belum tersedia:

- Backend pesanan, pembayaran, dan nomor antrean.
- Integrasi Midtrans.
- Login, logout, dan middleware role.
- CRUD admin yang tersimpan ke database.
- Laporan transaksi DB-backed.

## Stack

- Laravel 13
- PHP 8.5
- Blade + Tailwind CSS 4 + Vite 7
- JavaScript modular
- MySQL (`kedai_varya`); test memakai MySQL `kedai_varya_test`
- PHPUnit 12

## Setup Lokal

```bash
composer install
npm.cmd install
php artisan migrate
php artisan db:seed
php artisan serve
npm.cmd run dev
```

Di Windows/PowerShell gunakan `npm.cmd`, bukan `npm`.

## Perintah Pemeriksaan

```bash
php artisan test
php vendor/bin/pint --test
npm.cmd run build
php artisan route:list --except-vendor
```

## Dokumen Acuan

- `PRD.md` — aturan bisnis dan kriteria penerimaan.
- `docs/DECISIONS.md` — keputusan teknis yang mengikat.
- `docs/DATABASE.md` — kontrak skema database.
- `docs/ROUTES.md` — kontrak route dan API.
- `AGENTS.md` — panduan kerja agent.
- `admin.md`, `kasir.md`, `pelanggan-A.md`, `pelanggan-B.md` — alur per role.

## Catatan Penting

- Jangan memakai SQLite untuk aplikasi atau test.
- Jangan menjalankan `migrate:fresh` pada database yang berisi data nyata.
- Jangan memakai `localStorage`/`sessionStorage` untuk data nyata setelah backend tersedia.
- Status pembayaran lunas hanya boleh berasal dari webhook Midtrans, bukan simulasi browser.
- Data menu kanonik ada di `database/seeders/PelangganASeeder.php`.
