# PRODUCT REQUIREMENTS DOCUMENT (PRD)
## Website UMKM Kedai Kopi Varya

**Informasi Dokumen**
* **Nama Produk:** Website Kedai Kopi Varya (pemesanan, kasir, dan manajemen kedai)
* **Tanggal:** 9 Oktober 2026
* **Penyusun:** Kelompok 6
* **Konteks:** Tugas Project Based Learning (PJBL)

---

### 1. Ringkasan Produk
Website Kedai Kopi Varya adalah sistem berbasis web untuk UMKM Kedai Kopi Varya yang menggabungkan empat kebutuhan dalam satu platform: 
* Etalase digital untuk pelanggan yang berada di luar kedai.
* Pemesanan mandiri lewat *scan* QR untuk pelanggan yang berada di kedai.
* Antarmuka kasir untuk memproses pesanan dan transaksi.
* Panel admin/owner untuk mengelola menu, laporan, dan pengaturan toko.

Tujuan utamanya adalah merapikan alur pemesanan yang saat ini dicatat manual oleh kasir, sehingga pesanan tercatat berurutan, antrean lebih cepat dilayani, dan data transaksi tersimpan rapi untuk laporan bulanan.

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
Saat ini kasir menginput data pemesanan melalui Firebase, sedangkan *spreadsheet* digunakan sebagai database untuk menyimpan data.

#### 2.3 Permasalahan
* Terjadi *delay* pemesanan.
* Input pesanan menumpuk dan tidak terurut.
* Data tersebar di dua tempat (Firebase untuk input, *spreadsheet* untuk penyimpanan), sehingga berpotensi sulit direkap dan rawan selisih.
* *(Catatan: Poin ketiga adalah analisis tambahan dari gambaran kondisi saat ini dan perlu dikonfirmasi dengan pihak kedai).*

---

### 3. Tujuan dan Indikator Keberhasilan

#### 3.1 Tujuan
* Mempercepat dan merapikan proses pemesanan, termasuk pesanan yang masuk dari pelanggan di kedai lewat QR.
* Mengurangi input manual oleh kasir sehingga antrean tidak menumpuk.
* Menyediakan data transaksi terpusat untuk riwayat harian dan laporan bulanan.
* Memberi pelanggan di luar kedai informasi menu, sejarah, dan lokasi kedai.
* Memberi *owner* kendali atas menu, iklan/promo, jam operasional, dan info lokasi tanpa bantuan *developer*.

#### 3.2 Indikator Keberhasilan
*(Target angka ditetapkan setelah kondisi awal / baseline diukur bersama pihak kedai).*

* **Kecepatan pencatatan pesanan**
  * *Cara Pengukuran:* Selisih waktu pesanan dibuat hingga tercatat di kasir.
  * *Target:* Lebih cepat dari baseline.
* **Urutan pesanan**
  * *Cara Pengukuran:* Pesanan tampil berdasarkan waktu masuk.
  * *Target:* Seluruh pesanan terurut.
* **Penggunaan pesan lewat QR**
  * *Cara Pengukuran:* Persentase pesanan yang berasal dari QR.
  * *Target:* [Ditetapkan kemudian].
* **Akurasi laporan**
  * *Cara Pengukuran:* Kecocokan laporan sistem dengan rekap harian kasir.
  * *Target:* Tidak ada selisih.
* **Kemudahan penggunaan**
  * *Cara Pengukuran:* Umpan balik singkat dari kasir dan *owner*.
  * *Target:* Mayoritas menilai mudah.

---

### 4. Ruang Lingkup

#### 4.1 Dalam Lingkup
* *Landing page*, menu, tentang toko, dan lokasi untuk pelanggan.
* Pemesanan mandiri dengan keranjang untuk pelanggan di kedai via QR.
* Halaman buat pesanan, pengelolaan pesanan, transaksi, dan riwayat hari ini untuk kasir.
* Dashboard, kelola menu (CRUD), laporan transaksi per bulan, dan pengaturan toko untuk admin.

#### 4.2 Di Luar Lingkup (Versi Awal)
* Integrasi pembayaran online otomatis (*payment gateway*). Metode pembayaran dipilih pelanggan, lalu pembayaran diproses oleh kasir.
* Program loyalitas atau poin pelanggan.
* Manajemen stok bahan baku dan multi-cabang.

---

### 5. Pengguna dan Peran

* **Pelanggan A**
  * *Deskripsi:* Pelanggan di luar lokasi kedai.
  * *Akses:* Tanpa login. *Landing page*, menu (hanya lihat), tentang toko, lokasi.
  * *Kebutuhan Utama:* Mengetahui menu, harga, sejarah, dan lokasi kedai.
* **Pelanggan B**
  * *Deskripsi:* Pelanggan di lokasi kedai yang membuka website lewat scan QR.
  * *Akses:* Tanpa login. Menu mode pesan, keranjang, pemesanan, serta *landing page*, tentang toko, lokasi.
  * *Kebutuhan Utama:* Memesan sendiri dengan cepat tanpa antre.
* **Kasir**
  * *Deskripsi:* Petugas yang melayani pelanggan antre dan memproses transaksi.
  * *Akses:* Login. Dashboard hari ini, buat pesanan, kelola pesanan, riwayat hari ini.
  * *Kebutuhan Utama:* Mencatat dan menyelesaikan transaksi dengan cepat dan terurut.
* **Admin / Owner**
  * *Deskripsi:* Pemilik atau pengelola kedai.
  * *Akses:* Login. Dashboard, kelola menu, laporan, pengaturan toko.
  * *Kebutuhan Utama:* Mengelola menu, memantau penjualan, dan mengatur konten toko.

---

### 6. Alur Pengguna Utama
*(Diagram alur / activity diagram dan use case diagram tersedia sebagai dokumen terpisah. Ringkasan alurnya sebagai berikut).*

#### 6.1 Pelanggan A
1. Membuka website dan melihat *landing page*.
2. Memilih halaman Menu, Tentang toko, atau Lokasi.
3. Melihat informasi, lalu memilih halaman lain atau selesai.

#### 6.2 Pelanggan B (via QR)
1. *Scan* QR di meja dan langsung masuk ke halaman menu mode pesan.
2. Mengatur jumlah produk dengan *number stepper*. Keranjang muncul di sisi kanan saat tombol "+" ditekan.
3. Menambah item lagi atau menekan tombol Pesan.
4. Mengisi nama dan memilih metode pembayaran.
5. Pesanan tersimpan dan diteruskan ke kasir, lalu nomor antrean ditampilkan.
6. Setelah memesan, pelanggan dapat membuka Home, Tentang toko, Menu, dan Lokasi.

#### 6.3 Kasir
1. Login dan melihat dashboard ringkasan hari ini.
2. Membuka halaman buat pesanan.
3. Menginput pesanan pelanggan yang antre langsung, atau membuka pesanan masuk dari QR.
4. Memeriksa dan mengubah pesanan bila perlu.
5. Memproses pembayaran (hitung total, terima uang, hitung kembalian) dan menyimpan transaksi.
6. Membuka riwayat transaksi hari ini.

#### 6.4 Admin
1. Login dan melihat dashboard.
2. Memilih fitur: kelola menu, laporan transaksi, atau pengaturan toko.
3. Mengelola data pada fitur tersebut, menyimpan perubahan, lalu memilih fitur lain atau selesai.

---

### 7. Kebutuhan Fungsional
*Prioritas menggunakan skala MoSCoW: Must (wajib), Should (sebaiknya ada), Could (opsional).*

#### 7.1 Umum
* **[U-01] Login:** Kasir dan admin masuk dengan akun masing-masing. Pelanggan tidak perlu login. *(Prioritas: Must)*
* **[U-02] Pembatasan akses per role:** Halaman kasir hanya untuk kasir, halaman admin hanya untuk admin. *(Prioritas: Must)*
* **[U-03] Deteksi pelanggan di kedai:** QR berisi URL khusus yang membuat sistem mengenali Pelanggan B dan menampilkan menu mode pesan. *(Prioritas: Must)*

#### 7.2 Pelanggan A
* **[A-01] Landing page:** Halaman utama berisi sambutan, konten iklan/promo, dan informasi singkat kedai (misal: fasilitas PS Box gratis dan *event* Varya mode malam). *(Prioritas: Must)*
* **[A-02] Lihat menu:** Menampilkan gambar produk, harga, dan deskripsi singkat tanpa tombol pesan. *(Prioritas: Must)*
* **[A-03] Tentang toko:** Sejarah singkat berdirinya kedai. *(Prioritas: Must)*
* **[A-04] Lokasi:** Informasi alamat dan lokasi kedai. *(Prioritas: Must)*

#### 7.3 Pelanggan B
* **[B-01] Masuk langsung ke menu:** Setelah scan QR, pelanggan langsung menuju halaman menu mode pesan. *(Prioritas: Must)*
* **[B-02] Lihat menu:** Nama produk, harga, dan deskripsi singkat. *(Prioritas: Must)*
* **[B-03] Number stepper:** Tombol +/- untuk mengatur jumlah tiap produk. *(Prioritas: Must)*
* **[B-04] Keranjang:** Container keranjang muncul di sebelah kanan saat tombol "+" ditekan; berisi item, jumlah, subtotal, dan total. *(Prioritas: Must)*
* **[B-05] Pemesanan:** Tombol Pesan membuka isian nama dan pilihan metode pembayaran, lalu pesanan dikirim ke kasir. *(Prioritas: Must)*
* **[B-06] Konfirmasi pesanan:** Menampilkan nomor antrean dan ringkasan pesanan setelah pemesanan berhasil. *(Prioritas: Should)*
* **[B-07] Halaman lainnya:** Setelah memesan, pelanggan dapat membuka Home, Tentang toko, Menu, dan Lokasi, serta halaman keranjang. *(Prioritas: Must)*

#### 7.4 Kasir
* **[K-01] Dashboard hari ini:** Ringkasan hari ini yang tampil sebelum halaman buat pesanan (misal: jumlah transaksi dan total pendapatan hari ini). *(Prioritas: Must)*
* **[K-02] Buat pesanan:** Halaman input pesanan untuk pelanggan yang antre langsung. *(Prioritas: Must)*
* **[K-03] Pesanan masuk:** Daftar pesanan dari Pelanggan B (QR) yang tampil berurutan berdasarkan waktu masuk. *(Prioritas: Must)*
* **[K-04] Kelola pesanan:** Memeriksa, mengubah item/jumlah, dan membatalkan pesanan. *(Prioritas: Must)*
* **[K-05] Proses transaksi:** Menghitung total, menerima pembayaran, menghitung kembalian, dan menyimpan transaksi. *(Prioritas: Must)*
* **[K-06] Riwayat hari ini:** Daftar transaksi pada hari berjalan beserta total pendapatan. *(Prioritas: Must)*

#### 7.5 Admin / Owner
* **[AD-01] Dashboard:** Ringkasan penjualan dan aktivitas toko. *(Prioritas: Must)*
* **[AD-02] Kelola menu:** Menambah, melihat, mengubah, dan menghapus menu (nama, kategori, harga, deskripsi, foto, status). *(Prioritas: Must)*
* **[AD-03] Laporan transaksi:** Laporan transaksi yang dapat difilter per bulan beserta total pendapatan. *(Prioritas: Must)*
* **[AD-04] Konten iklan:** Mengatur konten iklan/promo yang tampil di website (termasuk info *event* Varya mode malam). *(Prioritas: Must)*
* **[AD-05] Jam operasional:** Mengedit jam operasional kedai. *(Prioritas: Must)*
* **[AD-06] Info lokasi:** Mengatur informasi lokasi yang tampil di halaman Lokasi. *(Prioritas: Should)*

#### 7.6 Fitur Tambahan yang Disarankan
* **[X-01] Status pesanan:** Status baru, diproses, siap, selesai agar alur kasir lebih jelas. *(Prioritas: Could)*
* **[X-02] Menu habis:** Kasir atau admin menandai menu yang sedang habis. *(Prioritas: Could)*
* **[X-03] Kategori & pencarian:** Filter kategori (kopi, non-kopi, makanan) dan pencarian. *(Prioritas: Could)*
* **[X-04] Ekspor laporan:** Unduh laporan bulanan dalam format PDF atau Excel. *(Prioritas: Could)*
* **[X-05] Kelola akun kasir:** Admin menambah atau menonaktifkan akun kasir. *(Prioritas: Could)*
* **[X-06] Generate QR:** Admin membuat QR yang mengarah ke mode Pelanggan B. *(Prioritas: Could)*

---

### 8. Aturan Bisnis (Usulan)
* Pelanggan B tidak melakukan pembayaran di website. Pesanan masuk ke kasir dengan status "menunggu pembayaran", lalu kasir memproses transaksinya.
* Pesanan ditampilkan ke kasir berurutan berdasarkan waktu masuk, untuk mengatasi masalah input yang tidak terurut.
* Riwayat hari ini hanya memuat transaksi pada hari berjalan; laporan penjualan memuat transaksi yang sudah selesai.
* Hanya admin yang dapat mengubah menu, konten iklan, jam operasional, dan info lokasi.

---

### 9. Kebutuhan Non-Fungsional

* **Kompatibilitas:** Tampilan responsif dengan pendekatan *mobile-first* karena Pelanggan B mengakses lewat HP; berjalan di *browser* modern.
* **Kinerja:** Halaman menu dimuat cepat; pesanan dari QR muncul di kasir dalam waktu singkat.
* **Keamanan:** Autentikasi kasir dan admin, pembatasan akses per *role*, password disimpan dalam bentuk *hash*, validasi input.
* **Keandalan:** Data pesanan dan transaksi tidak hilang; tersedia pencadangan data.
* **Kegunaan:** Antarmuka sederhana, terutama halaman kasir yang dipakai saat kedai ramai.
* **Pemeliharaan:** Konten (menu, iklan, jam, lokasi) dapat diubah admin tanpa mengubah kode program.

---

### 10. Kebutuhan Data

* **users:** nama, username/email, password, role (admin/kasir)
* **categories:** nama kategori
* **menus:** nama, kategori, harga, deskripsi, foto, status tersedia
* **orders:** nomor antrean, nama pelanggan, sumber (QR/kasir), status, metode pembayaran, total, waktu
* **order_items:** pesanan, menu, jumlah, harga satuan, subtotal
* **promos:** judul, deskripsi, gambar, status aktif
* **store_settings:** jam operasional per hari, alamat/lokasi, teks sejarah toko

---

### 11. Asumsi, Ketergantungan, dan Risiko

#### 11.1 Asumsi dan Ketergantungan
* Kedai memiliki koneksi internet yang cukup stabil untuk kasir dan pelanggan.
* QR dicetak dan ditempatkan di area kedai oleh pihak kedai.
* Foto produk dan teks konten disediakan oleh pihak kedai.

#### 11.2 Risiko dan Mitigasi
* **Koneksi internet tidak stabil**
  * *Mitigasi:* Halaman kasir dibuat ringan; sediakan prosedur cadangan pencatatan manual.
* **Pesanan palsu dari orang di luar kedai yang mengakses URL QR**
  * *Mitigasi:* Gunakan token pada URL QR dan kasir memverifikasi pesanan sebelum diproses.
* **Data lama di spreadsheet**
  * *Mitigasi:* Tentukan apakah data lama dimigrasi atau sistem baru berdiri sendiri.
* **Kasir terbiasa dengan cara lama (Firebase)**
  * *Mitigasi:* Antarmuka dibuat sangat sederhana dan dilakukan pelatihan singkat.

---

### 12. Rencana Rilis

* **Fase 1 (MVP):** Seluruh fitur berprioritas **Must** (login dan role, mode QR, halaman pelanggan, pemesanan dan keranjang, halaman kasir, riwayat hari ini, kelola menu, laporan bulanan, pengaturan toko).
* **Fase 2:** Fitur berprioritas **Should** dan **Could** (konfirmasi nomor antrean yang lebih lengkap, status pesanan, menu habis, kategori dan pencarian, ekspor laporan, kelola akun kasir, *generate* QR).

---

### 13. Pertanyaan Terbuka
* Metode pembayaran apa saja yang tersedia (Tunai, QRIS, Transfer, dll)?
* Apakah data di Firebase dan *spreadsheet* dimigrasi, atau sistem baru berdiri sendiri?
* Teknologi dan *hosting* apa yang akan digunakan? *(Catatan tambahan: Karena Anda terbiasa dengan ekosistem Laravel 13 dan MySQL, ini bisa jadi opsi utama untuk didiskusikan dengan kelompok).*
* Apakah Pelanggan B perlu mengisi nomor meja selain nama?
* Format dan isi konten *event* Varya mode malam di *landing page*?
* Jumlah kasir dan siapa yang bertindak sebagai admin?

---

### 14. Lampiran
* *Activity Diagram*: Pelanggan A, Pelanggan B, Kasir, dan Admin (dokumen terpisah).
* *Use Case Diagram*: Pelanggan A, Pelanggan B, Kasir, dan Admin (dokumen terpisah).
```eof
