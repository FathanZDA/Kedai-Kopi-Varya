# HANDOFF.md — Titik Lanjut Pengerjaan

Dokumen ini adalah **ringkasan serah-terima antar sesi**. `AGENTS.md` tetap aturan kerja utama dan `PRD.md` tetap sumber kebenaran. File ini hanya menjawab tiga hal: *di mana kita sekarang*, *apa yang belum*, dan *apa langkah berikutnya*.

**Terakhir diperbarui:** 29 Okt 2026, setelah slice `qris-checkout` (belum di-commit).

---

## 1. Status repo

| Item | Nilai |
|---|---|
| Branch aktif | `qris-checkout` (dipotong dari `kasir-backend` @ `a491b1c`, **belum di-commit**) |
| Baseline test | `php artisan test` → **84 passed** (376 assertions) |
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
| `qris-checkout` | *belum di-commit* | Checkout QRIS + transaksi Snap + `throttle:checkout` |

---

## 2. Peta status per area

| Area | Status | Keterangan |
|---|---|---|
| Publik Pelanggan A | **Selesai** | Semua data dari database |
| Pelanggan B | **Sebagian** | Menu/toko dari DB; keranjang masih `localStorage`; checkout → order QRIS + Snap; halaman status & bayar lagi belum ada |
| Kasir | **Selesai (kecuali yang menunggu QRIS)** | Lihat §3 |
| Admin | **Sebagian** | Menu, kategori, promosi, toko dari DB; transaksi/laporan/CRUD aksi masih data contoh + `localStorage` |
| Login/role | **Selesai** | `/login`, redirect per role, `role` middleware, rate limit |
| Database transaksi | **Selesai** | `orders`, `order_items`, `payments`, `payment_notifications`, `queue_counters` |
| Pembayaran Midtrans | **Sebagian** | Snap create transaction sudah; webhook, rekonsiliasi, bayar lagi, dan pembatalan belum |

---

## 3. Yang sudah selesai di area kasir

Route aktif (`routes/web.php`, semua `auth` + `role:kasir`):

| Method | URI | Nama |
|---|---|---|
| GET | `/kasir/pesanan/baru` | `kasir.pesanan.baru` |
| POST | `/kasir/pesanan` | `kasir.pesanan.store` |
| GET | `/kasir/pesanan` | `kasir.pesanan.index` |
| GET | `/kasir/pesanan/poll` | `kasir.pesanan.poll` (`throttle:120,1`) |
| PATCH | `/kasir/pesanan/{order}/status` | `kasir.pesanan.status` |
| GET | `/kasir/menu` | `kasir.menu.index` |
| PATCH | `/kasir/menu/{menu}/availability` | `kasir.menu.availability` |

Test terkait:

- `tests/Feature/KasirOrderTest.php` — order tunai, nomor antrean, validasi
- `tests/Feature/KasirPesananMasukTest.php` — daftar dari DB, transisi status
- `tests/Feature/KasirPesananPollTest.php` — snapshot, `sejak`, role, validasi
- `tests/Feature/KasirMenuAvailabilityTest.php` — toggle ketersediaan

Yang **belum** dibuat di kasir karena menunggu QRIS:

- `GET /kasir/pesanan/{order}` (`kasir.pesanan.show`)
- `POST /kasir/pesanan/{order}/cancel` (`kasir.pesanan.cancel`)
- `POST /kasir/pesanan/{order}/check-status` (`kasir.pesanan.check-status`)

Catatan: tombol **Batalkan** di UI hanya dirender untuk order `menunggu_pembayaran` yang belum lunas. Setelah slice ini order seperti itu **sudah bisa ada** (dari checkout QRIS), tetapi tombolnya belum tersambung ke server. Tombol "Cek Status" demo sudah dihapus agar tidak ada simulasi pembayaran.

---

## 4. Yang baru selesai: checkout QRIS (`qris-checkout`)

Route aktif:

| Method | URI | Nama | Middleware |
|---|---|---|---|
| POST | `/pesan/checkout` | `pelanggan-b.checkout.store` | `throttle:checkout` (10/menit per IP) |

Berkas utama:

- `app/Http/Requests/PelangganB/StoreCheckoutRequest.php` — validasi `nama_pelanggan`, `tipe_pesanan`, `catatan`, `item[]`.
- `app/Services/OrderService::buatOrderQris()` — simpan order + item dalam `DB::transaction`, lalu catat `payments` dan panggil Snap.
- `app/Services/MidtransService.php` — HTTP client + Basic Auth, payload PRD §11.2, tanpa SDK.
- `app/Models/Payment.php` — model `payments` (pertama kali dipakai).
- `app/Http/Resources/CheckoutResource.php` — respons `201` sesuai `docs/ROUTES.md` §4.1.
- `app/Exceptions/MidtransException.php` — membawa order + status/kode galat untuk audit.
- `app/Providers/AppServiceProvider.php` — limiter `checkout`.
- `resources/js/pelanggan-b.js` — POST JSON + CSRF, state tombol, pesan galat, kosongkan keranjang, redirect ke `snap_redirect_url`.
- `tests/Feature/PelangganBCheckoutTest.php` — 11 test.

Perilaku penting:

- Order QRIS: `menunggu_pembayaran`, `status_pembayaran = menunggu`, `sumber = qr`, `cashier_id = null`, **tanpa nomor antrean**, `batas_waktu_pembayaran = +15 menit`.
- `payments` dicatat sebelum HTTP: `{kode}-P{n}`. Snap gagal → baris `failure`, order tetap `menunggu`, respons `500` + `data.kode_pesanan`.
- Kredensial belum diatur → `503` **sebelum** order dibuat.
- Harga dari browser diabaikan; total dari `menus.harga`.
- Keranjang `localStorage` dihapus setelah order berhasil dibuat (termasuk kasus Snap gagal) agar tidak ada pesanan ganda.
- `callbacks.finish` belum dikirim karena `/pesanan/{kode_pesanan}` belum ada.
- Halaman `/pesan/pembayaran` dan `/pesan/berhasil` masih sisa demo dan tidak lagi dipakai alur checkout. Ganti dengan `/pesanan/{kode_pesanan}` pada slice berikutnya.

---

## 5. Slice berikutnya yang disarankan: `qris-webhook`

**Tujuan:** status pembayaran menjadi benar-benar berasal dari Midtrans, dan nomor antrean dibuat saat lunas.

**Branch:** `qris-webhook`, dipotong dari `qris-checkout` setelah user commit.

**Kontrak:** `docs/ROUTES.md` §4.7, PRD §11.4–11.5, `docs/DATABASE.md` §7.2.

**Langkah inti:**

1. `MidtransNotificationController@handle` + route `POST /api/midtrans/notification` (`midtrans.notification`), tanpa auth, dengan pengecualian CSRF di `bootstrap/app.php` (`validateCsrfTokens(except: [...])`).
2. Verifikasi `SHA512(order_id + status_code + gross_amount + server_key)` memakai `hash_equals`.
3. Simpan payload ke `payment_notifications` (model baru) dengan `signature_valid` dan `status_pemrosesan`.
4. Cari `payments.midtrans_order_id`; normalisasi `gross_amount` dan bandingkan dengan `orders.total`.
5. Proses idempoten dalam `DB::transaction` + `lockForUpdate`: `settlement`/`capture+accept` → `lunas`, `status_pesanan = antre`, ambil nomor antrean lewat `OrderService` (refactor `ambilNomorAntrean` agar bisa dipakai ulang).
6. Pemetaan status lain sesuai `docs/DATABASE.md` §3.4: `pending`, `deny`/`cancel`/`failure`, `expire`, `refund`/`chargeback` → `perlu_diperiksa`.
7. Respons: `200` untuk diproses/diabaikan/order tidak ditemukan, `403` signature invalid, `500` hanya untuk kegagalan sementara.
8. Tambahkan `dibatalkan` ke `jumlah` pada `GET /kasir/pesanan/poll` bila pembatalan ikut dikerjakan.

**Acceptance criteria:**

- Notifikasi ganda tidak membuat dua nomor antrean (idempoten).
- Nominal tidak cocok → `perlu_diperiksa`, tanpa nomor antrean, tetap `200`.
- Signature salah → `403`, tidak ada perubahan data.
- `order_id` tidak dikenal → `200`, dicatat di `payment_notifications` sebagai `gagal`.
- Kasir melihat pesanan pindah ke tab Antre lewat polling tanpa reload.
- Test: lunas + nomor antrean, notifikasi ganda, nominal salah, signature salah, status `expire`/`deny`.

**Slice setelahnya (belum dikerjakan):**

- `pesanan-status`: `GET /pesanan/{order:kode_pesanan}` + `/status` (polling 5–10 detik), `POST .../bayar-lagi`, hapus halaman demo `/pesan/pembayaran` & `/pesan/berhasil`, pasang `callbacks.finish` ke halaman konfirmasi.
- `kasir-cancel`: `POST /kasir/pesanan/{order}/cancel` + `check-status`.
- `admin-crud`: CRUD menu/promosi, simpan informasi toko, laporan & dashboard dari DB.
- Rekonsiliasi terjadwal (PRD §11.7) dan penutupan otomatis 24 jam.

---

## 6. Keputusan yang masih menunggu user

| Topik | Status | Dampak |
|---|---|---|
| `O-16` URL publik & akun Midtrans | OPEN | Webhook tidak bisa diuji end-to-end. Kredensial sandbox juga belum ada di `.env`. |
| `409 "menu berubah"` pada checkout/order | Belum diputuskan | Saat ini semua konflik menu → `422` |
| `callbacks.finish` Snap | Sementara tidak dikirim | Ditambahkan saat halaman `/pesanan/{kode_pesanan}` ada |

---

## 7. Catatan lingkungan (penting, sudah pernah menimbulkan masalah)

- **MySQL XAMPP sempat rusak.** 28 Okt 2026 tabel sistem `proxies_priv`/`db`/`event` korup dan server menolak start. Sudah diperbaiki dengan `aria_chk` + membuat ulang `proxies_priv`. Backup ada di `C:\xampp\mysql\_perbaikan_20261028` (boleh dihapus setelah yakin).
- **MySQL kadang mati sendiri.** Selalu cek `Test-NetConnection 127.0.0.1 -Port 3306` sebelum menyimpulkan ada bug kode. Kalau mati, nyalakan lewat XAMPP Control Panel atau `C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini --standalone`.
- **Jangan `migrate:fresh` di `kedai_varya`.** Database itu berisi data uji user. Test memakai `kedai_varya_test`.
- **Kredensial Midtrans:** `.env` sudah berisi placeholder `MIDTRANS_*` kosong. Isi dengan kredensial sandbox dari akun Midtrans. Jangan menaruh nilainya di `.env.example`, dokumentasi, JS, atau commit. Tanpa `MIDTRANS_SERVER_KEY`, checkout menjawab `503`.
- **Windows/PowerShell:** `npm.ps1` diblokir Execution Policy — pakai `npm.cmd`. `git add -A` tidak aman di mesin ini; daftarkan file satu per satu.
- **`public/build` masuk `.gitignore`.** Setelah mengubah JS/CSS, jalankan `npm.cmd run build`; jangan commit hasil build.
- **Jangan commit/push.** User yang melakukan commit dan push. Agent berhenti setelah melaporkan dan memberi perintah commit.

---

## 8. Aturan singkat untuk sesi baru

1. Baca `AGENTS.md`, `PRD.md`, `docs/DECISIONS.md`, `docs/ROUTES.md`, `docs/DATABASE.md`.
2. Verifikasi baseline: `git log -1` dan `php artisan test` harus hijau sebelum menulis kode (saat file ini ditulis 84 passed).
3. Potong branch baru dari ujung terbaru (`qris-checkout` setelah di-commit), bukan dari `main`.
4. Kerjakan satu slice kecil; jangan mengerjakan seluruh backend sekaligus.
5. Tanpa dependency baru tanpa persetujuan user. Tanpa `localStorage` untuk data nyata. Tanpa simulasi pembayaran.
6. Setelah selesai: `php artisan test`, `php vendor/bin/pint --test`, `npm.cmd run build`, lalu perbarui `AGENTS.md`, `docs/ROUTES.md`, `docs/DECISIONS.md`, `docs/DATABASE.md` bila perlu, dan **file ini**.

---

## 9. Prompt siap tempel

```text
Lanjutkan proyek Kedai Kopi Varya. Mulai dengan membaca docs/HANDOFF.md,
lalu AGENTS.md, PRD.md, docs/DECISIONS.md, docs/ROUTES.md, docs/DATABASE.md.

Verifikasi baseline dulu: git log -1 dan php artisan test (harus hijau;
saat file ini ditulis 84 passed).
Kalau MySQL mati, nyalakan XAMPP dulu sebelum menyimpulkan ada bug kode.

Kerjakan slice yang tertulis di docs/HANDOFF.md §5 (qris-webhook), dipotong
dari branch qris-checkout. Ikuti pola yang sudah ada: FormRequest, Service,
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
| 29 Okt 2026 | `qris-checkout` | *belum di-commit* | Checkout QRIS + Snap + `throttle:checkout` |

---

## 11. Cara memperbarui file ini

Perbarui di commit yang sama dengan perubahan perilaku:

- Ganti **Terakhir diperbarui** dan hash commit.
- Perbarui tabel status dan daftar route jika ada yang berubah.
- Pindahkan slice yang selesai dari §5 ke §10.
- Tulis slice berikutnya di §5 beserta acceptance criteria.
