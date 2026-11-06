# PLAN — Perbaikan Temuan `docs/TEST-FITUR.md`

Dokumen ini adalah **rencana pengerjaan**, bukan catatan hasil. Sumber aturan tetap `PRD.md`, `docs/DECISIONS.md`, `docs/ROUTES.md`, dan `docs/DATABASE.md`. Temuan asli ada di `docs/TEST-FITUR.md`.

**Disusun:** 4 Nov 2026, dari commit `aae928d` (branch `keputusan-tertunda`).

---

## 0. Baseline dan catatan sesi ini

| Item | Nilai |
|---|---|
| Commit terakhir | `aae928d` — `fitur: foto tentang kami dan pengaman harga checkout` |
| Branch | `keputusan-tertunda` |
| Working tree | bersih kecuali `docs/TEST-FITUR.md`, dokumen rencana ini, dan `public/images/promos/unggahan-promo-*` (artefak uji unggah, belum di-commit) |
| Test | **terverifikasi 6 Nov 2026** — `php artisan test` → **214 passed** (1116 assertions) setelah S1–S2; Pint bersih, build berhasil. MySQL XAMPP harus hidup sebelum menjalankan test. |

**Wajib sebelum menulis kode:** nyalakan MySQL XAMPP, lalu jalankan `php artisan test` sampai hijau. Jangan menyimpulkan ada bug kode sebelum MySQL hidup (lihat `docs/HANDOFF.md` §7). Kalau test menggantung lagi, cek `SHOW PROCESSLIST` sebelum menuduh kode.

---

## 1. Ringkasan akar masalah

| # | Area | Gejala (TEST-FITUR) | Akar masalah | Jenis | Irisan |
|---|---|---|---|---|---|
| 1 | Admin → Iklan & Promosi; publik A+B | Foto Tentang Kami tidak muncul walau sudah dipilih; simpan malah berbunyi “Pilih foto terlebih dahulu.” | **Bug terverifikasi.** `resources/js/admin.js` mengosongkan `aboutImageInput.value = ''` tepat setelah file dipilih, sehingga form multipart tidak mengirim `foto` dan `AboutPhotoRequest` menolak `foto.required` (“Pilih foto terlebih dahulu.”). Dropzone juga hanya membuat pratinjau, tidak menaruh `File` ke input. | Bug front-end | S1 |
| 2 | Admin → Informasi Toko | “Bagaimana mengubah jam operasional? Harus lewat database?” | **Bukan bug backend.** Jam sudah bisa diubah lewat form `PUT /admin/toko` (tujuh baris `data-store-hour-row`), tetapi formnya tersembunyi di dalam `<details>` berlabel “Atur jadwal per hari”. | Discoverability | S2 |
| 3 | Pelanggan A & B | Katalog menu cukup 4 item, lalu tombol “Lihat Semua Menu”. | Belum ada pembatas; kedua halaman merender semua menu. Untuk Pelanggan B ini permukaan pemesanan, jadi tidak boleh benar-benar memotong data. | Kebutuhan UI | S3 |
| 4 | Pelanggan A | Navbar harus di tengah seperti Pelanggan B dan klik navbar tidak pindah halaman. | Header publik memakai empat route terpisah (`pelanggan-a.menu`, `.tentang`, `.lokasi`) dan layout `justify-between` tanpa kolom tengah. Halaman `/` sebenarnya sudah memuat semua section (`#tentang`, `#menu`, `#lokasi`). | Kebutuhan UI + PRD gap | S4 |
| 5 | Pelanggan B | Pil hijau aktif tetap di “Beranda” saat klik Tentang/Menu/Lokasi. | **Bug terverifikasi.** `resources/views/components/pelanggan-b/header.blade.php` menentukan aktif dari nama route; tautan anchor `#tentang`/`#lokasi` di-hardcode `active => false`, dan tidak ada scroll-spy. | Bug front-end | S5 |
| 6 | Admin → Dashboard | Tiga kotak ringkasan operasional harus bisa diklik: hari ini / minggu ini / bulan ini. | Belum ada fitur. Strip di `dashboard.blade.php` hanya dua angka statis (hari ini & bulan ini) tanpa tautan atau state. `DashboardService` hanya menghitung hari ini + bulan berjalan. | Fitur baru | S6 |
| 7 | Admin → Iklan & Promosi | Urutan slide > 4 “berubah menjadi 1”. | **Belum terbukti di kode.** Tidak ada `take(4)`/`limit(4)` pada promo; `Promo::ordered()` = `urutan, id`, hero merender semua slide, validasi `urutan` 1–999. Perlu reproduksi sebelum memperbaiki. | Dugaan bug / ekspektasi UI | S7 |
| 8 | Kasir, Kelola Menu, Laporan | “Aman.” | Tidak ada perubahan yang diminta. Cukup regression check di S8. | — | S8 |

**Catatan penting:** dua file `public/images/promos/unggahan-promo-*` adalah hasil uji unggah promo, bukan aset kanonik. Jangan di-commit; putuskan bersama user apakah folder unggahan runtime perlu masuk `.gitignore` (di luar scope perbaikan ini).

---

## 2. Urutan pengerjaan

| Urutan | Irisan | Prioritas | Ukuran | Risiko |
|---|---|---|---|---|
| 1 | S1 — Foto Tentang Kami | P0 (bug, memblokir 2 area) | S | Rendah |
| 2 | S2 — Jam operasional terlihat | P1 (UX) | XS | Rendah |
| 3 | S3 — Katalog 4 + Lihat Semua | P1 | M | Sedang (filter + pemesanan B) |
| 4 | S4 — Navbar Pelanggan A | P1 | M | Sedang (anchor + scroll-spy) |
| 5 | S5 — Pil aktif Pelanggan B | P1 | S | Rendah |
| 6 | S6 — Dashboard 3 periode | P2 (fitur baru) | L | Sedang (query param, test) |
| 7 | S7 — Urutan promo > 4 | P2 (butuh reproduksi) | S/M | Tidak pasti |
| 8 | S8 — Regression & dokumen | Wajib | S | — |

Kerjakan satu irisan per commit, dipotong dari ujung branch terbaru, mengikuti pola repo: FormRequest → Service → Resource, `DB::transaction` + `lockForUpdate` bila menyentuh order/pembayaran, named route, tanpa dependency baru.

**Jangan** mengubah skema database untuk S1–S7. Tidak ada kolom baru yang dibutuhkan.

---

## 3. Detail per irisan

### S1 — Foto Tentang Kami benar-benar tersimpan dan tampil

**Tujuan:** admin bisa memilih **atau** menyeret foto, menekan **Simpan Foto**, dan foto tampil di `/`, `/tentang`, dan beranda `/pesan`.

**Status 4 Nov 2026: selesai dikerjakan** — kode, test, dan catatan `docs/HANDOFF.md` sudah ada di working tree. Verifikasi browser (pilih berkas + drag & drop) masih manual.

**Akar masalah (terverifikasi):**

1. `resources/js/admin.js` — handler `aboutImageInput` memanggil `handleAboutImage(...)` lalu `aboutImageInput.value = ''`. Nilai input file yang dikosongkan berarti tidak ada `File` di form; submit multipart mengirim tanpa `foto`.
2. Dropzone Tentang Kami (dan dropzone promo) hanya memanggil `handleX(file)` untuk pratinjau. `File` hasil drag tidak pernah dimasukkan ke `<input type=file>`, jadi drag & drop selalu gagal tersimpan.
3. Server sudah benar: `AboutPhotoRequest` (`foto` wajib, JPG/PNG/WEBP ≤ 3 MB), `StoreSettingsService::simpanFotoTentang`, kolom `store_settings.foto_tentang`, partial `pelanggan-a.partials.tentang` dipakai Pelanggan A **dan** Pelanggan B. `AdminAboutPhotoTest` tidak menangkap bug ini karena test memanggil endpoint langsung tanpa melewati JS.

**Perubahan:**

- `resources/js/admin.js`
  - Hapus `aboutImageInput.value = ''` dari listener `change`; kosongkan input hanya saat `openAboutDialog()` dibuka.
  - Pada `drop` Tentang Kami dan Promo, masukkan file ke input memakai `DataTransfer` sebelum pratinjau:
    `const dt = new DataTransfer(); dt.items.add(file); input.files = dt.files;`
  - Jangan menghapus file setelah pratinjau; biarkan form multipart mengirimkannya.
- `resources/views/admin/promosi.blade.php` (opsional kecil): tampilkan `@error('foto')` di dekat tombol Simpan Foto agar kegagalan validasi terlihat di panel, bukan hanya ringkasan atas.

**Kriteria penerimaan:**

- Pilih foto lewat “Pilih dari Komputer” → Simpan Foto → redirect ke `/admin/promosi`, status “Tampil”, `store_settings.foto_tentang` berisi `images/toko/unggahan-*`, berkas ada di `public/images/toko`.
- Drag & drop foto melakukan hal yang sama (bukan hanya pratinjau).
- Foto tampil di section Tentang pada `/`, halaman `/tentang`, dan beranda `/pesan`.
- Unggah pengganti menghapus berkas `unggahan-*` lama; Hapus Foto mengembalikan placeholder dan status “Tidak Tampil”.
- File non-gambar / > 3 MB ditolak dengan pesan yang terlihat, tanpa menghapus foto lama.

**Test:**

- `tests/Feature/AdminAboutPhotoTest.php` tetap hijau.
- Tambah satu test: `POST /admin/toko/foto-tentang` tanpa file → redirect + pesan “Pilih foto terlebih dahulu.” terlihat di halaman promosi (memastikan error sampai ke UI).
- Perilaku JS diverifikasi manual (repo belum punya test runner JS); catat di laporan bahwa ini manual.

**Dokumen:** catatan perbaikan di `docs/HANDOFF.md`; tandai temuan `TEST-FITUR` A2/B2/AD4 saat sudah lolos.

---

### S2 — Jam operasional bisa ditemukan dan diubah tanpa database

**Tujuan:** admin mengubah jam buka/tutup tujuh hari dari UI, bukan dari database.

**Status 6 Nov 2026: selesai dikerjakan** — jadwal tujuh hari tampil langsung di form dengan dropdown jam + menit per hari; test memastikan tidak ada lagi `<details>`, ketujuh baris jam dirender, dan nilai menit ikut tersimpan.

**Akar masalah:** form sudah ada dan berfungsi (`PUT /admin/toko` native, field `jam[hari][buka|tutup|libur]`, `StoreSettingsRequest` + `StoreSettingsService`), tetapi tersembunyi di `<details>` “Atur jadwal per hari” pada `resources/views/admin/toko.blade.php`.

**Perubahan:**

- `resources/views/admin/toko.blade.php`: jadwal tujuh hari ditampilkan langsung (tanpa `<details>`) dengan dropdown jam + menit per sisi (buka/tutup) dan checkbox **Libur**; format 24 jam konsisten dan nilai lama terisi dari database.
- `app/Http/Requests/Admin/StoreSettingsRequest.php`: field request menjadi `jam[hari][buka_jam|buka_menit|tutup_jam|tutup_menit|libur]`; validasi regex rentang jam/menit dan penggabungan menjadi `HH:MM` lewat `waktu()`.
- `resources/js/admin.js`: pratinjau “Hari Ini” membaca dropdown dan dropdown dinonaktifkan untuk hari libur.
- Tidak ada perubahan controller/route/service; `store_hours` tetap menyimpan `HH:MM`.

**Kriteria penerimaan:**

- Buka `/admin/toko` → tujuh baris jam langsung terlihat.
- Ubah jam salah satu hari → Simpan → reload → nilai tersimpan; halaman publik `/lokasi` dan ringkasan “Hari Ini” ikut berubah.
- Menandai “Libur” menyimpan `jam_buka`/`jam_tutup` `null`, `tutup = true`; hari lain wajib punya jam (validasi server tetap).
- Tidak perlu menyentuh database manual.

**Test:** `AdminStoreSettingsTest` diperluas: halaman render tujuh `data-store-hour-row` dengan dropdown `buka_jam`/`buka_menit`/`tutup_jam`/`tutup_menit` dan checkbox `libur`; simpan perubahan jam + menit tetap lulus (test lama), dan hari tanpa jam tetap ditolak kecuali ditandai libur.

**Dokumen:** perjelas `admin.md` §E/README bahwa jam diubah di Informasi Toko; catat di HANDOFF.

---

### S3 — Katalog 4 menu + “Lihat Semua Menu” (A dan B)

**Tujuan:** beranda menampilkan 4 kartu pertama; tombol memperluas seluruh menu **di tempat**; pencarian/filter tetap menemukan semua menu.

**Pendekatan:** render semua kartu dari server (supaya pencarian/filter dan pemesanan B tetap utuh), lalu batasi secara visual di klien.

**Perubahan:**

- `resources/views/components/pelanggan-a/partials/menu-section.blade.php`
  - Ganti prop `showAllLink` menjadi `previewLimit` (mis. `4`) dan render tombol `data-menu-more` (bukan tautan ke `/menu`).
  - Tambah atribut `data-menu-index` pada tiap item dan `data-menu-preview-limit="{{ $previewLimit }}"` pada grid.
- `resources/views/components/pelanggan-b/menu-section.blade.php`
  - Atribut yang sama; tombol “Lihat Semua Menu” yang sekarang `href="#menu"` diganti tombol toggle.
- `resources/views/pelanggan-a/beranda.blade.php`, `resources/views/pelanggan-b/beranda.blade.php`: kirim `previewLimit: 4`.
- `resources/views/pelanggan-a/menu.blade.php`: tetap tanpa batas (halaman menu penuh).
- `resources/js/pelanggan-a.js` (dipakai A dan B — layout B memuat `pelanggan-a.js`):
  - State `expanded`; hitung `match` filter/pencarian seperti sekarang.
  - `hidden = !match || (!expanded && !filterAktif && index >= limit)`.
  - Tombol toggle: ubah label “Lihat Semua Menu” ↔ “Tampilkan Lebih Sedikit”, set `aria-expanded`; sembunyikan bila jumlah item ≤ limit.
  - Saat pencarian/filter aktif, tampilkan semua hasil (batas tidak berlaku).

**Kriteria penerimaan:**

- `/` dan `/pesan` menampilkan tepat 4 kartu menu saat pertama dibuka (bila menu > 4).
- Klik “Lihat Semua Menu” menampilkan seluruh menu tanpa pindah halaman; klik lagi mengembalikan ke 4.
- Mengetik di pencarian atau memilih kategori menampilkan seluruh hasil yang cocok, termasuk item ke-5 dan seterusnya.
- Di `/pesan`, item ke-5+ tetap bisa ditambahkan ke keranjang setelah diperluas; label “Habis” tetap bekerja.
- `/menu` tetap menampilkan seluruh menu.
- Tanpa JS, semua menu tetap tampil (progressive enhancement) — tidak ada data yang hilang.

**Test:** `tests/Feature/PelangganATest` / test Pelanggan B: halaman beranda memuat `data-menu-preview-limit="4"`, memuat seluruh item, dan **tidak** lagi memuat tautan “Lihat Semua Menu” ke `route('pelanggan-a.menu')` di beranda. Perilaku buka/tutup diverifikasi manual.

**Dokumen:** `docs/DECISIONS.md` — keputusan UI baru (batas pratinjau 4, ekspansi di klien, halaman `/menu` tetap penuh); `docs/ROUTES.md` §2.2 catatan bahwa beranda memakai anchor + pratinjau.

---

### S4 — Navbar Pelanggan A: terpusat + satu halaman (anchor)

**Tujuan:** navigasi desktop berada di tengah, dan klik Beranda/Tentang/Menu/Lokasi menggulir di halaman `/`, tidak berpindah halaman.

**Pendekatan yang disarankan (least destructive):** pertahankan route `/tentang`, `/menu`, `/lokasi` sebagai deep link/SEO, tetapi navbar memakai anchor ke beranda.

**Perubahan:**

- `resources/views/components/public/header.blade.php`
  - Layout desktop menjadi tiga kolom setara (`grid grid-cols-[1fr_auto_1fr]`): brand kiri, nav tengah, aksi kanan; nav tetap `hidden md:flex`.
  - Tautan menjadi `route('pelanggan-a.beranda').'#tentang'`, `...'#menu'`, `...'#lokasi'`, dan Beranda ke `route('pelanggan-a.beranda')` / `#beranda`.
  - Tambahkan `data-nav-section` + `data-nav-link`; aktif server-side hanya untuk halaman mandiri (`routeIs('pelanggan-a.tentang')` dsb.).
  - Panel mobile memakai tautan yang sama dan menutup panel setelah diklik.
- `resources/views/pelanggan-a/beranda.blade.php`: tambah anchor `<span id="beranda">` (seperti Pelanggan B).
- `resources/js/app.js`: fungsi `initSectionNav()` — scroll-spy dengan `IntersectionObserver` (fallback listener `scroll`), memperbarui kelas pil aktif + `aria-current`; abaikan saat section tidak ada di halaman. Pakai offset header sticky (`html` sudah `scroll-pt-24`).

**Kriteria penerimaan:**

- Navbar desktop rata tengah secara visual pada lebar ≥768px; mobile tetap tombol menu.
- Dari `/`, klik Tentang/Menu/Lokasi menggulir ke section tanpa request halaman baru; URL boleh berubah menjadi `#...`.
- Pil hijau mengikuti section yang sedang terlihat saat scroll.
- Dari `/menu`, `/tentang`, atau `/lokasi`, klik navbar membawa ke beranda lalu ke section yang benar.
- Route `/tentang`, `/menu`, `/lokasi` tetap dapat dibuka langsung (tidak dihapus/diubah).
- `prefers-reduced-motion` tidak dipaksa animasi (opsional: `scroll-behavior: smooth` di CSS dengan media query).

**Test:** feature test halaman `/` memuat `href="...#tentang"`, `#menu`, `#lokasi` dan `id="beranda"`; halaman `/tentang` tetap `200`. Scroll-spy manual.

**Dokumen:** `docs/DECISIONS.md` (keputusan navigasi satu halaman + anchor, route lama dipertahankan); PRD §4.3 catatan bahwa navigasi beranda memakai anchor dan halaman mandiri tetap ada; `docs/ROUTES.md` §2.2.

---

### S5 — Pil aktif Pelanggan B mengikuti section

**Tujuan:** pil hijau berpindah ke Tentang/Menu/Lokasi saat diklik atau digulir.

**Akar masalah:** `$links` di `resources/views/components/pelanggan-b/header.blade.php` memakai `active` dari route; tautan `#tentang`, `#menu`, `#lokasi` di-hardcode `false`, dan `Menu` hanya aktif di alur pesanan.

**Perubahan:**

- `resources/views/components/pelanggan-b/header.blade.php`: setiap tautan mendapat `data-nav-section` (`beranda`, `tentang`, `menu`, `lokasi`); `active` server-side hanya untuk Beranda (halaman beranda) dan Menu (alur pesanan). Render kelas awal berdasarkan itu; JS mengambil alih saat di beranda.
- `resources/js/app.js` (fungsi `initSectionNav()` dari S4, dipakai kedua layout) atau `resources/js/pelanggan-b.js` bila butuh logika khusus pesanan.
- Pastikan panel mobile juga mendapat state aktif dan menutup setelah klik.
- Jangan mengubah tautan keranjang atau alur pesanan.

**Kriteria penerimaan:**

- Di `/pesan`, klik Tentang/Menu/Lokasi memindahkan pil hijau ke tautan itu dan menggulir ke section.
- Saat scroll manual, pil mengikuti section yang terlihat; `aria-current` ikut berubah.
- Di halaman detail produk/keranjang/checkout, Menu tetap aktif (perilaku sekarang dipertahankan).
- Tidak ada request halaman baru saat klik anchor.

**Test:** feature test beranda B memuat `data-nav-section` untuk empat tautan dan `id="beranda"`, `#tentang`, `#menu`, `#lokasi`. Perilaku scroll manual.

**Dokumen:** catatan di HANDOFF; tidak perlu keputusan baru (memperbaiki bug perilaku yang sudah diinginkan desain).

---

### S6 — Dashboard: tiga kotak periode yang bisa diklik

**Tujuan:** kotak ringkasan operasional menjadi tiga pilihan — **Hari Ini**, **Minggu Ini**, **Bulan Ini** — dan memilih salah satu menampilkan ringkasan periode itu.

**Pendekatan yang disarankan:** server-side dengan query string `?periode=hari|minggu|bulan`, default `hari`. Tanpa JS, mudah diuji, dan tetap satu halaman (`GET`).

**Perubahan:**

- `app/Services/DashboardService.php`
  - Tambah `ringkasan(string $periode): array` yang mengembalikan: `periode`, `label`, `revenue`, `count` (transaksi lunas), `qris`, `tunai`, `qris_percent`, `cash_percent`, `orders` (semua status), `pembanding` (jumlah pesanan periode sebelumnya), `change_percent`.
  - Rentang: hari = `startOfDay..endOfDay`; minggu = Senin 00:00 s/d sekarang (`startOfWeek(Carbon::MONDAY)`); bulan = awal..akhir bulan. Zona `Asia/Jakarta`.
  - Hanya `status_pembayaran = lunas` untuk pendapatan/transaksi; semua status untuk hitungan pesanan.
  - `data()` tetap mengembalikan data lama (menu terlaris bulan, transaksi terbaru, pesanan menunggu/diproses hari ini) + `ringkasan` untuk periode aktif.
- `app/Http/Controllers/AdminController@dashboard`
  - Baca `?periode`, validasi ke `hari|minggu|bulan`, fallback `bulan` atau `hari` sesuai keputusan; kirim `periodeAktif`, `ringkasan`, dan label.
- `resources/views/admin/dashboard.blade.php`
  - Tiga kotak menjadi `<a href="{{ route('admin.dashboard', ['periode' => '...']) }}">` dengan angka ringkas; kotak aktif diberi `aria-current="page"` dan gaya aktif.
  - Kartu “Total Pendapatan” dan “Total Pesanan” memakai `ringkasan` periode aktif; subtitle menyebut periode (mis. “Hanya transaksi lunas • Minggu Ini”).
  - Menu terlaris tetap “bulan ini”; pesanan menunggu/diproses tetap “hari ini” dengan label jelas.
- `resources/js/admin.js`: `initDashboard()` tidak menghitung ulang angka ringkasan (tetap hanya tabel transaksi). Tidak ada perubahan data.

**Keputusan yang perlu disetujui user:**

- Periode default: **hari ini** (disarankan) atau tetap **bulan ini**.
- Definisi “minggu”: **Senin–Minggu** (disarankan, konvensi Indonesia) atau Minggu–Sabtu.

**Kriteria penerimaan:**

- Tiga kotak tampil dan bisa diklik; kotak aktif terlihat jelas.
- `?periode=hari` hanya menghitung order hari berjalan; `?periode=minggu` menghitung sejak Senin; `?periode=bulan` menghitung bulan berjalan.
- Nilai `?periode=...` tidak valid jatuh ke default tanpa error.
- Angka pendapatan tetap hanya transaksi lunas; transaksi belum lunas tidak menambah pendapatan.
- Menu terlaris dan sepuluh transaksi terbaru tidak berubah karena periode.
- Semua angka tetap dirender server (tidak ada perhitungan baru di `admin.js`).

**Test:** `tests/Feature/AdminDashboardTest` tambah kasus: default, `hari` (order kemarin tidak ikut), `minggu` (order minggu lalu tidak ikut, order Senin ikut), `bulan` (order bulan lalu tidak ikut), periode tidak valid, tiga tautan periode tampil.

**Dokumen:** `docs/DECISIONS.md` F-66 diperbarui (mengizinkan `?periode`, menambah definisi minggu); `docs/ROUTES.md` §4.16 (query string + tabel aturan); PRD AD-01 catatan bahwa tampilan minggu adalah tambahan UI, bukan aturan bisnis baru.

---

### S7 — Urutan slide promo di atas 4

**Tujuan:** memastikan `urutan` 5 dan seterusnya tersimpan, tampil, dan diputar dengan benar; bila ternyata bukan bug server, hilangkan kebingungan di UI.

**Status:** **belum bisa dipastikan dari kode.** Yang sudah diperiksa dan **tidak** membatasi:

- `Promo::scopeOrdered()` hanya `orderBy('urutan')->orderBy('id')`.
- `PelangganAController`/`PelangganBController` mengambil `Promo::active()->ordered()->get()` tanpa `limit`.
- `hero.blade.php` merender semua slide; `pelanggan-a.js` memutar `slides.length` berapa pun.
- `PromoRequest` mengizinkan `urutan` 1–999; `promos.urutan` `unsigned int`.
- `PromoService` menyimpan `(int) $data['urutan']` tanpa normalisasi.

**Langkah diagnosis (wajib lebih dulu):**

1. Pastikan ada 4 promo aktif berurutan 1–4, lalu tambah promo ke-5 dengan `urutan = 5` dari UI.
2. Periksa `SELECT id, judul, urutan, aktif FROM promos ORDER BY urutan, id;` — apakah nilai 5 benar tersimpan?
3. Periksa HTML `/` — apakah ada `data-hero-slide="4"` dan judul promo ke-5?
4. Tambah feature test: 5 promo aktif → `/` memuat 5 slide berurutan; `POST /admin/promosi` dengan `urutan=5` menyimpan 5; halaman admin menampilkan 5 kartu.
5. Bila perlu, minta user langkah persis (nilai yang diketik, promo mana, apakah memakai tombol Ubah atau Tambah Slide).

**Kemungkinan hasil dan tindakan:**

- **Server benar, UI membingungkan:** label “Slide NN” saat ini memakai posisi array, bukan `urutan`. Perjelas menjadi “Urutan #{{ urutan }}” + posisi, dan tambahkan validasi `urutan` unik (`distinct` terhadap promo lain) supaya dua promo tidak berebut nomor. Tambahkan pesan bantuan bahwa nomor besar berarti tampil belakangan.
- **Nilai tidak tersimpan / berubah:** perbaiki di lapisan yang terbukti (request/service/form) dan tambahkan regression test sesuai temuan.
- **Duplikat `urutan` membuat urutan tidak deterministik:** tambahkan normalisasi 1..N di `PromoService` (dalam `DB::transaction`) atau validasi unik; jangan mengubah skema.

**Kriteria penerimaan:**

- Admin dapat menyimpan `urutan` 5, 10, dst.; nilai tidak berubah menjadi 1.
- Landing page menampilkan seluruh promo aktif sesuai `urutan` lalu `id`; carousel memutar semuanya.
- Promo nonaktif / di luar periode tetap tidak tampil (PRD §8.9).
- Tidak ada perubahan skema dan tidak ada dependency baru.

**Test:** feature test 5+ promo (render + simpan), test validasi bila aturan unik ditambahkan. Bila hasil diagnosis menunjukkan bug yang tidak dapat direproduksi, **berhenti dan tanya user** sebelum mengubah perilaku.

**Dokumen:** perbarui `docs/DECISIONS.md` F-63 bila aturan urutan berubah; catat hasil diagnosis di HANDOFF.

---

### S8 — Regression, build, dan dokumentasi

**Wajib setelah S1–S7:**

1. `php artisan test` — seluruh suite hijau (baseline terakhir yang tercatat: 211 passed; tambahan test S1–S7 akan menaikkan angka).
2. `php vendor/bin/pint --test` — bersih.
3. `npm.cmd run build` — berhasil (ada perubahan JS/CSS).
4. Manual QA sesuai §5.
5. Perbarui `docs/HANDOFF.md` (status irisan, baseline baru, catatan), `AGENTS.md` §2 (ringkasan status area yang berubah), `docs/DECISIONS.md`, `docs/ROUTES.md`, `docs/DATABASE.md` bila perlu, dan tandai temuan di `docs/TEST-FITUR.md` sebagai selesai/terbuka.
6. Jangan commit/push — user yang melakukan commit (HANDOFF §7). Berikan daftar file yang perlu di-`git add`.

---

## 4. Keputusan yang perlu disetujui sebelum dikerjakan

Sesuai AGENTS §10, beberapa hal menyentuh PRD/keputusan FINAL:

| # | Topik | Usulan | Dampak |
|---|---|---|---|
| D1 | Navigasi Pelanggan A satu halaman | Navbar anchor ke `/#section`; route `/tentang`, `/menu`, `/lokasi` tetap ada sebagai deep link | Perlu catatan PRD §4.3 + keputusan baru (F-73) |
| D2 | Katalog beranda 4 item | Ekspansi di klien; `/menu` tetap penuh; pemesanan B tidak dipotong | Keputusan UI baru (F-72) |
| D3 | Periode dashboard | Tambah “Minggu Ini” + query `?periode`; default **hari ini**; minggu **Senin–Minggu** | F-66 berubah (sebelumnya “tanpa query string”); PRD AD-01 catatan tambahan |
| D4 | Urutan promo | Diagnosis dulu; bila perlu, `urutan` unik + normalisasi 1..N | F-63 berubah bila aturan urutan diperketat |

Bila user memilih arah lain (mis. navbar tetap multi-halaman, atau dashboard memakai tombol JS tanpa reload), rencana ini perlu disesuaikan sebelum S4/S6.

---

## 5. Checklist QA manual (setelah S1–S7)

**Pelanggan A**

- [ ] `/` terbuka; navbar di tengah; klik Tentang/Menu/Lokasi menggulir tanpa pindah halaman; pil aktif mengikuti scroll.
- [ ] Section Tentang menampilkan foto yang diunggah admin (bukan placeholder).
- [ ] Section Menu menampilkan 4 kartu; “Lihat Semua Menu” memperluas; pencarian/filter menemukan menu ke-5+.
- [ ] `/tentang`, `/menu`, `/lokasi` masih bisa dibuka langsung.

**Pelanggan B**

- [ ] `/pesan` menampilkan 4 kartu awal; “Lihat Semua Menu” memperluas; item ke-5+ bisa ditambahkan ke keranjang.
- [ ] Foto Tentang Kami tampil.
- [ ] Pil hijau berpindah saat klik/scroll; di halaman detail/keranjang/checkout, Menu aktif.
- [ ] Checkout QRIS, keranjang, dan halaman status tidak berubah perilakunya.

**Admin**

- [ ] Iklan & Promosi: unggah foto Tentang Kami lewat pilih file **dan** drag & drop; status berubah “Tampil”; foto muncul di publik; hapus foto mengembalikan placeholder.
- [ ] Iklan & Promosi: tambah promo dengan urutan 5; simpan; landing page menampilkan dan memutar 5 slide.
- [ ] Informasi Toko: tujuh baris jam langsung terlihat; ubah jam → Simpan → tampil di `/lokasi`.
- [ ] Dashboard: tiga kotak bisa diklik; angka berubah sesuai periode; pendapatan tetap hanya transaksi lunas.
- [ ] Kelola Menu & Laporan Transaksi: tidak ada regresi.

**Kasir**

- [ ] Buat Pesanan, Pesanan Masuk, polling, detail, pembatalan, Cek Status, Kelola Menu tetap berjalan (regression saja).

---

## 6. Yang tidak termasuk rencana ini

- Perbaikan tampilan/estetika front-end yang akan user kerjakan terpisah.
- Ekspor laporan PDF/Excel (F-19, Fase 2).
- Keranjang server-side (F-69).
- Perubahan skema database, dependency baru, atau perubahan aturan pembayaran/nomor antrean.
- Kredensial/akun Midtrans produksi (O-16).
