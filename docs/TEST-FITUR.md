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

---

## Status tindak lanjut — 10 Nov 2026 (UI & penanda Terlaris)

Temuan dari permintaan user di `docs/HANDOFF.md` §9 (“Prompt saya”), dikerjakan di slice `perbaikan-ui-terlaris` (T1–T4).

| Temuan | Status | Bukti / irisan |
|---|---|---|
| Banyak teks sangat kecil (`6px`–`10px`) dan abu-abu samar | **Selesai** | T1; 392 kemunculan dinaikkan ke lantai 11px, `--color-muted` `#63646a` (≈5:1), `--color-varya-muted` `#5f6f5c`, ukuran komponen CSS dinaikkan |
| Banner promo: label jauh dari judul, deskripsi tanpa latar | **Selesai** | T2; label “Promo” menempel di atas judul, jarak dirapatkan, deskripsi memakai blok hijau `bg-varya-dark` + border |
| Login: garis hijau diagonal mengganggu, tanpa garis terasa kosong | **Selesai** | T3; gradien radial lembut + watermark logo + panel informasi tiga poin; satu bar `admin-stripes` di kartu |
| Fitur baru: admin menandai menu “Terlaris”, badge tampil di pelanggan | **Selesai** | T4/F-75; `menus.terlaris`, checkbox form (**Ubah → Atribut Penjualan**; tombol/kolom tabel & endpoint `PATCH .../terlaris` dihapus pada R4), badge di katalog A/B + detail produk + kasir; test `AdminMenuCrudTest` |
| Istilah “Menu Terlaris” dashboard bentrok dengan penanda manual | **Selesai** | Judul dashboard menjadi “Paling Banyak Terjual” (tetap dihitung dari `order_items`) |

---

## Status tindak lanjut — 11 Nov 2026 (revisi tampilan dari `design/perlu-perbaikan`)

Koreksi atas slice T1–T4 setelah user membandingkan hasil dengan folder `design/perlu-perbaikan` (T5–T8). Semua baris di bawah berstatus **Selesai** dan sudah diverifikasi build + test.

| Temuan | Status | Bukti / irisan |
|---|---|---|
| Background banner terlalu samar, foto promo nyaris hilang | **Selesai** | T6; `blur 1.5px`, `saturate 0.95`, selubung `#eef3ea/50` — foto tetap terlihat tanpa mengganggu judul |
| Label “Promo” tidak sesuai desain | **Selesai** | T5; diganti **“Wajib Coba! 🔥”** di samping chip “Buka // 08.00 - 23.00 WIB”, miring −5° |
| Jarak label “Wajib Coba” menimpa chip jam | **Selesai** | T7; `-ml-2` → `ml-3` sehingga ada celah nyata antar keduanya |
| Tentang Kami masih terasa portrait, bukan landscape | **Selesai** | T6; grid `lg:grid-cols-2` + `items-stretch`, foto `object-cover` absolut setinggi panel teks |
| Ukuran Tentang Kami jadi terlalu kecil | **Selesai** | T7; ukuran semula dikembalikan (`max-w-1280`, `p-8`, paragraf 15–16px/1.9, statistik `py-5`) dengan struktur landscape tetap |
| Bayangan Tentang Kami kabur seperti halo, desain memakai bayangan tegas | **Selesai** | T6; `.about-shadow` → `8px 8px 0 0 rgb(27 28 29 / 90%)`, tanpa border |

---

## Status tindak lanjut — 12 Nov 2026 (revisi dashboard, kasir, admin)

Rencana lengkap R1–R7 ada di `docs/HANDOFF.md` §5.25. **R1–R6 sudah di-commit** di tumpukan branch `perbaikan-*` (tiga commit terakhir: R1 grafik penjualan `c59c566`, R2 kartu terlaris `a646e37`, revisi R1 grafik garis `b398806`); **R7 selesai** dan hasilnya menunggu commit dokumen di branch yang sama. Regression akhir R7 pada 12 Nov 2026: `php artisan test` → **247 passed** (1385 assertions), `php vendor/bin/pint --test` → 114 file bersih, `npm.cmd run build` → berhasil, QA grafik dashboard 360/768/1280 dan klik titik lolos.

| Temuan | Status | Bukti / irisan |
|---|---|---|
| Mengosongkan keranjang kasir langsung menghapus tanpa peringatan | **Selesai** | R5; dialog `<dialog data-clear-cart-dialog>` + tombol Batal/Ya, Kosongkan; test `KasirOrderTest` |
| Tombol/kolom Terlaris di tabel Kelola Menu membingungkan | **Selesai** | R4; kolom, tombol bintang, handler, route/service/request dihapus; penanda hanya di **Ubah → Atribut Penjualan**; badge ★ tetap sebagai informasi |
| Elemen yang bisa diklik tidak selalu memakai kursor tangan | **Selesai** | R6; aturan dasar `cursor: pointer` di `app.css` + `cursor-pointer` pada kartu promosi; nonaktif tetap `not-allowed` |
| Font kasir/admin masih 11px dan sulit dibaca | **Selesai** | R3; 388 `text-[11px]` → 12px, paragraf isi → 13px, `.admin-label`/`.admin-btn-*` → 12px, `.admin-checkbox` → 13px |
| Dashboard menampilkan tabel “Pesanan Masuk Terbaru” yang menduplikasi Laporan Transaksi | **Selesai** | R1; grafik garis SVG server-rendered per jam/hari/tanggal (`DashboardService::grafik()` → `x` + `garis`), rincian per titik lewat JS kecil, tanpa library chart; `AdminDashboardTest` 16 passed |
| Grafik batang terlihat kecil dan kurang terbaca | **Selesai** | Revisi R1 (§5.25.6); batang diganti grafik garis (area `<polygon>` + `<polyline>`) dengan area plot 300px/360px, titik tetap bisa diklik dan dipilih lewat keyboard |
| Panel “Paling Banyak Terjual” masih berupa daftar lima baris yang statis | **Selesai** | R2 + revisi Prompt 3; menjadi kartu keempat ringkasan (tanpa gambar) berisi nama menu dan jumlah terjual yang bergilir tiap 2 detik, tombol Jeda, `prefers-reduced-motion` + `aria-live="polite"` dihormati, grafik penjualan lebar penuh; `AdminDashboardTest` 16 passed |
| Kapsul indikator slide memutih setelah pindah slide | **Selesai** | T8; `pelanggan-a.js` men-toggle `w-7`/`bg-varya-dark` dan `w-2.5`/`bg-white/80`, sama dengan markup Blade |
| Regression akhir setelah R1–R7 | **Selesai** | R7; `php artisan test` 247 passed (1385 assertions), `pint --test` 114 file bersih, `npm.cmd run build` berhasil, QA grafik 360/768/1280 lolos |
