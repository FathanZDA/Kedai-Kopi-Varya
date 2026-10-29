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

Per 29 Oktober 2026. Ringkasan serah-terima antar sesi (branch, baseline test, slice berikutnya, catatan lingkungan) ada di **`docs/HANDOFF.md`** — baca itu lebih dulu saat memulai sesi baru.

- **Front-end publik Pelanggan A:** sudah memakai database (`categories`, `menus`, `promos`, `store_settings`, `store_hours`).
- **Front-end Pelanggan B:** menu dan toko sudah dari database. Keranjang dan checkout masih `localStorage`/`sessionStorage`; simulasi pembayaran sudah dihapus, tetapi Midtrans belum aktif sehingga tidak ada pesanan yang ditandai lunas dari browser.
- **Front-end kasir:** menu dan kategori sudah dari database. **Buat Pesanan sudah tersambung penuh**: keranjang dikirim ke `POST /kasir/pesanan`, nomor antrean dan kembalian diambil dari respons server, dan `localStorage` untuk pesanan sudah dihapus. **Pesanan Masuk** membaca order hari ini dari database, menyegarkan daftar lewat `GET /kasir/pesanan/poll` setiap 7 detik tanpa reload, dan mengubah status lewat `PATCH /kasir/pesanan/{order}/status` (`antre → diproses → selesai`). **Kelola Menu** membaca menu dari database dan menyimpan status Tersedia/Habis lewat `PATCH /kasir/menu/{menu}/availability`. Area kasir tidak lagi memakai `localStorage`.
- **Front-end admin:** menu, kategori, promosi, dan informasi toko sudah dari database. Transaksi, laporan, dan aksi CRUD masih data contoh + `localStorage` (`resources/js/admin.js`).
- **Login/role:** login bersama kasir/admin sudah aktif di `/login` dengan auth manual Laravel, password ter-hash, dan redirect sesuai role. Middleware `auth` + `role` melindungi seluruh area kasir/admin. Tampilan mengikuti `design/auth/`; tidak ada registrasi publik.
- **Database:** tabel `users` (dengan `role`), `categories`, `menus` (soft delete), `promos`, `store_settings`, dan `store_hours` sudah ada. Tabel transaksi `orders`, `order_items`, `payments`, `payment_notifications`, dan `queue_counters` sudah dibuat lewat `2026_10_27_000001_buat_tabel_transaksi`. Model `Order`, `OrderItem`, `QueueCounter` dan enum status sudah dipakai `OrderService` untuk order tunai; `payments` dan `payment_notifications` belum dipakai kode.
- **Backend pesanan/pembayaran:** order tunai kasir, perubahan status `antre → diproses → selesai`, dan polling `GET /kasir/pesanan/poll` sudah jalan (`OrderService::buatOrderTunai`, `OrderService::ubahStatus`, `KasirController@pollOrders`). Pembuatan order QRIS, webhook, laporan, dan CRUD admin belum ada. Midtrans belum terpasang.
- **Route:** route publik masih `GET`; auth memakai `GET/POST /login` dan `POST /logout`; kasir punya `POST /kasir/pesanan` (tunai), `GET /kasir/pesanan/poll` (`throttle:120,1`), `PATCH /kasir/pesanan/{order}/status`, dan `PATCH /kasir/menu/{menu}/availability`. Area kasir/admin memakai middleware `auth` + `role`. Belum ada webhook, pembatalan, atau rate limit checkout.

Data contoh di controller dan `localStorage` **bukan** sumber kebenaran. Data kanonik ada di `database/seeders/PelangganASeeder.php`: 5 kategori, 12 menu, 3 promo, dan 7 baris jam toko. Data transaksi demo harus lewat seeder database untuk local/testing, bukan browser storage.

---

## 3. Stack dan versi

- Laravel Framework **13.36.0**
- PHP **8.5.9**
- Blade + Tailwind CSS **4** + Vite **7**
- JavaScript modular biasa (bukan Vue/React)
- Database aplikasi: **MySQL saja**, database `kedai_varya` di `.env`
- Database test: **MySQL** `kedai_varya_test`; tidak memakai SQLite
- File `database/database.sqlite` tidak dipakai dan akan dihapus setelah dipastikan tidak berisi data nyata
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
- Memakai `localStorage`/`sessionStorage` untuk data nyata setelah backend tersedia.
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
- Jangan memakai `localStorage`/`sessionStorage` untuk keranjang, pesanan, menu, atau pembayaran setelah backend tersedia.

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
- `.env` memakai MySQL `kedai_varya`. `database/database.sqlite` tidak dipakai dan akan dihapus setelah dicek.
- Test memakai MySQL `kedai_varya_test`, bukan SQLite.
- Webhook Midtrans didaftarkan di `routes/web.php` dengan pengecualian CSRF; tidak memakai `routes/api.php`.
- Midtrans belum terpasang dan kredensial sandbox belum ada.
- README masih bawaan Laravel dan perlu diganti dengan README project.
