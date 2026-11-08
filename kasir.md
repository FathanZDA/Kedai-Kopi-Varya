### Kasir

**Status:** FINAL untuk alur bisnis. Implementasi backend pesanan, pembayaran tunai, dan nomor antrean belum selesai.

**Ringkasan:** Kasir memiliki **tiga fitur utama**: Buat Pesanan, Pesanan Masuk, dan Kelola Menu. Halaman awal setelah login adalah **Buat Pesanan**, yaitu antarmuka untuk memasukkan pesanan pelanggan yang membayar **tunai** di kasir. Pembayaran QRIS Pelanggan B diproses otomatis melalui Midtrans, sehingga kasir **tidak mengonfirmasi pembayaran QRIS secara manual**. Pembayaran tunai ditangani kasir, termasuk perhitungan kembalian.

**Ruang lingkup kasir:**

| No | Fitur Utama | Cakupan |
|---|---|---|
| 1 | **Buat Pesanan** | Halaman awal; input pesanan pelanggan, pilih menu, keranjang, data pelanggan, pembayaran tunai, kembalian, nomor antrean |
| 2 | **Pesanan Masuk** | Daftar pesanan QRIS dan tunai, status pembayaran otomatis, detail pesanan, perubahan status pesanan, riwayat pesanan selesai hari ini |
| 3 | **Kelola Menu** | Mengubah status ketersediaan menu (tersedia/habis) |

Tidak ada halaman dashboard kasir, kartu ringkasan penjualan, atau halaman riwayat terpisah. Setelah login, kasir langsung diarahkan ke **Buat Pesanan**. Riwayat hari ini berada pada tab **Selesai** di halaman **Pesanan Masuk**.

**Isi Halaman Buat Pesanan:**

Halaman ini mengikuti desain kasir pada folder `design/kasir`.

* **Sidebar kiri:** menu **Buat Pesanan** (aktif), **Pesanan Masuk**, **Kelola Menu**, tautan **Kunjungi Situs Web**, profil kasir yang sedang login, dan tombol **Keluar**.
* **Header:** menampilkan nama kasir yang sedang login, misalnya `Kasir: Dimas Pratama`.
* **Panel menu:**
  * Kolom pencarian produk/layanan dan tombol **Cari**.
  * Filter kategori: **Semua**, **Kopi**, **Teh**, **Autentik**, **Makanan**, dan **Cemilan**.
  * Grid kartu menu berisi foto, nama, deskripsi singkat, harga, dan tombol **+** untuk menambah ke pesanan.
  * Menu berstatus **Habis** tetap tampil dengan label **Habis**, tetapi tombol **+** tidak aktif.
* **Panel pesanan (kanan):**
  * **Nama Pelanggan** (wajib diisi).
  * **Kasir** terisi otomatis dari akun yang login dan tidak dapat diubah.
  * Pilihan tipe pesanan: **Dine In** atau **Take Away**.
  * Daftar item pesanan: nama item, harga, catatan per item, dan *number stepper* untuk mengubah jumlah.
  * **Subtotal**, jumlah item, dan **Total Tagihan**.
  * Metode pembayaran **Tunai**. Opsi QRIS pada desain tidak digunakan karena pesanan kasir hanya untuk pembayaran tunai.
  * Tombol **Proses Bayar**.
* **Modal pembayaran tunai:** menampilkan total tagihan, input **Uang Diterima**, dan **Kembalian**; tombol konfirmasi menyimpan transaksi.
* **Setelah transaksi disimpan:** sistem menampilkan konfirmasi berisi nomor antrean dan ringkasan pembayaran, lalu mengosongkan form untuk pesanan berikutnya. Pesanan langsung masuk ke tab **Antrean** di halaman **Pesanan Masuk**.

---

**Akses Fitur (Use Case):**

* Dapat melakukan login ke sistem dan langsung diarahkan ke halaman **Buat Pesanan**.

* Dapat mencari menu dan memfilter menu berdasarkan kategori.

* Dapat memilih menu dan mengatur jumlah item.

* Dapat mengisi catatan per item.

* Dapat mengisi nama pelanggan dan memilih tipe pesanan (Dine In/Take Away).

* Dapat melihat subtotal dan total tagihan.

* Dapat menerima pembayaran tunai, menginput uang diterima, dan melihat kembalian.

* Dapat menyimpan transaksi tunai; pesanan langsung lunas dan mendapat nomor antrean.

* Dapat melihat pesanan yang masuk dari QR Pelanggan B beserta status pembayarannya.

* Dapat melihat detail pesanan: item, jumlah, harga, subtotal, catatan pesanan, catatan per item, waktu, sumber, metode pembayaran, status pembayaran, nomor antrean, dan ID transaksi Midtrans.

* Dapat memantau status pembayaran QRIS yang diperbarui otomatis oleh sistem.

* Dapat memeriksa status pembayaran ke Midtrans melalui tombol **Cek Status** bila diperlukan.

* Dapat mengubah status pesanan: **Antrean → Diproses → Selesai**.

* Dapat membatalkan pesanan yang **belum dibayar** setelah konfirmasi.

* Dapat melihat daftar menu beserta kategori, harga, dan status ketersediaan.

* Dapat mengubah status ketersediaan menu menjadi **Tersedia** atau **Habis**.

* **Tidak dapat** menandai pembayaran QRIS menjadi lunas secara manual.

* **Tidak dapat** mengubah item, jumlah, atau total pesanan QRIS yang sudah dibuat.

* **Tidak dapat** menambah, menghapus, atau mengubah nama, harga, deskripsi, dan foto menu.

---

**Alur Aktivitas (Flowchart):**

**A. Login dan Buat Pesanan (Tunai)**

1. Kasir login sebagai kasir.

2. Sistem langsung menampilkan halaman **Buat Pesanan**.

3. Sistem menampilkan pencarian menu, filter kategori, grid menu, dan panel pesanan.

4. Kasir memilih menu yang tersedia dan mengatur jumlah item.

5. Menu habis tetap tampil dengan label **Habis**, tetapi tidak dapat dipilih.

6. Kasir mengisi nama pelanggan dan memilih tipe pesanan (Dine In/Take Away).

7. Kasir mengisi catatan pesanan dan catatan per item bila perlu.

8. Sistem menghitung subtotal dan total tagihan.

9. Kasir menekan **Proses Bayar**.

10. Sistem menampilkan modal pembayaran tunai.

11. Kasir menginput jumlah uang yang diterima.

12. Sistem memvalidasi jumlah uang:
    * Jika uang diterima kurang dari total, sistem menolak dan menampilkan pesan.
    * Jika cukup, sistem menghitung dan menampilkan kembalian.

13. Kasir menyimpan transaksi.

14. Sistem menandai pesanan **lunas**, membuat **nomor antrean**, dan menampilkan konfirmasi.

15. Pesanan langsung masuk ke tab **Antrean** di halaman **Pesanan Masuk**.

16. Form **Buat Pesanan** dikosongkan untuk pesanan berikutnya.

**B. Pesanan Masuk**

1. Kasir membuka halaman **Pesanan Masuk** dari sidebar.

2. Sistem menampilkan daftar pesanan tanpa kartu ringkasan dashboard.

3. Kasir memilih tab:
   * **Belum Dibayar** — pesanan QRIS yang belum lunas.
   * **Antrean** — pesanan lunas yang belum mulai disiapkan.
   * **Diproses** — pesanan yang sedang disiapkan.
   * **Selesai** — pesanan yang sudah diserahkan hari ini.

4. Pesanan ditampilkan berurutan berdasarkan waktu masuk.

5. Kasir membuka detail pesanan.

6. Jika pesanan berstatus **Antrean**, kasir menekan **Mulai Proses**. Status berubah menjadi **Diproses**.

7. Setelah pesanan siap dan diserahkan, kasir menekan **Selesai**. Status berubah menjadi **Selesai**.

**C. Pesanan QRIS Pelanggan B**

1. Pesanan dari Pelanggan B muncul di tab **Belum Dibayar** dengan status pembayaran **menunggu**.

2. Selama pelanggan belum membayar, pesanan **tidak masuk antrean produksi** dan **tidak memiliki nomor antrean**.

3. Setelah Midtrans mengirim notifikasi pembayaran berhasil:
   * Status pembayaran otomatis berubah menjadi **lunas**.
   * Sistem membuat **nomor antrean**.
   * Pesanan otomatis berpindah ke tab **Antrean**.
   * Kasir tidak perlu me-refresh halaman karena sistem melakukan *polling*.

4. Kasir **tidak menginput jumlah uang diterima** dan **tidak menandai lunas secara manual**.

5. Kasir hanya menyiapkan pesanan yang sudah lunas.

6. Jika status terlihat belum berubah padahal pelanggan sudah membayar, kasir menekan **Cek Status** untuk memeriksa ke Midtrans.

7. Jika pesanan belum dibayar dan pelanggan pergi, kasir dapat menekan **Batalkan Pesanan** setelah konfirmasi. Pelanggan tidak memiliki tombol pembatalan dari sisi pelanggan.

**D. Kelola Menu**

1. Kasir membuka halaman **Kelola Menu**.

2. Sistem menampilkan daftar menu beserta kategori, harga, dan status ketersediaan.

3. Kasir mencari atau memfilter menu bila perlu.

4. Kasir mengubah status menu menjadi **Tersedia** atau **Habis**.

5. Perubahan langsung berlaku pada halaman menu Pelanggan A dan Pelanggan B.

6. Kasir tidak dapat mengubah harga, nama, deskripsi, foto, menambah menu, atau menghapus menu.

---

**Detail Penting:**

* **Halaman awal kasir adalah Buat Pesanan.** Tidak ada halaman dashboard atau kartu ringkasan penjualan di area kasir.
* **Buat Pesanan hanya untuk pembayaran tunai.** Opsi QRIS pada desain tidak dipakai karena pesanan yang dibuat kasir tidak menggunakan QRIS.
* Nama kasir pada panel pesanan terisi otomatis dari akun yang sedang login.
* Setelah transaksi tunai disimpan, form **Buat Pesanan** dikosongkan untuk pesanan berikutnya.
* **Kasir tidak dapat menandai pembayaran QRIS menjadi lunas secara manual.** Status pembayaran berasal dari Midtrans.
* **Pesanan QRIS yang belum lunas tidak masuk antrean produksi.** Nomor antrean baru muncul setelah status pembayaran lunas.
* **Pesanan QRIS bersifat read-only.** Kasir tidak dapat mengubah item, jumlah, atau total setelah pesanan dibuat karena nominal pembayaran mengikuti pesanan dan perubahan dapat menyebabkan ketidaksesuaian.
* Kasir tidak perlu mencocokkan mutasi rekening atau bukti transfer QRIS secara manual.
* **Nomor antrean pesanan tunai dibuat saat transaksi disimpan**, bukan saat pesanan mulai disiapkan.
* Pembayaran tunai tetap diproses manual, termasuk validasi jumlah uang diterima dan kembalian.
* Bila notifikasi Midtrans terlambat atau tidak diterima, kasir menggunakan tombol **Cek Status**. Jika masih bermasalah, kasir menghubungi admin.
* Refund atau pembatalan pembayaran yang sudah lunas tidak dilakukan di halaman kasir; prosesnya melalui dashboard Midtrans dan dicatat oleh admin di luar aplikasi.
* Tab **Selesai** berfungsi sebagai riwayat harian kasir dan menampilkan metode pembayaran, status pembayaran, serta ID transaksi Midtrans.
* Pesanan yang sudah lunas tidak dapat dibatalkan dari aplikasi.
* Pelanggan tidak memiliki tombol pembatalan. Pembatalan pesanan yang belum dibayar dilakukan oleh kasir atau sistem.
* Pesanan yang gagal atau kedaluwarsa tetap tampil di tab **Belum Dibayar** sampai pelanggan membayar ulang dari halaman konfirmasi atau sistem menutupnya setelah 24 jam.
* Menu yang diubah menjadi **Habis** langsung tidak dapat dipesan pelanggan, tetapi tetap tampil di halaman pelanggan dengan label **Habis** dan tetap terlihat di daftar menu kasir.
* Kasir tidak dapat mengubah harga menu. Perubahan harga hanya dapat dilakukan admin.
* Halaman **Pesanan Masuk** melakukan *polling* setiap 5–10 detik agar status pembayaran dan pesanan baru muncul otomatis.

---

**Batasan Kasir:**

| Boleh dilakukan | Tidak boleh dilakukan |
|---|---|
| Membuka halaman **Buat Pesanan** sebagai halaman awal | Menandai QRIS lunas secara manual |
| Membuat pesanan tunai | Membuat pesanan QRIS |
| Mengisi catatan pesanan dan catatan per item | — |
| Menghitung kembalian | Mengubah item/jumlah/total pesanan QRIS |
| Melihat dan memantau semua pesanan | Membatalkan pesanan yang sudah lunas |
| Mengubah status pesanan (antre → diproses → selesai) | Mengubah harga/nama/deskripsi/foto menu |
| Membatalkan pesanan belum dibayar | Menambah atau menghapus menu |
| Mengubah status ketersediaan menu | Mengakses halaman admin |
| Melihat riwayat hari ini di tab Selesai | — |

---

**Skenario Penerimaan:**

1. Kasir login dan langsung melihat halaman **Buat Pesanan**; tidak ada kartu ringkasan dashboard.
2. Kasir memilih 2 Vanilla Latte, mengisi nama pelanggan, memilih **Dine In**, serta menambahkan catatan pesanan dan catatan per item; total muncul di panel pesanan.
3. Kasir menekan **Proses Bayar**, menginput uang tunai Rp50.000 untuk total Rp45.000, dan sistem menampilkan kembalian Rp5.000.
4. Kasir menyimpan transaksi; pesanan langsung lunas, mendapat nomor antrean, dan muncul di tab **Antrean** pada halaman **Pesanan Masuk**.
5. Kasir menginput uang tunai kurang dari total; sistem menolak dan menampilkan pesan validasi.
6. Pelanggan B membayar QRIS; dalam beberapa detik pesanan berpindah dari **Belum Dibayar** ke **Antrean** dengan nomor antrean, tanpa tindakan kasir.
7. Kasir menekan **Mulai Proses** lalu **Selesai**; status pesanan berubah dan pesanan masuk tab Selesai.
8. Kasir mengubah status Vanilla Latte menjadi **Habis**; menu tersebut tetap tampil di halaman pemesanan Pelanggan B dengan label **Habis**, tetapi tidak dapat dipilih.
9. Kasir mencoba mengubah item pesanan QRIS yang sudah lunas; tombol edit tidak tersedia.
10. Kasir mencoba menandai QRIS lunas secara manual; aksi tersebut tidak tersedia.
11. Notifikasi Midtrans dikirim dua kali; pesanan tetap hanya memiliki satu nomor antrean.
12. Kasir menekan **Cek Status** pada pesanan yang masih menunggu; status terbaru dari Midtrans ditampilkan.
