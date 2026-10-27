# Decision Log — Kedai Kopi Varya

**Status:** FINAL untuk keputusan inti
**Terakhir diperbarui:** 27 Oktober 2026
**Acuan utama:** `PRD.md`

Dokumen ini mencatat keputusan teknis dan bisnis yang mengikat pekerjaan backend. Jika ada konflik, `PRD.md` menang. Keputusan di sini hanya boleh menyimpang dari PRD jika PRD sudah diperbarui pada commit yang sama.

## Cara pakai

- `FINAL` — sudah disetujui, wajib diikuti.
- `OPEN` — belum diputuskan. Jangan membuat migration, route, atau perilaku final untuk area ini tanpa konfirmasi.
- `DIBATALKAN` — tidak masuk MVP. Jangan diimplementasikan tanpa keputusan baru.
- `SUPERSEDED` — sudah diganti keputusan lain; jangan dipakai.

Jika keputusan berubah, tambahkan baris baru dan tandai yang lama sebagai `SUPERSEDED`. Jangan menghapus riwayat.

---

## A. Prinsip utama

1. **PRD adalah acuan utama.** UI, route, database, dan kode menyesuaikan PRD, bukan sebaliknya.
2. **Satu sumber data.** Data menu, kategori, promosi, dan toko berasal dari database. Controller tidak boleh menyimpan data contoh sebagai sumber kebenaran.
3. **Tidak ada data nyata di browser.** `localStorage` dan `sessionStorage` tidak dipakai untuk keranjang, pesanan, menu, atau pembayaran setelah backend tersedia.
4. **MySQL saja.** Aplikasi dan test memakai MySQL. SQLite tidak dipakai.
5. **Bahasa Indonesia untuk domain.** Kolom bisnis, enum, JSON, label UI, dan pesan pengguna memakai bahasa Indonesia. Nama tabel mengikuti PRD; kolom teknis framework/Midtrans tetap English.
6. **Uang adalah integer Rupiah**, kecuali `gross_amount` Midtrans.
7. **Waktu memakai `Asia/Jakarta`.** Label UI boleh “WIB”.

---

## B. Keputusan final

| ID | Topik | Keputusan | Sumber |
|---|---|---|---|
| F-01 | Sumber kebenaran | `PRD.md` adalah acuan utama. Konflik diselesaikan dengan mengubah PRD lebih dulu, lalu menyesuaikan dokumen teknis dan kode. | Instruksi user, 25 Okt 2026 |
| F-02 | Zona waktu | Semua waktu memakai `Asia/Jakarta`. `Asia/Surabaya` tidak valid di PHP dan tidak dipakai. | PRD §8.1.7, §8.3.2 |
| F-03 | Nominal | Rupiah bilangan bulat; tidak ada pajak, biaya layanan, atau pembulatan. | PRD §8.1.6 |
| F-04 | Metode pembayaran | Pelanggan B hanya QRIS; pesanan kasir hanya tunai. | PRD §8.1.1–2 |
| F-05 | Integrasi pembayaran | QRIS dinamis Midtrans Sandbox; produksi di luar scope. | PRD §4.2, §11 |
| F-06 | Urutan pembuatan order | Order QRIS disimpan dulu sebagai `menunggu_pembayaran`; nomor antrean dibuat setelah lunas. | PRD §6.2, §8.1.3–4 |
| F-07 | Nomor antrean | Tiga digit, reset pukul 00:00 WIB, dibuat atomik, tidak dipakai ulang. | PRD §8.3 |
| F-08 | Batas pembayaran | 15 menit sejak transaksi Snap dibuat. | PRD §8.4.2 |
| F-09 | Percobaan pembayaran | Satu percobaan aktif per order; pembayaran ulang memakai `midtrans_order_id` baru. | PRD §8.4.3–6 |
| F-10 | Pesanan QRIS | Read-only setelah dibuat; kasir tidak bisa menandai lunas manual. | PRD §7.4 K-02h, §8.7.1 |
| F-11 | Sumber status pembayaran | Webhook Midtrans adalah sumber kebenaran, bukan parameter halaman finish. | PRD §8.1.5, §11.4 |
| F-12 | Pembatalan/pelacakan pelanggan | Pelanggan tidak punya tombol batal atau fitur cek pesanan. | PRD §4.2, §8.4.8–9 |
| F-13 | Kode pesanan | 8 karakter acak tanpa `I`, `O`, `0`, `1`; unik. Tidak memakai prefix `ORD-` atau `TRX-`. | PRD §15.1 |
| F-14 | Laporan | Hanya transaksi `lunas` yang dihitung sebagai pendapatan. | PRD §8.8.1 |
| F-15 | Kategori | Daftar tetap 5 kategori: **Kopi, Teh, Autentik, Makanan, Cemilan**. Tidak ada CRUD kategori. | PRD §7.4 K-01b, §7.5 AD-02e yang diperbarui |
| F-16 | Akun | Akun admin/kasir dibuat lewat seeder; belum ada UI manajemen akun. | PRD §4.2, §15.1 |
| F-17 | Nomor meja | Tidak dipakai pada versi ini. | PRD §4.2, §15.1 |
| F-18 | Pembaruan pesanan | Polling 5–10 detik; bukan websocket. | PRD §4.2, §7.4 K-02d |
| F-19 | Ekspor laporan | PDF/Excel bersifat `Could`, bukan syarat MVP; tunda ke Fase 2. | PRD §7.5 AD-03g |
| F-20 | Halaman awal kasir | Langsung ke Buat Pesanan; tidak ada dashboard kasir. | PRD §7.4 |
| F-21 | Fitur admin | Dashboard, Kelola Menu, Laporan, Promosi, Informasi Toko. | PRD §7.5 |
| F-22 | Framework test | PHPUnit 12.5 yang sudah terpasang; tidak menambah Pest. | `composer.json` |
| F-23 | Database engine | **MySQL saja** untuk aplikasi. `database/database.sqlite` tidak dipakai. | Instruksi user, 25 Okt 2026 |
| F-24 | Database test | Test memakai MySQL dengan database terpisah, `kedai_varya_test`. Tidak memakai SQLite in-memory. | Instruksi user, 25 Okt 2026 |
| F-25 | Nama tabel | Nama tabel mengikuti PRD: `users`, `categories`, `menus`, `orders`, `order_items`, `payments`, `payment_notifications`, `queue_counters`, `promos`, `store_settings`, `store_hours`. | PRD §10.1 |
| F-26 | Nama kolom | Kolom bisnis memakai bahasa Indonesia sesuai PRD, mis. `nama`, `harga`, `kode_pesanan`, `status_pembayaran`, `order_items.catatan`. Kolom teknis framework tetap English: `id`, `*_id` (mis. `orders.cashier_id`), `created_at`, `updated_at`, `deleted_at`, `email`, `password`, `role`, `remember_token`. | Instruksi user, 25 Okt 2026 |
| F-27 | Enum/status | Enum domain memakai nilai Indonesia: `menunggu`, `lunas`, `gagal`, `kedaluwarsa`, `dibatalkan`, `perlu_diperiksa`, `menunggu_pembayaran`, `antre`, `diproses`, `selesai`, `tunai`, `qris`, `qr`, `kasir`. `dine_in`/`take_away` tetap sesuai PRD. | PRD §8.2, §10.1 |
| F-28 | JSON/API | Field domain di JSON memakai bahasa Indonesia: `nama_pelanggan`, `tipe_pesanan`, `kode_pesanan`, `status_pembayaran`, `batas_waktu_pembayaran`, `dapat_bayar_ulang`. Kunci framework `message`/`errors` dan payload mentah Midtrans tetap English. | Instruksi user, 25 Okt 2026 |
| F-29 | Demo storage | `localStorage`/`sessionStorage` tidak dipakai untuk data nyata. Keranjang, pesanan, menu, dan pembayaran harus dari database. | Instruksi user, 25 Okt 2026 |
| F-30 | Data kanonik | Data menu kanonik adalah 12 menu di `database/seeders/PelangganASeeder.php`. `KasirController` dan `AdminController` harus memakai data database yang sama; 24 menu demo admin tidak dipertahankan sebagai sumber terpisah. | Instruksi user, 25 Okt 2026 |
| F-31 | Status `siap_saji` | Dihapus dari MVP. Alur status: `menunggu_pembayaran → antre → diproses → selesai`; `dibatalkan` hanya sebelum bayar. | PRD §8.2 |
| F-32 | Format kode tampil | UI menampilkan kode 8 karakter, mis. `V7K2M9QX`. Tidak ada `ORD-...` atau `TRX-...` sebagai ID kanonik. | PRD §15.1 |
| F-33 | URI route | Ikuti PRD: `/tentang`, `/admin/dashboard`, `/pesanan/{kode_pesanan}`. Sediakan redirect dari `/tentang-kami` dan `/admin`. | PRD §4.3 |
| F-34 | Jam toko | Tabel `store_hours` per hari, sesuai PRD. `store_settings.hours` JSON dihapus dari target. | PRD §10.1, AD-05b |
| F-35 | Promosi | Field mengikuti PRD: `judul`, `deskripsi`, `gambar`, `urutan`, `aktif`, `mulai`, `berakhir`. `subtitle` dan `badge_text` tidak masuk skema; UI menyesuaikan. | PRD §10.1, AD-04 |
| F-36 | Informasi toko | Field mengikuti PRD: `nama_toko`, `tagline`, `sejarah`, `alamat`, `maps_url`, `maps_embed`, `telepon`, `whatsapp`, `instagram`, `fasilitas`. `landmark` dan `hours` JSON tidak masuk skema; UI menyesuaikan. | PRD §10.1, AD-05 |
| F-37 | Menu | Field mengikuti PRD: `nama`, `harga`, `deskripsi`, `foto`, `tersedia`, `deleted_at`. Tambahan teknis: `slug` untuk URL detail dan `urutan` untuk urutan tampil. `is_featured` dihapus; menu terlaris dihitung dari `order_items`. | PRD §10.1, AD-01 |
| F-38 | Catatan per item | **FINAL.** Tambahkan `order_items.catatan` (padanan `order_items.notes`) nullable text. Catatan disimpan per baris item dan tidak mengubah snapshot nama/harga. | Instruksi user, 25 Okt 2026 |
| F-39 | Kasir pembuat order | **FINAL.** Tambahkan `orders.cashier_id` nullable, FK ke `users.id`, `nullOnDelete`. Diisi untuk order tunai; `null` untuk order QRIS. | Instruksi user, 25 Okt 2026 |
| F-40 | Autentikasi | Auth manual Laravel (`Auth`, session, middleware `role`), tanpa Breeze dan tanpa registrasi publik. Hanya role `admin` dan `kasir`. | Keputusan teknis agent; tidak menambah dependency |
| F-41 | Midtrans | Gunakan HTTP client Laravel + service class; tidak menambah SDK `midtrans/midtrans-php`. | Keputusan teknis agent; tidak menambah dependency |
| F-42 | Lokasi webhook | Daftarkan `POST /api/midtrans/notification` di `routes/web.php` dan kecualikan CSRF untuk path tersebut. Tidak menambah `routes/api.php`/Sanctum. | Keputusan teknis agent; tidak menambah dependency |
| F-43 | Data demo order | Order demo untuk local/testing disimpan di database lewat `DemoOrderSeeder`, bukan `localStorage`. Seeder ini tidak dijalankan di produksi. | Instruksi user, 25 Okt 2026 |
| F-44 | SQLite | File `database/database.sqlite` tidak dipakai. Jangan dihapus sebelum dicek isinya; setelah dipastikan tidak berisi data nyata, file boleh dihapus. | Instruksi user, 25 Okt 2026 |
| F-45 | Bahasa UI | Seluruh label, pesan validasi, dan teks antarmuka memakai bahasa Indonesia. Desain yang berbeda menyesuaikan PRD. | Instruksi user, 25 Okt 2026 |
| F-46 | Nama route | Pertahankan prefix yang sudah ada: `pelanggan-a.`, `pelanggan-b.`, `kasir.`, `admin.`. Gunakan pola `area.resource.action`. | `docs/ROUTES.md` |

---

## C. Keputusan yang masih terbuka

| ID | Topik | Status | Rekomendasi | Dampak jika tidak diputuskan |
|---|---|---|---|---|
| O-16 | URL publik dan akun Midtrans | OPEN | Tentukan tunnel (ngrok/cloudflared), pemilik akun sandbox, dan `MIDTRANS_*` keys sebelum uji end-to-end. | Webhook tidak bisa diuji dari perangkat/HP lain. |

Catatan: O-16 bersifat operasional dan tidak menghambat pembuatan dokumen, migration, atau route. Jangan menaruh kredensial di repository.

---

## D. Status keputusan lama

| ID lama | Topik | Status baru | Keputusan |
|---|---|---|---|
| O-01 | Daftar kategori | FINAL | 5 kategori: Kopi, Teh, Autentik, Makanan, Cemilan. |
| O-02 | Nama kolom database | FINAL | Kolom bisnis Indonesia sesuai PRD; tabel mengikuti PRD; kolom teknis framework English. |
| O-03 | `siap_saji` | FINAL | Dihapus dari MVP. |
| O-04 | Format kode | FINAL | 8 karakter acak; tanpa `ORD-`/`TRX-`. |
| O-05 | URI route | FINAL | Ikuti PRD + redirect URI lama. |
| O-06 | Database engine | FINAL | MySQL saja; test MySQL `kedai_varya_test`. |
| O-07 | Autentikasi | FINAL | Auth manual Laravel. |
| O-08 | Integrasi Midtrans | FINAL | HTTP client + service class. |
| O-09 | Demo storage | FINAL | Hapus `localStorage`/`sessionStorage` untuk data nyata. |
| O-10 | Scope dashboard admin | FINAL | Ikuti PRD AD-01; grafik jam sibuk dan `siap_saji` dihapus. |
| O-11 | Jam toko | FINAL | Tabel `store_hours`. |
| O-12 | Catatan per item | FINAL | `order_items.catatan` nullable; UI kasir dapat mengirim catatan per item. |
| O-13 | Kasir pembuat order | FINAL | `orders.cashier_id` nullable; diisi untuk order tunai. |
| O-14 | Periode promosi | FINAL | `mulai` dan `berakhir` sesuai PRD. |
| O-15 | Lokasi webhook | FINAL | `routes/web.php` + pengecualian CSRF. |
| O-17 | Ekspor laporan | FINAL | Tunda ke Fase 2. |
| O-18 | Sumber data seed | FINAL | 12 menu kanonik; demo order lewat seeder database local/testing. |

---

## E. Konflik yang sudah diselesaikan

| Konflik | Keputusan |
|---|---|
| Timezone `Asia/Surabaya` vs `Asia/Jakarta` | `Asia/Jakarta`; `Asia/Surabaya` tidak valid. |
| Kategori 3/4/5 versi | 5 kategori final. |
| Harga menu berbeda antara seeder dan admin | Seeder 12 menu menjadi kanonik; data admin demo diganti. |
| `siap_saji` tidak ada di PRD | Dihapus dari MVP. |
| `ORD-`/`TRX-` vs kode 8 karakter | Kode 8 karakter; prefix dihapus. |
| `/tentang-kami` vs `/tentang` | `/tentang` + redirect. |
| `/admin` vs `/admin/dashboard` | `/admin/dashboard` + redirect. |
| MySQL vs SQLite | MySQL saja; test MySQL. |
| `localStorage` vs database | Database; browser storage dihapus untuk data nyata. |
| Nama kolom English vs PRD Indonesia | Kolom bisnis Indonesia sesuai PRD. |
| `store_settings.hours` JSON vs `store_hours` | Tabel `store_hours`. |
| Promosi tanpa periode vs PRD | `mulai`/`berakhir` ditambahkan. |
| `subtitle`/`badge_text` promosi | Tidak masuk skema; UI menyesuaikan PRD. |
| `is_featured` menu | Dihapus; menu terlaris dihitung dari `order_items`. |
| `landmark`/`hours` toko | Tidak masuk skema; alamat/jam memakai field PRD. |
| Catatan per item | Ditambahkan sebagai `order_items.catatan`. |
| `cashier_id` | Ditambahkan sebagai `orders.cashier_id` nullable. |

---

## F. Riwayat perubahan

| Tanggal | Perubahan | Oleh |
|---|---|---|
| 25 Okt 2026 | Dokumen awal; konflik dan keputusan OPEN dicatat | Agent |
| 25 Okt 2026 | Keputusan inti difinalkan: MySQL, Indonesia, PRD utama, 5 kategori, 12 menu kanonik, tanpa `localStorage`, tanpa `siap_saji` | Agent |
| 25 Okt 2026 | `order_items.catatan` dan `orders.cashier_id` ditambahkan kembali ke MVP atas permintaan user | Agent |
| 27 Okt 2026 | Dokumen dan skema inti difinalkan; front-end diselaraskan dengan PRD dan data kanonik | Agent |
| 27 Okt 2026 | Auth manual diimplementasikan: login bersama `/login`, redirect per role, middleware `role`, logout, password hash, dan rate limit login. Akun seeder memakai email `@gmail.com` yang ditentukan user. | Agent |
| 27 Okt 2026 | Skema transaksi dibuat lewat `2026_10_27_000001_buat_tabel_transaksi`: `queue_counters`, `orders`, `order_items`, `payments`, `payment_notifications`, lengkap dengan FK dan index sesuai `docs/DATABASE.md`. Belum ada model, service, controller, atau route transaksi. | Agent |
| 27 Okt 2026 | Model `Order`, `OrderItem`, `QueueCounter` dan 6 enum status dibuat; status disimpan sebagai string sesuai kolom enum database. | Agent |
| 27 Okt 2026 | Backend order tunai kasir (`POST /kasir/pesanan`) diimplementasikan sesuai `docs/DATABASE.md` §7.1: validasi ulang menu, total dari harga database, nomor antrean via `queue_counters` + `lockForUpdate`, order + item dalam satu `DB::transaction`. Kontrak request memakai `item` (bukan `items`) dan `nomor_antrean` tiga digit mengikuti `docs/ROUTES.md` §4.4. Error `409` "menu berubah" belum aktif karena request tidak membawa harga pembanding. UI kasir belum memanggil endpoint ini. | Agent |
| 27 Okt 2026 | Halaman Buat Pesanan tersambung ke `POST /kasir/pesanan`: `kasir.js` mengirim JSON + header CSRF, memakai `nomor_antrean`/`total`/`kembalian` dari server, menampilkan pesan `422` di dialog pembayaran, menonaktifkan tombol saat menyimpan. Penyimpanan pesanan di `localStorage` dihapus; `nextQueue` dari data contoh dihapus dari layout. Respons memakai kunci `item` agar sama dengan request. | Agent |
