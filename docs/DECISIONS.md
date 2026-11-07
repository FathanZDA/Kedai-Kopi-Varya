# Decision Log — Kedai Kopi Varya

**Status:** FINAL untuk keputusan inti
**Terakhir diperbarui:** 3 November 2026
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
3. **Tidak ada data nyata di browser.** `localStorage` dan `sessionStorage` tidak dipakai untuk pesanan, menu, atau pembayaran setelah backend tersedia. Satu pengecualian resmi: keranjang tamu Pelanggan B (F-69) — daftar belanja sementara yang tidak pernah menentukan harga atau tagihan.
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
| F-29 | Demo storage | `localStorage`/`sessionStorage` tidak dipakai untuk data nyata. Pesanan, menu, dan pembayaran harus dari database. **Keranjang tamu Pelanggan B adalah pengecualian resmi** (lihat F-69): isinya hanya daftar belanja sementara, sedangkan harga, ketersediaan, dan total selalu dihitung ulang di server saat checkout. | Instruksi user, 25 Okt 2026; diperjelas 4 Nov 2026 (F-69) |
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
| F-47 | Cakupan Pesanan Masuk | Daftar memuat order yang dibuat pada hari berjalan (`created_at` hari ini, `Asia/Jakarta`), bukan memfilter `tanggal_antrean`, agar order QRIS yang belum dibayar tetap tampil di tab Belum Dibayar. | PRD §7.4 K-02, `docs/DATABASE.md` §7.1 |
| F-48 | Transisi status kasir | Hanya `antre → diproses → selesai`. Transisi ditegakkan di `OrderService::ubahStatus` dalam `DB::transaction` + `lockForUpdate`; melompati tahap, mengubah order `selesai`, atau mengubah `menunggu_pembayaran` ditolak `422`. Pesanan QRIS tidak bisa ditandai lunas atau diproses sebelum lunas. | PRD §8.2, §8.7.1 |
| F-49 | Pembatalan & cek status | `POST /kasir/pesanan/{order}/cancel` hanya untuk QRIS belum dibayar; percobaan `pending` dibatalkan lewat API Midtrans dengan fallback status, percobaan terminal lokal dibatalkan tanpa HTTP, dan `settlement` yang ditemukan membuat pembatalan ditolak `409` setelah status pembayaran diterapkan. Sukses menetapkan `dibatalkan` tanpa nomor antrean. `POST /kasir/pesanan/{order}/check-status` memanggil API status Midtrans dan memakai jalur pemrosesan yang sama (`MidtransNotificationService::terapkanStatus`) tanpa menulis audit webhook; settlement idempoten, settlement setelah pembatalan lokal menjadi `perlu_diperiksa`. Keduanya `throttle:30,1`; order `dibatalkan` tidak ditampilkan di polling. | PRD §7.4 K-02e/K-02g, §8.4.9-10, §7.6 PG-07 |
| F-50 | Ketersediaan menu oleh kasir | Kasir hanya boleh mengubah `menus.tersedia` lewat `PATCH /kasir/menu/{menu}/availability`. Nama, harga, kategori, deskripsi, dan foto tetap milik admin. Status tersedia/habis tidak lagi disimpan di `localStorage`; halaman Buat Pesanan membaca kolom database. | PRD §7.4 K-03, `admin.md` §8 |
| F-51 | Polling Pesanan Masuk | `GET /kasir/pesanan/poll` mengirim snapshot pesanan **hari ini**; dengan `sejak` hanya pesanan yang `updated_at >= sejak`. Klien memakai `waktu_server` (diambil sebelum query) sebagai `sejak` berikutnya, menggabungkan hasil ke daftar tanpa memuat ulang halaman, dan berhenti polling saat tab tidak aktif. Interval 7 detik, `throttle:120,1`. | PRD §4.2, §7.4 K-02d, `docs/ROUTES.md` §4.5 |
| F-52 | Checkout QRIS | `POST /pesan/checkout` menyimpan order + item lebih dulu dalam `DB::transaction` sebagai `menunggu_pembayaran` **tanpa nomor antrean**, lalu mencatat satu baris `payments` `{kode_pesanan}-P{n}` dan memanggil Snap lewat `MidtransService` (HTTP client, bukan SDK). Baris percobaan dicatat sebelum HTTP call agar nomor `P{n}` tidak dipakai ulang; percobaan gagal disimpan sebagai `status_transaksi = failure` dan order tetap `menunggu`. Bila `MIDTRANS_SERVER_KEY`/`MIDTRANS_CLIENT_KEY` belum diatur, endpoint menjawab `503` sebelum order dibuat. `callbacks.finish` dikirim ke halaman status. | PRD §8.1.3, §8.4, §11.2, `docs/ROUTES.md` §4.1 |
| F-53 | Rate limit checkout | `POST /pesan/checkout` dan `POST /pesanan/{kode}/bayar-lagi` memakai limiter `checkout` 10 permintaan/menit per IP, didefinisikan di `AppServiceProvider`. Polling status memakai limiter terpisah `order-status` 60/menit. Tamu tetap bisa checkout tanpa login. | `docs/ROUTES.md` §3, PRD §9 |
| F-54 | Webhook Midtrans | `POST /api/midtrans/notification` didaftarkan di `routes/web.php` tanpa auth, dikecualikan dari CSRF, dan tanpa throttle ketat. Signature diverifikasi dengan `hash_equals(sha512(order_id + status_code + gross_amount + server_key))` memakai `gross_amount` mentah dari payload. Payload tidak lengkap → `200` + log; `server_key` kosong → `500` + log; signature salah → `403` + log **tanpa** menyimpan payload (mencegah banjir request palsu); order tidak dikenal → `200` dan dicatat `gagal`; nominal tidak cocok → `200`, order `perlu_diperiksa`, tanpa nomor antrean, dicatat `diabaikan`. | PRD §11.4, §7.6 PG-11/13, `docs/ROUTES.md` §4.7 |
| F-55 | Idempotensi & nomor antrean QRIS | Pemrosesan berjalan dalam `DB::transaction` + `lockForUpdate` pada `payments` dan `orders`. `settlement`/`capture+accept` menandai `lunas`, `waktu_lunas`, `status_pesanan = antre`, dan mengambil nomor antrean lewat `QueueNumberService` hanya bila `nomor_antrean` masih `null`. Notifikasi ganda dari percobaan yang sama bersifat idempoten. Settlement dari percobaan **berbeda** pada order yang sudah lunas → `perlu_diperiksa` (pembayaran kedua). Settlement setelah pembatalan lokal → `perlu_diperiksa` tanpa nomor antrean. `refund`/`chargeback` tidak pernah menandai lunas. | PRD §8.4.7/10/11, §11.3, §11.5 |
| F-56 | Tunnel pengujian | Pengujian webhook lokal memakai **ngrok** (keputusan user, 30 Okt 2026). URL publik hanya ditulis di dashboard Midtrans dan `.env` lokal; tidak di-commit. | Instruksi user, `docs/MIDTRANS-SETUP.md` |
| F-57 | Popup Snap & halaman status | Pembayaran QRIS memakai **popup Snap.js** di halaman checkout (`snap.pay` dengan `MIDTRANS_CLIENT_KEY` publik), bukan redirect ke `snap_redirect_url`; pelanggan tetap di website ini. Setelah popup selesai/ditutup, browser diarahkan ke `GET /pesanan/{kode_pesanan}` yang dirender server dari database. Halaman status menampilkan rincian, status pembayaran, dan tiket antrean; polling `GET .../status` (5 detik, `throttle:order-status` 60/menit) memuat ulang saat status berubah. `callbacks.finish` tetap dikirim sebagai fallback. Status lunas hanya dari webhook; parameter `finish` tidak pernah menandai lunas. | PRD §6.2, §8.1.5, §11.6, `docs/ROUTES.md` §4.1–4.2 |
| F-58 | Pembayaran ulang | Satu percobaan aktif per order. `POST /pesanan/{kode}/bayar-lagi` mengembalikan token percobaan `pending` yang belum 15 menit tanpa HTTP baru; setelah 15 menit, percobaan pending ditandai `kedaluwarsa` lokal. Percobaan `-P{n+1}` hanya dibuat bila percobaan terakhir `failure`/`deny`/`cancel`/`expire`/`kedaluwarsa`; order `lunas`, `dibatalkan`, `perlu_diperiksa`, atau percobaan pending yang masih berjalan ditolak `409`. Order tetap `menunggu_pembayaran` tanpa nomor antrean sampai webhook menyatakan lunas. | PRD §8.4.3–8.4.6, `docs/ROUTES.md` §4.3 |
| F-59 | Rekonsiliasi terjadwal & penutupan otomatis | **FINAL.** `php artisan qris:reconcile` dijadwalkan setiap 5 menit memakai `Schedule::command(...)->withoutOverlapping(10)` di `routes/console.php`; command di `app/Console/Commands` didaftarkan lewat `->withCommands()` di `bootstrap/app.php`. Setiap putaran: (1) memeriksa pembayaran `pending` pada order QRIS `menunggu_pembayaran` yang berumur > 2 menit ke `GET /v2/{midtrans_order_id}/status`, lalu menerapkan hasilnya lewat `MidtransNotificationService::terapkanStatus` tanpa menulis `payment_notifications`; (2) menutup order QRIS `menunggu_pembayaran` berumur > 24 jam dengan `status_pembayaran` `menunggu`/`gagal`/`kedaluwarsa` menjadi `dibatalkan`. Penutupan berjalan di `DB::transaction` + `lockForUpdate`, tidak pernah membuat nomor antrean, dan tidak pernah menutup order yang punya bukti `settlement`/`capture` lokal atau percobaan `pending` < 15 menit. Kegagalan Midtrans hanya dihitung `gagal` + log dan tidak mengubah order. | PRD §11.7, §8.7.6, §7.6 PG-08, `docs/ROUTES.md` §4.11 |
| F-60 | Ketahanan panggilan API Midtrans | **FINAL.** Panggilan `GET /status` dan `POST /cancel` memakai `retry(2, 250ms)` untuk `ConnectionException` dan respons 5xx, dengan `throw: false` agar kegagalan akhir tetap dipetakan menjadi `MidtransException`/`502` oleh service. Respons `status_code = 407` dengan `transaction_status = expire` diperlakukan sebagai hasil pemeriksaan yang sah (transaksi sudah kedaluwarsa), bukan kegagalan API. | PRD §11.7, `docs/ROUTES.md` §4.9–4.11 |
| F-61 | Pembatalan saat transaksi Midtrans belum ada | **FINAL.** Snap baru mendaftarkan transaksi di Core API setelah pelanggan membuka halaman pembayaran, sehingga percobaan `pending` yang belum dibuka menjawab `404` pada `POST /v2/{order_id}/cancel` **dan** `GET /v2/{order_id}/status`. Bila kedua panggilan itu `404`, backend membatalkan **sesi Snap** lewat `POST /snap/v1/transactions/{snap_token}/cancel`. Sesi yang tertutup atau sudah pernah dibatalkan (`token already canceled`) membuat token tidak dapat dipakai membayar, sehingga pembatalan lokal dijalankan (`status_pesanan = dibatalkan`, `status_pembayaran = dibatalkan`, tanpa nomor antrean). Bila sesi masih diproses pembayaran (`Transaction is on progress`), token tidak tercatat, atau Midtrans tidak dapat dihubungi, order tetap `menunggu_pembayaran`. Aturan PRD §8.4.9 tidak berubah: transaksi yang masih `pending`/aktif di Midtrans tetap tidak boleh dibatalkan lokal. | PRD §8.4.9, §7.6 PG-07, `docs/ROUTES.md` §4.9 |
| F-62 | CRUD menu admin & unggah foto | **FINAL.** Admin menambah/mengubah menu lewat `POST /admin/menu` dan `PUT/PATCH /admin/menu/{menu}` (multipart, validasi di `App\Http\Requests\Admin\MenuRequest`) dan menghapusnya lewat `DELETE` (soft delete). Foto disimpan di `public/images/menus` dengan nama `unggahan-{acak}.{ext}`; hanya berkas berprefiks itu yang dihapus saat diganti, sehingga foto kanonik seeder tidak tersentuh. `slug` dibuat sekali dari `nama` (`Str::slug` + akhiran angka, dicek juga terhadap baris soft-delete) dan tidak diubah saat menu diubah agar URL detail stabil. `urutan` menu baru = `max(urutan)` + 1. Perubahan cepat ketersediaan memakai `PATCH /admin/menu/{menu}/availability` (JSON, hanya `tersedia`); setara pembatasan kasir di F-50. Snapshot `order_items` tidak pernah diubah. | PRD AD-02, §8.6, `docs/ROUTES.md` §4.12 |
| F-63 | CRUD promosi admin & unggah gambar | **FINAL.** Admin menambah/mengubah promosi lewat `POST /admin/promosi` dan `PUT/PATCH /admin/promosi/{promo}` (multipart, validasi `App\Http\Requests\Admin\PromoRequest`: judul ≤ 80, deskripsi ≤ 220, urutan 1–999, periode opsional dengan `berakhir` ≥ `mulai`, gambar ≤ 3 MB), menghapus lewat `DELETE` (hard delete karena `promos` tanpa soft delete), dan mengubah status tampil lewat `PATCH /admin/promosi/{promo}/status`. Gambar disimpan di `public/images/promos` dengan prefiks `unggahan-promo-`; hanya berkas berprefiks itu yang dihapus saat gambar diganti atau promosi dihapus, sehingga gambar kanonik seeder tidak tersentuh. Landing page tetap memakai `Promo::active()->ordered()` sehingga promosi nonaktif atau di luar periode tidak tampil. | PRD AD-04, §8.9, `docs/ROUTES.md` §4.13 |
| F-64 | Informasi toko admin | **FINAL.** `PUT /admin/toko` menyimpan profil `store_settings` (satu baris, id = 1, dibuat bila belum ada) dan tujuh baris `store_hours` (hari 0 = Minggu … 6 = Sabtu) sekaligus dalam `DB::transaction` + `lockForUpdate`, lewat `App\Http\Requests\Admin\StoreSettingsRequest` + `App\Services\StoreSettingsService`. Hari yang ditandai `libur` menyimpan `jam_buka`/`jam_tutup` `null` dan `tutup = true`; hari lain wajib punya kedua jam. `fasilitas` adalah teks dipisah koma (maks. 20 item, tiap item ≤ 50 karakter) yang disimpan sebagai array JSON; `instagram` dinormalisasi menjadi `@handle`; field opsional kosong disimpan `null`, kecuali `tagline` yang disimpan `''` karena kolomnya NOT NULL. Halaman publik membaca data yang sama sehingga perubahan langsung tampil. Foto Tentang Kami **tidak** termasuk form ini agar `PUT /admin/toko` tetap JSON; unggahannya punya endpoint sendiri (F-70). `localStorage` informasi toko dihapus dari `admin.js`. | PRD AD-05, §8.9; F-34/F-36; `docs/ROUTES.md` §4.14 |
| F-65 | Laporan transaksi admin | **FINAL.** `GET /admin/laporan` dirender server dari `orders`/`order_items`/`payments` lewat `App\Services\ReportService`, dengan filter `?bulan=YYYY-MM` (fallback bulan berjalan `Asia/Jakarta`) dan rentang `created_at` bulan tersebut. Ringkasan pendapatan hanya menjumlahkan `status_pembayaran = lunas` (PRD §8.8.1); transaksi berstatus lain tetap ditampilkan di tabel sebagai informasi. Pilihan bulan dibuat dari bulan terlama pada `orders` sampai bulan berjalan, dibatasi 36 bulan, terbaru lebih dulu. Rincian per transaksi memakai `GET /admin/laporan/{order}` (JSON, `admin.reports.show`) dari snapshot `order_items` beserta `uang_diterima`/`kembalian` dan `midtrans_order_id`/`transaction_id`; `{order}` adalah id numerik dan order tidak dikenal menghasilkan `404`. Tidak ada route untuk mengubah status pembayaran dari laporan, dan tidak ada kolom/migration baru. Perhitungan ringkasan client-side dan data contoh laporan di `admin.js` dihapus. | PRD AD-03, §8.8; F-14/F-19; `docs/ROUTES.md` §4.15 |
| F-66 | Dashboard admin | **FINAL.** `GET /admin/dashboard` dirender server dari `orders`/`order_items`/`payments` lewat `App\Services\DashboardService`, tanpa query string. Pendapatan hari ini dan bulan berjalan hanya menjumlahkan `status_pembayaran = lunas` (PRD §8.8.1). Jumlah transaksi lunas memakai bulan berjalan; jumlah pesanan hari ini, pesanan `menunggu` dan pesanan `diproses` memakai `created_at` hari berjalan `Asia/Jakarta` (selaras F-47). Perbandingan jumlah pesanan memakai hari kemarin. Lima menu terlaris dihitung dari `SUM(order_items.jumlah)` transaksi lunas bulan berjalan per snapshot `nama_menu`; harga/foto diambil dari baris `menus` termasuk soft-delete. Sepuluh transaksi terbaru menampilkan semua status sebagai informasi. Seluruh data contoh dashboard di `AdminController` (`recentOrders()`, `popularMenus()`, `transactions()`, `makeTransaction()`, `summarizeMonth()`, `kodePesanan()`, `menuKanonik()`) dihapus; tidak ada kolom, migration, atau route baru. | PRD AD-01; `admin.md` §A; `docs/ROUTES.md` §4.16 |
| F-67 | Detail pesanan kasir | **FINAL.** `GET /kasir/pesanan/{order}` (`kasir.pesanan.show`) mengembalikan JSON detail satu pesanan dengan bentuk data yang sama seperti item polling. Hanya order yang `created_at` hari berjalan `Asia/Jakarta` yang dapat dibuka; order hari lain, order `dibatalkan`, dan id tidak dikenal menghasilkan `404` (selaras F-47 dan PRD K-02b). Data dibaca langsung dari database: item + `catatan` per item, `orders.catatan`, `uang_diterima`/`kembalian`, dan `payments.midtrans_order_id` terakhir. Tidak ada perubahan data, dependency baru, atau route tulis; UI membuka dialog dengan snapshot lalu memperbarui dari respons, dan `404` menutup dialog + menyegarkan daftar lewat polling. | PRD K-02c; `docs/ROUTES.md` §4.17 |
| F-68 | Dokumentasi proyek dan `database/database.sqlite` | **FINAL.** `README.md` diganti menjadi README proyek (fitur, stack, setup, seed, database test, Midtrans + ngrok, scheduler, perintah pemeriksaan, struktur, dokumen acuan) dan tidak memuat kredensial. `.env.example` memuat placeholder `MIDTRANS_*` kosong. `database/database.sqlite` tidak dipakai aplikasi maupun test; isinya sudah diperiksa pada 3 Nov 2026 dan hanya berisi data seed lama (1 `users`, 12 `menus`, 5 `categories`, 3 `promos`, 1 `store_settings`, 7 baris `migrations`) tanpa tabel transaksi, sehingga aman dihapus. **Berkas dihapus pada 4 Nov 2026 setelah user menyetujui** (AGENTS §10); aplikasi dan test hanya memakai MySQL, jadi tidak ada yang bergantung pada berkas itu. | AGENTS §11, F-44; `README.md` |
| F-69 | Keranjang Pelanggan B | **FINAL.** Keranjang tamu Pelanggan B tetap disimpan di `localStorage` (`varya_cart_v1`) dan **tidak** dipindahkan ke endpoint server. Alasannya: (1) checkout selalu mengirim `menu_id`, `jumlah`, dan harga acuan, lalu server menghitung sendiri harga, subtotal, total, dan nomor antrean, sehingga isi browser tidak dapat memalsukan tagihan; (2) PRD B-04 hanya menuntut keranjang bertahan saat refresh, dan `localStorage` memenuhinya tanpa login; (3) keranjang server tetap tidak bertahan lintas perangkat karena Pelanggan B tidak punya akun. Keranjang **tidak** dipakai untuk data pesanan, pembayaran, atau ketersediaan menu. Bila kelak Pelanggan B punya akun, keputusan ini ditinjau ulang. | PRD B-04; F-29; `docs/ROUTES.md` §1 |
| F-70 | Foto Tentang Kami | **FINAL.** Kolom baru `store_settings.foto_tentang` (varchar 255, nullable) ditambahkan lewat migration baru. Unggahan memakai endpoint terpisah `POST /admin/toko/foto-tentang` (multipart, validasi `App\Http\Requests\Admin\AboutPhotoRequest`: JPG/PNG/WEBP, maks. 3 MB) dan `DELETE /admin/toko/foto-tentang`, sehingga `PUT /admin/toko` tetap JSON (F-64). Berkas disimpan di `public/images/toko` dengan prefiks `unggahan-`; hanya berkas berprefiks itu yang dihapus saat foto diganti/dihapus, sehingga aset kanonik tidak tersentuh. `null` berarti halaman `/tentang` memakai placeholder bermotif yang sudah ada, dan panel admin berstatus “Tidak Tampil”. Satu foto dipakai untuk bingkai desktop (3:2) dan mobile (16:9) dengan pemotongan CSS. | PRD AD-05; F-34/F-36/F-64; `docs/ROUTES.md` §4.14 |
| F-71 | Konflik harga saat checkout | **FINAL.** `POST /pesan/checkout` mewajibkan `item.*.harga_satuan` sebagai **harga acuan** yang dilihat pelanggan di keranjang. Server membandingkannya dengan `menus.harga`: bila berbeda, checkout ditolak `409` dengan `data.item` berisi `menu_id`, `nama`, `harga_lama`, `harga_baru`, dan tidak ada order/payment yang dibuat. Menu habis atau sudah dihapus tetap `422` pada field `item`. Harga yang ditagih selalu `menus.harga`, bukan nilai kiriman browser. Alur kasir tunai tidak berubah: `siapkanItem` hanya memeriksa harga ketika `periksaHarga` aktif, karena kasir melihat total final di dialog sebelum menyimpan. | PRD B-10, §8.2 butir 8; `docs/ROUTES.md` §4.1 |
| F-72 | Pratinjau katalog beranda | **FINAL.** Beranda `/` (Pelanggan A) dan `/pesan` (Pelanggan B) tetap merender **seluruh** kartu menu dari server, lalu `previewLimit=4` membuat `resources/js/pelanggan-a.js` menyembunyikan kartu ke-5 dan seterusnya selama belum diperluas dan tidak ada pencarian/filter aktif. Tombol `data-menu-more` adalah `<button>` toggle (`aria-expanded`, label “Lihat Semua Menu” ↔ “Tampilkan Lebih Sedikit”), bukan tautan ke `/menu`; tombol disembunyikan bila jumlah menu ≤ 4 atau pencarian/filter aktif. Pencarian dan filter kategori selalu menampilkan semua hasil yang cocok, sehingga item ke-5+ tetap dapat ditambahkan ke keranjang Pelanggan B. Halaman `/menu` tetap penuh (`previewLimit=0`, tanpa tombol). Tanpa JavaScript semua kartu tampil (progressive enhancement), dan data menu tidak pernah dipotong di server. | `docs/PLAN-PERBAIKAN-TEST-FITUR.md` S3; `docs/ROUTES.md` §2.2 |
| F-73 | Kelompok filter & tata letak menu publik | **FINAL.** Filter kategori publik di `/`, `/pesan`, dan `/menu` memakai tiga kelompok tampilan: **Kopi** (`kopi`), **Bukan Kopi** (`teh` + `autentik`), dan **Makanan & Camilan** (`makanan` + `cemilan`), ditambah **Semua**. Pemetaan ada di `App\Support\MenuGroup`; kategori database tetap lima baris (PRD AD-02e) agar admin/kasir masih bisa membedakan teh, autentik, makanan, dan cemilan — tidak ada migration atau perubahan data. Tata letak menu memakai **4 kolom di desktop** dan 2 kolom di mobile, dibatasi `max-w-6xl`, dipusatkan, dan memakai flex-wrap sehingga baris terakhir ikut di tengah; 4 kartu pratinjau dan seluruh kartu setelah “Lihat Semua Menu” memakai ukuran serta tata letak yang sama. Filter tetap di tengah. | PRD AD-02e; F-72; `docs/ROUTES.md` §2.2–2.3 |

---

## C. Keputusan yang masih terbuka

| ID | Topik | Status | Rekomendasi | Dampak jika tidak diputuskan |
|---|---|---|---|---|
| O-16 | URL publik dan akun Midtrans | OPEN | Tunnel: **ngrok**. Isi `MIDTRANS_*` sandbox di `.env` dan daftarkan `https://<domain-ngrok>/api/midtrans/notification` di dashboard Midtrans sebelum uji end-to-end. | Webhook tidak bisa diuji dari perangkat/HP lain. |

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
| 28 Okt 2026 | Halaman Pesanan Masuk membaca order hari ini dari database dan `PATCH /kasir/pesanan/{order}/status` mengubah status `antre → diproses → selesai` lewat `OrderService::ubahStatus`. Tombol "Cek Status" demo dihapus; pembatalan belum tersambung ke server karena belum ada order belum-bayar. | Agent |
| 28 Okt 2026 | Kelola Menu tersambung ke `PATCH /kasir/menu/{menu}/availability`: kasir hanya mengubah `menus.tersedia`. Override ketersediaan di `localStorage` (`readStorage`/`writeStorage`) dihapus, sehingga area kasir tidak lagi memakai browser storage. | Agent |
| 29 Okt 2026 | Polling Pesanan Masuk (`GET /kasir/pesanan/poll`) ditambahkan: snapshot hari ini, filter `sejak` berbasis `updated_at`, `waktu_server` sebagai penanda berikutnya, `throttle:120,1`. `kasir.js` menggabungkan hasil polling tanpa reload, menampilkan toast untuk pesanan baru, dan berhenti saat tab tidak aktif. Bug slice 3 (`config` tidak dideklarasikan di `initPesananMasuk`) diperbaiki. | Agent |
| 29 Okt 2026 | Checkout QRIS Pelanggan B (`POST /pesan/checkout`) ditambahkan: order `menunggu_pembayaran` tanpa nomor antrean, item snapshot dari database, percobaan `payments` `{kode}-P{n}`, transaksi Snap lewat `MidtransService`, `throttle:checkout`, dan UI checkout yang mengosongkan keranjang setelah order dibuat. Model `Payment` dipakai pertama kali. Webhook dan halaman status pesanan belum ada. | Agent |
| 30 Okt 2026 | Webhook Midtrans (`POST /api/midtrans/notification`) ditambahkan: verifikasi signature, audit `payment_notifications`, transaksi idempoten, dan nomor antrean hanya setelah lunas lewat `QueueNumberService`. Panduan ngrok ditulis di `docs/MIDTRANS-SETUP.md`. | Agent |
| 31 Okt 2026 | Popup Snap.js dan halaman status `/pesanan/{kode_pesanan}` ditambahkan: checkout tidak lagi redirect ke Midtrans, halaman status dirender server dengan tiket antrean, polling status 5 detik, dan pembayaran ulang memakai percobaan aktif atau membuat `-P{n+1}` setelah gagal/kedaluwarsa. Route demo `/pesan/pembayaran` dan `/pesan/berhasil` dihapus. | Agent |
| 1 Nov 2026 | Pembatalan QRIS dan Cek Status kasir ditambahkan: `POST /kasir/pesanan/{order}/cancel` memanggil API cancel Midtrans dengan fallback status, sedangkan `POST /kasir/pesanan/{order}/check-status` memakai jalur pemrosesan webhook tanpa audit. Keduanya memakai `DB::transaction` + `lockForUpdate`, tidak pernah memberi nomor antrean untuk pembatalan, dan menyembunyikan order batal dari polling. | Agent |
| 1 Nov 2026 | Rekonsiliasi terjadwal (`qris:reconcile`, tiap 5 menit) dan penutupan otomatis order QRIS > 24 jam ditambahkan lewat `QrisReconciliationService` + `ReconcileQrisPayments`; panggilan API Midtrans diberi retry untuk gangguan jaringan/5xx dan `407 expire` diperlakukan sebagai status sah. | Agent |
| 1 Nov 2026 | Pembatalan QRIS oleh kasir kini juga menutup **sesi Snap** ketika Core API menjawab `404` (`Transaction doesn't exist`), sehingga order yang pelanggannya belum membuka halaman Snap dapat dibatalkan tanpa menunggu kedaluwarsa. Transaksi yang masih aktif tetap tidak dapat dibatalkan lokal. | Agent |
| 1 Nov 2026 | CRUD menu admin tersambung ke database: tambah/ubah dengan unggah foto, soft delete, dan toggle ketersediaan. Data menu di `localStorage` (`varya.admin.menus.v2`) dihapus dari `admin.js`. | Agent |
| 1 Nov 2026 | CRUD promosi admin tersambung ke database: tambah/ubah dengan unggah gambar, hard delete, dan status aktif. Data promosi di `localStorage` (`varya.admin.promos.v2`) dihapus dari `admin.js`; foto tentang kami tetap demo sampai slice `admin-store-settings`. | Agent |
| 2 Nov 2026 | Informasi toko tersambung ke database: `PUT /admin/toko` menyimpan profil `store_settings` + tujuh baris `store_hours` dalam satu transaksi. `admin.js` tidak lagi memakai `localStorage` untuk informasi toko; input statistik demo dihapus (tidak ada kolomnya). Foto tentang kami tetap demo karena belum ada kolom `store_settings` untuk foto. | Agent |
| 3 Nov 2026 | Laporan transaksi admin tersambung ke database: `GET /admin/laporan` memakai `ReportService` (filter bulan, ringkasan hanya transaksi lunas) dan `GET /admin/laporan/{order}` memberi rincian snapshot item + data tunai/Midtrans. Perhitungan ringkasan di `admin.js` dan data contoh laporan dihapus; dashboard masih data contoh. | Agent |
| 3 Nov 2026 | Dashboard admin tersambung ke database: `DashboardService` menghitung pendapatan hari ini/bulan (hanya lunas), hitungan pesanan menunggu/diproses, perbandingan QRIS:tunai, 5 menu terlaris dari `order_items`, dan 10 transaksi terbaru. Data contoh dashboard di `AdminController` dan pemetaan status demo di `admin.js` dihapus. | Agent |
| 3 Nov 2026 | Detail pesanan kasir tersedia lewat `GET /kasir/pesanan/{order}`: JSON dari database untuk dialog Pesanan Masuk, dibatasi order hari ini dan bukan order batal, tanpa perubahan data. | Agent |
| 3 Nov 2026 | README proyek menggantikan README Laravel, `.env.example` mendapat placeholder `MIDTRANS_*`, dan isi `database/database.sqlite` diverifikasi hanya data seed lama (penghapusan menunggu persetujuan user). | Agent |
| 4 Nov 2026 | Keputusan tertunda diselesaikan user: keranjang Pelanggan B tetap di browser sebagai pengecualian resmi F-29 (F-69), foto Tentang Kami mendapat kolom `foto_tentang` + endpoint unggah sendiri (F-70), checkout publik memakai `409` saat harga menu berubah (F-71), dan `database/database.sqlite` dihapus. | Agent |
| 7 Nov 2026 | Katalog beranda Pelanggan A dan B dibatasi tampilan 4 kartu dengan tombol “Lihat Semua Menu” yang memperluas di tempat (F-72); seluruh kartu tetap dirender server, pencarian/filter menembus batas, dan `/menu` tetap penuh. | Agent |
| 7 Nov 2026 | Filter publik memakai kelompok Kopi/Bukan Kopi/Makanan & Camilan (F-73); tata letak menu diperbesar menjadi 4 kolom terpusat di desktop dan 2 kolom di mobile, dengan ukuran sama untuk kartu pratinjau maupun hasil ekspansi. | Agent |
