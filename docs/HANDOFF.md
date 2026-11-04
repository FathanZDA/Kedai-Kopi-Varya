# HANDOFF.md — Titik Lanjut Pengerjaan

Dokumen ini adalah **ringkasan serah-terima antar sesi**. `AGENTS.md` tetap aturan kerja utama dan `PRD.md` tetap sumber kebenaran. File ini hanya menjawab tiga hal: *di mana kita sekarang*, *apa yang belum*, dan *apa langkah berikutnya*.

**Terakhir diperbarui:** 4 Nov 2026, setelah tiga keputusan tertunda disetujui user dan dikerjakan (`pelanggan-b-cart-policy`, `admin-about-photo`, `checkout-price-guard`) serta `database/database.sqlite` dihapus. Semuanya ada di working tree dan **belum di-commit**.

---

## 1. Status repo

| Item | Nilai |
|---|---|
| Branch aktif | `docs-readme` @ `0af5014`; working tree memuat tiga slice 4 Nov 2026 yang **belum di-commit** |
| Baseline test | `php artisan test` → **211 passed** (1070 assertions) setelah tiga slice 4 Nov 2026 |
| Pint | `php vendor/bin/pint --test` → passed |
| Build frontend | `npm.cmd run build` → berhasil |
| Database aplikasi | `kedai_varya` (MySQL, berisi data uji user) |
| Database test | `kedai_varya_test` (MySQL, bukan SQLite) |

Tumpukan branch bersifat **linear**. Branch baru selalu dipotong dari ujung terbaru, bukan dari `main`:

| Branch | Tip | Isi |
|---|---|---|
| `main` | `e6527c4` | Setup project |
| `pelanggan-a` | `a3ca7e2` | Halaman publik Pelanggan A |
| `pelanggan-b` | `bfc4a00` | Front-end Pelanggan B |
| `kasir` | `96d4228` | Front-end kasir |
| `admin` | `501113f` | Front-end admin |
| `login` | `896168e` | Auth manual + middleware role |
| `database` | `7a3b362` | Skema transaksi, model, enum |
| `kasir-backend` | `b1cbb96` | Backend kasir: order tunai, pesanan masuk, polling, ketersediaan menu |
| `qris-checkout` | `450036c` | Checkout QRIS + transaksi Snap + `throttle:checkout` |
| `qris-webhook` | `a9d04ab` | Webhook Midtrans, popup Snap, halaman status, pembayaran ulang |
| `kasir-qris-ops` | `2fb738f` | Pembatalan QRIS + Cek Status kasir |
| `auto-expire-reconcile` | `28e78ff` | Rekonsiliasi terjadwal + penutupan otomatis 24 jam |
| `kasir-cancel-snap-session` + `admin-menu-crud` | `6a8cb0d` | Pembatalan QRIS lewat sesi Snap + CRUD menu admin |
| `admin-promo-crud` | `cd07b7a` | CRUD promosi admin + unggah gambar + status aktif |
| `admin-store-settings` | `5acfb66` | Informasi Toko (profil + jam operasional 7 hari) |
| `admin-reports` | `f212512` | Laporan Transaksi dari DB + filter bulan + rincian transaksi |
| `admin-dashboard` | `5a406c4` | Dashboard dari DB: ringkasan, menu terlaris, transaksi terbaru |
| `kasir-pesanan-detail` | `9e7dfc0` | Detail pesanan kasir dari DB (`GET /kasir/pesanan/{order}`) |
| `docs-readme` | `0af5014` | README proyek, `.env.example` Midtrans, catatan SQLite |
| `keputusan-tertunda` (usulan) | *belum di-commit* | Kebijakan keranjang, foto Tentang Kami, `409` harga checkout, hapus SQLite |

---

## 2. Peta status per area

| Area | Status | Keterangan |
|---|---|---|
| Publik Pelanggan A | **Selesai** | Semua data dari database |
| Pelanggan B | **Selesai** | Menu/toko dari DB; keranjang browser sesuai F-69; checkout → order QRIS + Snap dengan konflik harga `409`; webhook menandai lunas; halaman status, tiket antrean, polling, dan bayar lagi sudah ada |
| Kasir | **Selesai** | Buat Pesanan, Pesanan Masuk, detail pesanan dari DB, pembatalan QRIS, Cek Status, dan Kelola Menu |
| Admin | **Selesai** | CRUD menu, CRUD promosi, Informasi Toko, foto Tentang Kami, Laporan Transaksi, dan Dashboard sudah dari DB |
| Login/role | **Selesai** | `/login`, redirect per role, `role` middleware, rate limit |
| Database transaksi | **Selesai** | `orders`, `order_items`, `payments`, `payment_notifications`, `queue_counters` |
| Pembayaran Midtrans | **Selesai** | Snap create + webhook + nomor antrean + popup + halaman status/bayar lagi + pembatalan + cek status + rekonsiliasi terjadwal + penutupan otomatis 24 jam + pembatalan sesi Snap |

---

## 3. Yang sudah selesai di area kasir

Route aktif (`routes/web.php`, semua `auth` + `role:kasir`):

| Method | URI | Nama |
|---|---|---|
| GET | `/kasir/pesanan/baru` | `kasir.pesanan.baru` |
| POST | `/kasir/pesanan` | `kasir.pesanan.store` |
| GET | `/kasir/pesanan` | `kasir.pesanan.index` |
| GET | `/kasir/pesanan/poll` | `kasir.pesanan.poll` (`throttle:120,1`) |
| GET | `/kasir/pesanan/{order}` | `kasir.pesanan.show` |
| PATCH | `/kasir/pesanan/{order}/status` | `kasir.pesanan.status` |
| POST | `/kasir/pesanan/{order}/cancel` | `kasir.pesanan.cancel` (`throttle:30,1`) |
| POST | `/kasir/pesanan/{order}/check-status` | `kasir.pesanan.check-status` (`throttle:30,1`) |
| GET | `/kasir/menu` | `kasir.menu.index` |
| PATCH | `/kasir/menu/{menu}/availability` | `kasir.menu.availability` |

Test terkait:

- `tests/Feature/KasirOrderTest.php` — order tunai, nomor antrean, validasi
- `tests/Feature/KasirPesananMasukTest.php` — daftar dari DB, transisi status
- `tests/Feature/KasirPesananPollTest.php` — snapshot, `sejak`, role, validasi
- `tests/Feature/KasirMenuAvailabilityTest.php` — toggle ketersediaan
- `tests/Feature/KasirQrisOpsTest.php` — pembatalan, fallback status Midtrans, Cek Status, idempotensi nomor antrean, role
- `tests/Feature/KasirPesananDetailTest.php` — detail dari DB, ID Midtrans terakhir, batas hari ini, order batal, role

Tidak ada route kasir yang belum dibuat.

Catatan: tombol **Batalkan** hanya dirender untuk order `menunggu_pembayaran` yang belum lunas. Tombol **Cek Status** tampil untuk QRIS yang belum dibayar dan memanggil API Midtrans dari server. Pesanan yang dibatalkan tidak ditampilkan di daftar/polling, tetapi tetap tersimpan untuk laporan admin.

---

## 4. Webhook QRIS dan Cek Status server-side (`qris-webhook`)

Route aktif:

| Method | URI | Nama | Middleware |
|---|---|---|---|
| POST | `/api/midtrans/notification` | `midtrans.notification` | Publik, tanpa CSRF, tanpa throttle ketat |

Berkas utama:

- `app/Http/Controllers/MidtransNotificationController.php` — validasi payload minimum, cek konfigurasi, verifikasi signature, pemetaan respons `200`/`403`/`500`.
- `app/Services/MidtransNotificationService.php` — verifikasi `SHA512`, pencatatan audit, pemrosesan idempoten dalam `DB::transaction`, pemetaan status, transisi order, deteksi pembayaran ganda; `terapkanStatus()` dipakai juga oleh Cek Status kasir.
- `app/Services/QueueNumberService.php` — ekstraksi penomoran antrean dari `OrderService`; dipakai order tunai dan webhook.
- `app/Models/PaymentNotification.php` — model `payment_notifications` tanpa timestamps.
- `app/Models/Order.php` — relasi `notifications()`.
- `app/Services/OrderService.php` — memakai `QueueNumberService`; tidak lagi punya `ambilNomorAntrean()`.
- `bootstrap/app.php` — `validateCsrfTokens(except: ['api/midtrans/notification'])`.
- `routes/web.php` — route webhook di `routes/web.php`, bukan `routes/api.php`.
- `tests/Feature/MidtransNotificationTest.php` — 17 test.
- `docs/MIDTRANS-SETUP.md` — panduan sandbox + ngrok + troubleshooting.

Perilaku penting:

- Signature: `hash_equals(sha512(order_id . status_code . gross_amount . server_key))`, memakai `gross_amount` mentah dari payload.
- Payload tidak lengkap → `200` + log, tanpa baris audit. `server_key` kosong → `500` + log. Signature salah → `403` + log, payload **tidak** disimpan.
- `order_id` tidak dikenal → `200`, dicatat `gagal`.
- Nominal tidak cocok → `200`, order `perlu_diperiksa` (kecuali sudah lunas), tanpa nomor antrean, dicatat `diabaikan`.
- `settlement` / `capture+accept` → `lunas`, `waktu_lunas`, `status_pesanan = antre`, nomor antrean dibuat bila belum ada.
- Notifikasi ganda dari percobaan yang sama → idempoten; nomor antrean dan `queue_counters` tidak bertambah.
- `settlement` dari percobaan **berbeda** pada order yang sudah lunas → `perlu_diperiksa`, nomor antrean lama dipertahankan.
- `settlement` setelah pembatalan lokal → `perlu_diperiksa`, tanpa nomor antrean.
- `refund`/`partial_refund`/`chargeback`/`partial_chargeback` dan status tak dikenal → `perlu_diperiksa`, tidak pernah `lunas`.
- `pending` tidak menurunkan status yang sudah `gagal`/`kedaluwarsa`/`perlu_diperiksa`.
- `payments.raw_response` (respons Snap) tidak ditimpa; payload webhook ada di `payment_notifications.payload`.

Acceptance criteria terpenuhi: lunas + nomor antrean, notifikasi ganda, nominal salah, signature salah, `expire`/`deny`, pembayaran kedua, pembatalan lokal, audit. Kasir melihat perpindahan ke tab Antre lewat polling yang sudah ada.

---

## 5. Yang baru selesai: `pesanan-status`, `kasir-qris-ops`, `auto-expire-reconcile`, `kasir-cancel-snap-session`, `admin-menu-crud`, `admin-promo-crud`, `admin-store-settings`, `admin-reports`, `admin-dashboard`, dan `kasir-pesanan-detail`

### 5.1 `pesanan-status`

**Status:** selesai dan sudah di-commit bersama `qris-webhook` @ `a9d04ab`.

**Kontrak:** `docs/ROUTES.md` §4.1–4.3, PRD §6.2 langkah 9–11, §8.4.3–6, §11.6.

**Yang sudah ada:**

1. Checkout QRIS membuka **popup Snap.js** (`snap.pay`) memakai `MIDTRANS_CLIENT_KEY` publik. Browser tidak lagi diarahkan ke `snap_redirect_url`. Setelah popup selesai/ditutup, pelanggan diarahkan ke halaman status di website ini.
2. `GET /pesanan/{order:kode_pesanan}` (`pesanan.status`) — halaman Blade server-side, tanpa login, kode acak 8 karakter. Menampilkan status, rincian item, total, dan tiket bernomor antrean setelah lunas.
3. `GET /pesanan/{order:kode_pesanan}/status` (`pesanan.status.json`) — JSON polling 5 detik, `throttle:order-status` 60/menit per IP. Hanya order `sumber = qr`; kode salah/order kasir → `404`.
4. `POST /pesanan/{order:kode_pesanan}/bayar-lagi` (`pesanan.bayar-lagi`) — memakai token percobaan aktif yang belum 15 menit; setelah gagal/kedaluwarsa membuat `payments` baru `-P{n+1}`. Order lunas/dibatalkan/perlu diperiksa → `409`.
5. `callbacks.finish` sudah dikirim ke `route('pesanan.status', $order)` sebagai fallback.
6. Route demo `GET /pesan/pembayaran` dan `GET /pesan/berhasil` sudah dihapus; `resources/views/pelanggan-b/berhasil.blade.php` sekarang menjadi halaman status. `pembayaran.blade.php` lama tidak dipakai.
7. Test baru: `tests/Feature/PesananStatusTest.php` (12 test) dan pengujian popup/client key di `PelangganBCheckoutTest`. Baseline setelah slice ini **114 passed** (515 assertions).

**Perilaku penting:**

- Nomor antrean tetap `null` sampai webhook menyatakan lunas; UI menampilkan `#--` dan "Menunggu Pembayaran" tanpa nomor.
- Parameter `finish`/`onSuccess` Snap tidak pernah menandai lunas. Status hanya berubah lewat webhook atau Cek Status server-side kasir; halaman status melakukan polling sampai berubah lalu reload.
- Snap gagal saat checkout (`500`) tetap menyimpan order; browser diarahkan ke halaman status yang menampilkan tombol **Coba Bayar Lagi**.
- `MIDTRANS_CLIENT_KEY` kosong → checkout/bayar lagi menjawab `503`; `MIDTRANS_SERVER_KEY` kosong tetap membuat webhook `500` untuk dicoba ulang.
- Setelah 24 jam, pesanan yang belum dibayar otomatis `dibatalkan` (PRD §8.7.6) — sudah diimplementasikan di §5.3 (`qris:reconcile`).

### 5.2 `kasir-qris-ops`

**Status:** selesai dan sudah di-commit di `kasir-qris-ops` @ `2fb738f`.

**Kontrak:** `docs/ROUTES.md` §4.9–4.10, PRD §7.4 K-02e/K-02g, §8.4.9–10, §8.4.10, §11.4–11.5, §7.6 PG-07.

**Yang sudah ada:**

1. `POST /kasir/pesanan/{order}/cancel` (`kasir.pesanan.cancel`, `throttle:30,1`) — membatalkan order QRIS yang belum dibayar. Percobaan `pending` dibatalkan lewat API Midtrans `/cancel`; bila ditolak, backend membaca `/status` untuk memastikan hasil akhirnya. Percobaan yang sudah terminal lokal dibatalkan tanpa HTTP.
2. `POST /kasir/pesanan/{order}/check-status` (`kasir.pesanan.check-status`, `throttle:30,1`) — membaca API status Midtrans dari server dan menerapkan hasilnya lewat jalur yang sama dengan webhook, tanpa menulis `payment_notifications`.
3. `MidtransService` diperluas dengan `ambilStatus()`, `batalkanTransaksi()`, dan pembacaan respons API; `MidtransNotificationService` mendapat `terapkanStatus()` yang dipakai bersama webhook dan Cek Status.
4. `OrderService` mendapat `batalkanPesananQris()` dan `periksaStatusPembayaran()` dengan `DB::transaction` + `lockForUpdate`, pengecekan idempotensi, dan perlindungan balapan `settlement`.
5. UI Pesanan Masuk mengirim pembatalan ke server, menampilkan toast hasil Cek Status, menangani `409` dengan menyegarkan daftar, serta menampilkan state loading/error pada tombol.
6. Route config layout kasir dan filter daftar order sudah diperbarui; order `dibatalkan` tidak tampil di Pesanan Masuk maupun polling.
7. Test baru: `tests/Feature/KasirQrisOpsTest.php` (18 test) mencakup pembatalan, respons cancel yang masih pending, fallback kedaluwarsa, balapan settlement, kegagalan jaringan, respons Midtrans untuk order lain, nominal hilang, idempotensi, keamanan nomor antrean, Cek Status, dan proteksi role. Baseline total **132 passed** (611 assertions).

**Perilaku penting:**

- Pembatalan tidak pernah membuat nomor antrean. Order tunai, order lunas, order `perlu_diperiksa`, atau order tanpa percobaan pembayaran ditolak `409`.
- Respons `200` dari API cancel tidak langsung dipercaya: status harus terminal, atau `settlement`/`capture` untuk ditolak sebagai konflik. Status yang masih `pending` membuat order tetap menunggu.
- Bila Midtrans melaporkan `settlement`, pembatalan ditolak `409` dan status pembayaran diterapkan lebih dulu (dapat menjadi `lunas` + nomor antrean).
- `settlement` yang datang setelah pembatalan lokal menjadi `perlu_diperiksa` tanpa nomor antrean; order tetap `dibatalkan`.
- Respons status wajib membawa `order_id` yang cocok; bila tidak, respons ditolak tanpa mengubah data. `gross_amount` yang hilang/tidak cocok menandai pembayaran `perlu_diperiksa` tanpa nomor antrean.
- Midtrans tidak dapat dihubungi → `502`; `MIDTRANS_SERVER_KEY` kosong → `503`; order tetap `menunggu_pembayaran`.
- Cek Status tidak menimpa `payments.raw_response` dan tidak menulis audit webhook. Tombol ini hanya ada di area kasir, bukan halaman pelanggan.

### 5.3 `auto-expire-reconcile`

**Status:** selesai dan sudah di-commit di `auto-expire-reconcile` @ `28e78ff`.

**Kontrak:** PRD §11.7, §8.7.6, §7.6 PG-08; `docs/ROUTES.md` §4.11; `docs/DATABASE.md` §7.5; `docs/DECISIONS.md` F-59/F-60.

**Yang sudah ada:**

1. `app/Services/QrisReconciliationService.php` — dua pekerjaan per putaran: memeriksa pembayaran `pending` berumur > 2 menit ke Midtrans, dan menutup order QRIS berumur > 24 jam. Penutupan memakai `DB::transaction` + `lockForUpdate`, tidak pernah membuat nomor antrean, dan melewati order yang punya bukti `settlement`/`capture` lokal atau percobaan `pending` < 15 menit.
2. `app/Console/Commands/ReconcileQrisPayments.php` — command `qris:reconcile`, mencetak `N diperiksa, N ditutup, N gagal`.
3. `routes/console.php` — `Schedule::command('qris:reconcile')->everyFiveMinutes()->withoutOverlapping(10)`.
4. `bootstrap/app.php` — `->withCommands()` agar command di `app/Console/Commands` terdaftar.
5. `MidtransService` — `retry(2, 250ms)` untuk `ConnectionException`/5xx dan `status_code = 407` + `transaction_status = expire` diperlakukan sebagai hasil sah.
6. `OrderService` — log `warning`/`error` pada kegagalan pembatalan/pemeriksaan, dan pesan 409 diubah menjadi "…akan ditutup otomatis setelah 24 jam".
7. Test: `tests/Feature/QrisReconciliationTest.php` (9 test) + 4 test tambahan di `KasirQrisOpsTest` (407 expire, retry 5xx, 5xx berulang).

**Perilaku penting:**

- Rekonsiliasi memakai `MidtransNotificationService::terapkanStatus`, jalur yang sama dengan webhook, sehingga `settlement` tetap memberi nomor antrean secara idempoten dan `perlu_diperiksa` tetap terbentuk bila nominal tidak cocok.
- Rekonsiliasi **tidak** menulis `payment_notifications` dan tidak menimpa `payments.raw_response`.
- Pesanan yang ditutup otomatis: `status_pesanan = dibatalkan`, `status_pembayaran = dibatalkan`, `nomor_antrean` tetap `null`, `queue_counters` tidak bertambah.
- Order yang punya bukti pembayaran (`payments.status_transaksi = settlement`/`capture`) tidak pernah ditutup meskipun status order sempat tidak sinkron.
- Kegagalan Midtrans tidak mengubah order; putaran berikutnya mencoba lagi.
- Penanganan lanjutan `perlu_diperiksa` **bukan** bagian slice ini: `admin.md` dan PRD §126 menyatakan koreksi dilakukan di dashboard Midtrans.

### 5.4 `kasir-cancel-snap-session`

**Status:** selesai dan sudah di-commit di `6a8cb0d` (digabung dengan `admin-menu-crud`).

**Masalah yang diperbaiki:** kasir tidak dapat membatalkan order QRIS yang masih `menunggu`. Snap baru mendaftarkan transaksi di Core API setelah pelanggan membuka halaman pembayaran, sehingga `POST /cancel` dan `GET /status` menjawab `404 Transaction doesn't exist`. Akibatnya pembatalan hanya berhasil setelah transaksi itu sendiri kedaluwarsa di Midtrans (mis. order `17`/`VMVDCTJI` yang baru batal dengan `expire#407`).

**Kontrak:** PRD §8.4.9, §7.6 PG-07; `docs/ROUTES.md` §4.9; `docs/DECISIONS.md` F-61.

**Yang sudah ada:**

1. `MidtransService::batalkanSesiSnap()` — `POST /snap/v1/transactions/{snap_token}/cancel` dengan retry jaringan/5xx. `200` (`canceled_at`) dan `token already canceled` dianggap berhasil (idempoten); `Transaction is on progress` dan `token not found` ditolak.
2. `OrderService::batalkanLewatSesiSnap()` — dipakai bila `/cancel` **atau** `/status` menjawab `404`. Setelah sesi tertutup, status pembayaran diterapkan sebagai `cancel` lalu order dibatalkan lokal tanpa nomor antrean.
3. Test baru di `KasirQrisOpsTest`: pembatalan lewat sesi Snap berhasil (order `dibatalkan`, payment `cancel`, tanpa nomor antrean), sesi Snap gagal (`502`, order tetap menunggu), dan `token already canceled` dianggap berhasil. Baseline total **148 passed** (709 assertions).

**Perilaku penting:**

- Aturan PRD §8.4.9 tidak berubah: transaksi yang masih `pending`/aktif di Midtrans tetap tidak boleh dibatalkan lokal; kasir tetap dapat menunggu kedaluwarsa atau menutup otomatis 24 jam.
- Pembatalan sesi Snap membuat token lama tidak dapat dipakai membayar, sehingga pembatalan lokal tidak menghasilkan `settlement` yang tidak terduga.
- Tidak ada route, migration, atau dependency baru.

### 5.5 `admin-menu-crud`

**Status:** selesai dan sudah di-commit di `6a8cb0d` (digabung dengan `kasir-cancel-snap-session`).

**Kontrak:** PRD AD-02, §8.6; `admin.md` §B; `docs/ROUTES.md` §4.12; `docs/DECISIONS.md` F-62.

**Yang sudah ada:**

1. `POST /admin/menu`, `PUT/PATCH /admin/menu/{menu}`, `DELETE /admin/menu/{menu}` — form biasa (redirect + session flash), validasi di `App\Http\Requests\Admin\MenuRequest`.
2. `PATCH /admin/menu/{menu}/availability` — JSON `{tersedia}` untuk toggle dari tabel.
3. `App\Services\MenuCatalogService` — slug unik (termasuk baris soft-delete), `urutan` = max + 1, foto `public/images/menus/unggahan-*`, hapus foto lama hanya bila berprefiks unggahan, soft delete.
4. `resources/views/admin/menu/form.blade.php` — form multipart asli, `old()`, ringkasan error + error per field, tombol hapus memakai form `DELETE` terpisah, waktu `updated_at` asli.
5. `resources/js/admin.js` — `localStorage` menu (`varya.admin.menus.v2`) dihapus; toggle dan hapus memakai `fetch` + CSRF; submit form kini native ke server; preview foto tetap jalan.
6. Test baru: `tests/Feature/AdminMenuCrudTest.php` (11 test) mencakup tambah dengan/tanpa foto, slug bentrok, validasi server, snapshot order tidak berubah, foto lama terhapus, soft delete hilang dari admin/publik/kasir, toggle ketersediaan, proteksi role, dan render form.

**Perilaku penting:**

- Slug tidak berubah saat menu diubah agar URL detail stabil; slug baru hanya dibuat saat menu dibuat.
- Foto kanonik seeder tidak pernah dihapus; hanya berkas `unggahan-*` milik menu tersebut yang diganti/dihapus.
- `order_items` tetap menyimpan snapshot nama/harga lama setelah menu diubah atau dihapus.
- Menu terhapus tidak tampil di admin, kasir, dan halaman publik, tetapi baris database tetap ada (`deleted_at`).
- Pencarian/filter tetap client-side dari `CONFIG.menus` yang dikirim server (bukan `localStorage`).

### 5.6 `admin-promo-crud`

**Status:** selesai dan sudah di-commit di `cd07b7a`.

**Kontrak:** PRD AD-04, §8.9; `admin.md` §D; `docs/ROUTES.md` §4.13; `docs/DECISIONS.md` F-63.

**Yang sudah ada:**

1. `POST /admin/promosi`, `PUT/PATCH /admin/promosi/{promo}`, `DELETE /admin/promosi/{promo}` — form multipart (redirect + session flash), validasi di `App\Http\Requests\Admin\PromoRequest`.
2. `PATCH /admin/promosi/{promo}/status` — JSON `{aktif}` untuk toggle dari daftar.
3. `App\Services\PromoService` — simpan gambar ke `public/images/promos/unggahan-promo-*`, hapus gambar unggahan saat diganti/dihapus, hard delete (tabel `promos` tanpa soft delete).
4. `resources/views/admin/promosi.blade.php` — form asli dengan `@csrf`/`_method`, field `judul`/`deskripsi`/`urutan`/`aktif`/`mulai`/`berakhir`/`gambar`, `old()` dan ringkasan error; mode edit ditentukan dari `promo_id` lama sehingga isian tidak hilang saat validasi gagal.
5. `resources/js/admin.js` — `localStorage` promosi (`varya.admin.promos.v2`) dihapus; daftar dari `CONFIG.promos`; toggle status dan hapus memakai `fetch` + CSRF; tombol Ubah mengisi editor dan mengubah `action`/`_method` form; submit form native ke server. Bagian **Foto Tentang Kami** tetap demo.
6. Test baru: `tests/Feature/AdminPromoCrudTest.php` (10 test) mencakup render halaman, tambah dengan/tanpa gambar, validasi server (termasuk periode terbalik), ubah + ganti gambar, toggle status, promosi nonaktif/kedaluwarsa hilang dari landing page, hapus + berkas gambar terhapus, proteksi role, dan tamu diarahkan ke login.

**Perilaku penting:**

- Gambar kanonik seeder (`images/menus/*`) tidak pernah dihapus; hanya berkas `unggahan-promo-*` milik promosi tersebut.
- Landing page tetap membaca `Promo::active()->ordered()`, jadi promosi nonaktif atau di luar periode langsung hilang tanpa perubahan kode di sisi publik.
- Promosi dihapus permanen; tidak ada pemulihan seperti soft delete menu.
- Edit promosi memakai satu halaman (tanpa route `edit` terpisah): `admin.js` mengubah `action` form ke URL promosi yang dipilih dan mengirim `_method=PUT`.

### 5.7 `admin-store-settings`

**Status:** selesai dan sudah di-commit di `5acfb66`.

**Kontrak:** PRD AD-05, §8.9; `admin.md` §E; `docs/ROUTES.md` §4.14; `docs/DECISIONS.md` F-64.

**Yang sudah ada:**

1. `PUT /admin/toko` (`admin.store.update`) — form biasa dengan redirect + session flash; validasi di `App\Http\Requests\Admin\StoreSettingsRequest`.
2. `App\Services\StoreSettingsService` — upsert satu baris `store_settings` (id = 1, dibuat bila belum ada) dan tujuh baris `store_hours` (`hari` 0–6) dalam satu `DB::transaction` + `lockForUpdate`; jam ditulis dengan `updateOrCreate` per hari.
3. Validasi server: nama toko wajib; tujuh hari wajib punya jam buka/tutup kecuali ditandai libur; fasilitas maks. 20 item dan tiap item maks. 50 karakter; `instagram` menerima `handle` maupun `@handle` lalu disimpan `@handle`; telepon/WhatsApp hanya angka, spasi, `+`, `-`, `()`; `maps_url`/`maps_embed` harus URL.
4. `resources/views/admin/toko.blade.php` — form native `@csrf` + `@method('PUT')`, `old()` di semua field, ringkasan error + pesan per field, preview nama/alamat/kontak, penghitung karakter deskripsi, dan toast dari `data-admin-flash`. Input **3 Poin Statistik** demo dihapus (tidak ada kolomnya) dan diganti tampilan statis; angka publik tidak berubah.
5. `resources/js/admin.js` — `localStorage` informasi toko dan handler submit/cancel demo dihapus; modul hanya menangani preview, penghitung karakter, dan toast.
6. Test baru: `tests/Feature/AdminStoreSettingsTest.php` (9 test) mencakup render halaman, simpan profil + kontak + fasilitas, jam tujuh hari termasuk hari libur, perubahan tampil di halaman publik, validasi server, field opsional kosong, proteksi tamu/kasir, dan isian tidak hilang saat validasi gagal. Baseline **178 passed** (905 assertions).

**Perilaku penting:**

- Perubahan langsung tampil di `/`, `/tentang`, dan `/lokasi` karena halaman publik membaca `store_settings`/`store_hours` yang sama.
- Field opsional kosong disimpan `null`, `fasilitas` kosong menjadi `[]`, tetapi `tagline` disimpan `''` karena kolomnya NOT NULL.
- Hari yang ditandai libur menyimpan `jam_buka`/`jam_tutup` `null` dan `tutup = true`; hari lain wajib punya kedua jam.
- **Foto Tentang Kami** saat slice ini belum tersimpan (tidak ada kolom foto). Sudah diselesaikan 4 Nov 2026 di §5.13 (F-70).
- Migration `2026_10_25_000001_align_schema_with_prd` sudah mengisi 7 baris `store_hours`; test membersihkan data bawaan itu di `setUp()` sebelum membuat data sendiri agar unique `hari` tidak bentrok.

### 5.8 `admin-reports`

**Status:** sudah diimplementasikan di working tree, **belum di-commit**.

**Kontrak:** PRD AD-03, §8.8; F-14, F-19; `docs/DECISIONS.md` F-65; `docs/ROUTES.md` §4.15.

**Yang sudah ada:**

1. `GET /admin/laporan` (`admin.reports`) — dirender server dari database, filter `?bulan=YYYY-MM` lewat form `GET` biasa. Nilai kosong/tidak valid jatuh ke bulan berjalan `Asia/Jakarta`.
2. `App\Services\ReportService` — `bulanValid()`, `daftarBulan()` (maks. 36 bulan, terbaru lebih dulu, selalu memuat bulan berjalan dan bulan terpilih), `transaksi()` (semua status pada bulan itu, terbaru lebih dulu), `ringkasan()`, dan `detail()`.
3. Ringkasan hanya menjumlahkan `status_pembayaran = lunas`; total QRIS/tunai, rata-rata, persentase, dan perbandingan bulan sebelumnya dihitung dari transaksi lunas. Transaksi belum lunas tetap tampil di tabel sebagai informasi.
4. `GET /admin/laporan/{order}` (`admin.reports.show`) — JSON rincian untuk dialog: snapshot `order_items` (+ `catatan`), `uang_diterima`/`kembalian`, `midtrans_order_id`/`transaction_id`/`payment_type`, nomor antrean tiga digit.
5. `resources/views/admin/laporan.blade.php` — periode, ringkasan, dan kolom antrean dari data server; form filter bulan berfungsi tanpa JS (`<noscript>`); teks "0 pengembalian" dan label demo dihapus.
6. `resources/js/admin.js` — perhitungan ringkasan client-side (`summarize`, `renderSummary`, `previousMonth`), filter `state.month`, dan `transactions` statis dihapus; rincian diambil dari `CONFIG.reportOrderBase` + id order.
7. `resources/views/components/layouts/admin.blade.php` — `CONFIG.reportOrderBase` ditambahkan.
8. Test baru: `tests/Feature/AdminReportTest.php` (9 test, 55 assertions): render halaman dari DB, hanya lunas dihitung, filter bulan, bulan tidak valid, daftar bulan, rincian tunai/QRIS, proteksi role + 404, tamu diarahkan ke login.
9. Baseline setelah slice ini **187 passed** (960 assertions); Pint bersih.

**Perilaku penting:**

- Tidak ada migration/kolom baru; laporan membaca tabel yang sudah ada.
- Rentang periode tetap memakai `created_at` (bukan `waktu_lunas`), sama dengan daftar pesanan kasir.
- Order `dibatalkan` ikut tampil di tabel laporan (sebagai informasi) karena PRD §8.8.3 hanya melarang perhitungan pendapatan, bukan menampilkan.
- Metode `transactions()`, `summarizeMonth()`, `recentOrders()`, dan `popularMenus()` di `AdminController` masih dipakai dashboard; penghapusannya masuk slice `admin-dashboard`.
- Ekspor PDF/Excel tetap Fase 2 (F-19).

### 5.9 `admin-dashboard`

**Status:** sudah diimplementasikan di working tree, **belum di-commit**.

**Kontrak:** PRD AD-01; `admin.md` §A; `docs/DECISIONS.md` F-66; `docs/ROUTES.md` §4.16.

**Yang sudah ada:**

1. `App\Services\DashboardService` — `data()` mengembalikan ringkasan hari ini/bulan, perbandingan QRIS:tunai, hitungan pesanan, lima menu terlaris, dan sepuluh transaksi terbaru dari `orders`/`order_items`/`payments`.
2. `GET /admin/dashboard` — dirender server; `AdminController@dashboard` sekarang hanya memanggil service dan `menuData()` (kartu "Daftar Menu Aktif").
3. Pendapatan hari ini dan bulan berjalan hanya dari `status_pembayaran = lunas`; pesanan `menunggu`/`diproses` dan jumlah pesanan hari ini memakai `created_at` hari berjalan; perbandingan memakai hari kemarin.
4. Menu terlaris: `SUM(order_items.jumlah)` dari transaksi lunas bulan berjalan per snapshot `nama_menu`, urut terbanyak, maks. 5; harga/kategori/foto dari baris `menus` (termasuk soft-delete).
5. Transaksi terbaru: 10 order terbaru semua status, lengkap dengan antrean, jumlah item/porsi, total, metode, status pesanan, status pembayaran, dan ID Midtrans.
6. `resources/views/admin/dashboard.blade.php` — angka dari server (bukan `count($orders)`), perbandingan kemarin dihitung, judul menu menjadi "Menu Terlaris Bulan Ini", dan subtitle pendapatan menyebut "Hanya transaksi lunas".
7. `resources/js/admin.js` — status badge dashboard memakai `status_label` dari server dan mencakup `menunggu_pembayaran`/`antre`/`dibatalkan`; baris pembayaran menampilkan status pembayaran yang sebenarnya.
8. Dihapus dari `AdminController`: `recentOrders()`, `popularMenus()`, `transactions()`, `makeTransaction()`, `summarizeMonth()`, `kodePesanan()`, `menuKanonik()` (controller turun dari 1118 menjadi ±430 baris).
9. Test baru: `tests/Feature/AdminDashboardTest.php` (7 test, 40 assertions): ringkasan dari DB, hanya lunas dihitung, hitungan menunggu/diproses hari ini, perbandingan kemarin, menu terlaris dari snapshot, maksimal 10 transaksi terbaru, proteksi role.
10. Baseline setelah slice ini **194 passed** (1000 assertions); Pint bersih; `npm.cmd run build` berhasil.

**Perilaku penting:**

- Hanya transaksi lunas yang masuk angka pendapatan; pesanan belum lunas tetap tampil di tabel transaksi terbaru sebagai informasi.
- Tidak ada filter periode di dashboard; menu terlaris memakai bulan berjalan agar tidak kosong saat hari sedang sepi (PRD AD-01 tidak menentukan periode).
- Tidak ada kolom, migration, route, atau dependency baru.
- Foto/menu yang sudah dihapus permanen tetap muncul di daftar terlaris lewat snapshot nama dengan foto bawaan.

### 5.10 `kasir-pesanan-detail`

**Status:** sudah diimplementasikan di working tree, **belum di-commit**.

**Kontrak:** PRD K-02c; `docs/DECISIONS.md` F-67; `docs/ROUTES.md` §4.17.

**Yang sudah ada:**

1. `GET /kasir/pesanan/{order}` (`kasir.pesanan.show`) — JSON detail satu pesanan, bentuk data sama dengan item polling sehingga klien tidak perlu transformasi.
2. `KasirController@showOrder` — hanya order `created_at` hari ini (`Asia/Jakarta`), order `dibatalkan` dan id tidak dikenal `404`; data dimuat dari `items` + `payments` terakhir.
3. `resources/js/kasir.js` — `openDetail` membuka dialog memakai snapshot lalu memuat ulang detail dari server; `404` menutup dialog, memberi tahu pengguna, dan memicu polling.
4. `resources/views/components/layouts/kasir.blade.php` — `config.routes.pesananShow` baru.
5. Test baru: `tests/Feature/KasirPesananDetailTest.php` (6 test, 27 assertions): detail tunai lengkap dengan catatan item, ID Midtrans percobaan terakhir untuk QRIS, order kemarin `404`, order batal `404`, id tidak dikenal `404`, tamu `401` + admin `403`.
6. Baseline setelah slice ini **200 passed** (1027 assertions); Pint bersih; `npm.cmd run build` berhasil.

**Perilaku penting:**

- Detail tidak mengubah data apa pun; tidak ada route tulis baru, migration, atau dependency.
- Batas “hari ini” sama dengan daftar/polling (F-47), sehingga pesanan batal konsisten tidak dapat dibuka (PRD K-02b).
- Dialog tetap tampil instan dari snapshot; respons server menimpa nilai yang mungkin sudah berubah (mis. status pembayaran dari webhook).

### 5.11 `docs-readme`

**Status:** sudah diimplementasikan di working tree, **belum di-commit**.

**Kontrak:** AGENTS §11; F-44; `docs/DECISIONS.md` F-68.

**Yang sudah ada:**

1. `README.md` ditulis ulang sebagai README proyek (bukan bawaan Laravel): daftar fitur per area, catatan aturan penting, stack, kebutuhan, langkah setup lengkap (dependensi, `.env`, database, migrate/seed, build), akun seeder (tanpa kata sandi), folder unggahan, konfigurasi Midtrans + ngrok, scheduler `qris:reconcile`, perintah pemeriksaan, database test, struktur folder, daftar dokumen, dan aturan kerja singkat.
2. `.env.example` mendapat blok `MIDTRANS_*` kosong beserta URL sandbox, sehingga setup tidak perlu menebak nama variabel. Tidak ada kredensial yang ditulis.
3. Catatan `database/database.sqlite` ditulis di README: file tidak dipakai; isinya hanya data seed lama (1 user, 12 menu, 5 kategori, 3 promosi, 1 `store_settings`, 7 migration) tanpa tabel transaksi. **File belum dihapus** karena penghapusan berkas menunggu persetujuan user (AGENTS §10).
4. Baseline tetap **200 passed** (1027 assertions); Pint bersih; `npm.cmd run build` tidak terpengaruh (tidak ada perubahan JS/CSS).

**Perilaku penting:**

- README menyebut keranjang Pelanggan B masih `localStorage` dan foto Tentang Kami masih demo supaya tidak menyesatkan pembaca. Keduanya diperbarui 4 Nov 2026 (§5.12/§5.13).
- Tidak ada kode aplikasi yang berubah pada slice ini; hanya dokumentasi dan template `.env`.
- Penghapusan `database/database.sqlite` dan keputusan keranjang/foto/`409` masuk daftar tunggu user (§6 dan §5.12).

### 5.12 `pelanggan-b-cart-policy` (A1)

**Status:** sudah diimplementasikan di working tree, **belum di-commit**.

**Kontrak:** PRD B-04; `docs/DECISIONS.md` F-29, F-69; `docs/ROUTES.md` §1/§2.3.

**Yang sudah ada:**

1. Keranjang Pelanggan B tetap di `localStorage` (`varya_cart_v1`) — **tidak** ada endpoint keranjang baru, sesuai keputusan user.
2. F-29 diperjelas: larangan browser storage tetap berlaku untuk pesanan, menu, dan pembayaran, sedangkan keranjang tamu adalah pengecualian resmi karena server menghitung ulang harga, ketersediaan, dan total saat checkout.
3. `AGENTS.md` §2/§6/§7, `docs/ROUTES.md`, dan README memakai rumusan yang sama supaya tidak ada aturan internal yang dilanggar diam-diam.

**Perilaku penting:**

- Tidak ada kode yang berubah pada slice ini; hanya dokumen.
- Keranjang tidak boleh dipakai untuk menyimpan pesanan, status pembayaran, atau ketersediaan menu.
- Bila Pelanggan B kelak punya akun, keputusan ini ditinjau ulang (F-69).

### 5.13 `admin-about-photo` (B1)

**Status:** sudah diimplementasikan di working tree, **belum di-commit**.

**Kontrak:** PRD AD-05; `docs/DECISIONS.md` F-70; `docs/ROUTES.md` §4.14.

**Yang sudah ada:**

1. Migration `2026_11_04_000001_tambah_foto_tentang_pada_store_settings` — kolom `store_settings.foto_tentang` (varchar 255, nullable).
2. `POST /admin/toko/foto-tentang` (`admin.store.foto-tentang.store`, multipart) dan `DELETE /admin/toko/foto-tentang` (`admin.store.foto-tentang.destroy`, JSON bila `Accept: application/json`). `PUT /admin/toko` tetap JSON (F-64).
3. `App\Http\Requests\Admin\AboutPhotoRequest` — wajib gambar JPG/PNG/WEBP, maks. 3 MB.
4. `StoreSettingsService::simpanFotoTentang`/`hapusFotoTentang` — berkas di `public/images/toko/unggahan-{acak}.{ext}`, unggahan lama dihapus hanya bila berprefiks `unggahan-`.
5. Panel **Foto Tentang Kami** di halaman promosi kini form multipart asli (Simpan = submit, Hapus = `fetch` DELETE), status “Tampil”/“Tidak Tampil” dari database, dan pratinjau memakai `public/images/toko/belum-ada-foto.svg` bila kosong.
6. `resources/views/components/pelanggan-a/partials/tentang.blade.php` menampilkan foto dari database; placeholder bermotif dipertahankan saat `foto_tentang` `null`.
7. Test baru: `tests/Feature/AdminAboutPhotoTest.php` (9 test, 30 assertions) — unggah tersimpan, tampil di `/tentang`, placeholder saat kosong, unggahan baru menghapus berkas lama, berkas non-gambar ditolak, hapus menghapus berkas + kolom, status panel, kasir `403`, tamu ke login.

**Perilaku penting:**

- Satu foto untuk bingkai desktop (3:2) dan mobile (16:9); pemotongan lewat CSS `object-cover`.
- Berkas kanonik di `public/images` tidak pernah dihapus.
- Panel berada di halaman promosi (desain yang sudah ada), bukan halaman Informasi Toko.

### 5.14 `checkout-price-guard` (C1)

**Status:** sudah diimplementasikan di working tree, **belum di-commit**.

**Kontrak:** PRD B-10, §8.2 butir 8; `docs/DECISIONS.md` F-71; `docs/ROUTES.md` §4.1.

**Yang sudah ada:**

1. `StoreCheckoutRequest` mewajibkan `item.*.harga_satuan` (integer ≥ 0); nilainya hanya harga acuan, bukan harga yang ditagih.
2. `OrderService::siapkanItem` menerima `$periksaHarga`; `buatOrderQris` mengaktifkannya, `buatOrderTunai` tidak, sehingga alur kasir tidak berubah.
3. `App\Exceptions\HargaMenuBerubahException` mengumpulkan semua item yang harganya berubah (nama, harga lama, harga baru) dan menyusun pesan; `PelangganBController@storeCheckout` menjawab `409` dengan `data.item`, tanpa membuat order/payment dan tanpa memanggil Midtrans.
4. `resources/js/pelanggan-b.js` mengirim `harga_satuan` dari keranjang dan menampilkan pesan `409` tanpa mengosongkan keranjang.
5. Test `PelangganBCheckoutTest` diperbarui: payload menyertakan `harga_satuan`; test baru untuk harga berubah → `409` dan `harga_satuan` hilang → `422`.

**Perilaku penting:**

- Menu habis/terhapus tetap `422`; hanya konflik harga yang `409`.
- Tidak ada order tersimpan saat `409`, jadi pelanggan bisa checkout ulang setelah menyesuaikan keranjang.
- Harga yang ditagih dan disimpan di `order_items` selalu `menus.harga`.

## 5.15 Kandidat berikutnya

Tidak ada slice tertunda yang menunggu keputusan; tiga keputusan 4 Nov 2026 sudah dikerjakan. Sisa pekerjaan bersifat opsional/operasional:

| # | Topik | Catatan | Acuan |
|---|---|---|---|
| 1 | Ekspor laporan PDF/Excel | Fase 2, belum masuk scope | F-19; PRD AD-03 |
| 2 | Akun/kredensial Midtrans produksi | Operasional, butuh akun dan URL publik | O-16; `docs/MIDTRANS-SETUP.md` |
| 3 | Keranjang server-side | Hanya relevan bila Pelanggan B kelak punya akun | F-69 |

## 6. Keputusan yang masih menunggu user

| Topik | Status | Dampak |
|---|---|---|
| `O-16` akun/kredensial Midtrans | OPEN operasional (sandbox sudah diuji end-to-end) | URL ngrok berubah setiap restart; perbarui Notification URL Midtrans. Kredensial produksi belum disiapkan. Lihat `docs/MIDTRANS-SETUP.md`. |
| Foto Tentang Kami | **Selesai** (F-70) | Kolom `foto_tentang` + endpoint unggah terpisah |
| `409 "menu berubah"` pada checkout/order | **Selesai** (F-71) | Harga acuan wajib di checkout publik; kasir tunai tidak berubah |
| Keranjang Pelanggan B | **Selesai** (F-69) | Tetap di browser sebagai pengecualian resmi F-29 |
| `callbacks.finish` Snap | **Selesai** | Dikirim ke halaman status sebagai fallback |

---

## 7. Catatan lingkungan (penting, sudah pernah menimbulkan masalah)

- **MySQL XAMPP sempat rusak.** 28 Okt 2026 tabel sistem `proxies_priv`/`db`/`event` korup dan server menolak start. Sudah diperbaiki dengan `aria_chk` + membuat ulang `proxies_priv`. Backup ada di `C:\xampp\mysql\_perbaikan_20261028` (boleh dihapus setelah yakin).
- **MySQL kadang mati sendiri.** Selalu cek `Test-NetConnection 127.0.0.1 -Port 3306` sebelum menyimpulkan ada bug kode. Kalau mati, nyalakan lewat XAMPP Control Panel atau `C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini --standalone`.
- **Jangan `migrate:fresh` di `kedai_varya`.** Database itu berisi data uji user. Test memakai `kedai_varya_test`.
- **Kredensial Midtrans:** `.env` sudah berisi placeholder `MIDTRANS_*` kosong. Isi dengan kredensial sandbox dari akun Midtrans. Jangan menaruh nilainya di `.env.example`, dokumentasi, JS, atau commit. Tanpa `MIDTRANS_SERVER_KEY`/`MIDTRANS_CLIENT_KEY`, checkout dan bayar lagi menjawab `503`; tanpa `MIDTRANS_SERVER_KEY`, webhook menjawab `500`.
- **Tunnel:** gunakan **ngrok** (`ngrok http 127.0.0.1:8000`, bukan `8000` saja — IPv6 `localhost` menyebabkan `502`). URL gratis berubah setiap restart; perbarui Notification URL di dashboard Midtrans. Panduan lengkap: `docs/MIDTRANS-SETUP.md`.
- **ngrok 30 Okt 2026:** paket winget masih 3.3.1 dan ditolak (`ERR_NGROK_121`, minimal 3.28.0). Sudah di-`ngrok update` ke **3.39.13**. Defender memblokir binary baru sebagai `Trojan:Win32/Kepavll!rfn` (false positive); sudah ditambahkan exclusion path paket + process `ngrok.exe` + allow ThreatID `2147939874`. Bila ngrok diblokir lagi setelah update, ulangi langkah di `docs/MIDTRANS-SETUP.md` §4.2.
- **PHP cURL CA 31 Okt 2026:** checkout Snap sempat gagal `cURL error 60 ... unable to get local issuer certificate` karena `curl.cainfo`/`openssl.cafile` kosong. Sudah dipasang `C:\php-8.5.9-Win32-vs17-x64\extras\ssl\cacert.pem` dan diisi di `php.ini`. **Restart `php artisan serve` setelah mengubah `php.ini`.**
- **Checkout gagal setelah order dibuat:** order tetap `menunggu_pembayaran` dan keranjang browser sudah dihapus, sehingga klik berikutnya berbunyi “keranjang masih kosong”. Perbaiki penyebab Snap lalu buat order baru; order gagal lama dapat dibatalkan lewat kasir atau dicoba bayar lagi dari halaman status.
- **Windows/PowerShell:** `npm.ps1` diblokir Execution Policy — pakai `npm.cmd`. `git add -A` tidak aman di mesin ini; daftarkan file satu per satu.
- **`public/build` masuk `.gitignore`.** Setelah mengubah JS/CSS, jalankan `npm.cmd run build`; jangan commit hasil build.
- **Jangan commit/push.** User yang melakukan commit dan push. Agent berhenti setelah melaporkan dan memberi perintah commit.

---

## 8. Aturan singkat untuk sesi baru

1. Baca `AGENTS.md`, `PRD.md`, `docs/DECISIONS.md`, `docs/ROUTES.md`, `docs/DATABASE.md`.
2. Verifikasi baseline: `git log -1` dan `php artisan test` harus hijau sebelum menulis kode (saat file ini ditulis 200 passed).
3. Potong branch baru dari ujung terbaru (`docs-readme` @ `0af5014` + commit keputusan tertunda), bukan dari `main`.
4. Kerjakan satu slice kecil; jangan mengerjakan seluruh backend sekaligus.
5. Tanpa dependency baru tanpa persetujuan user. Tanpa `localStorage` untuk data nyata selain keranjang tamu Pelanggan B (F-69). Tanpa simulasi pembayaran.
6. Setelah selesai: `php artisan test`, `php vendor/bin/pint --test`, `npm.cmd run build`, lalu perbarui `AGENTS.md`, `docs/ROUTES.md`, `docs/DECISIONS.md`, `docs/DATABASE.md` bila perlu, dan **file ini**.

---

## 9. Prompt siap tempel

```text
Lanjutkan proyek Kedai Kopi Varya. Mulai dengan membaca docs/HANDOFF.md,
lalu AGENTS.md, PRD.md, docs/DECISIONS.md, docs/ROUTES.md, docs/DATABASE.md.

Verifikasi baseline dulu: git log -1 dan php artisan test (harus hijau;
saat file ini ditulis 178 passed).
Kalau MySQL mati, nyalakan XAMPP dulu sebelum menyimpulkan ada bug kode.

Kerjakan slice berikutnya yang tertulis di docs/HANDOFF.md §5.8, dipotong
dari branch terbaru. Ikuti pola yang sudah ada: FormRequest, Service,
API Resource, DB::transaction + lockForUpdate, enum, named route.

Jangan menambah dependency apa pun. Kredensial Midtrans hanya dari .env.
Jangan commit/push — saya yang commit. Berhenti dan tanya jika perlu
mengubah skema, menambah package, atau ada keputusan OPEN.
Perbarui dokumen dan docs/HANDOFF.md di akhir pekerjaan.
```

---

## 10. Riwayat slice

| Tanggal | Branch | Commit | Ringkasan |
|---|---|---|---|
| 27 Okt 2026 | `login` | `896168e` | Auth manual, role, rate limit |
| 27 Okt 2026 | `database` | `33bada8` | Skema transaksi |
| 27 Okt 2026 | `database` | `7a3b362` | Model + enum status |
| 27 Okt 2026 | `kasir-backend` | `726a293` | Order tunai + nomor antrean |
| 27 Okt 2026 | `kasir-backend` | `6fb3d72` | UI Buat Pesanan → `POST /kasir/pesanan` |
| 28 Okt 2026 | `kasir-backend` | `5acfd5c` | Pesanan Masuk dari DB + ubah status |
| 28 Okt 2026 | `kasir-backend` | `a892855` | Ketersediaan menu via `PATCH` |
| 29 Okt 2026 | `kasir-backend` | `b1cbb96` | Polling Pesanan Masuk |
| 29 Okt 2026 | `kasir-backend` | `a491b1c` | Dokumentasi HANDOFF |
| 29 Okt 2026 | `qris-checkout` | `450036c` | Checkout QRIS + Snap + `throttle:checkout` |
| 30-31 Okt 2026 | `qris-webhook` | `a9d04ab` | Webhook Midtrans, popup Snap, halaman status/tiket, polling, bayar lagi |
| 1 Nov 2026 | `kasir-qris-ops` | `2fb738f` | Pembatalan QRIS + Cek Status kasir |
| 1 Nov 2026 | `auto-expire-reconcile` | `28e78ff` | Rekonsiliasi terjadwal + penutupan otomatis 24 jam |
| 1 Nov 2026 | `kasir-cancel-snap-session` + `admin-menu-crud` | `6a8cb0d` | Pembatalan QRIS lewat sesi Snap + CRUD menu admin + unggah foto + ketersediaan |
| 1 Nov 2026 | `admin-promo-crud` | `cd07b7a` | CRUD promosi admin + unggah gambar + status aktif |
| 2 Nov 2026 | `admin-store-settings` | `5acfb66` | Informasi Toko: profil `store_settings` + jam `store_hours` 7 hari |
| 3 Nov 2026 | `admin-reports` | `f212512` | Laporan Transaksi dari DB: filter bulan, ringkasan hanya lunas, rincian snapshot item |
| 3 Nov 2026 | `admin-dashboard` | `5a406c4` | Dashboard dari DB: ringkasan, menu terlaris, 10 transaksi terbaru |
| 3 Nov 2026 | `kasir-pesanan-detail` | `9e7dfc0` | Detail pesanan kasir dari DB untuk dialog Pesanan Masuk |
| 3 Nov 2026 | `docs-readme` | `0af5014` | README proyek, template `MIDTRANS_*`, catatan SQLite |
| 4 Nov 2026 | `keputusan-tertunda` (usulan) | *belum di-commit* | Kebijakan keranjang (dokumen), foto Tentang Kami + `foto_tentang`, `409` harga checkout, hapus `database/database.sqlite` |

---

## 11. Cara memperbarui file ini

Perbarui di commit yang sama dengan perubahan perilaku:

- Ganti **Terakhir diperbarui** dan hash commit.
- Perbarui tabel status dan daftar route jika ada yang berubah.
- Pindahkan slice yang selesai dari §5 ke §10.
- Tulis slice berikutnya di §5 beserta acceptance criteria.
