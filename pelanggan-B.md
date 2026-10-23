### Pelanggan B (Pemesan di Tempat via QR Code)

**Ringkasan:** Pembayaran Pelanggan B menggunakan **QRIS dinamis Midtrans Sandbox**. Pesanan dibuat lebih dulu dengan status **menunggu pembayaran**, lalu pelanggan membayar melalui QRIS. Status pembayaran berubah otomatis dari notifikasi Midtrans, nomor antrean baru diberikan setelah lunas, dan pembayaran gagal/kedaluwarsa dapat diulang dari halaman konfirmasi.

Pelanggan B **tidak memiliki fitur Cek Pesanan atau pelacakan pesanan**. Setelah pembayaran, pelanggan hanya melihat **halaman konfirmasi pembayaran** yang menampilkan nomor antrean dan pesan bahwa nama/nomor antrean akan dipanggil saat pesanan selesai. Pelanggan juga **tidak memiliki tombol pembatalan**; pesanan yang tidak dibayar akan kedaluwarsa otomatis.

**Ruang lingkup Pelanggan B:**

* Memiliki akses yang sama dengan Pelanggan A: *landing page*, menu, tentang toko, dan lokasi.
* Memesan tanpa login setelah *scan* QR di kedai.
* Metode pembayaran hanya **QRIS**.
* Pembayaran diproses melalui Midtrans Snap (Sandbox).
* Halaman konfirmasi pembayaran muncul otomatis setelah pembayaran.
* Nomor antrean hanya diberikan setelah pembayaran lunas.
* Tidak ada menu Cek Pesanan, pencarian pesanan, atau halaman pelacakan.
* Tidak ada tombol pembatalan pesanan oleh pelanggan.

---

**Akses Fitur (Use Case):**

* Memiliki akses yang sama dengan Pelanggan A (melihat *landing page*, menu, tentang, dan lokasi).

* Dapat masuk langsung ke menu mode pesan setelah *scan* QR.

* Dapat melihat menu beserta nama, harga, deskripsi, dan foto. Menu yang habis tetap tampil dengan label **Habis**, tetapi tidak dapat dipesan.

* Dapat mengatur jumlah produk (menambah/mengurangi) dengan *number stepper*.

* Dapat melihat keranjang belanja berisi item, jumlah, subtotal, dan total.

* Dapat melakukan pemesanan dengan mengisi nama, tipe pesanan (makan di tempat/bawa pulang), dan catatan opsional.

* Dapat melakukan pembayaran QRIS melalui Midtrans tanpa menunggu konfirmasi manual kasir.

* Dapat melihat halaman konfirmasi pembayaran yang menampilkan status terkini, batas waktu, dan total secara otomatis.

* Dapat mencoba lagi dari halaman konfirmasi bila pembayaran gagal atau kedaluwarsa.

* Dapat melihat nomor antrean, kode pesanan, dan ringkasan pesanan setelah pembayaran lunas.

* Dapat melihat pesan bahwa nama/nomor antrean akan dipanggil saat pesanan selesai.

* **Tidak dapat** membuka halaman Cek Pesanan atau mencari pesanan berdasarkan kode.

* **Tidak dapat** membatalkan pesanan dari aplikasi.

---

**Alur Aktivitas (Flowchart):**

1. Pelanggan B melakukan *scan* QR Code yang ada di meja.

2. Sistem langsung menampilkan menu dalam mode pemesanan.

3. Pelanggan B mengatur jumlah produk (+ atau -) yang ingin dipesan.

4. Sistem menampilkan keranjang belanja (di sisi kanan pada layar desktop, panel bawah pada layar HP).

5. Jika ingin menambah item, pelanggan kembali mengatur jumlah produk. Jika tidak, pelanggan menekan tombol **Pesan**.

6. Sistem menampilkan form pesanan.

7. Pelanggan B mengisi nama, memilih tipe pesanan (makan di tempat/bawa pulang), dan mengisi catatan bila perlu.

8. Metode pembayaran sudah tetap **QRIS**. Pelanggan menekan tombol **Bayar dengan QRIS**.

9. Sistem memvalidasi keranjang terhadap ketersediaan dan harga menu terbaru.

10. Sistem menyimpan pesanan dengan status **menunggu pembayaran**, membuat percobaan pembayaran Midtrans dengan `order_id` unik, lalu menampilkan halaman pembayaran QRIS dari Midtrans Snap.

11. Pelanggan B memindai QRIS menggunakan aplikasi *mobile banking* atau *e-wallet* yang mendukung QRIS, lalu menyelesaikan pembayaran.

12. Midtrans mengirim notifikasi pembayaran ke sistem:
    * Jika `settlement` atau `capture` dengan `fraud_status=accept`, sistem mengubah status pembayaran menjadi **lunas** dan membuat **nomor antrean**.
    * Jika `pending`, status tetap **menunggu pembayaran**.
    * Jika `expire`, status menjadi **kedaluwarsa** dan pelanggan dapat mencoba lagi.
    * Jika `deny`/`cancel`/`failure`, status menjadi **gagal** dan pelanggan dapat mencoba lagi.

13. Sistem menampilkan halaman konfirmasi pembayaran berisi status, nomor antrean (jika sudah lunas), kode pesanan, dan ringkasan pesanan.

14. Jika pembayaran belum selesai, halaman konfirmasi menampilkan status **menunggu pembayaran** dan memperbarui dirinya secara otomatis. Tidak ada tombol **Cek Status** manual.

15. Jika pembayaran gagal atau kedaluwarsa, pelanggan dapat menekan **Coba Bayar Lagi** dari halaman konfirmasi. Nomor antrean tidak diberikan untuk pesanan yang belum lunas.

16. Setelah pembayaran lunas, halaman konfirmasi menampilkan pesan bahwa **nama/nomor antrean akan dipanggil saat pesanan selesai**.

17. Terakhir, pelanggan dapat memilih untuk membuka halaman lain (beranda, menu, tentang kami, atau lokasi) atau menyelesaikan proses.

---

**Detail Penting:**

* **Pesanan dibuat sebelum pembayaran.** QRIS dinamis Midtrans memerlukan `order_id` dari pesanan, sehingga sistem menyimpan pesanan berstatus **menunggu pembayaran** terlebih dahulu. Pesanan tersebut belum masuk antrean produksi sampai pembayaran lunas.
* QRIS yang ditampilkan adalah **QRIS dinamis per pesanan** dari Midtrans, bukan QRIS statis milik kedai. Nominal dan kode transaksi mengikuti pesanan pelanggan.
* **Pembayaran tidak dikonfirmasi manual oleh kasir.** Status lunas berasal dari notifikasi resmi Midtrans, bukan dari parameter halaman *finish*.
* **Nomor antrean dibuat setelah pembayaran lunas.** Pesanan yang masih menunggu pembayaran, gagal, atau kedaluwarsa tidak masuk antrean produksi.
* Batas waktu pembayaran adalah **15 menit** sejak transaksi Snap dibuat.
* Pelanggan memerlukan koneksi internet untuk membuka halaman pembayaran Midtrans dan menyelesaikan pembayaran.
* Pelanggan sebaiknya tidak menutup halaman sebelum pembayaran selesai. Jika halaman tertutup, status tetap diperbarui oleh sistem; nomor antrean juga terlihat di layar kasir, sehingga pelanggan dapat menanyakannya ke kasir.
* **Tidak ada fitur Cek Pesanan.** Kode pesanan hanya ditampilkan di halaman konfirmasi dan digunakan untuk identifikasi pesanan, bukan untuk pencarian mandiri.
* Jika pembayaran gagal atau kedaluwarsa, pelanggan dapat membuat percobaan pembayaran baru melalui tombol **Coba Bayar Lagi** di halaman konfirmasi. Percobaan baru hanya dibuat setelah percobaan sebelumnya tidak aktif.
* Jika masih ada percobaan pembayaran yang aktif, tombol **Coba Bayar Lagi** membuka kembali QRIS yang sama agar pelanggan tidak membayar dua kali.
* **Pelanggan tidak memiliki tombol pembatalan.** Pesanan yang tidak dibayar akan kedaluwarsa otomatis. Pembatalan pesanan belum dibayar dilakukan oleh kasir.
* Pada versi ini, metode pembayaran Pelanggan B hanya **QRIS**. Pembayaran tunai hanya tersedia untuk pesanan yang dibuat langsung oleh kasir.
* Harga dan nama menu dikunci saat pesanan dibuat. Perubahan harga setelahnya tidak mengubah total pesanan pelanggan.
* Setelah pesanan dibuat, item tidak dapat diubah. Jika pelanggan ingin mengubah pesanan, pelanggan harus kembali ke menu dan membuat pesanan baru; pesanan lama akan kedaluwarsa dengan sendirinya.
* Jika ada menu yang menjadi habis saat pelanggan mengisi keranjang, sistem menolak checkout dan meminta pelanggan menyesuaikan keranjang.

---

**Penanganan Kondisi Khusus:**

| Kondisi | Perilaku Sistem |
|---|---|
| Pembuatan transaksi Snap gagal | Pesanan tetap tersimpan sebagai menunggu pembayaran; pelanggan dapat mencoba lagi dari halaman konfirmasi |
| Pelanggan menutup halaman sebelum membayar | Pesanan tetap menunggu dan akan kedaluwarsa otomatis; tidak ada halaman Cek Pesanan |
| Pelanggan sudah membayar tetapi menutup halaman | Status tetap diperbarui lewat webhook; nomor antrean terlihat di kasir dan dapat ditanyakan pelanggan |
| Notifikasi Midtrans terlambat | Halaman konfirmasi melakukan *polling* otomatis; kasir dapat menekan Cek Status dari halaman Pesanan Masuk |
| Pembayaran kedaluwarsa | Status menjadi kedaluwarsa; tombol Coba Bayar Lagi tersedia di halaman konfirmasi |
| Pembayaran ditolak | Status menjadi gagal; tombol Coba Bayar Lagi tersedia di halaman konfirmasi |
| Nominal Midtrans tidak cocok | Pesanan ditandai perlu diperiksa dan tidak diberi nomor antrean; pelanggan diminta menghubungi kasir |
| Pembayaran ganda | Pembayaran pertama diproses; pembayaran kedua ditandai perlu diperiksa |
| Pesanan sudah lunas | Nomor antrean, ringkasan, dan pesan panggilan ditampilkan; tidak ada tombol pembatalan |

---

**Skenario Penerimaan:**

1. Pelanggan memesan 2 Vanilla Latte, membayar QRIS, lalu halaman konfirmasi menampilkan nomor antrean dan pesan bahwa nama/nomor antrean akan dipanggil saat pesanan selesai.
2. Pelanggan menutup halaman sebelum membayar; pesanan tetap menunggu dan kedaluwarsa otomatis. Jika pelanggan sudah membayar, nomor antrean dapat ditanyakan ke kasir.
3. Pembayaran kedaluwarsa setelah 15 menit; pelanggan menekan **Coba Bayar Lagi** di halaman konfirmasi dan berhasil membayar dengan `order_id` baru.
4. Notifikasi Midtrans dikirim dua kali; nomor antrean tetap hanya satu.
5. Pelanggan tidak menemukan tombol pembatalan di halaman konfirmasi.
6. Pelanggan memesan menu yang menjadi habis sebelum checkout; sistem menolak dan meminta keranjang disesuaikan.
7. Pelanggan tidak menemukan menu Cek Pesanan atau kolom pencarian pesanan di website.
