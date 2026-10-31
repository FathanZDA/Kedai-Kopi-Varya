# HANDOFF.md — Titik Lanjut Pengerjaan

Dokumen ini adalah **ringkasan serah-terima antar sesi**. `AGENTS.md` tetap aturan kerja utama dan `PRD.md` tetap sumber kebenaran. File ini hanya menjawab tiga hal: *di mana kita sekarang*, *apa yang belum*, dan *apa langkah berikutnya*.

**Terakhir diperbarui:** 31 Okt 2026, setelah slice `qris-webhook` + `pesanan-status` (belum di-commit).

---

## 1. Status repo

| Item | Nilai |
|---|---|
| Branch aktif | `qris-webhook` (dipotong dari `qris-checkout` @ `450036c`, **belum di-commit**; working tree juga memuat slice `pesanan-status`) |
| Baseline test | `php artisan test` → **114 passed** (515 assertions) |
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
| `qris-webhook` | *belum di-commit* | Webhook Midtrans + nomor antrean saat lunas |
| `pesanan-status` | *menumpang di working tree `qris-webhook`, belum di-commit* | Popup Snap, halaman status/tiket, polling, dan bayar lagi |

---

## 2. Peta status per area

| Area | Status | Keterangan |
|---|---|---|
| Publik Pelanggan A | **Selesai** | Semua data dari database |
| Pelanggan B | **Sebagian** | Menu/toko dari DB; keranjang masih `localStorage`; checkout → order QRIS + Snap; webhook sudah menandai lunas; halaman status, tiket antrean, polling, dan bayar lagi sudah ada |
| Kasir | **Selesai (kecuali yang menunggu QRIS)** | Lihat §3 |
| Admin | **Sebagian** | Menu, kategori, promosi, toko dari DB; transaksi/laporan/CRUD aksi masih data contoh + `localStorage` |
| Login/role | **Selesai** | `/login`, redirect per role, `role` middleware, rate limit |
| Database transaksi | **Selesai** | `orders`, `order_items`, `payments`, `payment_notifications`, `queue_counters` |
| Pembayaran Midtrans | **Sebagian** | Snap create + webhook + nomor antrean + popup + halaman status/bayar lagi sudah; rekonsiliasi, cek status, dan pembatalan belum |

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

Yang **belum** dibuat di kasir:

- `GET /kasir/pesanan/{order}` (`kasir.pesanan.show`)
- `POST /kasir/pesanan/{order}/cancel` (`kasir.pesanan.cancel`)
- `POST /kasir/pesanan/{order}/check-status` (`kasir.pesanan.check-status`)

Catatan: tombol **Batalkan** di UI hanya dirender untuk order `menunggu_pembayaran` yang belum lunas. Setelah webhook aktif, order seperti itu bisa berubah menjadi lunas kapan saja; tombol batal masih belum tersambung ke server. Tombol "Cek Status" demo sudah dihapus agar tidak ada simulasi pembayaran.

---

## 4. Yang baru selesai: webhook QRIS (`qris-webhook`)

Route aktif:

| Method | URI | Nama | Middleware |
|---|---|---|---|
| POST | `/api/midtrans/notification` | `midtrans.notification` | Publik, tanpa CSRF, tanpa throttle ketat |

Berkas utama:

- `app/Http/Controllers/MidtransNotificationController.php` — validasi payload minimum, cek konfigurasi, verifikasi signature, pemetaan respons `200`/`403`/`500`.
- `app/Services/MidtransNotificationService.php` — verifikasi `SHA512`, pencatatan audit, pemrosesan idempoten dalam `DB::transaction`, pemetaan status, transisi order, deteksi pembayaran ganda.
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

## 5. Yang baru selesai: `pesanan-status`

**Status:** sudah diimplementasikan di working tree, **belum di-commit**. Branch `pesanan-status` disarankan dipotong dari `qris-webhook` setelah commit pertama.

**Kontrak:** `docs/ROUTES.md` §4.1–4.3, PRD §6.2 langkah 9–11, §8.4.3–6, §11.6.

**Yang sudah ada:**

1. Checkout QRIS membuka **popup Snap.js** (`snap.pay`) memakai `MIDTRANS_CLIENT_KEY` publik. Browser tidak lagi diarahkan ke `snap_redirect_url`. Setelah popup selesai/ditutup, pelanggan diarahkan ke halaman status di website ini.
2. `GET /pesanan/{order:kode_pesanan}` (`pesanan.status`) — halaman Blade server-side, tanpa login, kode acak 8 karakter. Menampilkan status, rincian item, total, dan tiket bernomor antrean setelah lunas.
3. `GET /pesanan/{order:kode_pesanan}/status` (`pesanan.status.json`) — JSON polling 5 detik, `throttle:order-status` 60/menit per IP. Hanya order `sumber = qr`; kode salah/order kasir → `404`.
4. `POST /pesanan/{order:kode_pesanan}/bayar-lagi` (`pesanan.bayar-lagi`) — memakai token percobaan aktif yang belum 15 menit; setelah gagal/kedaluwarsa membuat `payments` baru `-P{n+1}`. Order lunas/dibatalkan/perlu diperiksa → `409`.
5. `callbacks.finish` sudah dikirim ke `route('pesanan.status', $order)` sebagai fallback.
6. Route demo `GET /pesan/pembayaran` dan `GET /pesan/berhasil` sudah dihapus; `resources/views/pelanggan-b/berhasil.blade.php` sekarang menjadi halaman status. `pembayaran.blade.php` lama tidak dipakai.
7. Test baru: `tests/Feature/PesananStatusTest.php` (12 test) dan pengujian popup/client key di `PelangganBCheckoutTest`. Baseline total **114 passed** (515 assertions).

**Perilaku penting:**

- Nomor antrean tetap `null` sampai webhook menyatakan lunas; UI menampilkan `#--` dan "Menunggu Pembayaran" tanpa nomor.
- Parameter `finish`/`onSuccess` Snap tidak pernah menandai lunas. Status hanya dari webhook; halaman status melakukan polling sampai berubah lalu reload.
- Snap gagal saat checkout (`500`) tetap menyimpan order; browser diarahkan ke halaman status yang menampilkan tombol **Coba Bayar Lagi**.
- `MIDTRANS_CLIENT_KEY` kosong → checkout/bayar lagi menjawab `503`; `MIDTRANS_SERVER_KEY` kosong tetap membuat webhook `500` untuk dicoba ulang.
- Setelah 24 jam, pesanan yang belum dibayar otomatis `dibatalkan` (PRD §8.7.6) — slice terpisah bersama rekonsiliasi.

**Slice setelahnya (belum dikerjakan):**

- `kasir-cancel`: `POST /kasir/pesanan/{order}/cancel` (batalkan transaksi Midtrans atau tunggu kedaluwarsa) + `check-status` ke API Midtrans.
- `admin-crud`: CRUD menu/promosi, simpan informasi toko, laporan & dashboard dari DB.
- Rekonsiliasi terjadwal (PRD §11.7 / PG-08) dan penutupan otomatis 24 jam.

## 6. Keputusan yang masih menunggu user

| Topik | Status | Dampak |
|---|---|---|
| `O-16` akun/kredensial Midtrans | OPEN operasional (sandbox sudah diuji end-to-end) | URL ngrok berubah setiap restart; perbarui Notification URL Midtrans. Kredensial produksi belum disiapkan. Lihat `docs/MIDTRANS-SETUP.md`. |
| `409 "menu berubah"` pada checkout/order | Belum diputuskan | Saat ini semua konflik menu → `422` |
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
- **Checkout gagal setelah order dibuat:** order tetap `menunggu_pembayaran` dan keranjang browser sudah dihapus, sehingga klik berikutnya berbunyi “keranjang masih kosong”. Perbaiki penyebab Snap lalu buat order baru; order gagal lama dibiarkan sampai slice pembatalan/`bayar-lagi`.
- **Windows/PowerShell:** `npm.ps1` diblokir Execution Policy — pakai `npm.cmd`. `git add -A` tidak aman di mesin ini; daftarkan file satu per satu.
- **`public/build` masuk `.gitignore`.** Setelah mengubah JS/CSS, jalankan `npm.cmd run build`; jangan commit hasil build.
- **Jangan commit/push.** User yang melakukan commit dan push. Agent berhenti setelah melaporkan dan memberi perintah commit.

---

## 8. Aturan singkat untuk sesi baru

1. Baca `AGENTS.md`, `PRD.md`, `docs/DECISIONS.md`, `docs/ROUTES.md`, `docs/DATABASE.md`.
2. Verifikasi baseline: `git log -1` dan `php artisan test` harus hijau sebelum menulis kode (saat file ini ditulis 114 passed).
3. Potong branch baru dari ujung terbaru (`qris-webhook` dan `pesanan-status` setelah di-commit), bukan dari `main`.
4. Kerjakan satu slice kecil; jangan mengerjakan seluruh backend sekaligus.
5. Tanpa dependency baru tanpa persetujuan user. Tanpa `localStorage` untuk data nyata. Tanpa simulasi pembayaran.
6. Setelah selesai: `php artisan test`, `php vendor/bin/pint --test`, `npm.cmd run build`, lalu perbarui `AGENTS.md`, `docs/ROUTES.md`, `docs/DECISIONS.md`, `docs/DATABASE.md` bila perlu, dan **file ini**.

---

## 9. Prompt siap tempel

```text
Lanjutkan proyek Kedai Kopi Varya. Mulai dengan membaca docs/HANDOFF.md,
lalu AGENTS.md, PRD.md, docs/DECISIONS.md, docs/ROUTES.md, docs/DATABASE.md.

Verifikasi baseline dulu: git log -1 dan php artisan test (harus hijau;
saat file ini ditulis 114 passed).
Kalau MySQL mati, nyalakan XAMPP dulu sebelum menyimpulkan ada bug kode.

Kerjakan slice berikutnya yang tertulis di docs/HANDOFF.md §5, dipotong
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
| 30 Okt 2026 | `qris-webhook` | *belum di-commit* | Webhook Midtrans + nomor antrean saat lunas |
| 31 Okt 2026 | `pesanan-status` | *belum di-commit* | Popup Snap, halaman status/tiket, polling, bayar lagi |

---

## 11. Cara memperbarui file ini

Perbarui di commit yang sama dengan perubahan perilaku:

- Ganti **Terakhir diperbarui** dan hash commit.
- Perbarui tabel status dan daftar route jika ada yang berubah.
- Pindahkan slice yang selesai dari §5 ke §10.
- Tulis slice berikutnya di §5 beserta acceptance criteria.
