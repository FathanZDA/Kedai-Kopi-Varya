# AGENTS.md — Panduan Kerja Agent

Dokumen ini adalah *operating manual* untuk agent coding di repo **Kedai Kopi Varya**. Baca sebelum mengubah kode.

> **Status:** FINAL untuk keputusan inti. `PRD.md` adalah acuan utama. Keputusan teknis yang mengikat ada di `docs/DECISIONS.md`; jangan menyimpang tanpa memperbarui PRD dan decision log.

---

## 1. Konteks produk

Website untuk UMKM Kedai Kopi Varya dengan empat area:

| Area | Pengguna | Akses | Fokus |
|---|---|---|---|
| Publik | Pelanggan A | Tanpa login | Landing page, menu, tentang, lokasi |
| Pesan QR | Pelanggan B | Tanpa login | Menu, keranjang, checkout, QRIS Midtrans |
| Kasir | Kasir | Login | Buat Pesanan (tunai), Pesanan Masuk, Kelola Menu |
| Admin | Admin/Owner | Login | Dashboard, Kelola Menu, Laporan, Promosi, Informasi Toko |

Sumber aturan bisnis utama adalah `PRD.md`. Detail alur per role ada di `kasir.md`, `admin.md`, `pelanggan-A.md`, dan `pelanggan-B.md`.

---

## 2. Status saat ini

Per 8 November 2026 (slice S8 `perbaikan-s8-regression-dokumen` di working tree; S1–S7 sudah di-commit). Ringkasan serah-terima antar sesi (branch, baseline test, slice berikutnya, catatan lingkungan) ada di **`docs/HANDOFF.md`** — baca itu lebih dulu saat memulai sesi baru.

- **Front-end publik Pelanggan A:** sudah memakai database (`categories`, `menus`, `promos`, `store_settings`, `store_hours`). Beranda menampilkan pratinjau **4 kartu menu** dengan tombol “Lihat Semua Menu” yang memperluas di klien; halaman `/menu` tetap menampilkan seluruh menu (F-72). Filter publik memakai kelompok **Semua/Kopi/Bukan Kopi/Makanan & Camilan** dan tata letak 4 kolom desktop terpusat (F-73); kategori database tiga baris (Kopi, Bukan Kopi, Makanan & Camilan). Navbar Pelanggan A mengikuti pola responsif Pelanggan B: di bawah `lg` memakai flex + tombol menu, mulai `lg` memakai grid tiga kolom dengan nav terpusat. Tautan anchor `#beranda`/`#tentang`/`#menu`/`#lokasi`; route `/tentang`, `/menu`, `/lokasi` tetap ada sebagai deep link, dan pil aktif mengikuti section saat scroll di beranda (F-74).
- **Front-end Pelanggan B:** menu dan toko sudah dari database. Beranda `/pesan` memakai pratinjau 4 kartu yang sama, tetapi seluruh kartu tetap dirender dan dapat dipesan setelah diperluas atau lewat pencarian/filter (F-72/F-73). Navbar Pelanggan B memakai tautan anchor `#beranda`/`#tentang`/`#menu`/`#lokasi` dan pil aktif mengikuti section di `/pesan`; halaman alur pesanan tetap menandai Menu aktif (mengikuti F-74). Keranjang tamu sengaja tetap di `localStorage` (`varya_cart_v1`) sebagai pengecualian resmi F-29 (F-69) karena hanya berisi daftar belanja sementara; tidak ada endpoint keranjang, dan keranjang tidak pernah menentukan harga. **Checkout sudah tersambung ke server**: `POST /pesan/checkout` membuat order QRIS `menunggu_pembayaran` tanpa nomor antrean dan mengembalikan `snap_token`; **popup Snap.js** dibuka di halaman checkout (bukan redirect ke Midtrans) dan keranjang dihapus. Nama pelanggan, tipe pesanan, dan catatan tidak lagi disimpan di `sessionStorage`. **Status lunas dan nomor antrean berasal dari webhook Midtrans** (`POST /api/midtrans/notification`) atau Cek Status server-side kasir. Halaman status `/pesanan/{kode}` menampilkan tiket antrean server-side, polling status 5 detik, dan tombol **Coba Bayar Lagi** lewat `POST /pesanan/{kode}/bayar-lagi`. Checkout mengirim harga acuan per item; harga menu yang berubah sejak masuk keranjang ditolak `409` (PRD B-10), bukan diam-diam ditagih dengan harga baru.
- **Front-end kasir:** menu dan kategori sudah dari database. **Buat Pesanan sudah tersambung penuh**: keranjang dikirim ke `POST /kasir/pesanan`, nomor antrean dan kembalian diambil dari respons server, dan `localStorage` untuk pesanan sudah dihapus. **Pesanan Masuk** membaca order hari ini dari database, menyegarkan daftar lewat `GET /kasir/pesanan/poll` setiap 7 detik tanpa reload, mengubah status lewat `PATCH /kasir/pesanan/{order}/status` (`antre → diproses → selesai`), membatalkan QRIS belum dibayar lewat `POST /kasir/pesanan/{order}/cancel`, dan memeriksa pembayaran lewat `POST /kasir/pesanan/{order}/check-status`; order `dibatalkan` tidak ditampilkan. **Detail Pesanan** dibaca ulang dari database lewat `GET /kasir/pesanan/{order}` (hanya order hari ini). **Kelola Menu** membaca menu dari database dan menyimpan status Tersedia/Habis lewat `PATCH /kasir/menu/{menu}/availability`. Area kasir tidak lagi memakai `localStorage`.
- **Front-end admin:** menu, kategori, promosi, dan informasi toko sudah dari database. Kategori menu memakai tiga baris tetap (Kopi, Bukan Kopi, Makanan & Camilan); admin dan kasir membaca daftar yang sama dari database (F-73). **Kelola Menu sudah tersambung penuh**: tambah/ubah lewat form multipart (`POST /admin/menu`, `PUT/PATCH /admin/menu/{menu}`) dengan validasi server dan unggah foto ke `public/images/menus`, hapus lewat `DELETE /admin/menu/{menu}` (soft delete), dan status Tersedia/Habis lewat `PATCH /admin/menu/{menu}/availability`. `localStorage` menu (`varya.admin.menus.v2`) sudah dihapus dari `admin.js`. **Kelola Promosi juga sudah tersambung penuh**: tambah/ubah lewat `POST /admin/promosi` dan `PUT/PATCH /admin/promosi/{promo}` (multipart, unggah gambar ke `public/images/promos`, validasi di `PromoRequest`), hapus lewat `DELETE /admin/promosi/{promo}` (hard delete), dan status aktif lewat `PATCH /admin/promosi/{promo}/status`. `urutan` promo wajib unik (kecuali dirinya saat diubah); form memakai `max(urutan) + 1` dan kartu membedakan **Posisi NN** dari **Urutan #N**. `localStorage` promosi (`varya.admin.promos.v2`) sudah dihapus. **Informasi Toko juga sudah tersambung**: `PUT /admin/toko` menyimpan profil toko (`store_settings`) dan jam operasional tujuh hari (`store_hours`) lewat `StoreSettingsRequest` + `StoreSettingsService`; `localStorage` informasi toko sudah dihapus dari `admin.js`. **Laporan Transaksi sudah tersambung**: `GET /admin/laporan` membaca `orders`/`order_items`/`payments` lewat `ReportService` dengan filter bulan (ringkasan hanya menghitung transaksi `lunas`), dan `GET /admin/laporan/{order}` mengirim rincian JSON memakai snapshot item. **Dashboard juga sudah tersambung**: ringkasan pendapatan/pesanan periode **Hari Ini** (default), **Minggu Ini** (Senin-Minggu), atau **Bulan Ini** lewat `?periode`; hanya transaksi lunas untuk pendapatan, pesanan menunggu/diproses tetap hari ini, lima menu terlaris tetap bulan berjalan, dan sepuluh transaksi terbaru tidak difilter periode, dihitung lewat `DashboardService`. **Foto Tentang Kami sudah tersambung**: `POST /admin/toko/foto-tentang` dan `DELETE /admin/toko/foto-tentang` menyimpan `store_settings.foto_tentang` dan berkas di `public/images/toko`; halaman `/tentang` memakai foto itu dan kembali ke placeholder bila kolomnya `null`.
- **Login/role:** login bersama kasir/admin sudah aktif di `/login` dengan auth manual Laravel, password ter-hash, dan redirect sesuai role. Middleware `auth` + `role` melindungi seluruh area kasir/admin. Tampilan mengikuti `design/auth/`; tidak ada registrasi publik.
- **Database:** tabel `users` (dengan `role`), `categories`, `menus` (soft delete), `promos`, `store_settings`, dan `store_hours` sudah ada. Tabel transaksi `orders`, `order_items`, `payments`, `payment_notifications`, dan `queue_counters` sudah dibuat lewat `2026_10_27_000001_buat_tabel_transaksi`. Model `Order`, `OrderItem`, `Payment`, `PaymentNotification`, dan `QueueCounter` sudah dipakai service backend.
- **Backend pesanan/pembayaran:** order tunai kasir, perubahan status `antre → diproses → selesai`, polling `GET /kasir/pesanan/poll`, order QRIS + transaksi Snap + **popup Snap.js**, **webhook Midtrans + nomor antrean saat lunas**, **halaman status `/pesanan/{kode}` + polling + pembayaran ulang**, **pembatalan QRIS + Cek Status kasir**, serta **rekonsiliasi terjadwal + penutupan otomatis 24 jam** (`php artisan qris:reconcile` tiap 5 menit) sudah jalan (`OrderService`, `KasirController@pollOrders`, `PelangganBController@storeCheckout`, `PesananController`, `MidtransService`, `MidtransNotificationService`, `QueueNumberService`, `QrisReconciliationService`). Laporan admin sudah ada lewat `ReportService` dan dashboard lewat `DashboardService`; data contoh transaksi di `AdminController` sudah dihapus. CRUD menu admin sudah ada lewat `MenuCatalogService`, CRUD promosi lewat `PromoService`, dan informasi toko lewat `StoreSettingsService`. Midtrans dipanggil lewat HTTP client Laravel, bukan SDK.
- **Route:** route publik masih `GET` kecuali `POST /pesan/checkout` (`throttle:checkout`), `POST /pesanan/{order:kode_pesanan}/bayar-lagi` (`throttle:checkout`), dan `POST /api/midtrans/notification` (publik, tanpa CSRF, verifikasi signature); status pesanan memakai `GET /pesanan/{order:kode_pesanan}` dan `GET .../status` (`throttle:order-status`). Auth memakai `GET/POST /login` dan `POST /logout`; kasir punya `POST /kasir/pesanan` (tunai), `GET /kasir/pesanan/poll` (`throttle:120,1`), `PATCH /kasir/pesanan/{order}/status`, `POST /kasir/pesanan/{order}/cancel` (`throttle:30,1`), `POST /kasir/pesanan/{order}/check-status` (`throttle:30,1`), `GET /kasir/pesanan/{order}`, dan `PATCH /kasir/menu/{menu}/availability`; admin punya `POST /admin/menu`, `PUT/PATCH /admin/menu/{menu}`, `DELETE /admin/menu/{menu}`, `PATCH /admin/menu/{menu}/availability`, `POST /admin/promosi`, `PUT/PATCH /admin/promosi/{promo}`, `DELETE /admin/promosi/{promo}`, `PATCH /admin/promosi/{promo}/status`, `PUT /admin/toko`, serta `GET /admin/laporan/{order}`; dashboard memakai `GET /admin/dashboard?periode=hari|minggu|bulan`. Area kasir/admin memakai middleware `auth` + `role`. Rekonsiliasi otomatis dan penutupan 24 jam berjalan lewat scheduler (`routes/console.php` → `qris:reconcile`), bukan route HTTP.

Data contoh di controller dan `localStorage` **bukan** sumber kebenaran. Data kanonik ada di `database/seeders/PelangganASeeder.php`: 3 kategori, 12 menu, 3 promo, dan 7 baris jam toko. Data transaksi demo harus lewat seeder database untuk local/testing, bukan browser storage.

---

## 3. Stack dan versi

- Laravel Framework **13.36.0**
- PHP **8.5.9**
- Blade + Tailwind CSS **4** + Vite **7**
- JavaScript modular biasa (bukan Vue/React)
- Database aplikasi: **MySQL saja**, database `kedai_varya` di `.env`
- Database test: **MySQL** `kedai_varya_test`; tidak memakai SQLite
- `database/database.sqlite` **sudah dihapus** (4 Nov 2026) setelah isinya diperiksa; aplikasi dan test hanya memakai MySQL
- Test: PHPUnit **12.5**
- Belum ada paket auth tambahan dan belum ada `midtrans/midtrans-php`

---

## 4. Perintah penting

```bash
composer install
npm.cmd install
php artisan migrate:fresh --seed
php artisan serve
npm.cmd run dev
php artisan test
php vendor/bin/pint --test
php artisan route:list --except-vendor
```

Catatan Windows/PowerShell di mesin ini: `npm.ps1` diblokir Execution Policy. Gunakan `npm.cmd`, bukan `npm`.

Jangan menjalankan `migrate:fresh` pada database yang berisi data nyata. Untuk eksperimen, gunakan database lokal/test.

---

## 5. Urutan sumber kebenaran

Jika terjadi konflik, gunakan urutan ini:

1. `PRD.md` — acuan utama aturan bisnis dan kriteria penerimaan
2. `docs/DECISIONS.md` — keputusan teknis berstatus `FINAL`; tidak boleh menyimpang dari PRD
3. `kasir.md`, `admin.md`, `pelanggan-A.md`, `pelanggan-B.md` — detail alur per role, selama tidak bertentangan dengan PRD
4. `docs/DATABASE.md` dan `docs/ROUTES.md` — kontrak teknis
5. Folder `design/` — visual dan layout; UI menyesuaikan PRD
6. Kode yang sudah ada — pola implementasi, bukan sumber data

Jika dua sumber bertentangan dan belum ada keputusan `FINAL`, **berhenti dan tanya**. Jangan memilih diam-diam.

---

## 6. Aturan kerja (guardrails)

### Jangan

- Mengubah aturan bisnis, status, atau alur tanpa memperbarui `PRD.md` dan `docs/DECISIONS.md`.
- Memakai `localStorage`/`sessionStorage` untuk data nyata setelah backend tersedia. Satu pengecualian: keranjang tamu Pelanggan B (F-69), yang hanya boleh berisi daftar belanja sementara.
- Memakai SQLite untuk aplikasi atau test.
- Memakai data menu dari array controller sebagai sumber kebenaran.
- Membuat simulasi pembayaran yang menandai pesanan lunas.
- Menandai pembayaran QRIS lunas secara manual dari sisi kasir.
- Membuat nomor antrean sebelum pembayaran lunas (khusus QRIS).
- Mengubah snapshot `order_items` ketika menu diedit.
- Memakai status `siap_saji` atau prefix kode `ORD-`/`TRX-`.
- Menambah dependency baru tanpa persetujuan.
- Menghapus atau mengganti file di `design/`.
- Menulis ulang UI yang sudah jadi tanpa alasan yang jelas.
- Menaruh `server_key`, password, atau token di kode frontend.
- Mengubah skema database lewat GUI tanpa migration.

### Wajib

- Validasi input di server, bukan hanya di JavaScript.
- Gunakan named route, jangan hardcode URL di Blade.
- Gunakan transaksi database untuk pembuatan order, pembayaran, dan nomor antrean.
- Gunakan `Asia/Jakarta` untuk waktu.
- Simpan nominal Rupiah sebagai integer (kecuali `gross_amount` Midtrans).
- Pertahankan snapshot nama dan harga menu pada `order_items`.
- Idempoten untuk webhook dan pembuatan nomor antrean.
- Lindungi area kasir/admin dengan `auth` + role middleware.
- Webhook Midtrans wajib verifikasi `signature_key`, `order_id`, dan nominal.
- Ambil menu, kategori, promosi, dan toko dari database; jangan dari array controller.
- Gunakan nama kolom bisnis bahasa Indonesia sesuai `docs/DATABASE.md`.
- Gunakan data menu kanonik dari `PelangganASeeder` sampai admin mengubahnya lewat CRUD.

---

## 7. Konvensi kode

### PHP/Laravel

- Controller tipis; logika bisnis di Service/Action.
- Validasi di Form Request.
- Otorisasi di Policy/Middleware.
- Status pakai PHP Enum, bukan string tersebar.
- Response JSON pakai API Resource.
- Gunakan `DB::transaction()` dan `lockForUpdate()` untuk order/payment/queue.
- Ikuti PSR-12 dan jalankan Pint.

### Blade/Tailwind

- Gunakan komponen Blade yang sudah ada di `resources/views/components/`.
- Jangan menaruh query database atau aturan bisnis di Blade.
- Pertahankan gaya visual yang sudah ada; jangan mengganti design token sepihak.
- Pastikan tampilan mobile-first dan state `disabled`, `loading`, `empty`, `error` jelas.

### JavaScript

- Satu modul per area: `pelanggan-a.js`, `pelanggan-b.js`, `kasir.js`, `admin.js`.
- Setelah backend siap, hapus penyimpanan demo dan ambil data dari endpoint.
- Jangan menyimpan data sensitif di browser.
- Jangan memakai `localStorage`/`sessionStorage` untuk pesanan, menu, atau pembayaran setelah backend tersedia. Keranjang tamu Pelanggan B adalah pengecualian resmi (F-69): hanya `menu_id`/nama/harga tampilan/jumlah, dan checkout tetap menghitung ulang semuanya di server.

### Database

- Nama tabel mengikuti PRD. Kolom bisnis memakai bahasa Indonesia; kolom teknis framework/Midtrans tetap English.
- Semua foreign key diberi constraint dan index.
- Gunakan soft delete hanya untuk `menus`.
- Jangan mengubah migration yang sudah dijalankan; buat migration baru.
- MySQL saja; jangan menambahkan konfigurasi SQLite.
- Gunakan `order_items.catatan` untuk catatan per item (padanan `notes`) dan `orders.cashier_id` untuk kasir pembuat order tunai; jangan memakai nama `notes`.

### Git

- Commit kecil, satu tujuan.
- Jangan commit `vendor/`, `node_modules/`, `.env`, atau file build.
- Sertakan update dokumen dalam commit yang sama dengan perubahan perilaku.

---

## 8. Alur kerja per tugas

1. Baca task, `docs/DECISIONS.md`, dan bagian PRD/flow yang relevan.
2. Tulis rencana singkat: tujuan, file yang akan diubah, risiko, dan cara test.
3. Implementasikan slice terkecil yang bisa diterima.
4. Tambahkan test untuk jalur kritis.
5. Jalankan `php artisan test`, `php vendor/bin/pint --test`, dan `npm.cmd run build` bila frontend berubah.
6. Perbarui dokumen/status bila ada keputusan atau perilaku baru.
7. Laporkan: apa yang berubah, apa yang diuji, apa yang belum, dan risiko.

Jangan mengerjakan seluruh backend dalam satu task. Pecah per vertical slice.

---

## 9. Definition of Done

Sebuah task dianggap selesai jika:

- Semua acceptance criteria terpenuhi.
- Test baru/terkait lulus.
- Tidak ada kode debug, `dd()`, `dump()`, atau data contoh yang tertinggal tanpa penanda.
- Tidak ada secret di kode.
- Tampilan responsif di mobile dan desktop (jika UI berubah).
- `npm.cmd run build` berhasil (jika frontend berubah).
- `php vendor/bin/pint --test` bersih (jika PHP berubah).
- Dokumen terkait diperbarui.
- Tidak ada perubahan di luar scope task.

---

## 10. Berhenti dan tanya

Berhenti sebelum melanjutkan jika:

- Perlu mengubah skema, nama kolom, enum, atau route yang sudah disepakati.
- Perlu menambah package atau layanan eksternal.
- Perlu menghapus file atau mengganti UI yang sudah ada.
- Ada konflik antara PRD, flow, desain, dan kode.
- Ada keputusan berstatus `OPEN` di `docs/DECISIONS.md` yang memengaruhi task.
- Menyentuh autentikasi, pembayaran, atau nomor antrean tanpa test.
- Tidak yakin apakah data yang ada adalah data nyata atau data demo.

---

## 11. Catatan lingkungan

- OS: Windows, XAMPP.
- `npm.ps1` diblokir; gunakan `npm.cmd`.
- `.env` memakai MySQL `kedai_varya`. `database/database.sqlite` sudah dihapus setelah dicek; jangan menambahkan konfigurasi SQLite baru.
- Test memakai MySQL `kedai_varya_test`, bukan SQLite.
- Webhook Midtrans didaftarkan di `routes/web.php` dengan pengecualian CSRF; tidak memakai `routes/api.php`. Panduan sandbox + ngrok ada di `docs/MIDTRANS-SETUP.md`.
- Integrasi Midtrans memakai HTTP client Laravel (bukan SDK). Kredensial sandbox hanya ada di `.env`; tanpa `MIDTRANS_SERVER_KEY`, checkout menjawab `503` dan webhook `500`.
- `README.md` adalah README proyek (setup, seed, test, catatan Midtrans). `.env.example` memuat placeholder `MIDTRANS_*` tanpa kredensial.
