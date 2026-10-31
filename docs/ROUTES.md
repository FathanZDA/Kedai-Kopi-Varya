# Kontrak Route & API — Kedai Kopi Varya

**Status:** FINAL untuk route inti; O-16 (akun/kredensial Midtrans) masih OPEN
**Terakhir diperbarui:** 31 Oktober 2026
**Acuan:** `PRD.md` §4.3, §7, §11; `docs/DECISIONS.md`; `routes/web.php`

> Route autentikasi, transaksi kasir, checkout QRIS, webhook Midtrans, serta halaman status pesanan dan pembayaran ulang sudah aktif. Rekonsiliasi, pembatalan, laporan, dan CRUD admin belum aktif.

---

## 1. Route yang sudah ada

Route publik (Pelanggan A/B) tetap terbuka. Route kasir dan admin sudah dilindungi `auth` + `role`, sehingga tamu diarahkan ke `/login` dan role yang salah mendapat `403`.

| Method | URI | Nama Route | Controller | Catatan |
|---|---|---|---|---|
| GET | `/login` | `login` | `AuthController@create` | `guest`; login bersama kasir/admin |
| POST | `/login` | `login.store` | `AuthController@store` | `guest`; redirect sesuai role |
| POST | `/logout` | `logout` | `AuthController@destroy` | `auth` |
| GET | `/` | `pelanggan-a.beranda` | `PelangganAController@beranda` | Publik |
| GET | `/tentang` | `pelanggan-a.tentang` | `PelangganAController@tentang` | Publik |
| ANY | `/tentang-kami` | — | Redirect 301 ke `/tentang` | Kompatibilitas URI lama |
| GET | `/menu` | `pelanggan-a.menu` | `PelangganAController@menu` | Publik |
| GET | `/lokasi` | `pelanggan-a.lokasi` | `PelangganAController@lokasi` | Publik |
| GET | `/pesan` | `pelanggan-b.beranda` | `PelangganBController@beranda` | Mode pesan QR |
| GET | `/pesan/menu/{menu:slug}` | `pelanggan-b.produk` | `PelangganBController@produk` | Detail produk; tambahan UI |
| GET | `/pesan/keranjang` | `pelanggan-b.keranjang` | `PelangganBController@keranjang` | Masih client-side |
| GET | `/pesan/checkout` | `pelanggan-b.checkout` | `PelangganBController@checkout` | Form checkout; `POST` sudah ada |
| POST | `/pesan/checkout` | `pelanggan-b.checkout.store` | `PelangganBController@storeCheckout` | Publik; `throttle:checkout`; order QRIS + Snap |
| GET | `/pesanan/{order:kode_pesanan}` | `pesanan.status` | `PesananController@show` | Publik; kode acak 8 karakter; hanya order QRIS |
| GET | `/pesanan/{order:kode_pesanan}/status` | `pesanan.status.json` | `PesananController@status` | Publik; polling; `throttle:order-status` |
| POST | `/pesanan/{order:kode_pesanan}/bayar-lagi` | `pesanan.bayar-lagi` | `PesananController@bayarLagi` | Publik; `throttle:checkout`; token Snap |
| POST | `/api/midtrans/notification` | `midtrans.notification` | `MidtransNotificationController@handle` | Publik; tanpa CSRF; verifikasi signature; satu-satunya jalur status lunas QRIS |
| ANY | `/kasir` | — | Redirect 301 ke `/kasir/pesanan/baru` | `auth` + `role:kasir` |
| GET | `/kasir/pesanan/baru` | `kasir.pesanan.baru` | `KasirController@buatPesanan` | `auth` + `role:kasir`; menu dari DB |
| POST | `/kasir/pesanan` | `kasir.pesanan.store` | `KasirController@storeOrder` | `auth` + `role:kasir`; order tunai langsung lunas + nomor antrean |
| GET | `/kasir/pesanan` | `kasir.pesanan.index` | `KasirController@pesananMasuk` | `auth` + `role:kasir`; pesanan hari ini dari DB |
| PATCH | `/kasir/pesanan/{order}/status` | `kasir.pesanan.status` | `KasirController@updateStatus` | `auth` + `role:kasir`; `antre → diproses → selesai` |
| GET | `/kasir/menu` | `kasir.menu.index` | `KasirController@kelolaMenu` | `auth` + `role:kasir`; menu dari DB |
| PATCH | `/kasir/menu/{menu}/availability` | `kasir.menu.availability` | `KasirController@updateAvailability` | `auth` + `role:kasir`; hanya `tersedia` |
| ANY | `/admin` | — | Redirect 301 ke `/admin/dashboard` | `auth` + `role:admin` |
| GET | `/admin/dashboard` | `admin.dashboard` | `AdminController@dashboard` | `auth` + `role:admin`; transaksi contoh |
| GET | `/admin/menu` | `admin.menu.index` | `AdminController@menus` | `auth` + `role:admin`; menu dari DB |
| GET | `/admin/menu/create` | `admin.menu.create` | `AdminController@createMenu` | `auth` + `role:admin`; belum ada `POST` |
| GET | `/admin/menu/{menu}/edit` | `admin.menu.edit` | `AdminController@editMenu` | `auth` + `role:admin`; belum ada `PUT` |
| GET | `/admin/laporan` | `admin.reports` | `AdminController@reports` | `auth` + `role:admin`; data contoh |
| GET | `/admin/promosi` | `admin.promos` | `AdminController@promos` | `auth` + `role:admin`; CRUD demo |
| GET | `/admin/toko` | `admin.store` | `AdminController@store` | `auth` + `role:admin`; simpan demo |

Belum ada:

- Route `POST`/`PATCH`/`DELETE` untuk transaksi dan CRUD selain `POST /kasir/pesanan`, `PATCH /kasir/pesanan/{order}/status`, `PATCH /kasir/menu/{menu}/availability`, `POST /pesan/checkout`, dan `POST /pesanan/{kode_pesanan}/bayar-lagi`.
- Rekonsiliasi, pembatalan, cek status pembayaran, laporan, dan CRUD admin.
- Route detail pesanan kasir dan pembatalan.
- Rate limiting untuk endpoint lain di luar login, polling kasir, checkout, dan status pesanan.


---

## 2. Route target

Nama route yang sudah ada dipertahankan. Yang dirapikan adalah URI dan penambahan action.

### 2.1 Auth

| Method | URI | Nama Route | Controller | Middleware |
|---|---|---|---|---|
| GET | `/login` | `login` | `AuthController@create` | `guest` |
| POST | `/login` | `login.store` | `AuthController@store` | `guest`, rate limit 5/menit |
| POST | `/logout` | `logout` | `AuthController@destroy` | `auth` |

Setelah login:

- `role = kasir` → redirect `/kasir/pesanan/baru`.
- `role = admin` → redirect `/admin/dashboard`.

Tidak ada registrasi publik. Akun dibuat lewat `DatabaseSeeder` (admin dan kasir). Password disimpan sebagai hash. Rate limit login (5 percobaan/menit per kombinasi email + IP) ditangani `LoginRequest` agar pesan gagal tampil di halaman login, bukan halaman 429.


### 2.2 Publik / Pelanggan A

| Method | URI | Nama Route | Controller | Middleware |
|---|---|---|---|---|
| GET | `/` | `pelanggan-a.beranda` | `PelangganAController@beranda` | — |
| GET | `/menu` | `pelanggan-a.menu` | `PelangganAController@menu` | — |
| GET | `/tentang` | `pelanggan-a.tentang` | `PelangganAController@tentang` | — |
| GET | `/lokasi` | `pelanggan-a.lokasi` | `PelangganAController@lokasi` | — |

Saat mengganti `/tentang-kami` menjadi `/tentang`, sediakan redirect 301 dari URI lama.

### 2.3 Pelanggan B

| Method | URI | Nama Route | Controller | Middleware |
|---|---|---|---|---|
| GET | `/pesan` | `pelanggan-b.beranda` | `PelangganBController@beranda` | — |
| GET | `/pesan/menu/{menu:slug}` | `pelanggan-b.produk` | `PelangganBController@produk` | — |
| GET | `/pesan/keranjang` | `pelanggan-b.keranjang` | `PelangganBController@keranjang` | — |
| GET | `/pesan/checkout` | `pelanggan-b.checkout` | `PelangganBController@checkout` | — |
| POST | `/pesan/checkout` | `pelanggan-b.checkout.store` | `PelangganBController@storeCheckout` | `throttle:checkout` |
| GET | `/pesanan/{order:kode_pesanan}` | `pesanan.status` | `PesananController@show` | - |
| GET | `/pesanan/{order:kode_pesanan}/status` | `pesanan.status.json` | `PesananController@status` | `throttle:order-status` |
| POST | `/pesanan/{order:kode_pesanan}/bayar-lagi` | `pesanan.bayar-lagi` | `PesananController@bayarLagi` | `throttle:checkout` |

Catatan:

- `/pesanan/{order:kode_pesanan}` menggantikan halaman demo `/pesan/pembayaran` dan `/pesan/berhasil`; kedua route demo sudah dihapus.
- Halaman ini adalah halaman konfirmasi pembayaran, **bukan** fitur pelacakan.
- Route model binding memakai `kode_pesanan`, bukan `id`; route hanya menerima pola 8 karakter `[A-Z0-9]` dan menolak order non-QRIS dengan `404`.
- `/pesan/menu/{menu:slug}` adalah tambahan UI untuk detail produk; tidak menambah aturan bisnis baru.
- `POST /pesan/checkout` menggantikan simulasi di `resources/js/pelanggan-b.js`.

### 2.4 Kasir

Semua route memakai middleware `auth` + `role:kasir`.

| Method | URI | Nama Route | Controller | Catatan |
|---|---|---|---|---|
| GET | `/kasir` | `kasir.` | redirect | Ke `/kasir/pesanan/baru` |
| GET | `/kasir/pesanan/baru` | `kasir.pesanan.baru` | `KasirController@buatPesanan` | Halaman awal |
| POST | `/kasir/pesanan` | `kasir.pesanan.store` | `KasirController@storeOrder` | Buat order tunai |
| GET | `/kasir/pesanan` | `kasir.pesanan.index` | `KasirController@pesananMasuk` | Daftar pesanan |
| GET | `/kasir/pesanan/poll` | `kasir.pesanan.poll` | `KasirController@pollOrders` | JSON polling; daftarkan **sebelum** `{order}` |
| GET | `/kasir/pesanan/{order}` | `kasir.pesanan.show` | `KasirController@showOrder` | Detail |
| PATCH | `/kasir/pesanan/{order}/status` | `kasir.pesanan.status` | `KasirController@updateStatus` | `antre → diproses → selesai` |
| POST | `/kasir/pesanan/{order}/cancel` | `kasir.pesanan.cancel` | `KasirController@cancelOrder` | Hanya belum dibayar |
| POST | `/kasir/pesanan/{order}/check-status` | `kasir.pesanan.check-status` | `KasirController@checkPaymentStatus` | Cek ke Midtrans |
| GET | `/kasir/menu` | `kasir.menu.index` | `KasirController@kelolaMenu` | Daftar menu |
| PATCH | `/kasir/menu/{menu}/availability` | `kasir.menu.availability` | `KasirController@updateAvailability` | Hanya status tersedia/habis |

Catatan:

- `GET /kasir/pesanan/poll` harus didaftarkan sebelum `GET /kasir/pesanan/{order}`.
- Kasir tidak punya route untuk mengubah item, harga, atau menandai QRIS lunas.
- `POST /kasir/pesanan` hanya menerima `metode_pembayaran = tunai`; QRIS ditolak.

Status implementasi 29 Okt 2026:

- `GET /kasir/pesanan/baru`, `POST /kasir/pesanan`, `GET /kasir/pesanan`, `GET /kasir/pesanan/poll`, `PATCH /kasir/pesanan/{order}/status`, `GET /kasir/menu`, dan `PATCH /kasir/menu/{menu}/availability` **sudah aktif**.
- `GET /kasir/pesanan/{order}`, `POST .../cancel`, dan `POST .../check-status` **belum dibuat**.
- Daftar pesanan memuat order yang dibuat pada hari berjalan (`created_at` hari ini, zona `Asia/Jakarta`), supaya order QRIS yang belum dibayar tetap tampil di tab Belum Dibayar meski belum punya nomor antrean.

### 2.5 Admin

Semua route memakai middleware `auth` + `role:admin`.

| Method | URI | Nama Route | Controller | Catatan |
|---|---|---|---|---|
| GET | `/admin/dashboard` | `admin.dashboard` | `AdminController@dashboard` | Ganti URI dari `/admin` |
| GET | `/admin/menu` | `admin.menu.index` | `AdminController@menus` | |
| GET | `/admin/menu/create` | `admin.menu.create` | `AdminController@createMenu` | |
| POST | `/admin/menu` | `admin.menu.store` | `AdminController@storeMenu` | |
| GET | `/admin/menu/{menu}/edit` | `admin.menu.edit` | `AdminController@editMenu` | |
| PUT/PATCH | `/admin/menu/{menu}` | `admin.menu.update` | `AdminController@updateMenu` | |
| DELETE | `/admin/menu/{menu}` | `admin.menu.destroy` | `AdminController@destroyMenu` | Soft delete |
| GET | `/admin/laporan` | `admin.reports` | `AdminController@reports` | Nama route lama dipertahankan |
| GET | `/admin/laporan/{order}` | `admin.reports.show` | `AdminController@showReportOrder` | Detail transaksi |
| GET | `/admin/promosi` | `admin.promos` | `AdminController@promos` | |
| POST | `/admin/promosi` | `admin.promos.store` | `AdminController@storePromo` | |
| PUT/PATCH | `/admin/promosi/{promo}` | `admin.promos.update` | `AdminController@updatePromo` | |
| DELETE | `/admin/promosi/{promo}` | `admin.promos.destroy` | `AdminController@destroyPromo` | |
| GET | `/admin/toko` | `admin.store` | `AdminController@store` | |
| PUT | `/admin/toko` | `admin.store.update` | `AdminController@updateStore` | |

Pertahankan `GET /admin` sebagai redirect ke `/admin/dashboard` agar bookmark lama tetap bekerja.

### 2.6 Webhook Midtrans

| Method | URI | Nama Route | Controller | Middleware |
|---|---|---|---|---|
| POST | `/api/midtrans/notification` | `midtrans.notification` | `MidtransNotificationController@handle` | Tanpa auth, tanpa CSRF, verifikasi signature |

Keputusan: route didaftarkan di `routes/web.php` dengan pengecualian CSRF untuk path ini. Tidak menambah `routes/api.php` atau Sanctum.

Catatan:

- Endpoint harus dapat diakses publik lewat HTTPS/tunnel saat pengujian.
- Jangan mengandalkan halaman `finish` Snap untuk menandai lunas.
- Jangan menaruh `server_key` di frontend.

---

## 3. Middleware dan keamanan

| Middleware | Dipakai di | Fungsi |
|---|---|---|
| `auth` | Kasir, admin, logout | Wajib login |
| `role:kasir` | Semua `/kasir/*` | Hanya kasir |
| `role:admin` | Semua `/admin/*` | Hanya admin |
| `guest` | Login | Hanya tamu |
| `throttle:login` | `POST /login` | Cegah brute force (implementasi: `RateLimiter` di `LoginRequest`, 5/menit per email + IP) |
| `throttle:checkout` | Checkout dan retry | Cegah spam order |
| `throttle:order-status` | Halaman/status pesanan | Cegah polling berlebihan |
| CSRF exception | Webhook | Midtrans tidak mengirim token CSRF |

Batas rate yang disarankan:

- Login: 5 percobaan/menit per IP+email.
- Checkout: 10/menit per IP.
- Status pesanan: 60/menit per IP.
- Webhook: jangan throttle ketat; andalkan signature dan validasi.

---

## 4. Kontrak JSON

Format sukses:

```json
{
  "data": {}
}
```

Format error validasi:

```json
{
  "message": "Keranjang tidak valid.",
  "errors": {
    "item.0.menu_id": ["Menu sudah habis."]
  }
}
```

`message` dan `errors` mengikuti Laravel. Field domain memakai bahasa Indonesia.

Kode HTTP:

| Kode | Arti |
|---|---|
| 200 | Sukses |
| 201 | Dibuat |
| 401 | Belum login |
| 403 | Tidak punya akses / signature invalid |
| 404 | Data tidak ditemukan |
| 409 | Konflik status (mis. percobaan pembayaran masih aktif) |
| 422 | Validasi gagal |
| 429 | Terlalu banyak permintaan |
| 500 | Gagal sementara |

### 4.1 `POST /pesan/checkout`

Request:

```json
{
  "nama_pelanggan": "Budi",
  "tipe_pesanan": "dine_in",
  "catatan": "Tanpa es",
  "item": [
    {
      "menu_id": 12,
      "jumlah": 2
    }
  ]
}
```

Response `201`:

```json
{
  "data": {
    "kode_pesanan": "V7K2M9QX",
    "total": 45000,
    "status_pembayaran": "menunggu",
    "status_pesanan": "menunggu_pembayaran",
    "batas_waktu_pembayaran": "2026-10-25T21:15:00+07:00",
    "snap_token": "snap-token",
    "snap_redirect_url": "https://app.sandbox.midtrans.com/snap/v4/redirection/..."
  }
}
```

Error penting:

- `422` — menu habis, harga berubah, jumlah tidak valid.
- `429` — terlalu banyak checkout.
- `500` — Snap gagal; order tetap tersimpan `menunggu` dan bisa dicoba lagi.

Catatan: catatan per item hanya dipakai pada alur kasir; checkout Pelanggan B memakai `orders.catatan` untuk catatan pesanan.

Status 31 Okt 2026: **sudah diimplementasikan** di `PelangganBController@storeCheckout` + `App\Http\Requests\PelangganB\StoreCheckoutRequest` + `App\Services\OrderService::buatOrderQris` + `App\Services\MidtransService` + `App\Http\Resources\CheckoutResource`.

Perilaku implementasi yang perlu diketahui konsumen endpoint:

- `POST /pesan/checkout` terbuka untuk tamu dan dibatasi `throttle:checkout` (10 permintaan/menit per IP).
- Order + item disimpan lebih dulu dalam `DB::transaction`: `sumber = qr`, `metode_pembayaran = qris`, `status_pesanan = menunggu_pembayaran`, `status_pembayaran = menunggu`, `cashier_id = null`, `batas_waktu_pembayaran = sekarang + 15 menit`, **tanpa** `nomor_antrean`/`tanggal_antrean`.
- Harga, subtotal, dan total selalu dihitung dari `menus.harga`; nilai harga/total dari browser diabaikan.
- Menu habis, menu sudah di-soft-delete, atau `item` kosong menghasilkan `422` pada field `item` dan tidak ada order/payments yang tersimpan. Panggilan ke Midtrans juga tidak dilakukan.
- Bila `MIDTRANS_SERVER_KEY` atau `MIDTRANS_CLIENT_KEY` belum diatur, endpoint menjawab `503` **sebelum** order dibuat, supaya tidak ada order yatim yang tidak bisa dibayar.
- Bila Snap gagal (respons non-2xx, tanpa `token`/`redirect_url`, atau gangguan jaringan), endpoint menjawab `500` dengan `data.kode_pesanan`. Order tetap tersimpan sebagai `menunggu_pembayaran`.
- Setiap panggilan Snap memakai baris `payments` baru: `midtrans_order_id = {kode_pesanan}-P{n}`. Baris dicatat **sebelum** HTTP call agar nomor percobaan tidak dipakai ulang saat pembayaran ulang (PRD §8.4.4). Percobaan yang gagal diubah menjadi `status_transaksi = failure` dengan `status_code` dari Midtrans (atau `500`) dan `raw_response` berisi galat; `snap_token`/`snap_redirect_url` tetap `null`.
- Respons `201` mengikuti kontrak: `kode_pesanan`, `total`, `status_pesanan`, `status_pembayaran`, `batas_waktu_pembayaran`, `snap_token`, `snap_redirect_url`.
- `callbacks.finish` dikirim ke `route('pesanan.status', $order)`. Popup Snap memakai `snap_token` dan **tidak** mengarahkan browser ke `snap_redirect_url`; pelanggan tetap berada di website ini.
- `resources/js/pelanggan-b.js` mengirim JSON + header `X-CSRF-TOKEN`, menonaktifkan tombol saat menyimpan, menampilkan pesan `422`/`429`/`503`, membuka popup Snap setelah `201`, mengosongkan `localStorage` keranjang, lalu mengarahkan browser ke `/pesanan/{kode}` saat popup selesai/ditutup. Bila Snap gagal (`500`), browser juga diarahkan ke halaman status agar bisa mencoba bayar lagi.
- Belum ada simulasi yang menandai pesanan lunas. Status lunas hanya boleh berasal dari webhook; halaman status melakukan polling ke §4.2 sampai status berubah.

### 4.2 `GET /pesanan/{kode_pesanan}/status`

Response `200`:

```json
{
  "data": {
    "kode_pesanan": "V7K2M9QX",
    "status_pembayaran": "lunas",
    "status_pesanan": "antre",
    "nomor_antrean": "007",
    "total": 45000,
    "waktu_lunas": "2026-10-25T21:03:12+07:00",
    "batas_waktu_pembayaran": null,
    "dapat_bayar_ulang": false
  }
}
```

Catatan:

- Jangan mengembalikan data sensitif seperti `server_key`.
- `nomor_antrean` hanya ada setelah lunas.
- Halaman konfirmasi melakukan polling ke endpoint ini.

Status 31 Okt 2026: **sudah diimplementasikan** di `PesananController@status` + `App\Http\Resources\PesananStatusResource`, dengan `throttle:order-status` 60 permintaan/menit per IP. Kode harus 8 karakter `[A-Z0-9]` dan order harus `sumber = qr`; selain itu `404`. `dapat_bayar_ulang` bernilai `false` bila `MIDTRANS_CLIENT_KEY` tidak tersedia.

### 4.3 `POST /pesanan/{kode_pesanan}/bayar-lagi`

Response `200`:

```json
{
  "data": {
    "kode_pesanan": "V7K2M9QX",
    "snap_token": "snap-token",
    "snap_redirect_url": "https://app.sandbox.midtrans.com/snap/v4/redirection/...",
    "batas_waktu_pembayaran": "2026-10-25T21:30:00+07:00"
  }
}
```

Jika masih ada percobaan aktif, kembalikan URL yang sama dengan `200`; jangan buat transaksi baru.
Jika tidak ada percobaan aktif dan status bukan `gagal`/`kedaluwarsa`, kembalikan `409`.

Status 31 Okt 2026: **sudah diimplementasikan** di `OrderService::bayarLagi` + `PesananController@bayarLagi` + `App\Http\Resources\PembayaranUlangResource`.

- Percobaan `pending` yang punya token dan berumur < 15 menit dikembalikan apa adanya dengan `200`; tidak ada HTTP call baru.
- Percobaan `pending` yang sudah lewat 15 menit ditandai `status_transaksi = kedaluwarsa` secara lokal, lalu percobaan baru `-P{n+1}` dibuat. Webhook tetap sumber kebenaran bila notifikasinya datang.
- Percobaan baru hanya dibuat bila percobaan terakhir `failure`/`deny`/`cancel`/`expire`/`kedaluwarsa`; order `lunas`, `dibatalkan`, `perlu_diperiksa`, atau percobaan `pending` yang masih berjalan menghasilkan `409`.
- `503` bila `MIDTRANS_CLIENT_KEY` tidak tersedia; `500` bila Snap gagal (order tetap `menunggu` dan percobaan baru ditandai `failure`).
- Payload Snap pembayaran ulang juga memuat `callbacks.finish` ke halaman status.

### 4.4 `POST /kasir/pesanan`

Request:

```json
{
  "nama_pelanggan": "Budi",
  "tipe_pesanan": "dine_in",
  "catatan": "",
  "item": [
    {
      "menu_id": 12,
      "jumlah": 2,
      "catatan": "Tanpa gula"
    }
  ],
  "uang_diterima": 50000
}
```

Response `201`:

```json
{
  "data": {
    "kode_pesanan": "V7K2M9QX",
    "nomor_antrean": "007",
    "total": 45000,
    "uang_diterima": 50000,
    "kembalian": 5000
  }
}
```

Error penting:

- `422` — `uang_diterima < total`, menu habis, item kosong.
- `403` — bukan kasir.
- `409` — menu berubah saat transaksi disimpan.

Catatan: `catatan` per item opsional dan disimpan di `order_items.catatan`; order tunai juga mengisi `orders.cashier_id` dari sesi login.

Status 27 Okt 2026: **sudah diimplementasikan** di `KasirController@storeOrder` + `App\Services\OrderService::buatOrderTunai` + `App\Http\Resources\OrderResource`.

Hal yang perlu diketahui konsumen endpoint ini:

- `nomor_antrean` dikirim sebagai string tiga digit (`"007"`), mengikuti contoh pada kontrak di atas.
- Respons berisi field tambahan di luar contoh (`tipe_pesanan`, `catatan`, `sumber`, label status, `waktu_lunas`, `item`) agar dialog konfirmasi kasir tidak perlu request kedua. Semua field contoh tetap ada, jadi kontrak lama tetap terpenuhi. Daftar item memakai kunci `item` agar sama dengan request.
- Harga dan subtotal **tidak** dikirim klien dan diabaikan bila ada; server selalu memakai harga dari `menus.harga`.
- Error `409` (menu berubah saat transaksi disimpan) **belum** bisa dihasilkan karena request tidak membawa harga pembanding. Saat ini menu yang hilang atau habis menghasilkan `422` dengan field `item`. Jika Anda ingin `409` aktif, request perlu ditambah harga acuan per item — keputusan ini menunggu persetujuan.

### 4.5 `GET /kasir/pesanan/poll`

Query opsional: `?sejak=2026-10-25T21:00:00+07:00`

Response `200`:

```json
{
  "data": {
    "waktu_server": "2026-10-25T21:05:00+07:00",
    "jumlah": {
      "belum_dibayar": 2,
      "antre": 3,
      "diproses": 1,
      "selesai": 5
    },
    "pesanan": []
  }
}
```

Catatan:

- Polling setiap 5–10 detik.
- Jangan mengirim seluruh riwayat; gunakan `sejak` atau filter tab.
- Pastikan kasir hanya melihat data yang relevan.

Perilaku implementasi 29 Okt 2026 (`KasirController@pollOrders` + `PollOrdersRequest`):

- Tanpa `sejak`: mengirim seluruh pesanan **hari ini** (bukan riwayat semua hari). Filter memakai `orders.created_at` hari berjalan, sama dengan halaman Pesanan Masuk.
- Dengan `sejak`: hanya mengirim pesanan yang `updated_at >= sejak`. Perbandingan `>=` dipakai karena kolom timestamp MySQL berpresisi detik; satu baris terkirim ulang lebih aman daripada melewatkan perubahan status pada detik yang sama.
- `waktu_server` diambil **sebelum** query dijalankan, sehingga baris yang berubah saat query berjalan tetap terkirim pada polling berikutnya. Klien memakai nilai ini sebagai `sejak` berikutnya, bukan jam perangkat.
- `jumlah` selalu berisi hitungan penuh hari ini, termasuk saat `pesanan` kosong, supaya badge tab tetap benar.
- `sejak` yang tidak bisa diparse menghasilkan `422` pada field `sejak`.
- Route diberi `throttle:120,1` (120 permintaan/menit per pengguna) — cukup untuk polling 7 detik di beberapa tab.
- Bentuk tiap item `pesanan` sama dengan `data-orders-data` pada halaman Pesanan Masuk, sehingga klien bisa menggabungkan tanpa transformasi.
- `midtrans_id` diisi dari `payments.midtrans_order_id` percobaan terakhir (mis. `V7K2M9QX-P1`) dan `null` untuk order tunai. Nomor antrean `null` selama order masih `menunggu_pembayaran`.
- Belum ada `dibatalkan` di `jumlah`; akan ditambahkan saat pembatalan QRIS dibuat.

### 4.6 `PATCH /kasir/pesanan/{order}/status`

Request:

```json
{
  "status": "diproses"
}
```

Response `200`:

```json
{
  "data": {
    "kode_pesanan": "V7K2M9QX",
    "nomor_antrean": "007",
    "status_pesanan": "diproses",
    "status_pesanan_label": "Diproses"
  }
}
```

Aturan:

- Hanya transisi `antre → diproses` dan `diproses → selesai` yang diterima.
- Transisi lain (melompati tahap, mengubah pesanan `selesai`, atau pesanan `menunggu_pembayaran`) ditolak `422` dengan error pada field `status`.
- `{order}` adalah id numerik order, bukan `kode_pesanan`.
- Perubahan status berjalan di dalam `DB::transaction` + `lockForUpdate` agar dua kasir tidak mengubah bersamaan.
- Error penting: `401` tamu, `403` bukan kasir, `404` order tidak ada, `422` transisi tidak sah.

Status 28 Okt 2026: **sudah diimplementasikan** di `KasirController@updateStatus` + `App\Services\OrderService::ubahStatus` + `App\Http\Requests\Kasir\UpdateOrderStatusRequest`. Respons memakai `OrderResource` sehingga berisi field tambahan di luar contoh (`total`, `uang_diterima`, `kembalian`, `item`, label lain) — semua field contoh tetap ada.

### 4.7 `POST /api/midtrans/notification`

Payload contoh (key mengikuti Midtrans, bukan bahasa Indonesia):

```json
{
  "transaction_time": "2026-10-25 21:03:12",
  "transaction_status": "settlement",
  "transaction_id": "abc-123",
  "status_code": "200",
  "signature_key": "sha512...",
  "payment_type": "other_qris",
  "order_id": "V7K2M9QX-P1",
  "gross_amount": "45000.00",
  "fraud_status": "accept",
  "status_message": "midtrans payment notification"
}
```

Response:

- `200` — diproses, diabaikan, atau order tidak ditemukan (tetap 200 agar Midtrans tidak mengulang tanpa henti; catat log).
- `403` — signature tidak valid.
- `500` — gagal sementara (database tidak tersedia); Midtrans boleh mengulang.

Aturan:

- Verifikasi `SHA512(order_id + status_code + gross_amount + server_key)`.
- Bandingkan `order_id` dengan `payments.midtrans_order_id`.
- Normalisasi `gross_amount` dan bandingkan dengan `orders.total`.
- Proses idempoten; notifikasi ganda tidak boleh membuat nomor antrean ganda.
- Simpan payload ke `payment_notifications`.

Status 30 Okt 2026: **sudah diimplementasikan** di `MidtransNotificationController@handle` + `App\Services\MidtransNotificationService` + `App\Models\PaymentNotification`, dengan pengecualian CSRF `api/midtrans/notification` di `bootstrap/app.php`. Nomor antrean memakai `App\Services\QueueNumberService` yang juga dipakai order tunai kasir.

Perilaku implementasi:

- Payload wajib memuat `order_id`, `status_code`, `gross_amount`, `transaction_status`, dan `signature_key`. Bila tidak lengkap: `200` + log, tanpa baris audit (payload rusak tidak akan membaik bila diulang).
- `MIDTRANS_SERVER_KEY` kosong: `500` + log, tanpa perubahan data. Midtrans boleh mengulang setelah kredensial diisi.
- Signature salah: `403` + log; payload **tidak** disimpan agar request palsu tidak membanjiri `payment_notifications`.
- `order_id` tidak cocok dengan `payments.midtrans_order_id`: `200`, dicatat `gagal`.
- Nominal tidak cocok: `200`, order `perlu_diperiksa` (kecuali sudah lunas), tanpa nomor antrean, dicatat `diabaikan`.
- `settlement` / `capture+accept`: order `lunas`, `status_pesanan = antre`, nomor antrean dibuat bila belum ada.
- Notifikasi ganda dari percobaan yang sama: `200`, tidak menambah nomor antrean.
- `settlement` dari percobaan berbeda pada order yang sudah lunas: order `perlu_diperiksa`, nomor antrean lama dipertahankan (pembayaran kedua).
- `settlement` setelah pembatalan lokal: `perlu_diperiksa`, tanpa nomor antrean.
- `refund`/`partial_refund`/`chargeback`/`partial_chargeback` dan status tak dikenal: `perlu_diperiksa`, tidak pernah `lunas`.
- `pending` tidak menurunkan status yang sudah `gagal`/`kedaluwarsa`/`perlu_diperiksa`.
- Baris `payments` selalu diperbarui dengan `transaction_id`, `status_transaksi`, `status_code`, `fraud_status`, `payment_type`, dan `waktu_diproses`. `raw_response` (respons Snap) tidak ditimpa; payload webhook disimpan di `payment_notifications.payload`.
- Panduan pengujian lokal dengan ngrok ada di `docs/MIDTRANS-SETUP.md`.

### 4.8 `PATCH /kasir/menu/{menu}/availability`

Request:

```json
{
  "tersedia": false
}
```

Response `200`:

```json
{
  "data": {
    "id": 1,
    "nama": "Varya Signature",
    "slug": "varya-signature",
    "harga": 22000,
    "tersedia": false,
    "tersedia_label": "Habis"
  }
}
```

Aturan:

- `{menu}` adalah id numerik menu; menu yang sudah di-soft-delete menghasilkan `404`.
- Hanya kolom `menus.tersedia` yang diubah. Field lain (`nama`, `harga`, `category_id`, `deskripsi`, `foto`) diabaikan walau dikirim.
- `tersedia` wajib diisi dan harus boolean (`true`/`false`, `1`/`0`); nilai lain ditolak `422`.
- Error penting: `401` tamu, `403` bukan kasir, `404` menu tidak ada, `422` validasi gagal.

Status 28 Okt 2026: **sudah diimplementasikan** di `KasirController@updateAvailability` + `App\Http\Requests\Kasir\UpdateMenuAvailabilityRequest` + `App\Http\Resources\MenuResource`. Halaman Kelola Menu memanggil endpoint ini; status ketersediaan tidak lagi disimpan di `localStorage`.

---

## 5. Perbedaan route sekarang vs target

| Sekarang | Target | Tindakan |
|---|---|---|
| `/tentang-kami` | `/tentang` | Ganti URI + semua `route()`; redirect 301 |
| `/admin` | `/admin/dashboard` | Ganti URI; redirect |
| `/pesan/pembayaran` | `/pesanan/{kode_pesanan}` | **Selesai** - route dihapus; view lama tidak dipakai |
| `/pesan/berhasil` | `/pesanan/{kode_pesanan}` | **Selesai** - route dihapus; `berhasil.blade.php` menjadi halaman status server-side |
| Belum ada login | `/login`, `POST /login`, `POST /logout` | **Selesai** - auth manual + role redirect |
| Hanya `GET` | `POST`/`PATCH`/`DELETE` | `POST /kasir/pesanan`, `PATCH /kasir/pesanan/{order}/status`, `PATCH /kasir/menu/{menu}/availability`, `POST /pesan/checkout`, dan `POST /pesanan/{kode_pesanan}/bayar-lagi` **selesai**; action lain menyusul |
| Belum ada webhook | `/api/midtrans/notification` | **Selesai** - route + CSRF exception + verifikasi signature |
| Belum ada role | `auth` + `role:*` | **Selesai** - middleware `role` aktif di area kasir/admin |
| Belum ada rate limit | `throttle:*` | Login, `GET /kasir/pesanan/poll`, `POST /pesan/checkout`, dan status pesanan **selesai**; webhook sengaja tanpa throttle ketat |

---

## 6. Aturan penamaan route

- Pertahankan prefix yang sudah ada: `pelanggan-a.`, `pelanggan-b.`, `kasir.`, `admin.`.
- Gunakan pola `area.resource.action`: `kasir.pesanan.store`, `admin.menu.update`.
- Jangan memakai nama route yang sama untuk dua URI.
- Jangan hardcode URL di Blade; gunakan `route()`.
- Route publik yang memakai kode harus memakai route model binding `{order:kode_pesanan}`.
- Route internal kasir/admin boleh memakai `{order}` (id); pilih satu dan konsisten.
- Setiap perubahan route harus memperbarui dokumen ini dan semua test terkait.

---

## 7. Keputusan terbuka terkait route

- O-16 — akun/kredensial Midtrans untuk pengujian end-to-end. Tunnel sudah diputuskan memakai ngrok; lihat `docs/MIDTRANS-SETUP.md`.
