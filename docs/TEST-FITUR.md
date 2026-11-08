File TEST-FITUR.md ini saya buat untuk mendokumentasikan hasil ketika saya melakukan tes fitur secara manual pada website ini, disini isinya ada hasil dari tes fitur saya beserta hal apa yang harus diperbaiki berdasarkan tes yang saya lakukan. Selain itu saya juga akan menyampaikan keinginan saya disini entah untuk menambah fitur, perbaikan sistem back end, tampilan front end, adanya bug dan eror, dan lain lain.

Test Fitur (apakah fitur sudah berjalan dengan baik dan sesuai dengan keinginan saya atau belum) :
Pelanggan A :
1. beranda = aman
2. foto tentang kami = foto seharusnya muncul ketika admin menambahkan foto dari halaman admin(tapi sekarang belum bisa)
3. menu = saya ingin agar pada katalog menu cukup menampilkan 4 menu saja, lalu jika klik "lihat semua menu" maka akan menampilkan seluruh menu yang ada
4. lokasi = aman
5. navbar = saya ingin agar tombol navigasi berada ditengah seperti milik Pelanggan B, ketika navbar di klik saya ingin agar tidak perlu pindah halaman seperti Pelanggan B (jadi satu halaman dan tidak pindah halaman)

Pelanggan B :
1. beranda = aman
2. foto tentang kami = foto seharusnya muncul ketika admin menambahkan foto dari halaman admin(tapi sekarang belum bisa)
3. menu = saya ingin agar pada katalog menu cukup menampilkan 4 menu saja, lalu jika klik "lihat semua menu" maka akan menampilkan seluruh menu yang ada
4. lokasi = aman
5. navbar = background berwarna hijau pada beranda tidak ikut berpindah ketika saya klik navbar yang lain (contoh tentang kami, menu, lokasi)
6. checkout dan keranjang = aman

Kasir :
1. semua fitur sudah berjalan dengan baik

Admin :
1. dashboard = seharusnya tiga kotak kecil di ringkasan operasional dashboard itu bisa diklik, 3 kotak tersebut seharusnya isinya adalah hari ini (untuk melihat ringkasan hari ini), minggu ini (untuk melihat ringkasan minggu ini), dan bulan ini (untuk melihat ringkasan bulan ini), jadi seharusnya ketika diklik hari ini akan memperlihatkan ringkasan hari ini lalu ketika diklik minggu ini akan memperlihatkan ringkasan minggu ini.
2. kelola menu = sejauh ini masih aman
3. laporan transaksi = sejauh ini masih aman
4. iklan & promosi = pertama untuk slide banner utama, setelah saya coba sepertinya urutan tampil tidak bisa lebih dari 4 slide (jadi jika memasukkan urutan slide lebih dari 4 akan berubah menjadi 1),  kedua untuk foto tentang kami, fitur masih belum berjalan dengan baik, jadi setelah saya coba untuk memasukkan foto lalu ketika saya klik simpan foto malah muncul notifikasi disuruh memilih foto terlebih dahulu meskipun saya sudah menambahkan foto
5. informasi toko = untuk jam operasional, bagiamana cara saya mengubah jam operasional? apakah harus diubah dari database?

---

## Status tindak lanjut — 8 Nov 2026

Semua temuan fungsional di atas sudah ditutup di slice S1–S7. Dokumen ini tetap menyimpan catatan asli sebagai riwayat.

| Temuan | Status | Bukti / irisan |
|---|---|---|
| Pelanggan A: foto Tentang Kami tidak muncul | **Selesai** | S1; unggah foto tersimpan dan tampil di `/` serta `/tentang` |
| Pelanggan A: katalog cukup 4 menu + "Lihat Semua Menu" | **Selesai** | S3; pratinjau 4 kartu, ekspansi di klien, `/menu` tetap penuh |
| Pelanggan A: navbar terpusat dan anchor satu halaman | **Selesai** | S4; anchor + scroll-spy, route mandiri tetap ada |
| Pelanggan B: foto Tentang Kami tidak muncul | **Selesai** | S1; memakai partial yang sama dengan Pelanggan A |
| Pelanggan B: katalog cukup 4 menu + "Lihat Semua Menu" | **Selesai** | S3; item ke-5+ tetap bisa dipesan setelah ekspansi/pencarian |
| Pelanggan B: pil navbar tidak berpindah | **Selesai** | S5; pil mengikuti section, alur pesanan tetap menandai Menu |
| Pelanggan B: checkout dan keranjang | **Aman** | Tidak ada perubahan perilaku; regression S8 hijau |
| Kasir: semua fitur | **Aman** | Regression S8 hijau; tidak ada perubahan yang diminta |
| Admin: dashboard tiga kotak periode | **Selesai** | S6; Hari Ini/Minggu Ini/Bulan Ini lewat `?periode` |
| Admin: urutan slide promo > 4 | **Selesai** | S7; diagnosis menunjukkan server sudah benar, UI posisi/urutan diperjelas dan `urutan` dibuat unik |
| Admin: foto Tentang Kami gagal disimpan | **Selesai** | S1; input file tidak lagi dikosongkan sebelum submit |
| Admin: jam operasional | **Selesai** | S2; tujuh hari tampil langsung dengan dropdown jam/menit |

**Yang masih terbuka (bukan bug fungsional):**

- Checklist QA manual akhir §5 `docs/PLAN-PERBAIKAN-TEST-FITUR.md` tetap disarankan sebagai smoke test sebelum deploy.
- Ekspor laporan PDF/Excel masih Fase 2 (F-19).
- Kredensial Midtrans produksi dan URL webhook publik masih operasional (O-16).
- Keranjang server-side hanya relevan bila Pelanggan B kelak punya akun (F-69).
