### Admin / Owner

**Status:** FINAL untuk alur bisnis. Implementasi backend CRUD, transaksi, dan laporan belum selesai.

**Ringkasan:** Admin memiliki **lima fitur utama**: Dashboard, Kelola Menu, Laporan Transaksi, Iklan & Promosi, dan Informasi Toko. Fitur rekonsiliasi pembayaran dan refund manual **tidak ada di aplikasi** karena penanganannya dilakukan melalui dashboard Midtrans.

**Ruang lingkup admin:**

| No | Fitur Utama | Cakupan |
|---|---|---|
| 1 | **Dashboard** | Ringkasan penjualan, transaksi, pesanan, menu terlaris, dan transaksi terbaru |
| 2 | **Kelola Menu** | Tambah, lihat, ubah, hapus (soft delete), dan atur ketersediaan menu |
| 3 | **Laporan Transaksi** | Laporan per bulan yang terhubung dengan transaksi kasir dan pesanan QR |
| 4 | **Iklan & Promosi** | Tambah, ubah, aktif/nonaktif, dan hapus konten promosi |
| 5 | **Informasi Toko** | Profil, jam operasional, alamat/lokasi, kontak, dan fasilitas |

Admin tidak memiliki fitur rekonsiliasi pembayaran, refund, atau manajemen akun kasir pada versi ini.

---

**Akses Fitur (Use Case):**

* Dapat melakukan login ke sistem sebagai admin.

* Dapat melihat dashboard berisi ringkasan penjualan dan aktivitas toko.

* Dapat menambah menu baru.

* Dapat melihat daftar menu.

* Dapat mengubah data menu: nama, kategori, harga, deskripsi, foto, dan status ketersediaan.

* Dapat menghapus menu dengan *soft delete*.

* Dapat mengatur status ketersediaan menu.

* Dapat melihat laporan transaksi per bulan.

* Dapat memfilter laporan berdasarkan bulan dan tahun.

* Dapat melihat ringkasan pendapatan, jumlah transaksi, dan perbandingan metode pembayaran.

* Dapat melihat rincian transaksi, termasuk item, uang diterima/kembalian untuk tunai, dan ID transaksi Midtrans untuk QRIS.

* Dapat menambah, mengubah, mengaktifkan/menonaktifkan, dan menghapus konten iklan/promosi.

* Dapat mengatur profil toko, sejarah, jam operasional, alamat, lokasi, kontak, dan fasilitas.

* **Tidak dapat** mengonfirmasi atau mengoreksi status pembayaran QRIS di aplikasi.

* **Tidak dapat** memproses refund di aplikasi.

* **Tidak dapat** menambah atau menonaktifkan akun kasir di aplikasi.

---

**Alur Aktivitas (Flowchart):**

**A. Login dan Dashboard**

1. Admin login sebagai admin.

2. Sistem menampilkan **Dashboard**.

3. Dashboard menampilkan:
   * Pendapatan hari ini (hanya transaksi lunas).
   * Pendapatan bulan berjalan.
   * Jumlah transaksi lunas.
   * Jumlah pesanan menunggu pembayaran.
   * Jumlah pesanan sedang diproses.
   * Perbandingan pendapatan QRIS dan tunai (hanya transaksi lunas).
   * Lima menu terlaris.
   * Sepuluh transaksi terbaru.

4. Admin memilih salah satu fitur lain pada menu: Kelola Menu, Laporan Transaksi, Iklan & Promosi, atau Informasi Toko.

**B. Kelola Menu**

1. Admin membuka halaman **Kelola Menu**.

2. Sistem menampilkan daftar menu dengan pencarian dan filter kategori/ketersediaan.

3. Admin memilih aksi:
   * **Tambah menu:** mengisi nama, kategori, harga, deskripsi, foto, dan status ketersediaan.
   * **Ubah menu:** mengubah data menu yang sudah ada.
   * **Hapus menu:** menghapus menu setelah konfirmasi.
   * **Atur ketersediaan:** mengubah status tersedia/habis.

4. Sistem memvalidasi input:
   * Nama wajib diisi.
   * Kategori wajib dipilih.
   * Harga berupa bilangan bulat positif.
   * Foto sesuai format dan ukuran yang diizinkan.

5. Sistem menyimpan perubahan.

6. Menu baru langsung tampil di halaman pelanggan. Jika berstatus tersedia, menu dapat dipesan; jika habis, menu tampil dengan label **Habis** dan tidak dapat dipesan.

7. Menu yang dihapus tidak tampil lagi untuk penjualan baru, tetapi riwayat pesanan lama tetap utuh.

**C. Laporan Transaksi**

1. Admin membuka halaman **Laporan Transaksi**.

2. Sistem menampilkan laporan bulan berjalan secara default.

3. Admin memilih bulan dan tahun.

4. Sistem menampilkan ringkasan:
   * Total pendapatan lunas.
   * Jumlah transaksi lunas.
   * Total QRIS lunas.
   * Total tunai.
   * Rata-rata nilai transaksi.

5. Sistem menampilkan tabel transaksi: tanggal, kode pesanan, nomor antrean, nama pelanggan, sumber, metode pembayaran, status pembayaran, dan total.

6. Admin membuka rincian transaksi untuk melihat item, uang diterima/kembalian, dan ID transaksi Midtrans.

7. Hanya transaksi berstatus **lunas** yang dihitung sebagai pendapatan.

**D. Iklan & Promosi**

1. Admin membuka halaman **Iklan & Promosi**.

2. Sistem menampilkan daftar promosi beserta judul, gambar, periode, urutan, dan status aktif.

3. Admin memilih aksi:
   * **Tambah promosi:** mengisi judul, deskripsi, gambar, urutan, dan periode opsional.
   * **Ubah promosi:** mengubah data promosi.
   * **Aktif/nonaktif:** mengubah status tampil.
   * **Hapus promosi:** menghapus setelah konfirmasi.

4. Sistem menyimpan perubahan.

5. Promosi aktif langsung tampil di *landing page* sesuai urutan.

**E. Informasi Toko**

1. Admin membuka halaman **Informasi Toko**.

2. Sistem menampilkan form berisi:
   * Nama toko dan tagline.
   * Sejarah/deskripsi toko.
   * Jam operasional per hari.
   * Alamat dan tautan/embed peta.
   * Nomor telepon/WhatsApp dan Instagram.
   * Fasilitas.

3. Admin mengubah data yang diperlukan.

4. Sistem memvalidasi input.

5. Sistem menyimpan perubahan.

6. Perubahan langsung tampil di halaman publik.

---

**Detail Penting:**

* **Dashboard hanya menghitung transaksi lunas sebagai pendapatan.** Pesanan menunggu, gagal, kedaluwarsa, dibatalkan, atau perlu diperiksa tidak dihitung.
* **Laporan terhubung dengan kasir.** Semua transaksi tunai yang dibuat kasir dan pesanan QRIS yang lunas otomatis muncul di laporan.
* **Laporan menampilkan QRIS dan tunai**, termasuk perbandingan total keduanya.
* **Menu yang dihapus menggunakan *soft delete*.** Menu tidak tampil untuk penjualan baru, tetapi data pesanan lama tetap utuh karena `order_items` menyimpan snapshot nama dan harga.
* **Perubahan harga hanya berlaku untuk pesanan baru.** Pesanan lama tidak berubah.
* Admin dapat mengubah ketersediaan menu. Kasir juga dapat mengubah ketersediaan, tetapi kasir tidak dapat mengubah data menu lainnya.
* **Kategori menu menggunakan daftar tetap** yang sudah di-*seed*: **Kopi, Teh, Autentik, Makanan, Cemilan**. Tidak ada CRUD kategori pada versi ini.
* **Promosi nonaktif tidak tampil di landing page.** Promosi dengan periode yang sudah berakhir juga tidak ditampilkan sebagai promosi aktif.
* **Informasi toko langsung berlaku** setelah disimpan, tanpa perlu mengubah kode program.
* **Rekonsiliasi pembayaran tidak dilakukan di aplikasi.** Jika ada status yang perlu diperiksa, admin memeriksa dashboard Midtrans dan menindaklanjuti secara manual.
* **Refund dan sengketa diproses melalui dashboard Midtrans.** Status `perlu_diperiksa` pada pesanan menjadi penanda untuk ditindaklanjuti.
* **Akun kasir dikelola melalui seeder**, bukan melalui antarmuka admin, pada versi ini.
* Admin tidak dapat mengubah item pesanan yang sudah lunas. Koreksi transaksi dilakukan di luar alur normal dan dicatat manual.

---

**Batasan Admin:**

| Boleh dilakukan | Tidak boleh dilakukan |
|---|---|
| Melihat ringkasan penjualan | Mengonfirmasi QRIS lunas secara manual |
| Menambah, mengubah, menghapus menu | Memproses refund di aplikasi |
| Mengatur ketersediaan menu | Mengubah item pesanan yang sudah lunas |
| Melihat laporan semua transaksi | Menambah/menonaktifkan akun kasir |
| Mengelola promosi | Mengubah status pembayaran Midtrans |
| Mengatur informasi toko | Mengakses halaman kasir |

---

**Skenario Penerimaan:**

1. Admin login dan melihat dashboard dengan pendapatan hari ini, pendapatan bulan ini, jumlah transaksi lunas, dan menu terlaris.
2. Admin menambah menu baru; menu langsung tampil di halaman pelanggan. Jika berstatus tersedia, menu dapat dipesan; jika habis, menu tampil dengan label **Habis**.
3. Admin mengubah harga menu; pesanan lama tetap menggunakan harga saat pesanan dibuat.
4. Admin menghapus menu; menu hilang dari halaman penjualan, tetapi rincian pesanan lama masih menampilkan nama dan harga menu tersebut.
5. Admin mengubah status menu menjadi habis; menu tetap tampil di halaman pelanggan dengan label **Habis**, tetapi tidak dapat dipesan.
6. Admin membuka laporan bulan berjalan; hanya transaksi lunas yang dihitung sebagai pendapatan.
7. Admin memfilter laporan bulan sebelumnya; data transaksi dan total pendapatan menyesuaikan.
8. Admin menambah promosi dan mengaktifkannya; promosi tampil di *landing page*.
9. Admin menonaktifkan promosi; promosi hilang dari *landing page*.
10. Admin mengubah jam operasional dan alamat; halaman publik menampilkan informasi terbaru.
11. Admin mencoba mengonfirmasi pembayaran QRIS secara manual; aksi tersebut tidak tersedia.
