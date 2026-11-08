# Panduan Midtrans Sandbox + ngrok

Panduan operasional untuk menghubungkan aplikasi ke **Midtrans Sandbox** dan menerima **webhook** pembayaran QRIS di mesin lokal.

Dokumen ini tidak berisi kredensial. Nilai asli hanya boleh ada di `.env` lokal dan dashboard Midtrans.

**Terakhir diperbarui:** 30 Oktober 2026
**Terkait:** PRD §11, `docs/ROUTES.md` §4.7, `docs/DECISIONS.md` F-54–F-56

---

## 0. Ringkasan alur

```text
Browser pelanggan
  → POST /pesan/checkout (Laravel)
  → Midtrans Snap (sandbox)
  → pelanggan membayar QRIS
  → Midtrans POST /api/midtrans/notification (lewat ngrok)
  → order jadi lunas + nomor antrean
  → polling kasir menampilkan pesanan di tab Antre
```

Yang **tidak** boleh dilakukan: menandai lunas lewat tombol, seeder, tinker, atau query manual. Status lunas hanya boleh berasal dari webhook Midtrans.

---

## 1. Prasyarat

- Akun **Midtrans Sandbox**. Daftar di <https://dashboard.sandbox.midtrans.com> (bukan dashboard produksi).
- PHP + MySQL (XAMPP) berjalan, database `kedai_varya` sudah dimigrasi.
- `php artisan serve` bisa dijalankan.
- **ngrok** terpasang, versi **>= 3.28.0**, dan sudah login. Jalankan `ngrok update` bila masih 3.3.1.
- `.env` memakai `APP_ENV=local` dan `APP_URL=http://localhost:8000` (tidak wajib diubah).

> Jangan memakai kredensial produksi. `MIDTRANS_IS_PRODUCTION` harus `false` selama pengembangan.

---

## 2. Ambil kredensial sandbox

1. Buka <https://dashboard.sandbox.midtrans.com> lalu login.
2. Buka menu **Settings** (ikon gerigi) → **Access Keys**.
3. Catat tiga nilai:
   - **Merchant ID**
   - **Client Key** - diawali `SB-Mid-client-...`; dipakai Snap.js untuk popup di browser (boleh publik)
   - **Server Key** - diawali `SB-Mid-server-...`; hanya untuk backend dan signature webhook
4. Jangan bagikan **Server Key**. Nilai ini hanya untuk backend.

Jika akun belum punya transaksi, Midtrans tetap menampilkan kredensial sandbox. Tidak perlu mengaktifkan mode produksi.

---

## 3. Isi `.env` dan bersihkan cache

Edit `.env` di root project:

```dotenv
MIDTRANS_MERCHANT_ID=isi-merchant-id
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxx
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxx
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SNAP_SANDBOX_URL=https://app.sandbox.midtrans.com/snap/v1/transactions
MIDTRANS_API_SANDBOX_URL=https://api.sandbox.midtrans.com/v2
```

Lalu:

```powershell
php artisan config:clear
```

Bila pernah menjalankan `php artisan config:cache`, jalankan `php artisan config:cache` lagi setelah mengubah `.env`.

Cek cepat tanpa membocorkan nilai:

```powershell
php artisan tinker --execute="dump(filled(config('services.midtrans.server_key')));"
```

Harus menghasilkan `true`. Bila `false`, checkout akan menjawab `503`.

> `.env` sudah masuk `.gitignore`. Jangan menambahkan nilai asli ke `.env.example`, dokumentasi, JS, atau commit. Bila Server Key sempat ter-commit atau terkirim ke browser, **rotate** key di dashboard Midtrans.

---

## 4. Jalankan server dan tunnel

Laravel harus berjalan lebih dulu. Tunnel hanya meneruskan HTTP ke port 8000.

> **Jika unduhan ngrok lambat/gagal**, lewati §4.2 dan pakai **§4.4 SSH** — tidak perlu mengunduh apa pun karena Windows sudah punya OpenSSH. Tunnel SSH aktif dalam hitungan detik.

### 4.1 Jalankan Laravel

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Biarkan terminal ini terbuka.

> **Windows/PHP: `cURL error 60`.** Jika checkout Snap gagal dengan `SSL certificate ... unable to get local issuer certificate`, PHP cURL belum punya CA bundle. Unduh <https://curl.se/ca/cacert.pem> ke `C:\php-8.5.9-Win32-vs17-x64\extras\ssl\cacert.pem`, lalu set di `php.ini`:
>
> ```ini
> curl.cainfo = "C:\php-8.5.9-Win32-vs17-x64\extras\ssl\cacert.pem"
> openssl.cafile = "C:\php-8.5.9-Win32-vs17-x64\extras\ssl\cacert.pem"
> ```
>
> Restart `php artisan serve` setelah mengubah `php.ini`. Mesin pengembangan ini sudah dikonfigurasi pada 31 Okt 2026.

### 4.2 Opsi A — ngrok (utama)

**Cek apakah ngrok sudah terpasang:**

```powershell
winget list --name ngrok
```

Bila muncul `Ngrok.Ngrok`, **jangan unduh ZIP**. Tutup semua jendela PowerShell/VS Code, buka PowerShell baru, lalu:

```powershell
ngrok version
```

Bila perintah belum dikenali padahal `winget list` menunjukkan terpasang, pakai path paket langsung:

```powershell
& "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\Ngrok.Ngrok_Microsoft.Winget.Source_8wekyb3d8bbwe\ngrok.exe" version
```

**Pasang bila belum ada:**

```powershell
winget install --id Ngrok.Ngrok -e --accept-package-agreements --accept-source-agreements
```

Tutup dan buka ulang terminal setelah pemasangan. Paket winget saat ini masih **3.3.1** dan sudah ditolak server ngrok (`ERR_NGROK_121`, minimal **3.28.0**), jadi **wajib update**:

```powershell
ngrok update
ngrok version
```

Versi yang lolos per 30 Okt 2026: **3.39.13**.

**Jika Defender memblokir binary hasil update** (`Trojan:Win32/Kepavll!rfn`):

Gejalanya `Program 'ngrok.exe' failed to run: ... contains a virus or potentially unwanted software`. Ini false positive pada binary tunnel. Tambahkan pengecualian khusus (butuh Administrator):

```powershell
Add-MpPreference -ExclusionPath "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\Ngrok.Ngrok_Microsoft.Winget.Source_8wekyb3d8bbwe"
Add-MpPreference -ExclusionProcess "ngrok.exe"
```

Jika binary yang **sudah terlanjur diblokir** masih gagal setelah dua exclusion di atas, barulah tambahkan allow untuk signature false-positive tersebut (lebih luas, gunakan hanya bila perlu):

```powershell
Set-MpPreference -ThreatIDDefaultAction_Ids 2147939874 -ThreatIDDefaultAction_Actions 3
```

Atau lewat Windows Security: **Virus & threat protection → Protection history → Trojan:Win32/Kepavll!rfn → Actions → Allow on device**. Jangan mematikan real-time protection.

Catatan: mesin pengembangan ini sudah diberi exclusion path + process dan allow signature tersebut pada 30 Okt 2026, jadi `ngrok version` langsung berjalan.

**Pasang authtoken dan jalankan tunnel:**

Ambil token di <https://dashboard.ngrok.com/get-started/your-authtoken>, lalu:

```powershell
ngrok config add-authtoken "<token-ngrok>"
ngrok http 127.0.0.1:8000
```

Gunakan `127.0.0.1:8000`, **bukan** `8000` saja. Di Windows, `localhost:8000` bisa resolve ke IPv6 `::1`, sedangkan `php artisan serve --host=127.0.0.1` hanya listen IPv4 — akibatnya ngrok menjawab `502`.

Salin URL HTTPS dari output ngrok, misalnya:

```text
https://abcd-1234-5678.ngrok-free.dev
```

Catatan:

- Folder paket winget otomatis ditambahkan ke **User PATH**. Terminal yang sudah terbuka sebelum pemasangan tidak memuat PATH baru — tutup semua terminal/VS Code lalu buka lagi.
- `ngrok config` tanpa subcommand hanya menampilkan bantuan, bukan error.
- Authtoken disimpan di `%LOCALAPPDATA%\ngrok\ngrok.yml`, bukan di repository.
- `ERR_NGROK_105` = token salah/terpotong. Ulangi `ngrok config add-authtoken`.
- `ERR_NGROK_121` = agent terlalu lama. Jalankan `ngrok update`.
- `502` dari ngrok = upstream tidak jalan atau salah alamat. Pastikan Laravel listen di `127.0.0.1:8000` dan jalankan `ngrok http 127.0.0.1:8000`.
- Unduh ZIP manual hanya bila winget gagal. Windows Defender sering menandai ZIP ngrok sebagai `Trojan:Win32/Kepavll!rfn` (deteksi generik/false positive). Jangan mematikan Defender; gunakan paket winget lalu `ngrok update`.
- Verifikasi token di dashboard: **Getting Started → Your Authtoken**.

### 4.3 Opsi B — cloudflared (tanpa akun)

Cocok bila ngrok tidak bisa dipasang. Satu file exe, tanpa login.

```powershell
$dir = "$env:USERPROFILE\cloudflared"
New-Item -ItemType Directory -Force -Path $dir | Out-Null
Invoke-WebRequest -Uri "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe" -OutFile "$dir\cloudflared.exe"
& "$dir\cloudflared.exe" tunnel --url http://localhost:8000
```

Cloudflared menampilkan URL acak, misalnya `https://kata-kata-acak.trycloudflare.com`. URL ini berubah setiap cloudflared dijalankan ulang.

### 4.4 Opsi C — SSH (tanpa unduhan)

Windows sudah punya OpenSSH:

```powershell
ssh -R 80:localhost:8000 nokey@localhost.run
```

Setujui host key dengan `yes` bila diminta. URL HTTPS muncul di output. Biarkan sesi SSH terbuka.

### 4.5 Opsi D — VS Code Port Forwarding

1. Buka folder project di VS Code dan jalankan `php artisan serve` di terminal VS Code.
2. Buka panel **Ports** (View → Terminal → Ports).
3. **Forward a Port** → isi `8000`.
4. Klik kanan port → **Port Visibility** → **Public**.
5. Salin **Forwarded Address** dan pakai sebagai URL publik.

VS Code dan Laravel harus tetap berjalan selama pengujian.

### 4.6 Cek tunnel

```powershell
curl.exe https://<url-tunnel>/up
```

Respons harus `200`. Jangan menutup terminal Laravel maupun tunnel selama pengujian. URL gratis umumnya berubah setiap tunnel dijalankan ulang; perbarui Notification URL Midtrans bila berubah.

---

## 5. Daftarkan Notification URL di Midtrans

1. Buka <https://dashboard.sandbox.midtrans.com>.
2. Masuk **Settings** → **Payment**.
3. Isi **Notification URL** dengan:

   ```text
   https://abcd-1234-5678.ngrok-free.app/api/midtrans/notification
   ```

4. Simpan.
5. Bila ada tombol **Test Notification URL**, tekan untuk memastikan endpoint dapat dijangkau. Endpoint aplikasi bisa menjawab `403` untuk payload uji karena signature tidak cocok — yang penting bukan `404`/timeout. Respons `403` berarti route dan CSRF exception sudah benar.

Pastikan path **persis** `/api/midtrans/notification` (tanpa slash tambahan, tanpa `/public`).

Opsional: atur **Finish Redirect URL** ke `/pesanan/{kode_pesanan}`. Aplikasi juga mengirim `callbacks.finish`; alur utama tetap memakai popup Snap di halaman checkout.

---

## 6. Uji satu pembayaran QRIS

1. Buka aplikasi lewat URL ngrok atau `http://localhost:8000/pesan`.
2. Tambahkan menu ke keranjang → **Checkout**.
3. Isi nama dan tipe pesanan → lanjut ke checkout; popup **Snap sandbox** muncul tanpa meninggalkan website.
4. Pilih **QRIS**. QR dinamis muncul.
5. Selesaikan pembayaran memakai salah satu cara sandbox:
   - **Midtrans Simulator** — buka <https://simulator.sandbox.midtrans.com>, pilih simulator QRIS, lalu masukkan/scan QR dari halaman Snap.
   - **Aplikasi GoPay sandbox** — scan QR memakai aplikasi sandbox Midtrans.
6. Tunggu beberapa detik. Midtrans mengirim `POST` ke Notification URL.

Jangan memakai QRIS produksi atau aplikasi pembayaran asli.

---

## 7. Verifikasi hasil

### 7.1 Di aplikasi

- Buka **Kasir → Pesanan Masuk** (login kasir).
- Dalam ±7 detik (interval polling), pesanan berpindah dari **Belum Dibayar** ke **Antre** dengan nomor antrean.
- Status pembayaran berubah menjadi **Lunas**.

### 7.2 Di database

```sql
SELECT kode_pesanan, status_pesanan, status_pembayaran, nomor_antrean, waktu_lunas
FROM orders
ORDER BY id DESC
LIMIT 5;

SELECT midtrans_order_id, transaction_status, status_code, signature_valid, status_pemrosesan, catatan
FROM payment_notifications
ORDER BY id DESC
LIMIT 5;
```

Yang diharapkan untuk satu pembayaran berhasil:

- `orders.status_pembayaran = lunas`
- `orders.status_pesanan = antre`
- `orders.nomor_antrean` terisi **satu kali** (tidak bertambah saat notifikasi diulang)
- `payment_notifications.status_pemrosesan = diproses`
- `payment_notifications.signature_valid = 1`

### 7.3 Di log

```powershell
Get-Content storage\logs\laravel.log -Tail 50
```

Cari pesan `Notifikasi Midtrans ...`. Log tidak boleh berisi Server Key.

### 7.4 Notifikasi ganda

Midtrans dapat mengirim ulang notifikasi. Ulangi pembayaran/notifikasi atau kirim ulang dari dashboard; hasil yang benar:

- `payment_notifications` bertambah barisnya (audit),
- `orders.nomor_antrean` **tidak berubah**,
- `queue_counters.nomor_terakhir` **tidak bertambah**.

---

## 8. Troubleshooting

| Gejala | Kemungkinan penyebab | Tindakan |
|---|---|---|
| `ngrok` tidak dikenali padahal sudah terpasang | Terminal lama belum memuat PATH baru | Tutup semua terminal/VS Code, buka baru; cek `winget list --name ngrok` |
| `ERR_NGROK_105` | Authtoken salah/terpotong | Ambil ulang token, `ngrok config add-authtoken` |
| `ERR_NGROK_121` | Agent ngrok terlalu lama (minimal 3.28.0) | `ngrok update`, lalu `ngrok version` |
| `Program 'ngrok.exe' failed to run ... contains a virus` | Defender memblokir binary ngrok (`Trojan:Win32/Kepavll!rfn`, false positive) | Tambahkan exclusion path + process seperti §4.2; jangan matikan Defender |
| `cURL error 60 ... unable to get local issuer certificate` | PHP cURL tidak punya CA bundle | Set `curl.cainfo` + `openssl.cafile` seperti §4.1, restart `php artisan serve` |
| Checkout gagal, order muncul `menunggu_pembayaran`, lalu klik berikutnya "keranjang kosong" | Order sudah tersimpan tetapi pembuatan Snap gagal; keranjang sudah dihapus | Buka `/pesanan/{kode}` lalu tekan **Coba Bayar Lagi**; jangan menandai lunas manual |
| ZIP ngrok diblokir Defender (`Trojan:Win32/Kepavll!rfn`) | Deteksi generik pada binary tunnel | Gunakan paket winget lalu `ngrok update`; jangan matikan Defender |
| Checkout/bayar lagi `503` | `MIDTRANS_SERVER_KEY`/`MIDTRANS_CLIENT_KEY` kosong | Isi `.env`, `php artisan config:clear` |
| Webhook `403` | Server Key salah, key produksi dipakai di sandbox, atau `gross_amount` diubah | Samakan key dengan dashboard sandbox; jangan mengubah payload |
| Webhook `500` | `MIDTRANS_SERVER_KEY` kosong saat notifikasi datang | Isi `.env`, lalu kirim ulang dari dashboard |
| Webhook `419` | CSRF exception tidak aktif / path salah | Pastikan `bootstrap/app.php` mengecualikan `api/midtrans/notification` dan route persis sama |
| Webhook `404` | Path Notification URL salah atau ngrok mati | Cek `/up` lewat URL ngrok, perbarui URL |
| Tidak ada notifikasi | URL ngrok berubah setelah restart | Perbarui Notification URL di dashboard |
| `payment_notifications` bertambah tapi order tidak berubah | `order_id` tidak cocok / nominal tidak cocok / pesanan sudah lunas | Lihat kolom `catatan` dan `status_pemrosesan` |
| Order tetap `menunggu` | Pembayaran belum benar-benar settlement di sandbox | Cek status transaksi di dashboard Midtrans |
| Nomor antrean tidak muncul | Order belum lunas, atau transisi gagal | Pastikan `status_pembayaran = lunas` dan cek log |

Jika MySQL mati, nyalakan XAMPP dulu. Gejala “semua error” sering berasal dari MySQL yang tidak berjalan, bukan dari kode.

---

## 9. Checklist keamanan

- [ ] Server Key hanya ada di `.env` backend.
- [ ] Tidak ada kredensial di `.env.example`, JS, Blade, dokumentasi, atau commit.
- [ ] `MIDTRANS_IS_PRODUCTION=false` selama pengembangan.
- [ ] Notification URL memakai HTTPS (ngrok).
- [ ] Tidak ada tombol atau query manual yang menandai lunas.
- [ ] Data uji hanya ada di database lokal; jangan `migrate:fresh` pada database berisi data nyata.
- [ ] Setelah selesai menguji, hentikan ngrok dan hapus URL uji dari dashboard bila tidak dipakai.

---

## 10. Perintah ringkas

```powershell
# terminal 1
php artisan serve --host=127.0.0.1 --port=8000

# terminal 2
ngrok http 127.0.0.1:8000

# setelah mengubah .env
php artisan config:clear

# verifikasi kode
php artisan test
php vendor/bin/pint --test
```
