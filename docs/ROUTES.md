# Kontrak Route & API — Kedai Kopi Varya

**Status:** FINAL untuk route inti; O-16 (akun/kredensial Midtrans) masih OPEN
**Terakhir diperbarui:** 10 November 2026
**Acuan:** `PRD.md` §4.3, §7, §11; `docs/DECISIONS.md`; `routes/web.php`

> Route autentikasi, transaksi kasir, checkout QRIS, webhook Midtrans, halaman status pesanan, pembayaran ulang, pembatalan QRIS, Cek Status, proses terjadwal rekonsiliasi, CRUD menu admin, CRUD promosi, Informasi Toko (termasuk foto Tentang Kami), Laporan Transaksi, dan Dashboard admin sudah aktif.

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
| GET | `/pesan/keranjang` | `pelanggan-b.keranjang` | `PelangganBController@keranjang` | Keranjang browser (`localStorage`, F-69); tanpa endpoint tulis |
| GET | `/pesan/checkout` | `pelanggan-b.checkout` | `PelangganBController@checkout` | Form checkout; `POST` sudah ada |
| POST | `/pesan/checkout` | `pelanggan-b.checkout.store` | `PelangganBController@storeCheckout` | Publik; `throttle:checkout`; order QRIS + Snap |
| GET | `/pesanan/{order:kode_pesanan}` | `pesanan.status` | `PesananController@show` | Publik; kode acak 8 karakter; hanya order QRIS |
| GET | `/pesanan/{order:kode_pesanan}/status` | `pesanan.status.json` | `PesananController@status` | Publik; polling; `throttle:order-status` |
| POST | `/pesanan/{order:kode_pesanan}/bayar-lagi` | `pesanan.bayar-lagi` | `PesananController@bayarLagi` | Publik; `throttle:checkout`; token Snap |
| POST | `/api/midtrans/notification` | `midtrans.notification` | `MidtransNotificationController@handle` | Publik; tanpa CSRF; verifikasi signature; sumber status pembayaran utama |
| ANY | `/kasir` | — | Redirect 301 ke `/kasir/pesanan/baru` | `auth` + `role:kasir` |
| GET | `/kasir/pesanan/baru` | `kasir.pesanan.baru` | `KasirController@buatPesanan` | `auth` + `role:kasir`; menu dari DB |
| POST | `/kasir/pesanan` | `kasir.pesanan.store` | `KasirController@storeOrder` | `auth` + `role:kasir`; order tunai langsung lunas + nomor antrean |
| GET | `/kasir/pesanan` | `kasir.pesanan.index` | `KasirController@pesananMasuk` | `auth` + `role:kasir`; pesanan hari ini dari DB, tanpa yang dibatalkan |
| GET | `/kasir/pesanan/{order}` | `kasir.pesanan.show` | `KasirController@showOrder` | `auth` + `role:kasir`; JSON detail, hanya order hari ini |
| PATCH | `/kasir/pesanan/{order}/status` | `kasir.pesanan.status` | `KasirController@updateStatus` | `auth` + `role:kasir`; `antre → diproses → selesai` |
| POST | `/kasir/pesanan/{order}/cancel` | `kasir.pesanan.cancel` | `KasirController@cancelOrder` | `auth` + `role:kasir`; `throttle:30,1`; hanya QRIS belum dibayar |
| POST | `/kasir/pesanan/{order}/check-status` | `kasir.pesanan.check-status` | `KasirController@checkPaymentStatus` | `auth` + `role:kasir`; `throttle:30,1`; cek API status Midtrans |
| GET | `/kasir/menu` | `kasir.menu.index` | `KasirController@kelolaMenu` | `auth` + `role:kasir`; menu dari DB |
| PATCH | `/kasir/menu/{menu}/availability` | `kasir.menu.availability` | `KasirController@updateAvailability` | `auth` + `role:kasir`; hanya `tersedia` |
| ANY | `/admin` | — | Redirect 301 ke `/admin/dashboard` | `auth` + `role:admin` |
| GET | `/admin/dashboard` | `admin.dashboard` | `AdminController@dashboard` | `auth` + `role:admin`; ringkasan dari DB; filter `?periode=hari|minggu|bulan` |
| GET | `/admin/menu` | `admin.menu.index` | `AdminController@menus` | `auth` + `role:admin`; menu dari DB |
| GET | `/admin/menu/create` | `admin.menu.create` | `AdminController@createMenu` | `auth` + `role:admin` |
| POST | `/admin/menu` | `admin.menu.store` | `AdminController@storeMenu` | `auth` + `role:admin`; multipart + foto |
| GET | `/admin/menu/{menu}/edit` | `admin.menu.edit` | `AdminController@editMenu` | `auth` + `role:admin` |
| PUT/PATCH | `/admin/menu/{menu}` | `admin.menu.update` | `AdminController@updateMenu` | `auth` + `role:admin`; multipart + foto |
| DELETE | `/admin/menu/{menu}` | `admin.menu.destroy` | `AdminController@destroyMenu` | `auth` + `role:admin`; soft delete |
| PATCH | `/admin/menu/{menu}/availability` | `admin.menu.availability` | `AdminController@updateMenuAvailability` | `auth` + `role:admin`; JSON; hanya `tersedia` |
| PATCH | `/admin/menu/{menu}/terlaris` | `admin.menu.terlaris` | `AdminController@updateMenuBestSeller` | `auth` + `role:admin`; JSON; hanya `terlaris` (F-75) |
| GET | `/admin/laporan` | `admin.reports` | `AdminController@reports` | `auth` + `role:admin`; filter `bulan`, data dari DB |
| GET | `/admin/laporan/{order}` | `admin.reports.show` | `AdminController@showReportOrder` | `auth` + `role:admin`; JSON rincian transaksi |
| GET | `/admin/promosi` | `admin.promos` | `AdminController@promos` | `auth` + `role:admin`; daftar + editor |
| POST | `/admin/promosi` | `admin.promos.store` | `AdminController@storePromo` | `auth` + `role:admin`; multipart + gambar |
| PUT/PATCH | `/admin/promosi/{promo}` | `admin.promos.update` | `AdminController@updatePromo` | `auth` + `role:admin`; multipart + gambar |
| DELETE | `/admin/promosi/{promo}` | `admin.promos.destroy` | `AdminController@destroyPromo` | `auth` + `role:admin`; hard delete |
| PATCH | `/admin/promosi/{promo}/status` | `admin.promos.status` | `AdminController@updatePromoStatus` | `auth` + `role:admin`; JSON; hanya `aktif` |
| GET | `/admin/toko` | `admin.store` | `AdminController@store` | `auth` + `role:admin`; data dari DB |
| PUT | `/admin/toko` | `admin.store.update` | `AdminController@updateStore` | `auth` + `role:admin`; profil + jam 7 hari |
| POST | `/admin/toko/foto-tentang` | `admin.store.foto-tentang.store` | `AdminController@storeAboutPhoto` | `auth` + `role:admin`; multipart, foto ≤ 3 MB |
| DELETE | `/admin/toko/foto-tentang` | `admin.store.foto-tentang.destroy` | `AdminController@destroyAboutPhoto` | `auth` + `role:admin`; JSON bila `Accept: application/json` |

Belum ada:

- Route `POST`/`PATCH`/`DELETE` untuk transaksi dan CRUD selain `POST /kasir/pesanan`, `PATCH /kasir/pesanan/{order}/status`, `POST /kasir/pesanan/{order}/cancel`, `POST /kasir/pesanan/{order}/check-status`, `PATCH /kasir/menu/{menu}/availability`, `POST /pesan/checkout`, `POST /pesanan/{kode_pesanan}/bayar-lagi`, `POST/PUT/PATCH/DELETE /admin/menu`, `PATCH /admin/menu/{menu}/availability`, `POST/PUT/PATCH/DELETE /admin/promosi` + `PATCH /admin/promosi/{promo}/status`, `PUT /admin/toko`, serta `POST`/`DELETE /admin/toko/foto-tentang`.
- Rekonsiliasi berjalan sebagai command terjadwal `qris:reconcile` (bukan route HTTP), lihat §4.11.
- Laporan: ekspor PDF/Excel (Fase 2) dan ringkasan lintas tahun selain daftar bulan pada filter.
- Dashboard: filter `?periode=hari|minggu|bulan` (default `hari`); angka ringkasan mengikuti periode aktif, sedangkan menu terlaris, pesanan menunggu/diproses, dan transaksi terbaru tetap seperti aturan F-66.
- Rate limiting untuk endpoint lain di luar login, polling kasir, pembatalan/Cek Status, checkout, dan status pesanan.


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

Beranda `/` menampilkan pratinjau **4 kartu menu** pertama dengan tombol "Lihat Semua Menu" yang memperluas seluruh kartu di tempat (tanpa pindah halaman). Halaman `/menu` tetap menampilkan seluruh menu tanpa batas. Atribut kontrak: `data-menu-preview-limit="4"` pada wadah menu beranda, `data-menu-preview-limit="0"` pada `/menu`, `data-menu-index` pada tiap item, dan tombol `data-menu-more` (bukan tautan). Pencarian/filter kategori mengabaikan batas pratinjau. Kategori database final: **Kopi** (`kopi`), **Bukan Kopi** (`bukan-kopi`), dan **Makanan & Camilan** (`makanan-camilan`); filter publik menambahkan **Semua** dan memakai `App\Support\MenuGroup` (alias slug lama tetap diterima selama transisi). Tata letak memakai 4 kolom desktop terpusat dan 2 kolom mobile. Detail keputusan ada di `docs/DECISIONS.md` F-72/F-73.

Navigasi Pelanggan A memakai anchor ke beranda: `/#beranda`, `/#tentang`, `/#menu`, dan `/#lokasi`. Route `/tentang`, `/menu`, dan `/lokasi` tetap tersedia sebagai deep link/SEO dan tetap menandai pil aktif sesuai halaman; scroll-spy hanya berjalan di beranda karena keempat section lengkap di sana. Navbar desktop Pelanggan A dan B sama-sama muncul mulai breakpoint `lg` (≥1024px); di bawahnya memakai tombol menu mobile. Kontrak atribut: `data-nav-link`, `data-nav-section`, `data-nav-desktop`, dan `id="beranda"` pada beranda. Detail keputusan ada di `docs/DECISIONS.md` F-74.

### 2.3 Pelanggan B

| Method | URI | Nama Route | Controller | Middleware |
|---|---|---|---|---|
| GET | `/pesan` | `pelanggan-b.beranda` | `PelangganBController@beranda` | — |
| GET | `/pesan/menu/{menu:slug}` | `pelanggan-b.produk` | `PelangganBController@produk` | — |
| GET | `/pesan/keranjang` | `pelanggan-b.keranjang` | `PelangganBController@keranjang` | Keranjang browser (F-69); tidak ada endpoint tulis |
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
- Beranda `/pesan` memakai pratinjau **4 kartu menu** yang sama (`data-menu-preview-limit="4"`, tombol `data-menu-more`); seluruh kartu tetap dirender server dan item ke-5+ dapat ditambahkan ke keranjang setelah diperluas atau lewat pencarian/filter. Filter publik memakai kelompok yang sama (Kopi/Bukan Kopi/Makanan & Camilan) dan tata letak 4 kolom desktop terpusat. Detail ada di F-72/F-73.
- Navbar `/pesan` memakai anchor `#beranda`/`#tentang`/`#menu`/`#lokasi` dan scroll-spy; halaman alur pesanan (`produk`, `keranjang`, `checkout`, status) tetap menandai Menu aktif. Kontrak atribut sama dengan Pelanggan A (F-74).

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

Status implementasi 1 Nov 2026:

- `GET /kasir/pesanan/baru`, `POST /kasir/pesanan`, `GET /kasir/pesanan`, `GET /kasir/pesanan/poll`, `GET /kasir/pesanan/{order}`, `PATCH /kasir/pesanan/{order}/status`, `POST /kasir/pesanan/{order}/cancel`, `POST /kasir/pesanan/{order}/check-status`, `GET /kasir/menu`, dan `PATCH /kasir/menu/{menu}/availability` **sudah aktif**.
- Filter kategori kasir membaca tiga kategori tetap dari database: Kopi, Bukan Kopi, dan Makanan & Camilan, ditambah Semua. Admin memakai daftar yang sama.
- `GET /kasir/pesanan/{order}` (`kasir.pesanan.show`) **sudah aktif**: detail dibaca ulang dari database untuk dialog Pesanan Masuk (§4.17).
- Daftar pesanan memuat order yang dibuat pada hari berjalan (`created_at` hari ini, zona `Asia/Jakarta`), supaya order QRIS yang belum dibayar tetap tampil di tab Belum Dibayar meski belum punya nomor antrean. Order `dibatalkan` tidak ditampilkan (PRD K-02b), tetapi tetap tersimpan untuk laporan admin.
- `POST .../cancel` dan `POST .../check-status` memakai `throttle:30,1` per pengguna.

### 2.5 Admin

Semua route memakai middleware `auth` + `role:admin`.

| Method | URI | Nama Route | Controller | Catatan |
|---|---|---|---|---|
| GET | `/admin/dashboard` | `admin.dashboard` | `AdminController@dashboard` | Ganti URI dari `/admin` |
| GET | `/admin/menu` | `admin.menu.index` | `AdminController@menus` | |
| GET | `/admin/menu/create` | `admin.menu.create` | `AdminController@createMenu` | |
| POST | `/admin/menu` | `admin.menu.store` | `AdminController@storeMenu` | |
| GET | `/admin/menu/{menu}/edit` | `admin.menu.edit` | `AdminController@editMenu` | |
| PUT/PATCH | `/admin/menu/{menu}` | `admin.menu.update` | `AdminController@updateMenu` | Multipart; foto opsional |
| DELETE | `/admin/menu/{menu}` | `admin.menu.destroy` | `AdminController@destroyMenu` | Soft delete |
| PATCH | `/admin/menu/{menu}/availability` | `admin.menu.availability` | `AdminController@updateMenuAvailability` | JSON; hanya `tersedia` |
| GET | `/admin/laporan` | `admin.reports` | `AdminController@reports` | Nama route lama dipertahankan |
| GET | `/admin/laporan/{order}` | `admin.reports.show` | `AdminController@showReportOrder` | Detail transaksi |
| GET | `/admin/promosi` | `admin.promos` | `AdminController@promos` | |
| POST | `/admin/promosi` | `admin.promos.store` | `AdminController@storePromo` | Multipart; gambar opsional |
| PUT/PATCH | `/admin/promosi/{promo}` | `admin.promos.update` | `AdminController@updatePromo` | Multipart; gambar opsional |
| DELETE | `/admin/promosi/{promo}` | `admin.promos.destroy` | `AdminController@destroyPromo` | Hard delete |
| PATCH | `/admin/promosi/{promo}/status` | `admin.promos.status` | `AdminController@updatePromoStatus` | JSON; hanya `aktif` |
| GET | `/admin/toko` | `admin.store` | `AdminController@store` | Data dari `store_settings` + `store_hours` |
| PUT | `/admin/toko` | `admin.store.update` | `AdminController@updateStore` | Form biasa; profil + jam 7 hari |

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
      "jumlah": 2,
      "harga_satuan": 18000
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

- `409` — harga menu berbeda dari `harga_satuan` yang dikirim (harga berubah sejak masuk keranjang); tidak ada order dibuat.
- `422` — menu habis/terhapus, `harga_satuan` tidak ada, jumlah tidak valid.
- `429` — terlalu banyak checkout.
- `500` — Snap gagal; order tetap tersimpan `menunggu` dan bisa dicoba lagi.

Response `409`:

```json
{
  "message": "Harga menu berikut berubah: Americano Street (Rp 18.000 → Rp 20.000). Hapus item tersebut dari keranjang, lalu tambahkan lagi dari menu terbaru.",
  "errors": {
    "item": ["Harga menu berikut berubah: ..."]
  },
  "data": {
    "item": [
      {"menu_id": 12, "nama": "Americano Street", "harga_lama": 18000, "harga_baru": 20000}
    ]
  }
}
```

Catatan: catatan per item hanya dipakai pada alur kasir; checkout Pelanggan B memakai `orders.catatan` untuk catatan pesanan.

Status 31 Okt 2026: **sudah diimplementasikan** di `PelangganBController@storeCheckout` + `App\Http\Requests\PelangganB\StoreCheckoutRequest` + `App\Services\OrderService::buatOrderQris` + `App\Services\MidtransService` + `App\Http\Resources\CheckoutResource`.

Perilaku implementasi yang perlu diketahui konsumen endpoint:

- `POST /pesan/checkout` terbuka untuk tamu dan dibatasi `throttle:checkout` (10 permintaan/menit per IP).
- Order + item disimpan lebih dulu dalam `DB::transaction`: `sumber = qr`, `metode_pembayaran = qris`, `status_pesanan = menunggu_pembayaran`, `status_pembayaran = menunggu`, `cashier_id = null`, `batas_waktu_pembayaran = sekarang + 15 menit`, **tanpa** `nomor_antrean`/`tanggal_antrean`.
- Harga, subtotal, dan total selalu dihitung dari `menus.harga`; `total`/`subtotal` kiriman browser diabaikan.
- `item.*.harga_satuan` **wajib** dan hanya berfungsi sebagai harga acuan yang dilihat pelanggan. Bila tidak sama dengan `menus.harga`, endpoint menjawab `409` (`data.item` berisi `menu_id`, `nama`, `harga_lama`, `harga_baru`) dan tidak ada order/payments dibuat (PRD B-10).
- Menu habis, menu sudah di-soft-delete, `harga_satuan` tidak dikirim, atau `item` kosong menghasilkan `422` pada field `item` dan tidak ada order/payments yang tersimpan. Panggilan ke Midtrans juga tidak dilakukan.
- Bila `MIDTRANS_SERVER_KEY` atau `MIDTRANS_CLIENT_KEY` belum diatur, endpoint menjawab `503` **sebelum** order dibuat, supaya tidak ada order yatim yang tidak bisa dibayar.
- Bila Snap gagal (respons non-2xx, tanpa `token`/`redirect_url`, atau gangguan jaringan), endpoint menjawab `500` dengan `data.kode_pesanan`. Order tetap tersimpan sebagai `menunggu_pembayaran`.
- Setiap panggilan Snap memakai baris `payments` baru: `midtrans_order_id = {kode_pesanan}-P{n}`. Baris dicatat **sebelum** HTTP call agar nomor percobaan tidak dipakai ulang saat pembayaran ulang (PRD §8.4.4). Percobaan yang gagal diubah menjadi `status_transaksi = failure` dengan `status_code` dari Midtrans (atau `500`) dan `raw_response` berisi galat; `snap_token`/`snap_redirect_url` tetap `null`.
- Respons `201` mengikuti kontrak: `kode_pesanan`, `total`, `status_pesanan`, `status_pembayaran`, `batas_waktu_pembayaran`, `snap_token`, `snap_redirect_url`.
- `callbacks.finish` dikirim ke `route('pesanan.status', $order)`. Popup Snap memakai `snap_token` dan **tidak** mengarahkan browser ke `snap_redirect_url`; pelanggan tetap berada di website ini.
- `resources/js/pelanggan-b.js` mengirim JSON + header `X-CSRF-TOKEN` beserta `harga_satuan` dari keranjang, menonaktifkan tombol saat menyimpan, menampilkan pesan `409` (keranjang tidak dikosongkan agar bisa disesuaikan), `422`/`429`/`503`, membuka popup Snap setelah `201`, mengosongkan `localStorage` keranjang, lalu mengarahkan browser ke `/pesanan/{kode}` saat popup selesai/ditutup. Bila Snap gagal (`500`), browser juga diarahkan ke halaman status agar bisa mencoba bayar lagi.
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
- Order `dibatalkan` tidak dikirim di `pesanan` dan tidak punya kunci di `jumlah`; PRD K-02b meminta pesanan batal tidak tampil secara default tetapi tetap tersedia untuk laporan admin.
- Setelah pembatalan/Cek Status, klien memperbarui baris dari respons aksi atau polling; pesanan yang dibatalkan dihapus dari daftar lokal.

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

### 4.9 `POST /kasir/pesanan/{order}/cancel`

Request tanpa body. `{order}` adalah id numerik order.

Response `200`:

```json
{
  "message": "Pesanan QR12CD34 dibatalkan.",
  "data": {
    "id": "12",
    "code": "QR12CD34",
    "queue": null,
    "status": "dibatalkan",
    "payment_status": "dibatalkan",
    "midtrans_id": "QR12CD34-P1"
  }
}
```

`data` memakai bentuk yang sama dengan item pada `GET /kasir/pesanan/poll`, sehingga klien dapat menghapus baris tanpa transformasi. Field lain tetap ada pada respons sebenarnya.

Aturan:

- Hanya order QRIS yang belum dibayar. Order tunai, order lunas, order `perlu_diperiksa`, atau order yang sudah memiliki nomor antrean ditolak `409`.
- Bila percobaan terakhir sudah terminal secara lokal (`failure`, `deny`, `cancel`, `expire`, `gagal`, `kedaluwarsa`), pembatalan lokal dilakukan tanpa memanggil Midtrans.
- Bila percobaan terakhir `pending`, backend memanggil `POST /v2/{midtrans_order_id}/cancel`. Bila Midtrans menolak, backend memanggil `GET /v2/{midtrans_order_id}/status`:
  - status `cancel`/`expire`/`deny`/`failure` → pembatalan lokal dilanjutkan;
  - status `pending` → `409`, order tetap `menunggu_pembayaran`;
  - status `settlement`/`capture` → status pembayaran diterapkan (dapat menjadi `lunas`), lalu pembatalan ditolak `409`.
- Respons `cancel` dengan HTTP `200` tetap harus berisi status terminal; bila statusnya masih `pending` atau tidak lengkap, pembatalan lokal tidak dilakukan (`409`/`502`).
- Bila `/cancel` **dan** `/status` sama-sama menjawab `404` (`Transaction doesn't exist` — pelanggan belum membuka halaman Snap, sehingga transaksi belum terdaftar di Core API), backend membatalkan sesi Snap lewat `POST /snap/v1/transactions/{snap_token}/cancel`. Respons `200` (`canceled_at`) atau `token already canceled` membuat pembatalan lokal dilanjutkan; `Transaction is on progress`, `token not found`, atau token tidak tercatat membuat order tetap `menunggu_pembayaran` (`502`/`409`).
- Bila Midtrans tidak dapat dihubungi, order tetap `menunggu_pembayaran` dan endpoint menjawab `502`. Bila `MIDTRANS_SERVER_KEY` kosong, endpoint menjawab `503`.
- Pembatalan akhir berjalan di `DB::transaction` + `lockForUpdate`, menetapkan `status_pesanan = dibatalkan` dan `status_pembayaran = dibatalkan` tanpa membuat nomor antrean.
- Klik ganda bersifat idempoten: order yang sudah `dibatalkan` tetap menjawab `200`.
- `settlement` yang masuk setelah pembatalan lokal ditangani webhook/Cek Status sebagai `perlu_diperiksa` tanpa nomor antrean (PRD §8.4.10).
- Error penting: `401` tamu, `403` bukan kasir, `404` order tidak ada, `409` tidak dapat dibatalkan, `429` throttle, `502`/`503` masalah Midtrans.
- Status 1 Nov 2026: **sudah diimplementasikan** di `KasirController@cancelOrder` + `OrderService::batalkanPesananQris` + `MidtransService::batalkanTransaksi`/`ambilStatus`/`batalkanSesiSnap`.

### 4.10 `POST /kasir/pesanan/{order}/check-status`

Request tanpa body. Hanya tersedia untuk order QRIS.

Response `200`:

```json
{
  "message": "Status pembayaran diperbarui: Lunas.",
  "data": {
    "id": "12",
    "code": "QR12CD34",
    "queue": "007",
    "status": "antre",
    "payment_status": "lunas",
    "midtrans_id": "QR12CD34-P1"
  }
}
```

Aturan:

- Backend memanggil `GET /v2/{midtrans_order_id}/status` memakai `server_key`; `client_key` dan `server_key` tidak pernah dikirim ke browser. Respons dengan `order_id` yang tidak cocok ditolak `502` tanpa mengubah data.
- Respons Midtrans diproses melalui `MidtransNotificationService::terapkanStatus`, jalur yang sama dengan webhook, dengan `DB::transaction` + `lockForUpdate`.
- `settlement`/`capture+accept` dapat menandai order `lunas`, `status_pesanan = antre`, dan membuat nomor antrean secara idempoten. Pemeriksaan berulang tidak menambah nomor antrean.
- `gross_amount` wajib ada dan cocok dengan `orders.total`; bila tidak, pembayaran menjadi `perlu_diperiksa` tanpa nomor antrean.
- `settlement` setelah pembatalan lokal menjadi `perlu_diperiksa` tanpa nomor antrean.
- `pending` tidak menurunkan status yang sudah `gagal`/`kedaluwarsa`/`perlu_diperiksa`.
- Pemeriksaan status **tidak** menulis `payment_notifications`; tabel itu khusus audit webhook. `payments.raw_response` (respons Snap) juga tidak ditimpa.
- Order tanpa percobaan pembayaran atau order non-QRIS ditolak `409`. Midtrans tidak dapat dihubungi → `502`; kredensial kosong → `503`; throttle → `429`.
- Tombol ini hanya ada di halaman kasir, bukan halaman pelanggan (PRD K-02e, PG-07).
- Status 1 Nov 2026: **sudah diimplementasikan** di `KasirController@checkPaymentStatus` + `OrderService::periksaStatusPembayaran` + `MidtransService::ambilStatus`.

### 4.11 Proses terjadwal `qris:reconcile` (bukan route HTTP)

Tidak ada endpoint HTTP baru. Proses ini dijalankan scheduler Laravel dan tidak dapat dipanggil dari browser.

```bash
php artisan qris:reconcile
```

Jadwal: `routes/console.php` → `Schedule::command('qris:reconcile')->everyFiveMinutes()->withoutOverlapping(10)`.

Keluaran (stdout, satu baris):

```text
Rekonsiliasi selesai: 3 diperiksa, 1 ditutup, 0 gagal.
```

Aturan:

- Memeriksa pembayaran `pending` pada order QRIS `menunggu_pembayaran` yang berumur lebih dari 2 menit, lalu memanggil `GET /v2/{midtrans_order_id}/status` dan menerapkannya lewat `MidtransNotificationService::terapkanStatus` (jalur yang sama dengan webhook). Tidak menulis `payment_notifications` dan tidak menimpa `payments.raw_response`.
- Menutup order QRIS `menunggu_pembayaran` berumur lebih dari 24 jam dengan `status_pembayaran` `menunggu`/`gagal`/`kedaluwarsa` menjadi `status_pesanan = dibatalkan`, `status_pembayaran = dibatalkan`.
- Tidak pernah membuat nomor antrean atau menambah `queue_counters`.
- Tidak menutup order yang masih punya bukti `settlement`/`capture` lokal atau percobaan `pending` yang belum lewat 15 menit.
- Kegagalan Midtrans (jaringan/5xx) atau order yang berubah bersamaan hanya menambah hitungan `gagal` dan menulis log; order tidak diubah.
- `GET /status` dan `POST /cancel` memakai `retry(2, 250ms)` untuk gangguan jaringan/5xx. Respons `status_code = 407` + `transaction_status = expire` adalah hasil sah (transaksi sudah kedaluwarsa), bukan kegagalan API.
- Status 1 Nov 2026: **sudah diimplementasikan** di `App\Services\QrisReconciliationService` + `App\Console\Commands\ReconcileQrisPayments`.

### 4.12 CRUD menu admin

Halaman Kelola Menu memakai form biasa (bukan API JSON) untuk tambah dan ubah menu, sehingga sukses/validasi dijawab dengan redirect + session flash.

`POST /admin/menu` dan `PUT/PATCH /admin/menu/{menu}` menerima `multipart/form-data`:

| Field | Aturan |
|---|---|
| `nama` | wajib, string, maks. 80 karakter |
| `category_id` | wajib, integer, harus ada di `categories` (tiga kategori tetap: Kopi, Bukan Kopi, Makanan & Camilan) |
| `harga` | wajib, integer, minimal 1 (Rupiah penuh) |
| `deskripsi` | opsional, string, maks. 300 karakter |
| `tersedia` | wajib, boolean (`1`/`0`) |
| `foto` | opsional, `image`, `mimes:jpg,jpeg,png,webp`, maks. 2 MB |

Aturan:

- Sukses → `302` ke `admin.menu.index` dengan `session('status')`; halaman daftar menampilkan toast dari `data-admin-flash`.
- Validasi gagal → `302` kembali ke form dengan `errors` dan `old()`; halaman form menampilkan ringkasan error + pesan per field.
- `slug` dibuat dari `nama` saat menu dibuat (`Str::slug` + akhiran `-2`, `-3`, ... bila bentrok, termasuk terhadap menu yang sudah di-soft-delete) dan **tidak diubah** saat menu diubah agar URL detail tetap stabil.
- `urutan` menu baru = `max(urutan)` (termasuk baris terhapus) + 1 sehingga tampil paling akhir.
- Foto disimpan di `public/images/menus` dengan nama `unggahan-{acak}.{ext}`. Saat foto diganti, hanya berkas berprefiks `unggahan-` milik menu itu yang dihapus; foto kanonik seeder tidak pernah disentuh.
- `DELETE` melakukan soft delete dan mengalihkan kembali ke daftar. Menu hilang dari halaman publik, kasir, dan admin; snapshot `order_items` tetap utuh. Berkas foto tidak dihapus agar menu masih dapat dipulihkan.
- `PATCH /admin/menu/{menu}/availability` hanya menerima `{"tersedia": true|false}` dan menjawab:

```json
{
  "message": "Menu \"Americano Street\" ditandai habis.",
  "data": { "id": 2, "tersedia": false }
}
```

- Error penting: `401` tamu (redirect `/login`), `403` bukan admin, `404` menu tidak ada, `302` + `errors` untuk validasi gagal.
- Status 1 Nov 2026: **sudah diimplementasikan** di `AdminController@storeMenu`/`updateMenu`/`destroyMenu`/`updateMenuAvailability` + `App\Http\Requests\Admin\MenuRequest`/`UpdateMenuAvailabilityRequest` + `App\Services\MenuCatalogService`.

### 4.13 CRUD promosi admin

Halaman Iklan & Promosi memakai form biasa (multipart) untuk tambah dan ubah, plus `fetch` untuk status dan hapus.

`POST /admin/promosi` dan `PUT/PATCH /admin/promosi/{promo}` menerima `multipart/form-data`:

| Field | Aturan |
|---|---|
| `judul` | wajib, string, maks. 80 karakter |
| `deskripsi` | opsional, string, maks. 220 karakter |
| `urutan` | wajib, integer, 1-999, unik terhadap promosi lain (kecuali dirinya saat diubah) |
| `aktif` | wajib, boolean (`1`/`0`) |
| `mulai` | opsional, tanggal |
| `berakhir` | opsional, tanggal, tidak boleh lebih awal dari `mulai` |
| `gambar` | opsional, `image`, `mimes:jpg,jpeg,png,webp`, maks. 3 MB |

Aturan:

- Sukses → `302` ke `admin.promos` dengan `session('status')`; halaman menampilkan toast dari `data-admin-flash`.
- Validasi gagal → `302` kembali dengan `errors` dan `old()`; form tetap dalam mode edit bila `promo_id` lama dikirim (field tersembunyi), sehingga perubahan tidak hilang.
- Gambar disimpan di `public/images/promos` dengan nama `unggahan-promo-{acak}.{ext}`. Gambar kanonik seeder (`images/menus/*`) tidak pernah dihapus. Saat gambar diganti atau promosi dihapus, hanya berkas berprefiks `unggahan-promo-` milik promosi itu yang dihapus.
- Promosi memakai hard delete (tabel `promos` tidak punya `deleted_at`).
- `PATCH /admin/promosi/{promo}/status` hanya menerima `{"aktif": true|false}` dan menjawab:

```json
{
  "message": "Promosi \"Ngopi Sore Varya\" dinonaktifkan.",
  "data": { "id": 1, "aktif": false }
}
```

- `DELETE /admin/promosi/{promo}` menjawab JSON `{message, data:{id}}` bila klien mengirim `Accept: application/json` (dipakai `admin.js`); permintaan form biasa diarahkan kembali ke daftar.
- Hanya promosi `aktif = true` yang periodenya sedang berjalan (`Promo::active()`) yang tampil di landing page.
- Error penting: `401` tamu (redirect `/login`), `403` bukan admin, `404` promosi tidak ada, `302` + `errors` untuk validasi gagal.
- Status 1 Nov 2026: **sudah diimplementasikan** di `AdminController@storePromo`/`updatePromo`/`destroyPromo`/`updatePromoStatus` + `App\Http\Requests\Admin\PromoRequest`/`UpdatePromoStatusRequest` + `App\Services\PromoService`.

### 4.14 Informasi toko admin

Halaman Informasi Toko memakai form biasa `PUT /admin/toko` untuk menyimpan profil toko dan jam operasional tujuh hari sekaligus.

| Field | Aturan |
|---|---|
| `nama_toko` | wajib, string, maks. 255 karakter |
| `tagline` | opsional, string, maks. 255 karakter; disimpan `''` bila dikosongkan (kolom NOT NULL) |
| `sejarah` | opsional, string, maks. 5000 karakter |
| `alamat` | opsional, string, maks. 500 karakter |
| `maps_url` | opsional, URL, maks. 500 karakter |
| `maps_embed` | opsional, URL, maks. 1000 karakter |
| `telepon` | opsional, maks. 30 karakter, hanya angka/spasi/`+`/`-`/`()` |
| `whatsapp` | opsional, maks. 30 karakter, hanya angka/spasi/`+`/`-`/`()` |
| `instagram` | opsional, maks. 100 karakter; `handle` dan `@handle` sama-sama diterima |
| `fasilitas` | opsional, teks dipisah koma, maks. 1000 karakter, maks. 20 item, tiap item maks. 50 karakter |
| `jam[0..6][buka_jam]` | opsional, dua digit `00`–`23`; wajib bersama `buka_menit` bila hari tidak libur |
| `jam[0..6][buka_menit]` | opsional, dua digit `00`–`59`; digabung menjadi `HH:MM` sebelum disimpan |
| `jam[0..6][tutup_jam]` | opsional, dua digit `00`–`23`; wajib bersama `tutup_menit` bila hari tidak libur |
| `jam[0..6][tutup_menit]` | opsional, dua digit `00`–`59`; digabung menjadi `HH:MM` sebelum disimpan |
| `jam[0..6][libur]` | opsional, boolean; bila `1`, `jam_buka`/`jam_tutup` dikosongkan dan `tutup = true` |

Aturan:

- Sukses → `302` ke `admin.store` dengan `session('status')`; halaman menampilkan toast dari `data-admin-flash`.
- Form Informasi Toko memakai dropdown jam dan menit (bukan `input type=time`) agar format 24 jam konsisten. Server menggabungkan `*_jam` dan `*_menit` menjadi `HH:MM` di `StoreSettingsRequest::waktu()`; klien tidak mengirim `HH:MM` gabungan.
- Hari yang ditandai libur membuat dropdown jam dinonaktifkan di klien sehingga tidak ikut terkirim; server tetap menyimpan `jam_buka`/`jam_tutup` `null` dan `tutup = true`.
- Validasi gagal → `302` kembali dengan `errors` dan `old()`; form menampilkan ringkasan error + pesan per field.
- Profil disimpan pada satu baris `store_settings` (id = 1, dibuat bila belum ada) dan jam pada tujuh baris `store_hours` (`hari` 0 = Minggu … 6 = Sabtu) lewat `updateOrCreate`.
- Profil dan jam ditulis dalam satu `DB::transaction` + `lockForUpdate` agar halaman publik tidak membaca data campuran.
- Field opsional yang kosong disimpan `null`; `fasilitas` kosong disimpan sebagai array `[]`; `instagram` dinormalisasi menjadi `@handle`.
- Perubahan langsung tampil di halaman publik (`/`, `/tentang`, `/lokasi`) karena semuanya membaca `store_settings`/`store_hours`.
- Error penting: `401` tamu (redirect `/login`), `403` bukan admin, `302` + `errors` untuk validasi gagal.
- Status 2 Nov 2026: **sudah diimplementasikan** di `AdminController@store`/`updateStore` + `App\Http\Requests\Admin\StoreSettingsRequest` + `App\Services\StoreSettingsService`; `localStorage` informasi toko dihapus dari `admin.js`.
- Status 6 Nov 2026: jam tujuh hari dapat diubah langsung dari UI lewat dropdown jam + menit; kontrak request memakai empat field `*_jam`/`*_menit` di atas (sebelumnya `buka`/`tutup` `HH:MM`).

#### Foto Tentang Kami

Unggahan foto sengaja terpisah dari `PUT /admin/toko` supaya form JSON Informasi Toko tidak perlu menjadi multipart (F-70). Panelnya ada di halaman **Iklan & Promosi**.

| Method | URI | Nama | Aturan |
|---|---|---|---|
| POST | `/admin/toko/foto-tentang` | `admin.store.foto-tentang.store` | form multipart (`foto`), `@csrf`; sukses `302` ke `admin.promos` + `session('status')` |
| DELETE | `/admin/toko/foto-tentang` | `admin.store.foto-tentang.destroy` | JSON untuk `fetch` (`Accept: application/json`); sukses `200`; tanpa JSON `302` ke `admin.promos` |

Aturan:

- `foto` wajib, `image`, `mimes:jpg,jpeg,png,webp`, maks. 3072 KB (`AboutPhotoRequest`).
- Berkas disimpan di `public/images/toko/unggahan-{acak}.{ext}`; unggahan baru menghapus berkas berprefiks `unggahan-` yang lama. Aset kanonik di `public/images` tidak pernah dihapus.
- Foto disimpan pada `store_settings.foto_tentang` (baris id = 1, dibuat bila belum ada). `null` berarti `GET /tentang` memakai placeholder dan panel berstatus “Tidak Tampil”.
- Satu foto dipakai untuk bingkai desktop (3:2) dan mobile (16:9); pemotongan dilakukan CSS (`object-cover`).
- Error penting: `401` tamu (redirect `/login`), `403` bukan admin, `302` + `errors` untuk validasi gagal, `422` untuk DELETE bukan admin/tamu.
- Status 4 Nov 2026: **sudah diimplementasikan** di `AdminController@storeAboutPhoto`/`destroyAboutPhoto` + `App\Http\Requests\Admin\AboutPhotoRequest` + `App\Services\StoreSettingsService::simpanFotoTentang`/`hapusFotoTentang`.

### 4.15 Laporan transaksi admin

Halaman Laporan Transaksi dirender server dari `orders`, `order_items`, dan `payments` lewat `App\Services\ReportService`. Form filter memakai `GET` biasa supaya URL laporan bisa di-bookmark.

`GET /admin/laporan?bulan=YYYY-MM`:

| Query | Aturan |
|---|---|
| `bulan` | opsional, format `YYYY-MM`; nilai kosong/tidak valid jatuh ke bulan berjalan (`Asia/Jakarta`) |

Aturan:

- Ringkasan (`total pendapatan`, jumlah transaksi, total QRIS, total tunai, rata-rata, persentase QRIS:tunai, perbandingan bulan sebelumnya) hanya menghitung `status_pembayaran = lunas` (PRD §8.8.1).
- Tabel menampilkan **semua** transaksi pada bulan itu beserta status pembayarannya sebagai informasi; status selain `lunas` tidak masuk perhitungan pendapatan.
- Pilihan bulan dibuat dari bulan terlama pada `orders` sampai bulan berjalan (maks. 36 bulan, terbaru lebih dulu) dan selalu memuat bulan berjalan serta bulan yang sedang dipilih.
- Rentang bulan memakai `created_at` pada zona `Asia/Jakarta`; tabel diurutkan terbaru lebih dulu.
- Nomor antrean ditampilkan tiga digit dan kosong (`—`) bila pesanan belum lunas.

`GET /admin/laporan/{order}` (JSON, dipakai dialog rincian):

```json
{
  "data": {
    "id": 12,
    "code": "QR12CD34",
    "queue": "007",
    "customer": "Budi",
    "datetime": "02 Nov 2026, 20:15 WIB",
    "status": "lunas",
    "status_label": "Lunas",
    "method": "tunai",
    "method_label": "Tunai",
    "total": 45000,
    "cash_received": 50000,
    "change": 5000,
    "items": [
      { "name": "Vanilla Latte", "qty": 2, "price": 18000, "subtotal": 36000, "catatan": null }
    ],
    "midtrans_order_id": null,
    "midtrans_transaction_id": null
  }
}
```

- `items` memakai snapshot `order_items` (`nama_menu`, `harga_satuan`), bukan data menu terkini, dan menyertakan `catatan` per item.
- `midtrans_order_id`/`midtrans_transaction_id`/`payment_type` diambil dari baris `payments` terakhir; `null` untuk order tunai.
- `{order}` adalah id numerik order; order yang tidak ada menghasilkan `404`.
- Tidak ada route untuk mengubah status pembayaran dari halaman ini (PRD §8.8.5, F-10).
- Error penting: `401` tamu (redirect `/login`), `403` bukan admin, `404` order tidak ada.
- Status 3 Nov 2026: **sudah diimplementasikan** di `AdminController@reports`/`showReportOrder` + `App\Services\ReportService`; data contoh laporan di `resources/js/admin.js` (perhitungan ringkasan client-side) sudah dihapus.

### 4.16 Dashboard admin

`GET /admin/dashboard?periode=hari|minggu|bulan` dirender server dari `orders`, `order_items`, dan `payments` lewat `App\Services\DashboardService`. Default `hari`; nilai tidak valid jatuh ke `hari`. `minggu` memakai Senin 00:00 sampai sekarang; `bulan` memakai awal sampai akhir bulan berjalan (`Asia/Jakarta`).

Data yang dikirim ke halaman:

| Bagian | Aturan |
|---|---|
| Pendapatan periode aktif | `SUM(total)` order `status_pembayaran = lunas` pada rentang periode aktif |
| Jumlah transaksi lunas | `COUNT` order `lunas` pada rentang periode aktif |
| Jumlah pesanan periode aktif | semua order pada rentang periode aktif, apa pun statusnya |
| Pembanding jumlah pesanan | jumlah semua order pada periode sebelumnya (kemarin, minggu lalu, atau bulan lalu) |
| Pesanan menunggu pembayaran | order hari ini dengan `status_pembayaran = menunggu` |
| Pesanan diproses | order hari ini dengan `status_pesanan = diproses` |
| Perbandingan QRIS:tunai | persentase dari pendapatan lunas periode aktif |
| Menu terlaris | 5 menu dengan `SUM(order_items.jumlah)` terbesar dari transaksi `lunas` bulan berjalan, grup per `nama_menu` |
| Transaksi terbaru | 10 order terbaru (semua status, tidak difilter periode), diurutkan `created_at` lalu `id` menurun |

Aturan:

- Hanya transaksi `lunas` yang masuk pendapatan (PRD §8.8.1); pesanan belum lunas tetap muncul di tabel transaksi terbaru sebagai informasi beserta status pembayarannya.
- Perbandingan jumlah pesanan memakai periode sebelumnya (`created_at` kemarin, minggu lalu, atau bulan lalu; semua status); bila periode sebelumnya tidak punya pesanan, persentase ditampilkan sebagai teks tanpa pembanding.
- Rentang periode: `hari` = hari berjalan, `minggu` = Senin 00:00 sampai sekarang, `bulan` = awal sampai akhir bulan berjalan. Nilai `periode` selain tiga itu jatuh ke `hari`.
- Menu terlaris memakai snapshot `order_items.nama_menu`; harga, kategori, dan foto diambil dari baris `menus` terbaru (termasuk yang sudah di-soft-delete). Menu yang sudah hilang permanen tetap tampil dengan foto bawaan.
- Tabel transaksi terbaru menampilkan kode pesanan, pelanggan, tipe, jumlah item/porsi, total, metode, dan status pesanan/pembayaran. Filter tab Dine In/Take Away berjalan client-side dari 10 baris itu.
- Tidak ada route JSON baru; filter periode memakai query string pada route `GET` yang sama.
- Error penting: `401` tamu (redirect `/login`), `403` bukan admin.
- Status 3 Nov 2026: **sudah diimplementasikan** di `AdminController@dashboard` + `App\Services\DashboardService`; seluruh data contoh dashboard (`recentOrders()`, `popularMenus()`, `transactions()`, `makeTransaction()`, `summarizeMonth()`, `kodePesanan()`, `menuKanonik()`) dihapus dari `AdminController`.
- Status 8 Nov 2026: pemilih periode **Hari Ini/Minggu Ini/Bulan Ini** aktif lewat `?periode`; default `hari`, minggu Senin-Minggu.

### 4.17 Detail pesanan kasir

`GET /kasir/pesanan/{order}` mengembalikan JSON detail satu pesanan untuk dialog Pesanan Masuk. Dipanggil `kasir.js` setiap kali tombol **Detail** ditekan, sehingga isi dialog tidak bergantung pada snapshot polling.

Response `200` memakai bentuk yang sama dengan item `GET /kasir/pesanan/poll`:

```json
{
  "data": {
    "id": "12",
    "code": "AB12CD34",
    "queue": "003",
    "customer": "Anjani",
    "type": "dine_in",
    "type_label": "Dine In",
    "source": "kasir",
    "status": "antre",
    "payment": "tunai",
    "payment_label": "Tunai",
    "payment_status": "lunas",
    "total": 44000,
    "created_at": "29 Okt 2026 • 10:00 WIB",
    "midtrans_id": null,
    "cash_received": 50000,
    "change": 6000,
    "expires_at": null,
    "notes": "Tanpa es",
    "items": [
      { "name": "Varya Signature", "price": 22000, "qty": 2, "subtotal": 44000, "note": "Less sugar" }
    ]
  }
}
```

Aturan:

- `{order}` adalah id numerik order.
- Hanya order yang dibuat **hari ini** (`created_at`, `Asia/Jakarta`) yang dapat dibuka; order hari lain, order `dibatalkan`, dan id yang tidak ada menghasilkan `404`. Ini sama dengan cakupan daftar/polling sehingga pesanan batal tetap tidak tampil (PRD K-02b).
- Data dibaca langsung dari database (item + `catatan` per item, `orders.catatan`, `uang_diterima`/`kembalian`, `payments.midtrans_order_id` terakhir). Tidak ada perubahan data; route ini hanya `GET`.
- Tidak ada otorisasi tambahan selain middleware `auth` + `role:kasir`.
- Di klien, dialog langsung dibuka dengan snapshot lalu diperbarui dari respons ini. Bila server menjawab `404`, dialog ditutup, daftar di-polling ulang, dan pengguna diberi tahu.
- Status 3 Nov 2026: **sudah diimplementasikan** di `KasirController@showOrder` + `resources/js/kasir.js` (`config.routes.pesananShow`).


---

## 5. Perbedaan route sekarang vs target

| Sekarang | Target | Tindakan |
|---|---|---|
| `/tentang-kami` | `/tentang` | Ganti URI + semua `route()`; redirect 301 |
| `/admin` | `/admin/dashboard` | Ganti URI; redirect |
| `/pesan/pembayaran` | `/pesanan/{kode_pesanan}` | **Selesai** - route dihapus; view lama tidak dipakai |
| `/pesan/berhasil` | `/pesanan/{kode_pesanan}` | **Selesai** - route dihapus; `berhasil.blade.php` menjadi halaman status server-side |
| Belum ada login | `/login`, `POST /login`, `POST /logout` | **Selesai** - auth manual + role redirect |
| Hanya `GET` | `POST`/`PATCH`/`DELETE` | `POST /kasir/pesanan`, `PATCH /kasir/pesanan/{order}/status`, `POST /kasir/pesanan/{order}/cancel`, `POST /kasir/pesanan/{order}/check-status`, `PATCH /kasir/menu/{menu}/availability`, `POST /pesan/checkout`, `POST /pesanan/{kode_pesanan}/bayar-lagi`, `POST/PUT/PATCH/DELETE /admin/menu` + `PATCH /admin/menu/{menu}/availability`, `POST/PUT/PATCH/DELETE /admin/promosi` + `PATCH /admin/promosi/{promo}/status`, serta `PUT /admin/toko` **selesai**; action lain menyusul |
| Data contoh laporan | `GET /admin/laporan` dari DB | **Selesai** - `ReportService` + filter `bulan`; rincian lewat `GET /admin/laporan/{order}` |
| Belum ada webhook | `/api/midtrans/notification` | **Selesai** - route + CSRF exception + verifikasi signature |
| Belum ada role | `auth` + `role:*` | **Selesai** - middleware `role` aktif di area kasir/admin |
| Belum ada rate limit | `throttle:*` | Login, `GET /kasir/pesanan/poll`, `POST /kasir/pesanan/{order}/cancel`, `POST /kasir/pesanan/{order}/check-status`, `POST /pesan/checkout`, dan status pesanan **selesai**; webhook sengaja tanpa throttle ketat |

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
