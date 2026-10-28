# Kontrak Route & API — Kedai Kopi Varya

**Status:** FINAL untuk route inti; O-16 (tunnel/akun Midtrans) masih OPEN
**Terakhir diperbarui:** 27 Oktober 2026
**Acuan:** `PRD.md` §4.3, §7, §11; `docs/DECISIONS.md`; `routes/web.php`

> Sebagian route target sudah diimplementasikan untuk tampilan. Route autentikasi sudah aktif; route transaksi dan webhook masih berupa kontrak dan belum boleh dianggap tersedia.

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
| GET | `/pesan/checkout` | `pelanggan-b.checkout` | `PelangganBController@checkout` | Belum ada `POST` |
| GET | `/pesan/pembayaran` | `pelanggan-b.pembayaran` | `PelangganBController@pembayaran` | Draft; Midtrans belum aktif |
| GET | `/pesan/berhasil` | `pelanggan-b.berhasil` | `PelangganBController@berhasil` | Halaman tiket; menunggu backend |
| ANY | `/kasir` | — | Redirect 301 ke `/kasir/pesanan/baru` | `auth` + `role:kasir` |
| GET | `/kasir/pesanan/baru` | `kasir.pesanan.baru` | `KasirController@buatPesanan` | `auth` + `role:kasir`; menu dari DB |
| POST | `/kasir/pesanan` | `kasir.pesanan.store` | `KasirController@storeOrder` | `auth` + `role:kasir`; order tunai langsung lunas + nomor antrean |
| GET | `/kasir/pesanan` | `kasir.pesanan.index` | `KasirController@pesananMasuk` | `auth` + `role:kasir`; pesanan hari ini dari DB |
| PATCH | `/kasir/pesanan/{order}/status` | `kasir.pesanan.status` | `KasirController@updateStatus` | `auth` + `role:kasir`; `antre → diproses → selesai` |
| GET | `/kasir/menu` | `kasir.menu.index` | `KasirController@kelolaMenu` | `auth` + `role:kasir`; menu dari DB |
| ANY | `/admin` | — | Redirect 301 ke `/admin/dashboard` | `auth` + `role:admin` |
| GET | `/admin/dashboard` | `admin.dashboard` | `AdminController@dashboard` | `auth` + `role:admin`; transaksi contoh |
| GET | `/admin/menu` | `admin.menu.index` | `AdminController@menus` | `auth` + `role:admin`; menu dari DB |
| GET | `/admin/menu/create` | `admin.menu.create` | `AdminController@createMenu` | `auth` + `role:admin`; belum ada `POST` |
| GET | `/admin/menu/{menu}/edit` | `admin.menu.edit` | `AdminController@editMenu` | `auth` + `role:admin`; belum ada `PUT` |
| GET | `/admin/laporan` | `admin.reports` | `AdminController@reports` | `auth` + `role:admin`; data contoh |
| GET | `/admin/promosi` | `admin.promos` | `AdminController@promos` | `auth` + `role:admin`; CRUD demo |
| GET | `/admin/toko` | `admin.store` | `AdminController@store` | `auth` + `role:admin`; simpan demo |

Belum ada:

- Route `POST`/`PATCH`/`DELETE` untuk transaksi dan CRUD selain `POST /kasir/pesanan` dan `PATCH /kasir/pesanan/{order}/status`.
- Route polling (`GET /kasir/pesanan/poll`), detail pesanan, pembatalan, dan cek status pembayaran.
- Webhook Midtrans.
- Rate limiting untuk checkout/status pesanan (login sudah dibatasi 5 percobaan/menit).


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
| GET | `/pesanan/{order:kode_pesanan}` | `pelanggan-b.pesanan.show` | `PelangganBController@showOrder` | `throttle:order-status` |
| GET | `/pesanan/{order:kode_pesanan}/status` | `pelanggan-b.pesanan.status` | `PelangganBController@status` | `throttle:order-status` |
| POST | `/pesanan/{order:kode_pesanan}/bayar-lagi` | `pelanggan-b.pesanan.retry` | `PelangganBController@retryPayment` | `throttle:checkout` |

Catatan:

- `/pesanan/{order:kode_pesanan}` menggantikan halaman demo `/pesan/pembayaran` dan `/pesan/berhasil`.
- Halaman ini adalah halaman konfirmasi pembayaran, **bukan** fitur pelacakan.
- Route model binding memakai `kode_pesanan`, bukan `id`.
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

Status implementasi 28 Okt 2026:

- `GET /kasir/pesanan/baru`, `POST /kasir/pesanan`, `GET /kasir/pesanan`, dan `PATCH /kasir/pesanan/{order}/status` **sudah aktif**.
- `GET /kasir/pesanan/poll`, `GET /kasir/pesanan/{order}`, `POST .../cancel`, `POST .../check-status`, dan `PATCH /kasir/menu/{menu}/availability` **belum dibuat**.
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

---

## 5. Perbedaan route sekarang vs target

| Sekarang | Target | Tindakan |
|---|---|---|
| `/tentang-kami` | `/tentang` | Ganti URI + semua `route()`; redirect 301 |
| `/admin` | `/admin/dashboard` | Ganti URI; redirect |
| `/pesan/pembayaran` | `/pesanan/{kode_pesanan}` | Hapus halaman demo |
| `/pesan/berhasil` | `/pesanan/{kode_pesanan}` | Hapus halaman demo |
| Belum ada login | `/login`, `POST /login`, `POST /logout` | **Selesai** — auth manual + role redirect |
| Hanya `GET` | `POST`/`PATCH`/`DELETE` | `POST /kasir/pesanan` dan `PATCH /kasir/pesanan/{order}/status` **selesai**; action lain menyusul |
| Belum ada webhook | `/api/midtrans/notification` | Tambah route + CSRF exception |
| Belum ada role | `auth` + `role:*` | **Selesai** — middleware `role` aktif di area kasir/admin |
| Belum ada rate limit | `throttle:*` | Login selesai; checkout/status menyusul |

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

- O-16 — URL publik/tunnel dan akun Midtrans untuk pengujian end-to-end.
