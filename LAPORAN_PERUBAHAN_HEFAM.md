# LAPORAN PERUBAHAN & PEMBARUAN SISTEM HEFAM
**Sistem Manajemen Peternakan Ayam Petelur Modern (SaaS)**  
*Dokumentasi Resmi Pembaruan Fitur, Audit Keamanan, Pengujian Otomatis & Panduan Deployment*  
*Tanggal: 13 Oktober 2026*

---

## 1. Ringkasan Eksekutif
Pembaruan ini mencakup integrasi fitur strategis untuk operasional peternakan komersial, modernisasi alur penagihan piutang, peluncuran portal mandiri pelanggan, sistem pengingat otomatis WhatsApp (Fonnte) dengan batasan jam operasional, kalkulator pakan/FCR, informasi harga pasar rujukan Pinsar Indonesia terfokus Bali, serta penguatan keamanan multi-tenancy.

Seluruh 235 skenario pengujian otomatis (**1.252 assertions**) dinyatakan **LULUS 100% (PASS)** tanpa kendala.

---

## 2. Rincian Pembaruan Fitur & Logika Bisnis

### A. Pengiriman Pesan Otomatis Tagihan WhatsApp (Fonnte) ke Pelanggan
* **Kebutuhan Lapangan**: Peternak membutuhkan sistem penagihan otomatis yang mengirimkan pesan langsung ke WhatsApp pembeli/tengkulak ketika tanggal jatuh tempo nota telah tiba, tanpa perlu mengklik tombol satu per satu.
* **Fitur & Logika**:
  1. **Sasaran Penerima**: Nomor WhatsApp pelanggan (`Customer::$phone`) yang memiliki nota penjualan telur belum lunas dengan `due_date <= hari ini`.
  2. **Batasan Jam Kirim Operasional (07:00 – 19:00)**: Pesan otomatis hanya dieksekusi antara pukul **07:00 pagi hingga 19:00 (7 malam)** untuk menjaga etika dagang dan kenyamanan pelanggan. Di luar jam tersebut, sistem otomatis menahan pengiriman.
  3. **Pemberian Portal Tagihan Resmi**: Setiap pesan otomatis melampirkan tautan portal unik tanpa login (`https://hefam.id/tagihan/{customer}/{token}`).
  4. **Pencegahan Spam & Siklus Pengulangan 7 Hari**: Kolom `last_reminded_at` pada nota dan penjualan diperbarui saat terkirim. Pesan tidak akan dikirim dobel di hari yang sama; jika masih belum lunas, pengingat baru diulang kembali setelah 7 hari.
  5. **Bypass Pengujian (`--paksa`)**: Perintah artisan menyediakan flag `--paksa` untuk memungkinkan admin/peternak melakukan uji kirim kapan saja di luar jam operasional.
  6. **Sakelar Pengaturan**: Menu Pengaturan (`/settings`) dilengkapi opsi on/off: *"Tagih pembeli otomatis via WhatsApp saat tanggal jatuh tempo"*.
* **File Terkait**:
  - `database/migrations/2026_10_26_000000_add_last_reminded_at_to_invoices_and_sales.php`
  - `app/Models/Invoice.php` & `app/Models/EggSale.php`
  - `app/Models/Setting.php` & `app/Http/Controllers/SettingController.php`
  - `app/Services/FarmReminders.php`
  - `routes/console.php` (`hefam:tagih-pelanggan`)
  - `resources/views/settings/edit.blade.php`

---

### B. Portal Tagihan & Unduh Nota Mandiri Pelanggan (Customer Portal)
* **Kebutuhan Lapangan**: Pembeli membutuhkan akses transparan untuk melihat rincian nota yang dibeli, riwayat pembayaran, sisa kewajiban, dan mengunduh faktur PDF tanpa harus membuat akun atau mengingat kata sandi.
* **Fitur & Logika**:
  1. **Tokenisasi Kriptografi**: URL portal dilindungi token HMAC SHA-256 berbasis `app.key`, `customer_id`, dan `farm_id`.
  2. **Rincian Status Utang**:
     - 🔴 **Sudah Jatuh Tempo** (dilengkapi tanggal tempo dan subtotal)
     - 🟡 **Belum Jatuh Tempo**
     - 💼 **Saldo Titip Uang / Deposit**
     - 💵 **Total Bersih yang Harus Ditransfer**
  3. **Cetak & Unduh Nota PDF**: Pembeli dapat langsung mengunduh salinan faktur resmi bertanda QR & rincian telur melalui `/tagihan/{customer}/{token}/nota/{invoice}/cetak`.
  4. **Proteksi Akses**: Dilindungi middleware rate limiting (`throttle:60,1`) dan verifikasi status peternakan aktif.
* **File Terkait**:
  - `app/Http/Controllers/CustomerPortalController.php`
  - `resources/views/portal/customer.blade.php`
  - `routes/web.php`
  - `tests/Feature/CustomerPortalSecurityTest.php`

---

### C. Modul Titip Uang / Saldo Deposit Pelanggan & Pemotongan Utang
* **Kebutuhan Lapangan**: Tengkulak telur sering kali membayar lebih (misal transfer nominal bulat) atau menitipkan uang muka di awal. Saldo titip uang ini harus tercatat rapi dan dapat digunakan untuk memotong tagihan nota berikutnya.
* **Fitur & Logika**:
  1. **Auto-Deposit Kelebihan Bayar**: Saat peternak mencatat pembayaran nota di mana nominal pembayaran melebihi utang nota, kelebihannya otomatis ditambahkan ke `deposit_balance` pelanggan dan dicatat di mutasi `customer_deposits`.
  2. **Potong Utang dari Saldo Deposit**: Pada modal pencatatan pembayaran nota (`_pay-modal.blade.php`), jika pelanggan memiliki saldo titip uang, muncul kotak informasi dan tombol *"Potong Titip Uang"*.
  3. **Audit Trail Mutasi**: Riwayat mutasi masuk (`in`) dan keluar (`out`) tersimpan lengkap di tabel `customer_deposits` dengan saldo akhir (`balance_after`).
* **File Terkait**:
  - `database/migrations/2026_10_25_000000_add_deposit_balance_to_customers.php`
  - `app/Models/Customer.php` (`addDeposit`, `useDeposit`)
  - `app/Models/CustomerDeposit.php`
  - `app/Http/Controllers/EggSaleController.php` (`payDebt`)
  - `resources/views/sales/_pay-modal.blade.php` & `resources/views/sales/index.blade.php`
  - `resources/views/customers/show.blade.php`

---

### D. Harga Pasar Telur, Ayam & Pakan Hari Ini (Fokus Wilayah Bali)
* **Kebutuhan Lapangan**: Peternak ayam di Bali membutuhkan rujukan harga pasar harian resmi Pinsar Indonesia agar tidak dirugikan oleh permainan harga tengkulak.
* **Fitur & Logika**:
  1. **Prioritas Utama Wilayah Bali**: Tab Bali (sentra Tabanan, Badung, dan Bangli) menjadi tab default terdepan, menyajikan harga Telur Ayam Ras Kandang, Pakan Konsentrat Layer, Jagung Pipil Kering, Dedak Halul, dan Ayam Broiler Hidup.
  2. **Pembanding Antar Wilayah**: Dilengkapi perbandingan harga Jawa Timur (Blitar), Jawa Tengah (Solo), Jawa Barat (Bogor), dan Luar Jawa (Medan/Makassar).
  3. **Live Sync & Fallback Scraping**: Service menghubungi Pinsar Indonesia (`pinsarindonesia.id`) dengan fallback dataset terstruktur, dilengkapi tombol AJAX *Refresh* langsung di landing page dan cache TTL 1 jam.
* **File Terkait**:
  - `app/Services/MarketPriceService.php`
  - `app/Http/Controllers/HomeController.php`
  - `routes/web.php` (`/api/market-prices`)
  - `resources/views/landing.blade.php`
  - `tests/Feature/LandingTest.php`

---

### E. Kalkulator Kebutuhan Pakan & Estimasi FCR Unggas
* **Fitur**: Form interaktif di landing page peternakan yang menghitung:
  - Rekomendasi komposisi ransum mandiri: Konsentrat (35%), Jagung Pipil (50%), Bekatul (15%).
  - Estimasi kebutuhan pakan harian (kg) dan mingguan (karung 50 kg).
  - Target FCR standar komersial (1,95 – 2,15).
  - Tombol konsultasi cepat langsung ke WhatsApp peternakan.
* **File Terkait**:
  - `resources/views/landing.blade.php`

---

## 3. Audit Keamanan & Perbaikan Bug (Security & Bug Fixes)

| Kerentanan / Area Audit | Resiko Semula | Solusi & Mitigasi yang Diterapkan | Status |
| :--- | :--- | :--- | :---: |
| **Isolasi Multi-Tenancy Portal** | Potensi IDOR jika nomor ID pelanggan ditebak. | Validasi HMAC SHA-256 token unik berbasis `app.key`, `farm_id`, dan `customer_id`. | **AMAN** |
| **Akses Portal Peternakan Nonaktif** | Pelanggan masih bisa mengakses data jika tenant dibekukan. | Penambahan validasi `!$farm->isAccessible()` -> HTTP 403 Forbidden pada `CustomerPortalController`. | **AMAN** |
| **Perlindungan Brute Force** | Percobaan tebak PIN atau token portal secara masif. | Middleware `throttle:60,1` pada portal dan `throttle:5,1` pada login pekerja. Penguncian otomatis setelah 20x salah. | **AMAN** |
| **Race Condition Pembayaran Nota** | Selisih uang jika pembayaran nota diproses bersamaan. | Penambahan `lockForUpdate()` dan transaksi atomik `DB::transaction()` pada `Invoice::applyPayment()`. | **AMAN** |
| **SQL Injection** | Manipulasi input pada pencarian atau query. | Semua query menggunakan Eloquent ORM atau parameter binding terikat `whereRaw('... = ?', [$val])`. | **AMAN** |
| **Cross-Site Scripting (XSS)** | Injeksi skrip berbahaya lewat nama atau catatan nota. | Seluruh Blade view menggunakan sintaks escaping otomatis `{{ ... }}`. | **AMAN** |
| **Spam Notifikasi WhatsApp** | Pelanggan terganggu pesan berulang di malam hari. | Pembatasan ketat jendela jam kirim **07:00 – 19:00**, interval pengulangan minimal 7 hari, dan status `last_reminded_at`. | **AMAN** |

---

## 4. Hasil Verifikasi Pengujian Otomatis (Test Suite)

Pengujian dijalankan melalui PHPUnit / Pest Test Suite dengan hasil sebagai berikut:

```text
Tests:    235 passed (1252 assertions)
Duration: 16.14s
Status:   100% PASS
```

### Rincian Modul Pengujian Kunci:
1. `Tests\Feature\CustomerDebtReminderTest` (8 tests):
   - `mengirim tagihan otomatis ke whatsapp pelanggan saat jatuh tempo antara 07 sampai 19`: **PASS**
   - `pesan tagihan memperhitungkan saldo titip uang deposit`: **PASS**
   - `tidak mengirim di luar jam operasional 07 sampai 19 tanpa opsi paksa`: **PASS**
   - `bisa mengirim di luar jam operasional bila memakai opsi paksa`: **PASS**
   - `tidak mengirim bila jatuh tempo belum tiba`: **PASS**
   - `tidak mengirim bila tagihan sudah lunas`: **PASS**
   - `tidak mengulang kirim di hari yang sama kecuali paksa atau setelah 7 hari`: **PASS**
   - `bisa dinonaktifkan lewat pengaturan`: **PASS**
2. `Tests\Feature\CustomerPortalSecurityTest` (5 tests):
   - `akses portal dengan token valid berhasil`: **PASS**
   - `akses portal dengan token palsu ditolak 403`: **PASS**
   - `tidak bisa mengakses nota milik pelanggan lain lewat portal`: **PASS**
   - `portal menampilkan perhitungan utang bersih yang akurat`: **PASS**
   - `portal ditolak jika peternakan dibekukan`: **PASS**
3. `Tests\Feature\SalesTest` (17 tests):
   - Pengujian siklus penjualan, multi-grade telur, utang jatuh tempo, deposit overpayment, dan pelunasan nota via titip uang: **PASS**
4. `Tests\Feature\LandingTest` (4 tests):
   - Ticker harga komoditas pasar Bali, Pinsar scraper, dan API endpoint: **PASS**
5. `Tests\Feature\WhatsAppReminderTest` (11 tests):
   - Pengingat rekap pagi/sore ke peternak via Fonnte: **PASS**

---

## 5. Panduan Deployment Production (cPanel / Server)

### A. Migrasi Basis Data
Jalankan migrasi database di server untuk menerapkan tabel dan kolom baru:
```bash
php artisan migrate --force
```
*Tabel & kolom yang ditambahkan:*
- `customer_deposits` (Pencatatan mutasi titip uang & deposit)
- `customers.deposit_balance` (Saldo titip uang aktif per pelanggan)
- `invoices.last_reminded_at` (Timestamp pengingat WhatsApp terakhir per nota)
- `egg_sales.last_reminded_at` (Timestamp pengingat WhatsApp terakhir per baris nota)

### B. Konfigurasi Environment (`.env`)
Pastikan variabel berikut terisi di file `.env` server:
```env
# Koneksi WhatsApp Gateway (Fonnte)
HEFAM_WA_DRIVER=fonnte
FONNTE_TOKEN=isi_token_fonnte_anda_disini
FONNTE_URL=https://api.fonnte.com/send

# Konfigurasi Zona Waktu
APP_TIMEZONE=Asia/Makassar
```

### C. Pembersihan Cache Sistem
Setelah pembaruan kode dan migrasi:
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

### D. Konfigurasi Penjadwal Cron (Cron Job cPanel)
Pastikan cron job cPanel mengeksekusi Laravel scheduler setiap menit:
```bash
* * * * * cd /home/hermeswo/public_html && php artisan schedule:run >> /dev/null 2>&1
```
*Perintah terjadwal yang otomatis berjalan:*
- `hefam:tagih-pelanggan`: Berjalan setiap jam pada rentang **07:00 – 19:00** untuk menagih nota jatuh tempo.
- `hefam:pengingat pagi`: Berjalan pukul **06:00** untuk briefing harian pemilik peternak.
- `hefam:pengingat sore`: Berjalan pukul **17:00** untuk pengecekan kandang dan sortir telur.
- `hefam:backup`: Berjalan pukul **01:30** untuk pencadangan database terkompresi.

### E. Perintah Uji Manual dari Terminal
Untuk menguji pengiriman tagihan WhatsApp pelanggan kapan saja:
```bash
php artisan hefam:tagih-pelanggan --paksa
```

---
*Laporan ini disusun secara komprehensif sebagai acuan teknis operasional, audit keamanan, dan rilis versi aplikasi HEFAM.*
