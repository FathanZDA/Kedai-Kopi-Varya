# HANDOFF.md — Titik Lanjut Pengerjaan

Dokumen ini adalah **ringkasan serah-terima antar sesi**. `AGENTS.md` tetap aturan kerja utama dan `PRD.md` tetap sumber kebenaran. File ini hanya menjawab tiga hal: *di mana kita sekarang*, *apa yang belum*, dan *apa langkah berikutnya*.

**Terakhir diperbarui:** 29 Okt 2026, setelah commit `b1cbb96`.

---

## 1. Status repo

| Item | Nilai |
|---|---|
| Branch aktif | `kasir-backend` @ `b1cbb96` (sudah di-push) |
| Baseline test | `php artisan test` → **72 passed** (292 assertions) |
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

---

## 2. Peta status per area

| Area | Status | Keterangan |
|---|---|---|
| Publik Pelanggan A | **Selesai** | Semua data dari database |
| Pelanggan B | **Sebagian** | Menu & toko dari DB; keranjang/checkout masih `localStorage`; belum ada pembayaran |
| Kasir | **Selesai (kecuali yang menunggu QRIS)** | Lihat §3 |
| Admin | **Sebagian** | Menu, kategori, promosi, toko dari DB; transaksi/laporan/CRUD aksi masih data contoh + `localStorage` |
| Login/role | **Selesai** | `/login`, redirect per role, `role` middleware, rate limit |
| Database transaksi | **Selesai** | `orders`, `order_items`, `payments`, `payment_notifications`, `queue_counters` |
| Pembayaran Midtrans | **Belum** | Belum ada paket, kredensial, Snap, webhook |

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

Catatan: tombol **Batalkan** di UI hanya dirender untuk order `menunggu_pembayaran` yang belum lunas, dan order seperti itu baru ada setelah QRIS aktif. Tombol "Cek Status" demo sudah dihapus agar tidak ada simulasi pembayaran.

---

## 4. Yang belum selesai di luar kasir

| Bagian | Yang belum | Bergantung pada |
|---|---|---|
| Pelanggan B | `POST /pesan/checkout`, halaman status pesanan, bayar lagi, hapus `localStorage` | Midtrans + backend order QRIS |
| Midtrans | Snap create transaction, webhook + verifikasi signature, `payments`, `payment_notifications` | Kredensial sandbox + tunnel |
| Admin | CRUD menu (`POST`/`PUT`/`DELETE`), CRUD promosi, simpan informasi toko, laporan, dashboard dari DB | Tidak ada, bisa dikerjakan paralel |
| Kasir | cancel & check-status | QRIS aktif |

---

## 5. Slice berikutnya yang disarankan: `qris-checkout`

**Tujuan:** Pelanggan B bisa checkout dan mendapat QRIS Midtrans; order tersimpan sebagai `menunggu_pembayaran` **tanpa** nomor antrean.

**Branch:** `qris-checkout`, dipotong dari `kasir-backend`.

**Kontrak:** `docs/ROUTES.md` §4.1 (`POST /pesan/checkout`, nama route `pelanggan-b.checkout.store`, `throttle:checkout`).

**Langkah inti:**

1. `StoreCheckoutRequest` — validasi `nama_pelanggan`, `tipe_pesanan` (`dine_in`/`take_away`), `catatan`, `item[]` (`menu_id`, `jumlah`).
2. `OrderService::buatOrderQris()` — dalam `DB::transaction`: validasi ulang menu dari database, snapshot `order_items`, `total` dari harga DB, `status_pesanan = menunggu_pembayaran`, `status_pembayaran = menunggu`, `sumber = qr`, `cashier_id = null`, `batas_waktu_pembayaran = now + 15 menit`.
3. `MidtransService` — panggil Snap lewat HTTP client Laravel (bukan SDK), simpan `payments` dengan `midtrans_order_id` unik per percobaan (`{kode_pesanan}-P{n}`).
4. `POST /pesan/checkout` mengembalikan `kode_pesanan`, `total`, status, `batas_waktu_pembayaran`, `snap_token`, `snap_redirect_url`.
5. Hapus keranjang `localStorage` di `pelanggan-b.js` setelah checkout sukses; jangan menyimpan data pembayaran di browser.

**Acceptance criteria:**

- Menu habis / tidak ada / `item` kosong → `422`, tidak ada order tersimpan.
- Total dihitung server; harga dari browser diabaikan.
- Nomor antrean **tidak** dibuat pada slice ini.
- `500` Snap gagal → order tetap tersimpan `menunggu` dan bisa dicoba lagi.
- Tamu bisa checkout (tanpa login), tetapi `throttle:checkout` aktif.
- Test: checkout sukses, menu habis, total, Snap gagal, rate limit.

**Risiko / yang harus diputuskan lebih dulu:**

- Kredensial `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION` harus ada di `.env` (jangan di kode/JS/`.env.example`).
- Keputusan OPEN `O-16`: tunnel publik (ngrok/cloudflared) untuk webhook.
- Pertanyaan lama: apakah error `409 "menu berubah"` mau diaktifkan? Butuh harga acuan di request; saat ini menu berubah/habis → `422`.

---

## 6. Keputusan yang masih menunggu user

| Topik | Status | Dampak |
|---|---|---|
| `O-16` URL publik & akun Midtrans | OPEN | Webhook tidak bisa diuji end-to-end |
| `409 "menu berubah"` pada checkout/order | Belum diputuskan | Saat ini semua konflik menu → `422` |

---

## 7. Catatan lingkungan (penting, sudah pernah menimbulkan masalah)

- **MySQL XAMPP sempat rusak.** 28 Okt 2026 tabel sistem `proxies_priv`/`db`/`event` korup dan server menolak start. Sudah diperbaiki dengan `aria_chk` + membuat ulang `proxies_priv`. Backup ada di `C:\xampp\mysql\_perbaikan_20261028` (boleh dihapus setelah yakin).
- **MySQL kadang mati sendiri.** Selalu cek `Test-NetConnection 127.0.0.1 -Port 3306` sebelum menyimpulkan ada bug kode. Kalau mati, nyalakan lewat XAMPP Control Panel atau `C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini --standalone`.
- **Jangan `migrate:fresh` di `kedai_varya`.** Database itu berisi data uji user (mis. pesanan `Jack` dan `Gibran` tanggal 28 Okt). Test memakai `kedai_varya_test`.
- **Windows/PowerShell:** `npm.ps1` diblokir Execution Policy — pakai `npm.cmd`. `git add -A` tidak aman di mesin ini; daftarkan file satu per satu.
- **`public/build` masuk `.gitignore`.** Setelah mengubah JS/CSS, jalankan `npm.cmd run build`; jangan commit hasil build.
- **Jangan commit/push.** User yang melakukan commit dan push. Agent berhenti setelah melaporkan dan memberi perintah commit.

---

## 8. Aturan singkat untuk sesi baru

1. Baca `AGENTS.md`, `PRD.md`, `docs/DECISIONS.md`, `docs/ROUTES.md`, `docs/DATABASE.md`.
2. Verifikasi baseline: `git log -1` dan `php artisan test` harus hijau sebelum menulis kode.
3. Potong branch baru dari `kasir-backend` (atau ujung terbaru), bukan dari `main`.
4. Kerjakan satu slice kecil; jangan mengerjakan seluruh backend sekaligus.
5. Tanpa dependency baru tanpa persetujuan user. Tanpa `localStorage` untuk data nyata.
6. Setelah selesai: `php artisan test`, `php vendor/bin/pint --test`, `npm.cmd run build`, lalu perbarui `AGENTS.md`, `docs/ROUTES.md`, `docs/DECISIONS.md`, dan **file ini**.

---

## 9. Prompt siap tempel

```text
Lanjutkan proyek Kedai Kopi Varya. Mulai dengan membaca docs/HANDOFF.md,
lalu AGENTS.md, PRD.md, docs/DECISIONS.md, docs/ROUTES.md, docs/DATABASE.md.

Verifikasi baseline dulu: git log -1 dan php artisan test (harus hijau;
saat file ini ditulis 72 passed).
Kalau MySQL mati, nyalakan XAMPP dulu sebelum menyimpulkan ada bug kode.

Kerjakan slice yang tertulis di docs/HANDOFF.md §5 (qris-checkout), dipotong
dari branch kasir-backend. Ikuti pola yang sudah ada: FormRequest, Service,
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

---

## 11. Cara memperbarui file ini

Perbarui di commit yang sama dengan perubahan perilaku:

- Ganti **Terakhir diperbarui** dan hash commit.
- Perbarui tabel status dan daftar route jika ada yang berubah.
- Pindahkan slice yang selesai dari §5 ke §10.
- Tulis slice berikutnya di §5 beserta acceptance criteria.
