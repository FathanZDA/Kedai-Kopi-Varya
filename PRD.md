# PRODUCT REQUIREMENTS DOCUMENT (PRD)
## Website UMKM Kedai Kopi Varya

**Informasi Dokumen**
* **Nama Produk:** Website Kedai Kopi Varya (pemesanan, kasir, dan manajemen kedai)
* **Tanggal:** 21 Oktober 2026
* **Penyusun:** Kelompok 6
* **Konteks:** Tugas Project Based Learning (PJBL)
* **Status:** Acuan implementasi

---

### 1. Ringkasan Produk

Website Kedai Kopi Varya adalah sistem berbasis web untuk UMKM Kedai Kopi Varya yang menggabungkan empat kebutuhan dalam satu platform:

* Etalase digital untuk pelanggan yang berada di luar kedai.
* Pemesanan mandiri lewat *scan* QR untuk pelanggan yang berada di kedai.
* Antarmuka kasir untuk membuat pesanan tunai, memantau pesanan masuk, dan mengatur ketersediaan menu.
* Panel admin/owner untuk mengelola menu, laporan, promosi, dan informasi toko.

Tujuan utamanya adalah merapikan alur pemesanan yang saat ini dicatat manual oleh kasir, sehingga pesanan tercatat berurutan, antrean lebih cepat dilayani, dan data transaksi tersimpan rapi untuk laporan bulanan.

Pelanggan B membayar pesanan langsung di website menggunakan **QRIS dinamis melalui Midtrans Sandbox**. Status pembayaran diperbarui otomatis dari notifikasi (*webhook*) Midtrans, sehingga kasir tidak perlu mencocokkan mutasi atau menandai lunas secara manual. Pesanan baru masuk antrean produksi setelah pembayaran berstatus **lunas**.

Ruang lingkup aplikasi dibuat lebih fokus:

* **Kasir** memiliki tiga fitur utama: Buat Pesanan, Pesanan Masuk, dan Kelola Menu. Halaman awal setelah login adalah **Buat Pesanan**.
* **Admin** memiliki lima fitur utama: Dashboard, Kelola Menu, Laporan Transaksi, Iklan & Promosi, dan Informasi Toko.

---

### 2. Latar Belakang dan Masalah

#### 2.1 Profil Kedai

* **Nama usaha:** Kedai Kopi Varya
* **Mulai beroperasi:** 27 Agustus
* **Grand Opening:** 5 September
* **Fasilitas:** PS Box (gratis)
* **Ciri khas:** Varya mode malam, yang menyediakan beberapa *event* khusus
* **Menu terlaris:**
  * Makanan: Mie Ae
  * Minuman kopi: Vanilla Latte
  * Minuman non-kopi: Heejo

#### 2.2 Kondisi Saat Ini

Saat ini kasir menginput data pemesanan melalui Firebase, sedangkan *spreadsheet* digunakan sebagai database untuk menyimpan data. Konfirmasi pembayaran QRIS juga masih dilakukan manual: kasir harus mencocokkan pembayaran pelanggan lalu mengubah status pesanan.

#### 2.3 Permasalahan

* Terjadi *delay* pemesanan.
* Input pesanan menumpuk dan tidak terurut.
* Data tersebar di dua tempat (Firebase untuk input, *spreadsheet* untuk penyimpanan), sehingga berpotensi sulit direkap dan rawan selisih.
* Konfirmasi pembayaran QRIS secara manual berpotensi lambat dan rawan keliru, terutama saat kedai ramai.
* Pesanan yang belum dibayar dapat mengganggu urutan antrean bila langsung dimasukkan ke antrean produksi.

*(Catatan: Poin ketiga, keempat, dan kelima adalah analisis tambahan dari gambaran kondisi saat ini dan perlu dikonfirmasi dengan pihak kedai).*

---

### 3. Tujuan dan Indikator Keberhasilan

#### 3.1 Tujuan

* Mempercepat dan merapikan proses pemesanan, termasuk pesanan dari pelanggan di kedai lewat QR.
* Mengurangi input manual oleh kasir sehingga antrean tidak menumpuk.
* Menyediakan data transaksi terpusat untuk riwayat harian dan laporan bulanan.
* Memberi pelanggan di luar kedai informasi menu, sejarah, promosi, dan lokasi kedai.
* Memberi *owner* kendali atas menu, promosi, dan informasi toko tanpa bantuan *developer*.
* **Memverifikasi pembayaran QRIS Pelanggan B secara otomatis melalui Midtrans**, sehingga kasir tidak perlu mencocokkan pembayaran secara manual.
* Menjaga integritas data pesanan yang sudah dibuat dengan melarang perubahan item setelah pesanan QRIS dibuat.

#### 3.2 Indikator Keberhasilan

*(Target angka ditetapkan setelah kondisi awal / baseline diukur bersama pihak kedai).*

| Indikator | Cara Pengukuran | Target |
|---|---|---|
| Kecepatan pencatatan pesanan | Selisih waktu pesanan dibuat hingga tercatat di kasir | Lebih cepat dari baseline |
| Urutan pesanan | Pesanan tampil berdasarkan waktu masuk | Seluruh pesanan terurut |
| Penggunaan pesanan lewat QR | Persentase pesanan yang berasal dari QR | Ditetapkan kemudian |
| Akurasi laporan | Kecocokan laporan sistem dengan rekap kasir dan transaksi Midtrans | Tidak ada selisih |
| Akurasi status pembayaran | Jumlah pesanan yang statusnya sesuai dengan dashboard Midtrans | Tidak ada pesanan lunas yang tertinggal berstatus menunggu |
| Kecepatan konfirmasi QRIS | Selisih waktu pelanggan menyelesaikan pembayaran hingga status menjadi lunas | Target operasional ≤ 10 detik setelah notifikasi diterima |
| Kemudahan penggunaan | Umpan balik singkat dari kasir dan owner | Mayoritas menilai mudah |

---

### 4. Ruang Lingkup

#### 4.1 Dalam Lingkup

* *Landing page*, menu, tentang toko, dan lokasi untuk Pelanggan A.
* Pemesanan mandiri dengan keranjang untuk Pelanggan B melalui QR di kedai.
* **Pembayaran QRIS Pelanggan B melalui Midtrans Sandbox (Snap), termasuk halaman konfirmasi pembayaran dan nomor antrean setelah lunas.**
* **Penerimaan notifikasi pembayaran (*webhook*) Midtrans dan pembaruan status pesanan otomatis.**
* **Pembayaran ulang untuk pesanan yang gagal atau kedaluwarsa melalui halaman konfirmasi.**
* **Pemberitahuan bahwa nama/nomor antrean akan dipanggil saat pesanan selesai.**
* Kasir:
  * Login dan pembatasan akses per *role*.
  * **Buat Pesanan:** halaman awal setelah login; input pesanan pelanggan langsung di kasir dengan pembayaran tunai, termasuk perhitungan kembalian.
  * **Pesanan Masuk:** daftar pesanan QRIS dan tunai, pemantauan status pembayaran, serta perubahan status pesanan.
  * **Kelola Menu:** mengubah status ketersediaan menu (tersedia/habis).
* Admin:
  * Login dan pembatasan akses per *role*.
  * **Dashboard:** ringkasan penjualan dan aktivitas toko.
  * **Kelola Menu:** menambah, mengubah, menghapus, dan mengatur ketersediaan menu.
  * **Laporan Transaksi:** laporan per bulan yang hanya menghitung transaksi lunas, terhubung dengan data kasir dan pesanan QR.
  * **Iklan & Promosi:** mengelola konten promosi yang tampil di *landing page*.
  * **Informasi Toko:** mengatur profil, jam operasional, alamat, lokasi, dan kontak toko.
* **Nomor antrean harian** yang dibuat otomatis setelah pembayaran lunas atau saat pesanan tunai disimpan.
* **Penyimpanan riwayat notifikasi pembayaran** untuk keperluan audit teknis.

#### 4.2 Di Luar Lingkup

* Metode pembayaran lain untuk Pelanggan B di luar QRIS (kartu kredit, *virtual account*, GoPay/OVO/ShopeePay langsung, dan sebagainya). Snap hanya mengaktifkan kanal `other_qris`.
* **QRIS untuk pesanan yang dibuat langsung oleh kasir (*walk-in*).** Pesanan kasir hanya menggunakan tunai pada versi ini.
* **Konfirmasi pembayaran QRIS secara manual oleh kasir.**
* **Fitur pelacakan/cek pesanan mandiri oleh pelanggan** (menu Cek Pesanan, pencarian pesanan berdasarkan kode, atau halaman status yang dapat dibuka kapan saja).
* **Pembatalan pesanan oleh pelanggan.** Pesanan yang tidak dibayar akan kedaluwarsa otomatis; pembatalan pesanan belum dibayar dilakukan oleh kasir.
* **Halaman rekonsiliasi atau koreksi status pembayaran di dalam aplikasi.** Pemeriksaan dan koreksi dilakukan melalui dashboard Midtrans.
* **Refund, pembatalan pembayaran lunas, dan penanganan sengketa di dalam aplikasi.** Diproses manual melalui dashboard Midtrans.
* Pencairan dana (*settlement/payout*) dan proses refund otomatis.
* Aktivasi Midtrans produksi (*go-live*). Versi ini menggunakan **Midtrans Sandbox**; produksi memerlukan akun, domain HTTPS, dan persetujuan merchant.
* Manajemen akun kasir/admin melalui antarmuka. Akun awal dibuat melalui *seeder*.
* Nomor meja pada pesanan Pelanggan B.
* Program loyalitas atau poin pelanggan.
* Manajemen stok bahan baku dan multi-cabang.
* Ekspor laporan PDF/Excel tidak termasuk kebutuhan MVP; tersedia sebagai fitur opsional pada Fase 2.
* Notifikasi *real-time* berbasis *websocket*. Versi ini menggunakan *polling* singkat.

#### 4.3 Peta Halaman Utama

| Area | Halaman | Keterangan |
|---|---|---|
| Publik | `/` | Landing page, promosi aktif, informasi singkat |
| Auth | `/login` | Login kasir/admin, lalu diarahkan sesuai role (kasir → Buat Pesanan, admin → Dashboard) |
| Publik | `/menu` | Menu read-only untuk Pelanggan A |
| Publik | `/tentang` | Sejarah dan profil kedai |
| Publik | `/lokasi` | Alamat, peta, jam operasional, kontak |
| Pelanggan B | `/pesan` | Menu mode pesan setelah scan QR |
| Pelanggan B | `/pesanan/{kode}` | Halaman konfirmasi pembayaran yang dibuka otomatis dari Snap; bukan fitur pelacakan |
| Kasir | `/kasir/pesanan/baru` | **Buat Pesanan tunai (halaman awal setelah login)** |
| Kasir | `/kasir/pesanan` | Pesanan Masuk |
| Kasir | `/kasir/menu` | Kelola Menu (ketersediaan) |
| Admin | `/admin/dashboard` | Dashboard |
| Admin | `/admin/menu` | Kelola Menu (CRUD) |
| Admin | `/admin/laporan` | Laporan Transaksi |
| Admin | `/admin/promosi` | Iklan & Promosi |
| Admin | `/admin/toko` | Informasi Toko |
| Sistem | `/api/midtrans/notification` | Endpoint webhook Midtrans (publik) |

*(Nama route dapat disesuaikan saat implementasi; yang penting pemisahan area dan hak aksesnya.)*

**Catatan:** Tidak ada route `/cek-pesanan`. Halaman `/pesanan/{kode}` hanya menjadi tujuan redirect Snap dan halaman konfirmasi yang dapat di-refresh; halaman ini tidak ditautkan sebagai menu pelacakan.

---

### 5. Pengguna dan Peran

#### 5.1 Pelanggan A — Informasional

* **Deskripsi:** Pelanggan di luar lokasi kedai.
* **Akses:** Tanpa login.
* **Halaman:** *Landing page*, menu (hanya lihat), tentang toko, lokasi.
* **Kebutuhan utama:** Mengetahui menu, harga, promosi, sejarah, jam operasional, dan lokasi kedai.

#### 5.2 Pelanggan B — Pemesan di Tempat via QR

* **Deskripsi:** Pelanggan di lokasi kedai yang membuka website lewat *scan* QR.
* **Akses:** Tanpa login.
* **Halaman:** Menu mode pesan, keranjang, *checkout*, pembayaran QRIS Midtrans, halaman konfirmasi pembayaran, serta halaman publik lainnya.
* **Kebutuhan utama:** Memesan dan membayar sendiri dengan cepat tanpa antre, lalu menunggu nama/nomor antrean dipanggil saat pesanan selesai.

#### 5.3 Kasir

* **Deskripsi:** Petugas yang melayani pelanggan antre dan memproses transaksi.
* **Akses:** Login.
* **Tiga fitur utama:**
  1. **Buat Pesanan** — halaman awal setelah login; menginput pesanan pelanggan langsung di kasir dengan pembayaran tunai, termasuk menghitung kembalian.
  2. **Pesanan Masuk** — memantau pesanan QRIS yang statusnya diperbarui otomatis, melihat antrean, mengubah status pesanan, dan melihat riwayat pesanan selesai hari ini. Tidak ada kartu ringkasan penjualan.
  3. **Kelola Menu** — mengubah status ketersediaan menu (tersedia/habis).
* **Kebutuhan utama:** Melayani pembayaran tunai dengan cepat dan menyiapkan pesanan yang sudah lunas, tanpa mencocokkan pembayaran QRIS secara manual.

#### 5.4 Admin / Owner

* **Deskripsi:** Pemilik atau pengelola kedai.
* **Akses:** Login.
* **Lima fitur utama:**
  1. **Dashboard** — ringkasan penjualan dan aktivitas toko.
  2. **Kelola Menu** — menambah, mengubah, menghapus, dan mengatur ketersediaan menu.
  3. **Laporan Transaksi** — laporan penjualan per bulan yang terhubung dengan transaksi kasir dan pesanan QR.
  4. **Iklan & Promosi** — mengelola konten promosi di *landing page*.
  5. **Informasi Toko** — mengatur profil, jam operasional, alamat, dan kontak toko.
* **Kebutuhan utama:** Mengelola menu dan konten toko, serta memantau penjualan tanpa bantuan *developer*.

---

### 6. Alur Pengguna Utama

*(Diagram alur / activity diagram dan use case diagram tersedia sebagai dokumen terpisah. Ringkasan alurnya sebagai berikut).*

#### 6.1 Pelanggan A

1. Membuka website dan melihat *landing page* berisi promosi dan informasi singkat kedai.
2. Memilih halaman Menu, Tentang toko, atau Lokasi.
3. Melihat informasi, lalu memilih halaman lain atau selesai.

#### 6.2 Pelanggan B (via QR)

1. *Scan* QR di meja dan langsung masuk ke halaman menu mode pesan.
2. Mengatur jumlah produk dengan *number stepper*. Keranjang muncul di sisi kanan saat tombol "+" ditekan.
3. Menambah item lagi atau menekan tombol **Pesan**.
4. Mengisi nama, memilih tipe pesanan (makan di tempat/bawa pulang), dan mengisi catatan bila perlu. Metode pembayaran sudah tetap **QRIS**.
5. Menekan tombol **Bayar dengan QRIS**.
6. Sistem memvalidasi keranjang, menyimpan pesanan berstatus **menunggu pembayaran**, membuat percobaan pembayaran Midtrans dengan `order_id` unik, lalu menampilkan halaman pembayaran QRIS dari Midtrans Snap.
7. Pelanggan memindai QRIS dengan aplikasi *mobile banking* atau *e-wallet* yang mendukung QRIS, lalu menyelesaikan pembayaran.
8. Midtrans mengirim notifikasi pembayaran ke sistem. Jika berstatus `settlement` atau `capture` dengan `fraud_status=accept`, sistem menandai pesanan **lunas** dan membuat **nomor antrean**.
9. Pelanggan melihat halaman konfirmasi berisi status lunas, nomor antrean, kode pesanan, ringkasan pesanan, dan pesan bahwa **nama/nomor antrean akan dipanggil saat pesanan selesai**.
10. Jika pembayaran belum selesai, halaman konfirmasi menampilkan status **menunggu pembayaran** dan memperbarui dirinya secara otomatis. Tidak ada menu atau tombol **Cek Pesanan** manual.
11. Jika pembayaran gagal atau kedaluwarsa, pelanggan dapat mencoba lagi dari halaman konfirmasi. Nomor antrean tidak diberikan untuk pesanan yang belum lunas.
12. Setelah memesan, pelanggan dapat membuka Home, Tentang toko, Menu, dan Lokasi.

**Catatan penting:** Pesanan dibuat **sebelum** pembayaran karena QRIS dinamis Midtrans memerlukan `order_id` dari pesanan. Pesanan tersebut belum masuk antrean produksi sampai pembayaran lunas.

#### 6.3 Kasir

**A. Alur Buat Pesanan (Tunai) — Halaman Awal**

1. Kasir login dan langsung diarahkan ke halaman **Buat Pesanan**.
2. Sistem menampilkan pencarian menu, filter kategori, grid menu, dan panel pesanan.
3. Kasir memilih menu yang tersedia dan mengatur jumlahnya.
4. Kasir mengisi nama pelanggan, memilih tipe pesanan (Dine In/Take Away), dan mengisi catatan bila perlu.
5. Sistem menghitung subtotal dan total pembayaran.
6. Kasir menekan **Proses Bayar**, lalu menginput jumlah uang tunai yang diterima.
7. Sistem memvalidasi jumlah uang dan menampilkan kembalian.
8. Kasir menyimpan transaksi. Sistem menandai pesanan **lunas**, membuat **nomor antrean**, dan menampilkan konfirmasi.
9. Pesanan langsung masuk ke tab **Antrean** di halaman **Pesanan Masuk**, lalu form **Buat Pesanan** dikosongkan untuk pesanan berikutnya.

**B. Alur Pesanan Masuk**

1. Kasir membuka halaman **Pesanan Masuk** dari sidebar.
2. Sistem menampilkan daftar pesanan tanpa kartu ringkasan dashboard.
3. Kasir memilih tab **Belum Dibayar**, **Antrean**, **Diproses**, atau **Selesai**.
4. Pesanan QRIS yang sudah dibayar otomatis berpindah dari tab **Belum Dibayar** ke **Antrean** setelah notifikasi Midtrans diterima.
5. Kasir membuka detail pesanan, lalu menekan **Mulai Proses** saat pesanan mulai disiapkan.
6. Setelah pesanan selesai, kasir menekan **Selesai**.
7. Jika status pembayaran QRIS terlihat belum berubah padahal pelanggan sudah membayar, kasir menekan **Cek Status** untuk memeriksa ke Midtrans.
8. Kasir dapat membatalkan pesanan yang **belum dibayar**. Pesanan yang sudah lunas tidak dapat dibatalkan dari aplikasi.

**C. Alur Kelola Menu**

1. Kasir membuka halaman **Kelola Menu**.
2. Sistem menampilkan daftar menu beserta kategori, harga, dan status ketersediaan.
3. Kasir mengubah status menu menjadi **Tersedia** atau **Habis**.
4. Perubahan langsung berlaku pada halaman menu Pelanggan A dan Pelanggan B.
5. Kasir tidak dapat mengubah nama, harga, deskripsi, foto, menambah menu, atau menghapus menu.

#### 6.4 Admin

1. Login dan melihat **Dashboard** berisi ringkasan penjualan dan aktivitas toko.
2. Memilih salah satu fitur: Dashboard, Kelola Menu, Laporan Transaksi, Iklan & Promosi, atau Informasi Toko.
3. **Kelola Menu:** menambah menu baru, mengubah menu, menghapus menu (soft delete), dan mengatur ketersediaan.
4. **Laporan Transaksi:** memfilter laporan per bulan dan melihat rincian transaksi; pendapatan hanya dihitung dari transaksi lunas.
5. **Iklan & Promosi:** menambah, mengubah, menonaktifkan, atau menghapus konten promosi.
6. **Informasi Toko:** mengubah profil, jam operasional, alamat, lokasi, dan kontak toko.
7. Menyimpan perubahan, lalu memilih fitur lain atau keluar.

---

### 7. Kebutuhan Fungsional

*Prioritas menggunakan skala MoSCoW: Must (wajib), Should (sebaiknya ada), Could (opsional).*

#### 7.1 Umum

* **[U-01] Login:** Kasir dan admin masuk dengan akun masing-masing. Pelanggan tidak perlu login. *(Prioritas: Must)*
* **[U-02] Pembatasan akses per role:** Halaman kasir hanya untuk kasir, halaman admin hanya untuk admin. *(Prioritas: Must)*
* **[U-03] Deteksi Pelanggan B:** QR berisi URL khusus (misalnya `/pesan`) yang langsung membuka menu mode pesan tanpa login. *(Prioritas: Must)*
* **[U-04] Sesi dan logout:** Sesi login berakhir saat logout atau kedaluwarsa. *(Prioritas: Must)*

#### 7.2 Pelanggan A

* **[A-01] Landing page:** Halaman utama berisi sambutan, promosi aktif, dan informasi singkat kedai (fasilitas PS Box gratis, *event* Varya mode malam, jam operasional). *(Prioritas: Must)*
* **[A-02] Lihat menu:** Menampilkan gambar, nama, harga, dan deskripsi produk. Menu yang berstatus habis tetap tampil dengan label **Habis**, tetapi tidak dapat dipesan. *(Prioritas: Must)*
* **[A-03] Tentang toko:** Sejarah singkat dan profil kedai. *(Prioritas: Must)*
* **[A-04] Lokasi:** Alamat, tautan peta, jam operasional, dan kontak kedai. *(Prioritas: Must)*

#### 7.3 Pelanggan B

* **[B-01] Masuk langsung ke menu:** Setelah *scan* QR, pelanggan langsung menuju halaman menu mode pesan. *(Prioritas: Must)*
* **[B-02] Lihat menu:** Nama, harga, deskripsi, dan foto produk. Menu yang berstatus habis tetap tampil dengan label **Habis**, tetapi tidak dapat dipesan. *(Prioritas: Must)*
* **[B-03] Number stepper:** Tombol +/- untuk mengatur jumlah tiap produk. *(Prioritas: Must)*
* **[B-04] Keranjang:** Keranjang muncul saat tombol "+" ditekan; di layar desktop tampil di sisi kanan, di layar HP tampil sebagai panel bawah. Keranjang berisi item, jumlah, subtotal, dan total, serta dipertahankan saat halaman di-refresh. *(Prioritas: Must)*
* **[B-05] Form pesanan:** Tombol **Pesan** membuka isian nama (wajib), tipe pesanan (makan di tempat/bawa pulang, wajib), dan catatan (opsional). Metode pembayaran tetap **QRIS**. *(Prioritas: Must)*
* **[B-06] Membuat pesanan dan pembayaran QRIS:** Setelah dikonfirmasi, sistem memvalidasi keranjang, menyimpan pesanan berstatus **menunggu pembayaran**, membuat transaksi Midtrans Snap, dan menampilkan QRIS dinamis. *(Prioritas: Must)*
* **[B-07] Halaman konfirmasi pembayaran:** Setelah Snap selesai, sistem menampilkan halaman konfirmasi berisi status terkini, batas waktu, total, dan pembaruan otomatis. Halaman ini bukan fitur pelacakan dan tidak memiliki menu **Cek Pesanan** atau tombol **Cek Status** manual. Bila pembayaran gagal/kedaluwarsa, tersedia opsi **Coba Bayar Lagi**. *(Prioritas: Must)*
* **[B-08] Konfirmasi pesanan dan nomor antrean:** Setelah pembayaran lunas, sistem menampilkan nomor antrean, kode pesanan, dan ringkasan pesanan. Nomor antrean tidak diberikan untuk pesanan yang belum dibayar. *(Prioritas: Must)*
* **[B-09] Pemberitahuan panggilan:** Halaman konfirmasi menampilkan pesan bahwa **nama/nomor antrean akan dipanggil saat pesanan selesai**. *(Prioritas: Must)*
* **[B-10] Validasi ketersediaan saat checkout:** Bila ada menu yang menjadi habis atau harganya berubah sejak dimasukkan ke keranjang, sistem menolak checkout dan meminta pelanggan menyesuaikan keranjang. *(Prioritas: Must)*
* **[B-11] Pembayaran ulang:** Pelanggan dapat membuat percobaan pembayaran baru untuk pesanan yang gagal atau kedaluwarsa, dengan `order_id` pembayaran baru. Opsi ini hanya muncul di halaman konfirmasi. *(Prioritas: Must)*

#### 7.4 Kasir

Kasir hanya memiliki **tiga fitur utama** berikut. Tidak ada halaman dashboard, kartu ringkasan penjualan, atau riwayat terpisah. Halaman awal setelah login adalah **Buat Pesanan**.

##### [K-01] Buat Pesanan — Tunai *(Prioritas: Must)*

* **[K-01a] Halaman awal:** Setelah login, kasir langsung diarahkan ke halaman **Buat Pesanan**.
* **[K-01b] Pilih menu:** Menampilkan menu yang tersedia dengan pencarian dan filter kategori **Semua**, **Kopi**, **Teh**, **Autentik**, dan **Makanan**. Menu habis tetap tampil dengan label **Habis** dan tidak dapat dipilih.
* **[K-01c] Keranjang pesanan:** Kasir dapat menambah, mengurangi, dan menghapus item, serta mengisi catatan per item.
* **[K-01d] Data pelanggan:** Nama pelanggan (wajib), tipe pesanan **Dine In** atau **Take Away** (wajib), dan nama kasir yang terisi otomatis dari akun yang login.
* **[K-01e] Pembayaran tunai:** Sistem menghitung subtotal dan total, kasir menginput uang diterima, sistem memvalidasi `uang_diterima >= total`, lalu menampilkan kembalian.
* **[K-01f] Simpan transaksi:** Saat disimpan, pesanan langsung berstatus **lunas**, mendapatkan **nomor antrean**, dan masuk tab **Antrean**.
* **[K-01g] Konfirmasi:** Sistem menampilkan nomor antrean dan ringkasan pembayaran setelah transaksi disimpan, lalu mengosongkan form untuk pesanan berikutnya.
* **[K-01h] Batasan:** Halaman ini hanya mendukung pembayaran tunai. Tidak ada pembayaran QRIS untuk pesanan yang dibuat kasir. Opsi QRIS pada desain tidak digunakan.

##### [K-02] Pesanan Masuk *(Prioritas: Must)*

* **[K-02a] Daftar pesanan:** Pesanan ditampilkan berurutan berdasarkan waktu masuk, lengkap dengan kode pesanan, **nomor antrean**, nama pelanggan, tipe pesanan, total, sumber (QR/tunai), metode pembayaran, dan status pembayaran. Pesanan dengan status **perlu diperiksa** diberi penanda khusus.
* **[K-02b] Tab pesanan:** Tersedia tab **Belum Dibayar**, **Antrean**, **Diproses**, dan **Selesai**. Tab **Selesai** berfungsi sebagai riwayat pesanan hari ini. Pesanan yang dibatalkan tidak ditampilkan secara default, tetapi datanya tetap tersedia untuk laporan admin.
* **[K-02c] Detail pesanan:** Menampilkan item, jumlah, harga satuan, subtotal, catatan, waktu pesanan, **nomor antrean**, status pembayaran, serta ID transaksi Midtrans untuk pesanan QRIS.
* **[K-02d] Pembaruan otomatis:** Status pembayaran QRIS diperbarui tanpa perlu me-refresh halaman, menggunakan *polling* singkat (5–10 detik).
* **[K-02e] Cek Status (kasir):** Tombol khusus halaman Pesanan Masuk untuk memeriksa status pembayaran langsung ke API Midtrans bila notifikasi terasa terlambat. Tombol ini tidak tersedia di halaman pelanggan.
* **[K-02f] Status pesanan:** Kasir dapat mengubah status **Antrean → Diproses → Selesai**.
* **[K-02g] Pembatalan pesanan belum dibayar:** Kasir dapat membatalkan pesanan yang belum dibayar setelah konfirmasi. Pesanan lunas tidak dapat dibatalkan dari aplikasi.
* **[K-02h] Pesanan QRIS bersifat read-only:** Kasir tidak dapat mengubah item, jumlah, atau total pesanan QRIS yang sudah dibuat. Kasir juga tidak dapat menandai pembayaran QRIS menjadi lunas secara manual.

##### [K-03] Kelola Menu — Status Ketersediaan *(Prioritas: Must)*

* **[K-03a] Daftar menu:** Menampilkan seluruh menu beserta kategori, harga, dan status ketersediaan.
* **[K-03b] Ubah ketersediaan:** Kasir dapat mengubah status menu menjadi **Tersedia** atau **Habis**. Perubahan langsung berlaku pada halaman pelanggan.
* **[K-03c] Batasan:** Kasir tidak dapat menambah, menghapus, atau mengubah nama, harga, deskripsi, dan foto menu.

#### 7.5 Admin / Owner

Admin hanya memiliki **lima fitur utama** berikut.

##### [AD-01] Dashboard *(Prioritas: Must)*

* Ringkasan pendapatan hari ini dan bulan berjalan (hanya transaksi lunas).
* Jumlah transaksi lunas, jumlah pesanan menunggu pembayaran, dan jumlah pesanan yang sedang diproses.
* Perbandingan pendapatan QRIS dan tunai.
* Daftar 5 menu terlaris berdasarkan jumlah item terjual.
* Daftar 10 transaksi terbaru.

##### [AD-02] Kelola Menu *(Prioritas: Must)*

* **[AD-02a] Lihat menu:** Daftar menu dengan pencarian dan filter kategori/ketersediaan.
* **[AD-02b] Tambah menu:** Nama, kategori, harga, deskripsi, foto, dan status ketersediaan.
* **[AD-02c] Ubah menu:** Mengubah seluruh data menu, termasuk harga dan ketersediaan.
* **[AD-02d] Hapus menu:** Menghapus menu dengan *soft delete* setelah konfirmasi. Menu yang dihapus tidak tampil di halaman pelanggan dan kasir, tetapi riwayat pesanan lama tetap utuh.
* **[AD-02e] Kategori:** Kategori menggunakan daftar tetap yang sudah di-*seed* (contoh: Kopi, Non-Kopi, Makanan). Tidak ada CRUD kategori terpisah pada versi ini.

##### [AD-03] Laporan Transaksi *(Prioritas: Must)*

* **[AD-03a] Filter periode:** Laporan dapat difilter per bulan dan tahun, default bulan berjalan.
* **[AD-03b] Ringkasan:** Total pendapatan lunas, jumlah transaksi lunas, total QRIS lunas, total tunai, dan rata-rata nilai transaksi.
* **[AD-03c] Tabel transaksi:** Tanggal, kode pesanan, nomor antrean, nama pelanggan, sumber (QR/kasir), metode pembayaran, status pembayaran, dan total.
* **[AD-03d] Rincian transaksi:** Item pesanan, uang diterima dan kembalian (tunai), serta ID transaksi Midtrans (QRIS).
* **[AD-03e] Dasar perhitungan:** Hanya transaksi berstatus **lunas** yang dihitung sebagai pendapatan. Pesanan menunggu, gagal, kedaluwarsa, atau dibatalkan tidak dihitung. Tabel tetap dapat menampilkan transaksi dengan status lain sebagai informasi, tetapi tidak masuk total pendapatan.
* **[AD-03f] Keterhubungan dengan kasir:** Transaksi tunai yang dibuat kasir dan pesanan QRIS yang lunas muncul otomatis di laporan. Pesanan QRIS yang belum lunas dapat ditampilkan sebagai informasi terpisah, tetapi tidak dihitung sebagai pendapatan.
* **[AD-03g] Ekspor:** Unduh laporan bulanan dalam format PDF/Excel. *(Prioritas: Could)*

##### [AD-04] Iklan & Promosi *(Prioritas: Must)*

* **[AD-04a] Daftar promosi:** Menampilkan judul, gambar, periode, dan status aktif.
* **[AD-04b] Tambah/ubah promosi:** Judul, deskripsi, gambar, urutan tampil, serta periode mulai dan berakhir (opsional).
* **[AD-04c] Aktif/nonaktif:** Promosi nonaktif tidak tampil di *landing page*.
* **[AD-04d] Hapus promosi:** Menghapus promosi setelah konfirmasi.
* **[AD-04e] Tampilan:** Promosi aktif tampil di *landing page* sesuai urutan.

##### [AD-05] Informasi Toko *(Prioritas: Must)*

* **[AD-05a] Profil toko:** Nama, tagline, dan deskripsi/sejarah singkat.
* **[AD-05b] Jam operasional:** Jam buka dan tutup untuk setiap hari, termasuk status tutup.
* **[AD-05c] Lokasi:** Alamat, tautan/embed peta, dan petunjuk lokasi.
* **[AD-05d] Kontak:** Nomor telepon/WhatsApp, Instagram, dan kontak lain yang ditampilkan.
* **[AD-05e] Fasilitas:** Informasi fasilitas seperti PS Box gratis.
* **[AD-05f] Perubahan langsung:** Perubahan informasi toko langsung tampil di halaman publik setelah disimpan.

#### 7.6 Integrasi Pembayaran Midtrans Sandbox

* **[PG-01] Pembuatan transaksi Snap:** Backend membuat transaksi Midtrans Snap dengan `order_id` unik, `gross_amount` sesuai total pesanan, `item_details`, `customer_details`, `enabled_payments: ["other_qris"]`, batas waktu 15 menit, dan `callbacks.finish` menuju halaman konfirmasi pembayaran. *(Prioritas: Must)*
* **[PG-02] Tampilan pembayaran QRIS:** Halaman pembayaran menampilkan QRIS dari Midtrans (Snap *redirect* atau *popup*) dan nyaman digunakan di browser HP. *(Prioritas: Must)*
* **[PG-03] Notifikasi pembayaran:** Endpoint publik menerima `POST` notifikasi Midtrans, memverifikasi `signature_key` dengan rumus `SHA512(order_id + status_code + gross_amount + server_key)`, serta memvalidasi `order_id` dan nominal. *(Prioritas: Must)*
* **[PG-04] Sinkronisasi status:** Memetakan status Midtrans ke status aplikasi sesuai tabel pada bagian 11.3. Nomor antrean dibuat saat `settlement` atau `capture` dengan `fraud_status=accept`. *(Prioritas: Must)*
* **[PG-05] Idempotensi:** Notifikasi ganda atau pesanan yang sudah lunas tidak boleh membuat nomor antrean ganda atau mengubah total. *(Prioritas: Must)*
* **[PG-06] Pembuatan nomor antrean:** Nomor antrean dibuat secara atomik saat pembayaran lunas (QRIS) atau saat pesanan tunai disimpan. *(Prioritas: Must)*
* **[PG-07] Cek Status (kasir):** Tombol Cek Status di halaman Pesanan Masuk memanggil API status Midtrans dan memperbarui status pesanan bila berbeda. Tombol ini tidak tersedia untuk pelanggan. *(Prioritas: Must)*
* **[PG-08] Rekonsiliasi terjadwal:** Proses terjadwal memeriksa pesanan yang masih menunggu pembayaran dan menyamakan statusnya dengan Midtrans. *(Prioritas: Should)*
* **[PG-09] Satu percobaan aktif:** Hanya satu percobaan pembayaran aktif per pesanan. Percobaan baru hanya dibuat setelah percobaan sebelumnya gagal atau kedaluwarsa. *(Prioritas: Must)*
* **[PG-10] Keamanan kredensial:** `server_key` hanya disimpan di `.env` backend dan tidak pernah dikirim ke browser. `client_key` boleh digunakan di frontend. Mode sandbox/produksi diatur lewat konfigurasi. *(Prioritas: Must)*
* **[PG-11] Audit notifikasi:** Menyimpan payload notifikasi, status validasi, dan hasil pemrosesan untuk keperluan audit teknis. *(Prioritas: Must)*
* **[PG-12] Penanganan kegagalan Snap:** Bila pembuatan transaksi Snap gagal, pesanan tetap tersimpan dengan status menunggu pembayaran dan pelanggan dapat mencoba lagi. *(Prioritas: Must)*
* **[PG-13] Anomali nominal:** Bila `gross_amount` valid tetapi tidak sama dengan total pesanan, pesanan ditandai **perlu diperiksa**, tidak diberi nomor antrean, dan dicatat di log. *(Prioritas: Must)*

#### 7.7 Fitur Tambahan yang Disarankan

* **[X-01] Ekspor laporan:** Unduh laporan bulanan dalam format PDF atau Excel. *(Prioritas: Could)*
* **[X-02] Notifikasi real-time:** Pembaruan pesanan masuk tanpa *polling* (websocket/SSE). *(Prioritas: Could)*
* **[X-03] Generate QR:** Admin membuat dan mengunduh QR yang mengarah ke mode Pelanggan B. *(Prioritas: Could)*
* **[X-04] Kelola akun kasir:** Admin menambah atau menonaktifkan akun kasir. *(Prioritas: Could)*
* **[X-05] Nomor meja:** Pelanggan B mengisi nomor meja saat memesan. *(Prioritas: Could)*
* **[X-06] QRIS walk-in:** Kasir membuat tagihan QRIS Midtrans untuk pelanggan yang memesan langsung. *(Prioritas: Could)*

---

### 8. Aturan Bisnis

#### 8.1 Prinsip Umum

1. Pelanggan B hanya dapat memesan dan membayar melalui **QRIS Midtrans**. Pelanggan B tidak dapat memilih tunai.
2. Pesanan yang dibuat kasir hanya menggunakan **tunai**.
3. Pesanan Pelanggan B disimpan lebih dulu sebagai **menunggu pembayaran** untuk mendapatkan `order_id` Midtrans.
4. Pesanan belum lunas **tidak masuk antrean produksi** dan tidak mendapatkan nomor antrean.
5. **Notifikasi Midtrans (webhook) adalah sumber kebenaran status pembayaran.** Parameter yang dikirim saat pelanggan kembali ke halaman *finish* tidak boleh dijadikan dasar tunggal untuk menandai lunas.
6. Total pesanan adalah jumlah subtotal item. Tidak ada pajak, biaya layanan, atau pembulatan lain pada versi ini. Semua nominal menggunakan Rupiah bilangan bulat.
7. Zona waktu sistem adalah **Asia/Jakarta**.

#### 8.2 Definisi Status

**Status pembayaran (`status_pembayaran`):**

| Status | Arti |
|---|---|
| `menunggu` | Pembayaran belum diterima; transaksi Midtrans masih aktif |
| `lunas` | Pembayaran berhasil (`settlement` atau `capture` dengan `fraud_status=accept`) |
| `gagal` | Pembayaran ditolak/dibatalkan oleh Midtrans (`deny`, `cancel`, `failure`) |
| `kedaluwarsa` | Transaksi Midtrans melewati batas waktu (`expire`) |
| `dibatalkan` | Pesanan dibatalkan sebelum dibayar |
| `perlu_diperiksa` | Ada anomali (nominal tidak cocok, refund, chargeback) yang perlu ditangani manual |

**Status pesanan (`status_pesanan`):**

| Status | Arti |
|---|---|
| `menunggu_pembayaran` | Pesanan dibuat, belum lunas, belum masuk antrean |
| `antre` | Sudah lunas dan masuk antrean produksi |
| `diproses` | Sedang disiapkan oleh kasir/barista |
| `selesai` | Pesanan sudah diserahkan |
| `dibatalkan` | Pesanan dibatalkan sebelum dibayar |

**Transisi status pembayaran:**

| Dari | Ke | Pemicu |
|---|---|---|
| `menunggu` | `lunas` | `settlement` atau `capture` dengan `fraud_status=accept` |
| `menunggu` | `gagal` | `deny`, `cancel`, atau `failure` |
| `menunggu` | `kedaluwarsa` | `expire` |
| `menunggu` | `dibatalkan` | Pembatalan oleh kasir atau sistem berhasil |
| `menunggu` | `perlu_diperiksa` | Nominal tidak cocok atau `capture` dengan `fraud_status=challenge` |
| `gagal` / `kedaluwarsa` | `lunas` | Pembayaran ulang berhasil |
| `gagal` / `kedaluwarsa` | `dibatalkan` | Pembatalan oleh kasir atau penutupan otomatis setelah 24 jam |
| `lunas` | `perlu_diperiksa` | Refund, partial refund, atau chargeback |
| `perlu_diperiksa` | — | Hanya ditangani manual melalui dashboard Midtrans |
| `dibatalkan` | `perlu_diperiksa` | `settlement` valid diterima setelah pembatalan (race condition) |
| `dibatalkan` | — | Selain kasus di atas, final |

**Transisi status pesanan:**

| Dari | Ke | Pemicu |
|---|---|---|
| `menunggu_pembayaran` | `antre` | Pembayaran lunas (QRIS) atau pesanan tunai disimpan |
| `menunggu_pembayaran` | `dibatalkan` | Pembatalan oleh kasir atau penutupan otomatis setelah 24 jam |
| `antre` | `diproses` | Kasir menekan **Mulai Proses** |
| `diproses` | `selesai` | Kasir menekan **Selesai** |
| `selesai` | — | Final |
| `dibatalkan` | — | Final |

#### 8.3 Aturan Nomor Antrean

1. Nomor antrean berupa bilangan bulat yang ditampilkan dalam **tiga digit** (contoh: `001`, `002`).
2. Nomor antrean di-*reset* setiap hari pada pukul **00:00 Asia/Jakarta**.
3. Nomor antrean diberikan:
   * Saat pembayaran QRIS berstatus `settlement` atau `capture` dengan `fraud_status=accept`.
   * Saat pesanan tunai disimpan oleh kasir.
4. Nomor antrean dibuat secara **atomik** di dalam transaksi database untuk mencegah nomor ganda saat dua pembayaran masuk bersamaan.
5. Nomor antrean yang sudah diberikan **tidak digunakan ulang**, termasuk bila pesanan kemudian dibatalkan atau di-*refund* di luar aplikasi.
6. Pesanan yang gagal, kedaluwarsa, atau dibatalkan sebelum bayar **tidak pernah mendapat** nomor antrean.
7. Nomor antrean ditentukan berdasarkan waktu pesanan masuk antrean (waktu *settlement* untuk QRIS, waktu simpan untuk tunai), bukan waktu pesanan dibuat.

#### 8.4 Aturan Pembayaran QRIS

1. QRIS yang ditampilkan adalah **QRIS dinamis per pesanan** dari Midtrans, bukan QRIS statis milik kedai.
2. Batas waktu pembayaran adalah **15 menit** sejak transaksi Snap dibuat.
3. Satu pesanan hanya boleh memiliki **satu percobaan pembayaran aktif** dalam satu waktu.
4. `order_id` Midtrans menggunakan format `{kode_pesanan}-P{nomor_percobaan}`, contoh `V7K2M9QX-P1`. Percobaan berikutnya menggunakan `-P2`, `-P3`, dan seterusnya.
5. Bila percobaan aktif belum kedaluwarsa, tombol **Coba Bayar Lagi** membuka kembali tautan Snap yang sama, bukan membuat transaksi baru.
6. Percobaan baru hanya dibuat setelah percobaan sebelumnya berstatus `gagal` atau `kedaluwarsa`.
7. Bila dua pembayaran untuk pesanan yang sama sama-sama berhasil (kasus langka), pembayaran pertama yang diproses menjadi acuan; pembayaran kedua ditandai **perlu diperiksa** untuk penanganan/refund manual.
8. Pelanggan tidak memiliki tombol pembatalan, baik sebelum maupun sesudah lunas. Pesanan yang belum dibayar akan kedaluwarsa otomatis.
9. Pembatalan pesanan belum dibayar dilakukan oleh kasir atau sistem. Pembatalan oleh kasir hanya berhasil bila transaksi Midtrans berhasil dibatalkan atau sudah kedaluwarsa. Bila pembatalan Midtrans gagal, status pesanan tetap menunggu sampai kedaluwarsa.
10. Bila notifikasi `settlement` yang valid tetap diterima setelah pembatalan lokal (race condition), pesanan ditandai **perlu diperiksa**, tidak diberi nomor antrean, dan diselesaikan melalui refund manual.
11. Refund dan sengketa diproses di luar aplikasi melalui dashboard Midtrans. Status `refund`, `partial_refund`, dan `chargeback` tidak boleh menandai pesanan sebagai lunas; pesanan ditandai **perlu diperiksa**.

#### 8.5 Aturan Pembayaran Tunai

1. Pembayaran tunai hanya tersedia pada halaman **Buat Pesanan** milik kasir.
2. `uang_diterima` wajib diisi dan harus **lebih besar atau sama dengan** total pesanan.
3. Kembalian dihitung otomatis: `kembalian = uang_diterima - total`.
4. Saat transaksi disimpan, pesanan langsung berstatus **lunas** dan langsung mendapatkan nomor antrean.
5. Tidak ada pembayaran sebagian, utang, atau pembayaran campuran pada versi ini.
6. Pesanan tunai yang sudah disimpan tidak dapat diubah itemnya. Bila terjadi kesalahan, kasir harus menghubungi admin; koreksi transaksi dilakukan di luar alur normal.

#### 8.6 Aturan Menu

1. Hanya menu berstatus **Tersedia** yang dapat dipesan. Menu berstatus **Habis** tetap tampil di halaman pelanggan dengan label **Habis**, tetapi tidak dapat dipilih.
2. Menu berstatus **Habis** tetap terlihat oleh pelanggan, kasir, dan admin, tetapi tidak dapat dipilih pelanggan.
3. Perubahan ketersediaan oleh kasir langsung berlaku pada halaman pelanggan.
4. Harga dan nama menu disalin (*snapshot*) ke `order_items` saat pesanan dibuat. Perubahan harga di kemudian hari tidak mengubah riwayat pesanan.
5. Penghapusan menu menggunakan *soft delete*. Menu yang dihapus tidak tampil untuk penjualan baru, tetapi tetap tersimpan pada riwayat pesanan lama.
6. Admin dapat mengubah harga menu. Perubahan hanya berlaku untuk pesanan baru.
7. Kasir tidak dapat mengubah harga, nama, deskripsi, foto, menambah, atau menghapus menu.
8. Bila menu menjadi habis saat pelanggan sedang mengisi keranjang, checkout ditolak dan pelanggan diminta menyesuaikan keranjang.

#### 8.7 Aturan Pesanan

1. Pesanan QRIS bersifat **read-only** setelah dibuat. Kasir tidak dapat mengubah item, jumlah, atau total.
2. Kasir hanya dapat mengubah **status pesanan** (`antre` → `diproses` → `selesai`).
3. Pesanan tunai yang sudah disimpan juga tidak dapat diubah itemnya.
4. Pesanan yang sudah lunas tidak dapat dibatalkan dari aplikasi.
5. Pesanan yang belum dibayar dapat dibatalkan oleh kasir atau sistem. Pelanggan tidak memiliki tombol pembatalan.
6. Pesanan yang kedaluwarsa tetap dapat dibayar ulang dari halaman konfirmasi sampai sistem menutupnya setelah **24 jam**. Setelah 24 jam, pesanan otomatis berstatus `dibatalkan`.
7. Satu pesanan hanya milik satu pelanggan. Tidak ada penggabungan pesanan.
8. Nama pelanggan wajib diisi, maksimal 100 karakter. Catatan maksimal 500 karakter.

#### 8.8 Aturan Laporan

1. Hanya transaksi dengan `status_pembayaran = lunas` yang dihitung sebagai pendapatan.
2. Laporan menampilkan transaksi QRIS dan tunai.
3. Pesanan menunggu, gagal, kedaluwarsa, dibatalkan, atau perlu diperiksa tidak dihitung sebagai pendapatan, tetapi boleh ditampilkan sebagai informasi terpisah.
4. Laporan admin dan data pesanan kasir menggunakan sumber data yang sama (`orders`, `order_items`, `payments`).
5. Refund/chargeback yang diproses di luar aplikasi tidak mengubah laporan secara otomatis; admin mencatatnya secara operasional di luar sistem pada versi ini.

#### 8.9 Aturan Konten dan Pengaturan

1. Promosi aktif tampil di *landing page* sesuai urutan.
2. Promosi dengan periode berakhir yang sudah lewat tidak ditampilkan sebagai promosi aktif.
3. Perubahan informasi toko, jam operasional, dan promosi langsung berlaku setelah disimpan.
4. Admin bertanggung jawab atas kebenaran konten yang ditampilkan.

---

### 9. Kebutuhan Non-Fungsional

* **Kompatibilitas:** Tampilan responsif dengan pendekatan *mobile-first* karena Pelanggan B mengakses lewat HP. Berjalan di browser modern (Chrome, Safari, Edge, Firefox versi terbaru). Halaman pembayaran Midtrans juga harus nyaman di layar HP.
* **Kinerja:** Halaman menu dimuat cepat; pesanan dari QR muncul di kasir dalam waktu singkat; status pembayaran diperbarui maksimal beberapa detik setelah notifikasi diterima. *Polling* halaman kasir menggunakan interval 5–10 detik.
* **Keamanan:**
  * Autentikasi kasir dan admin, pembatasan akses per *role*, dan password disimpan dalam bentuk *hash*.
  * Validasi input dan proteksi CSRF pada form aplikasi.
  * Verifikasi tanda tangan notifikasi Midtrans, validasi `order_id`, dan validasi nominal.
  * *Rate limiting* pada pembuatan pesanan, pengecekan status, dan halaman konfirmasi pembayaran.
  * `server_key` hanya berada di backend dan tidak pernah dikirim ke browser.
  * Kode pesanan menggunakan karakter acak yang sulit ditebak.
  * Tidak ada data kartu atau kredensial pembayaran yang disimpan.
* **Keandalan:**
  * Data pesanan dan transaksi tidak hilang; tersedia pencadangan database.
  * Notifikasi yang gagal diproses dapat diulang dan diproses secara idempoten.
  * Tersedia mekanisme rekonsiliasi status dan penutupan pesanan kedaluwarsa.
  * Nomor antrean dibuat secara atomik dan tidak pernah ganda.
  * Pesanan yang belum dibayar tidak menghambat antrean produksi.
* **Kegunaan:** Antarmuka sederhana, terutama halaman kasir yang dipakai saat kedai ramai. Status pembayaran harus mudah dibedakan (menunggu, lunas, gagal, kedaluwarsa, perlu diperiksa).
* **Pemeliharaan:** Konten (menu, promosi, jam operasional, informasi toko) dapat diubah admin tanpa mengubah kode program. Kredensial dan mode sandbox/produksi diatur melalui konfigurasi.
* **Ketersediaan:** Pembayaran QRIS bergantung pada koneksi internet pelanggan dan ketersediaan layanan Midtrans. Sediakan prosedur cadangan (misalnya pesanan tunai di kasir) bila layanan tidak tersedia.
* **Privasi:** Data pelanggan dibatasi pada nama dan catatan pesanan. Tidak ada akun pelanggan, nomor telepon, atau alamat yang dikumpulkan pada versi ini.

---

### 10. Kebutuhan Data

#### 10.1 Tabel Utama

**users**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| name | string | Nama pengguna |
| email | string | Unik, untuk login |
| password | string | Hash |
| role | enum | `admin`, `kasir` |
| timestamps | datetime | Dibuat/diubah |

**categories**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| nama | string | Contoh: Kopi, Non-Kopi, Makanan |
| slug | string | Unik |

**menus**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| category_id | bigint | Relasi ke `categories` |
| nama | string | Nama menu |
| harga | integer | Rupiah, bilangan bulat |
| deskripsi | text | Nullable |
| foto | string | Path/URL gambar |
| tersedia | boolean | Default `true` |
| deleted_at | datetime | Soft delete |
| timestamps | datetime | Dibuat/diubah |

**orders**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| kode_pesanan | string(8) | Unik, acak, contoh `V7K2M9QX` |
| nomor_antrean | integer | Nullable sampai lunas |
| tanggal_antrean | date | Nullable, tanggal nomor antrean |
| nama_pelanggan | string(100) | Wajib |
| tipe_pesanan | enum | `dine_in`, `take_away` |
| catatan | text | Nullable, maks. 500 karakter |
| sumber | enum | `qr`, `kasir` |
| status_pesanan | enum | `menunggu_pembayaran`, `antre`, `diproses`, `selesai`, `dibatalkan` |
| metode_pembayaran | enum | `qris`, `tunai` |
| status_pembayaran | enum | `menunggu`, `lunas`, `gagal`, `kedaluwarsa`, `dibatalkan`, `perlu_diperiksa` |
| total | integer | Rupiah |
| uang_diterima | integer | Nullable, khusus tunai |
| kembalian | integer | Nullable, khusus tunai |
| waktu_lunas | datetime | Nullable |
| batas_waktu_pembayaran | datetime | Nullable, khusus QRIS |
| timestamps | datetime | Dibuat/diubah |

**order_items**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| order_id | bigint | Relasi ke `orders` |
| menu_id | bigint | Nullable, relasi ke `menus` |
| nama_menu | string | **Snapshot** nama saat dipesan |
| harga_satuan | integer | **Snapshot** harga saat dipesan |
| jumlah | integer | Jumlah item |
| subtotal | integer | `harga_satuan * jumlah` |

**payments**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| order_id | bigint | Relasi ke `orders` |
| midtrans_order_id | string | Unik, format `{kode_pesanan}-P{n}` |
| transaction_id | string | Nullable, ID transaksi Midtrans |
| status_transaksi | string | Status mentah Midtrans |
| status_code | string | Kode status Midtrans |
| fraud_status | string | Nullable |
| gross_amount | decimal(15,2) | Nominal dari Midtrans |
| payment_type | string | Nullable, contoh `other_qris` |
| snap_token | string | Nullable |
| snap_redirect_url | text | Nullable |
| raw_response | json | Nullable |
| waktu_dibuat | datetime | Waktu percobaan dibuat |
| waktu_diproses | datetime | Nullable |
| timestamps | datetime | Dibuat/diubah |

**payment_notifications**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| midtrans_order_id | string | Index |
| order_id | bigint | Nullable |
| transaction_status | string | Status dari payload |
| status_code | string | Kode status |
| gross_amount | decimal(15,2) | Nominal dari payload |
| signature_valid | boolean | Hasil verifikasi |
| payload | json | Payload mentah |
| status_pemrosesan | enum | `diterima`, `diproses`, `diabaikan`, `gagal` |
| catatan | text | Nullable, alasan |
| received_at | datetime | Waktu diterima |
| processed_at | datetime | Nullable |

**queue_counters**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| tanggal | date | Unik |
| nomor_terakhir | integer | Nomor antrean terakhir hari itu |

**promos**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| judul | string | Judul promosi |
| deskripsi | text | Nullable |
| gambar | string | Path/URL gambar |
| urutan | integer | Urutan tampil |
| aktif | boolean | Default `true` |
| mulai | date | Nullable |
| berakhir | date | Nullable |
| timestamps | datetime | Dibuat/diubah |

**store_settings**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| nama_toko | string | Nama kedai |
| tagline | string | Nullable |
| sejarah | text | Nullable |
| alamat | text | Nullable |
| maps_url | text | Nullable |
| maps_embed | text | Nullable |
| telepon | string | Nullable |
| whatsapp | string | Nullable |
| instagram | string | Nullable |
| fasilitas | text | Nullable |
| timestamps | datetime | Dibuat/diubah |

**store_hours**

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| hari | integer | 0 = Minggu, 6 = Sabtu |
| jam_buka | time | Nullable |
| jam_tutup | time | Nullable |
| tutup | boolean | Default `false` |

#### 10.2 Relasi dan Constraint

* Satu `order` memiliki banyak `order_items`.
* Satu `order` dapat memiliki banyak `payments` (percobaan pembayaran), tetapi hanya satu yang boleh berstatus aktif (`menunggu`) dalam satu waktu.
* Satu `order` dapat memiliki banyak `payment_notifications` (log).
* `orders.kode_pesanan` unik.
* Kombinasi `(tanggal_antrean, nomor_antrean)` unik.
* `payments.midtrans_order_id` unik.
* `store_hours.hari` unik.
* `order_items.nama_menu` dan `harga_satuan` adalah *snapshot*; tidak boleh ikut berubah saat menu diedit.
* Semua nominal disimpan sebagai integer Rupiah, kecuali `gross_amount` Midtrans yang menggunakan desimal dua angka di belakang koma.

**Index yang disarankan:**

* `orders (status_pembayaran, created_at)` untuk dashboard dan laporan.
* `orders (status_pesanan, created_at)` untuk daftar pesanan kasir.
* `orders (tanggal_antrean, nomor_antrean)` unik untuk nomor antrean.
* `payments (midtrans_order_id)` unik untuk pencarian webhook.
* `payment_notifications (midtrans_order_id)` untuk audit.

---

### 11. Integrasi Midtrans Sandbox

#### 11.1 Konfigurasi

Konfigurasi disimpan di `.env` backend:

```env
MIDTRANS_MERCHANT_ID=
MIDTRANS_CLIENT_KEY=
MIDTRANS_SERVER_KEY=
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SNAP_SANDBOX_URL=https://app.sandbox.midtrans.com/snap/v1/transactions
MIDTRANS_API_SANDBOX_URL=https://api.sandbox.midtrans.com/v2
```

Aturan:

* `MIDTRANS_SERVER_KEY` hanya digunakan di backend.
* `MIDTRANS_CLIENT_KEY` boleh dikirim ke frontend bila diperlukan.
* Mode produksi tidak diaktifkan pada versi ini.
* URL notifikasi diatur di dashboard Midtrans dan diarahkan ke endpoint `/api/midtrans/notification`.
* Endpoint notifikasi harus dapat diakses publik (HTTPS). Saat pengembangan lokal, gunakan *tunnel*.

#### 11.2 Pembuatan Transaksi Snap

Contoh payload:

```json
{
  "transaction_details": {
    "order_id": "V7K2M9QX-P1",
    "gross_amount": 45000
  },
  "item_details": [
    {
      "id": "12",
      "price": 18000,
      "quantity": 2,
      "name": "Vanilla Latte"
    },
    {
      "id": "7",
      "price": 9000,
      "quantity": 1,
      "name": "Heejo"
    }
  ],
  "customer_details": {
    "first_name": "Budi"
  },
  "enabled_payments": ["other_qris"],
  "expiry": {
    "unit": "minutes",
    "duration": 15
  },
  "callbacks": {
    "finish": "https://domain-anda.com/pesanan/V7K2M9QX"
  }
}
```

Catatan:

* `gross_amount` harus sama dengan total pesanan.
* Jumlah `price * quantity` seluruh `item_details` harus sama dengan `gross_amount`.
* `item_details` diambil dari *snapshot* `order_items`.
* `enabled_payments` hanya `other_qris`.
* `callbacks.finish` hanya untuk mengarahkan pelanggan kembali ke halaman konfirmasi pembayaran; **bukan** dasar penentuan lunas.
* Simpan `token` dan `redirect_url` dari respons Snap ke tabel `payments`.

#### 11.3 Pemetaan Status Midtrans

| `transaction_status` | `fraud_status` | Status pembayaran aplikasi | Tindakan |
|---|---|---|---|
| `pending` | — | `menunggu` | Tidak ada nomor antrean |
| `settlement` | — | `lunas` | Buat nomor antrean, status pesanan `antre` |
| `capture` | `accept` | `lunas` | Buat nomor antrean, status pesanan `antre` |
| `capture` | `challenge` | `perlu_diperiksa` | Tidak ada nomor antrean, catat log |
| `capture` | `deny` | `gagal` | Tidak ada nomor antrean |
| `deny` | — | `gagal` | Tidak ada nomor antrean |
| `cancel` | — | `gagal` | Tidak ada nomor antrean; pertahankan `dibatalkan` bila sudah dibatalkan lokal |
| `expire` | — | `kedaluwarsa` | Pesanan tetap dapat dibayar ulang |
| `failure` | — | `gagal` | Tidak ada nomor antrean |
| `refund` / `partial_refund` | — | `perlu_diperiksa` | Refund ditangani manual |
| `chargeback` / `partial_chargeback` | — | `perlu_diperiksa` | Sengketa ditangani manual |
| Status tidak dikenal | — | `perlu_diperiksa` | Catat log, jangan tandai lunas |

#### 11.4 Verifikasi Notifikasi

Langkah pemrosesan webhook:

1. Terima `POST` JSON di endpoint publik (tanpa CSRF dan tanpa login).
2. Ambil `order_id`, `status_code`, `gross_amount`, `signature_key`, dan `transaction_status`.
3. Hitung `SHA512(order_id + status_code + gross_amount + server_key)`.
4. Bandingkan dengan `signature_key` menggunakan perbandingan waktu konstan. Bila tidak valid, catat log, kembalikan HTTP 403, dan hentikan proses.
5. Cari `payments.midtrans_order_id`. Bila tidak ditemukan, catat sebagai `gagal`, kembalikan HTTP 200 agar Midtrans tidak mengulang tanpa henti, lalu tindak lanjuti manual.
6. Normalisasi `gross_amount` (contoh `"45000.00"` menjadi `45000`) dan bandingkan dengan `orders.total`.
7. Bila nominal tidak cocok, tandai `perlu_diperiksa`, catat log, jangan beri nomor antrean, dan kembalikan HTTP 200.
8. Bila valid, proses perubahan status secara idempoten di dalam transaksi database.
9. Kembalikan HTTP 200 setelah berhasil diproses. Bila gagal sementara (misalnya database tidak tersedia), kembalikan kode error agar Midtrans mengulang.

#### 11.5 Pembuatan Nomor Antrean

Saat pembayaran lunas (`settlement` atau `capture` dengan `fraud_status=accept`):

1. Mulai transaksi database.
2. Kunci baris `orders` (`SELECT ... FOR UPDATE`).
3. Bila `status_pembayaran` sudah `lunas` **dan** `nomor_antrean` sudah ada, hentikan proses (idempoten).
4. Set `status_pembayaran = lunas`, `waktu_lunas = sekarang`, dan `status_pesanan = antre`.
5. Bila `nomor_antrean` masih kosong:
   * Kunci/ambil `queue_counters` untuk tanggal hari ini.
   * Naikkan `nomor_terakhir` sebanyak satu.
   * Simpan `nomor_antrean` dan `tanggal_antrean`.
6. Commit transaksi.
7. Perubahan terlihat di halaman kasir melalui *polling*.

#### 11.6 Pembayaran Ulang

1. Saat pelanggan menekan **Coba Bayar Lagi**, periksa percobaan pembayaran aktif.
2. Bila ada percobaan `menunggu` yang belum kedaluwarsa, buka kembali `snap_redirect_url` yang sama.
3. Bila tidak ada, pastikan percobaan terakhir berstatus `gagal` atau `kedaluwarsa`, lalu buat `payments` baru dengan `midtrans_order_id = {kode_pesanan}-P{n+1}`.
4. Pastikan hanya satu percobaan aktif melalui transaksi database dan pengecekan status.
5. Percobaan lama tetap tersimpan untuk audit.

#### 11.7 Rekonsiliasi dan Penutupan Otomatis

* Proses terjadwal setiap 5 menit memeriksa pembayaran `menunggu` yang berumur lebih dari 2 menit, lalu memanggil API status Midtrans.
* Bila status Midtrans berbeda, terapkan pemetaan status dan proses secara idempoten.
* Pesanan yang belum dibayar lebih dari 24 jam dan tidak memiliki percobaan aktif ditutup otomatis dengan `status_pesanan = dibatalkan` dan `status_pembayaran = dibatalkan`.
* Proses terjadwal juga menutup pesanan yang gagal/kedaluwarsa setelah 24 jam.

---

### 12. Asumsi, Ketergantungan, dan Risiko

#### 12.1 Asumsi dan Ketergantungan

* Kedai memiliki koneksi internet yang cukup stabil untuk kasir dan pelanggan.
* Akun **Midtrans Sandbox** tersedia dan kanal QRIS aktif.
* Pelanggan memiliki aplikasi pembayaran yang mendukung QRIS (*mobile banking*/*e-wallet*).
* Server pengembangan dapat diakses publik untuk menerima notifikasi Midtrans (misalnya melalui *tunnel* saat pengujian lokal). Domain HTTPS tersedia untuk produksi.
* QR dicetak dan ditempatkan di area kedai oleh pihak kedai.
* Foto produk dan teks konten disediakan oleh pihak kedai.
* Harga dan total pesanan menggunakan Rupiah dengan nilai bilangan bulat.
* Akun admin dan kasir awal dibuat melalui *seeder*.
* Kategori menu menggunakan daftar tetap yang sudah di-*seed*.

#### 12.2 Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Notifikasi pembayaran tidak diterima atau terlambat | Status pesanan tidak berubah otomatis | Tombol Cek Status di halaman kasir, rekonsiliasi terjadwal, dan *polling* halaman konfirmasi pelanggan |
| Pelanggan membayar lalu menutup halaman | Pelanggan tidak melihat nomor antrean | Status tetap diperbarui lewat webhook; nomor antrean terlihat di kasir dan pelanggan dapat menanyakan ke kasir |
| Koneksi internet tidak stabil | Pembayaran QRIS gagal atau lambat | Prosedur cadangan tunai di kasir; pesan error yang jelas |
| Pesanan palsu/spam dari luar kedai | Beban sistem dan antrean | Pesanan baru masuk antrean setelah lunas; *rate limiting* pembuatan pesanan |
| Pembayaran ganda atau notifikasi ganda | Nomor antrean ganda, data ganda | `order_id` unik, idempotensi, satu percobaan aktif, kunci database |
| Nominal tidak cocok atau notifikasi palsu | Pesanan ditandai lunas secara keliru | Verifikasi `signature_key`, validasi `order_id`, perbandingan `gross_amount`, status `perlu_diperiksa` |
| Dua pembayaran berhasil untuk satu pesanan | Pelanggan membayar dua kali | Deteksi pembayaran kedua, tandai `perlu_diperiksa`, refund manual |
| Salah konfigurasi sandbox/produksi | Transaksi nyata saat pengujian | Flag lingkungan di `.env`, checklist rilis, pemisahan kredensial |
| Nomor antrean ganda saat ramai | Kebingungan operasional | `queue_counters` dan transaksi database atomik |
| Menu dihapus saat masih dipakai | Riwayat pesanan rusak | Soft delete dan snapshot nama/harga pada `order_items` |
| Perubahan harga setelah pesanan dibuat | Total tidak sesuai | Snapshot harga dan validasi checkout |
| Refund/sengketa | Pendapatan tidak akurat | Proses manual di dashboard Midtrans; status `perlu_diperiksa` |
| Data lama di spreadsheet | Kehilangan riwayat | Tentukan migrasi atau sistem baru berdiri sendiri |
| Kasir terbiasa dengan cara lama | Kesalahan operasional | Antarmuka sederhana, status jelas, pelatihan singkat |

---

### 13. Rencana Rilis

#### Fase 1 — MVP (Must)

* Login dan pembatasan akses per *role*.
* Halaman publik Pelanggan A: *landing page*, menu, tentang, lokasi.
* Mode QR Pelanggan B: menu, keranjang, *checkout*.
* Pembayaran QRIS Midtrans Sandbox: pembuatan Snap, tampilan QRIS, webhook, idempotensi, status pembayaran, pembayaran ulang.
* Nomor antrean setelah lunas (QRIS) atau saat pesanan disimpan (tunai).
* Kasir: Buat Pesanan (tunai, halaman awal), Pesanan Masuk, Kelola Menu (ketersediaan).
* Admin: Dashboard, Kelola Menu, Laporan Transaksi, Iklan & Promosi, Informasi Toko.
* Halaman konfirmasi pembayaran dan kode pesanan (tanpa fitur Cek Pesanan).

#### Fase 2 — Penyempurnaan (Should/Could)

* Rekonsiliasi terjadwal (bila belum masuk MVP).
* Ekspor laporan PDF/Excel.
* Notifikasi *real-time* (websocket/SSE).
* *Generate* QR untuk meja.
* Kelola akun kasir.
* Nomor meja.
* QRIS *walk-in*.

#### Fase 3 — Produksi (Opsional, di luar PJBL)

* Aktivasi Midtrans produksi dan domain HTTPS.
* Kebijakan refund dan sengketa.
* Prosedur operasional dan pemantauan berkala.
* Migrasi data lama.

---

### 14. Kriteria Penerimaan MVP

1. Pelanggan B dapat memesan dari QR, membayar QRIS, dan melihat nomor antrean serta pesan panggilan pada halaman konfirmasi setelah lunas.
2. Status pembayaran QRIS berubah menjadi **lunas** tanpa interaksi kasir setelah webhook `settlement` diterima.
3. Notifikasi ganda tidak membuat nomor antrean ganda.
4. Pesanan yang belum dibayar tidak muncul di antrean produksi dan tidak memiliki nomor antrean.
5. Pesanan yang gagal/kedaluwarsa dapat dibayar ulang dengan `order_id` baru.
6. Kasir langsung diarahkan ke halaman **Buat Pesanan** setelah login; tidak ada kartu ringkasan dashboard di area kasir.
7. Kasir dapat membuat pesanan tunai, menghitung kembalian, dan pesanan langsung mendapat nomor antrean.
8. Kasir tidak dapat mengubah item pesanan QRIS yang sudah dibuat dan tidak dapat menandai QRIS lunas secara manual.
9. Kasir dapat mengubah status ketersediaan menu, dan perubahan langsung terlihat di halaman pelanggan.
10. Admin dapat menambah, mengubah, menghapus (soft delete), dan mengatur ketersediaan menu.
11. Laporan admin hanya menghitung transaksi lunas dan menampilkan transaksi QRIS maupun tunai.
12. Admin dapat mengelola promosi dan informasi toko, dan perubahannya tampil di halaman publik.
13. Total pendapatan pada laporan sama dengan total transaksi lunas di dashboard Midtrans untuk periode yang sama.

**Catatan:** Halaman konfirmasi pembayaran bukan fitur pelacakan; tidak ada menu Cek Pesanan atau pencarian pesanan berdasarkan kode. Pelanggan yang menutup halaman dapat menanyakan nomor antrean ke kasir.

---

### 15. Keputusan dan Pertanyaan Terbuka

#### 15.1 Keputusan yang Diambil

* Kasir memiliki **tiga fitur utama**: Buat Pesanan, Pesanan Masuk, dan Kelola Menu. Halaman awal setelah login adalah **Buat Pesanan**; tidak ada halaman dashboard, kartu ringkasan penjualan, atau riwayat terpisah. Buat Pesanan hanya untuk pembayaran tunai.
* Admin memiliki **lima fitur utama**; rekonsiliasi dan refund tidak menjadi fitur aplikasi.
* Pesanan QRIS bersifat **read-only** setelah dibuat; kasir hanya mengubah status pesanan.
* Pesanan tunai langsung lunas dan langsung mendapat nomor antrean.
* **Tidak ada fitur Cek Pesanan/pelacakan mandiri.** Pelanggan hanya melihat halaman konfirmasi otomatis setelah pembayaran; halaman tersebut tidak dapat dicari melalui menu.
* **Pelanggan tidak memiliki tombol pembatalan.** Pesanan yang tidak dibayar kedaluwarsa otomatis; kasir dapat membatalkan pesanan belum dibayar.
* Halaman konfirmasi menampilkan pesan bahwa nama/nomor antrean akan dipanggil saat pesanan selesai.
* Batas waktu pembayaran QRIS adalah **15 menit**.
* Format `order_id` Midtrans adalah `{kode_pesanan}-P{n}`.
* Kode pesanan menggunakan 8 karakter acak dari huruf/angka tanpa karakter ambigu (`I`, `O`, `0`, `1`) agar halaman konfirmasi tidak mudah ditebak dan mudah dibacakan.
* Nomor antrean di-*reset* harian dan ditampilkan tiga digit.
* Tidak ada nomor meja pada versi ini.
* Kategori menu menggunakan daftar tetap; tidak ada CRUD kategori.
* Akun kasir/admin dibuat melalui *seeder*; belum ada UI manajemen akun.

#### 15.2 Pertanyaan yang Masih Perlu Dikonfirmasi

* Apakah batas waktu 15 menit sudah sesuai dengan kebiasaan pelanggan kedai?
* Apakah nomor antrean cukup menggunakan format tiga angka (`001`), atau perlu prefix tertentu?
* Apakah laporan perlu ekspor PDF/Excel pada MVP, atau cukup tampilan di web?
* Siapa yang memegang akun Midtrans dan `server_key`, serta siapa yang boleh mengakses dashboard Midtrans?
* URL publik/HTTPS apa yang akan digunakan untuk notifikasi pembayaran saat demo dan produksi?
* Apakah data di Firebase dan spreadsheet dimigrasi, atau sistem baru berdiri sendiri?
* Apakah pesanan tunai yang sudah disimpan boleh diubah itemnya oleh admin? *(Usulan: tidak; koreksi dilakukan di luar alur normal dan dicatat manual).*
* Apakah diperlukan nomor meja untuk Pelanggan B? *(Usulan: belum, sampai diminta pihak kedai).*
* Apakah admin perlu mengelola akun kasir di aplikasi? *(Usulan: belum, gunakan seeder).*
* Format dan isi konten *event* Varya mode malam di *landing page*?

---

### 16. Lampiran

* *Activity Diagram*: Pelanggan A, Pelanggan B, Kasir, dan Admin (dokumen terpisah).
* *Use Case Diagram*: Pelanggan A, Pelanggan B, Kasir, dan Admin (dokumen terpisah).
* **Dokumen terkait:**
  * `pelanggan-B.md`
  * `kasir.md`
  * `admin.md`
* **Referensi Midtrans:**
  * Snap Integration Guide — `https://docs.midtrans.com/docs/snap-snap-integration-guide`
  * QRIS — `https://docs.midtrans.com/reference/qris`
  * HTTP(S) Notification/Webhook — `https://docs.midtrans.com/docs/https-notification-webhooks`
  * Testing Payment on Sandbox — `https://docs.midtrans.com/docs/testing-payment-on-sandbox`
  * Get Transaction Status — `https://docs.midtrans.com/reference/get-transaction-status`

#### 16.1 Catatan Status Implementasi

* Dokumen ini adalah acuan implementasi sebelum modul pesanan, kasir, admin, dan pembayaran dibuat.
* Dokumen ini tidak memuat kode aplikasi; status implementasi diperbarui terpisah.
* Modul yang belum diimplementasikan: pesanan, keranjang, kasir, admin, dan integrasi Midtrans.
* Paket `midtrans/midtrans-php` dan variabel lingkungan Midtrans belum tersedia saat dokumen ini disusun.

