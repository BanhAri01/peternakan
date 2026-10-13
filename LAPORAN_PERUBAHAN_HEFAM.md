# LAPORAN PERUBAHAN & PEMBARUAN SISTEM HEFAM
**Sistem Manajemen Peternakan Ayam Petelur Modern (SaaS)**  
*Dokumentasi Resmi Pembaruan Fitur, Audit Keamanan, Pengujian Otomatis & Panduan Deployment*  
*Tanggal: 13 Oktober 2026*

---

## 1. Ringkasan Eksekutif
Pembaruan ini mencakup integrasi fitur strategis untuk operasional peternakan komersial:
1. **Asisten Kandang (AI)** berbasis chat web interaktif dan WhatsApp (Fonnte API), dengan sistem pulsa kuota token, fungsi pembaca catatan kandang *read-only* anti-halusinasi, serta mode demo offline.
2. Modernisasi alur penagihan piutang otomatis WhatsApp (Fonnte) dengan batasan jam operasional 07:00 – 19:00.
3. Portal mandiri pelanggan tanpa login dengan token HMAC SHA-256.
4. Modul titip uang / saldo deposit pelanggan dan pemotongan nota.
5. Kalkulator pakan/FCR dan rujukan harga pasar harian Pinsar Indonesia (fokus Bali).

Seluruh **247 skenario pengujian otomatis (1.297 assertions)** dinyatakan **LULUS 100% (PASS)** tanpa kendala.

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
### F. Modul Asisten Kandang (AI) untuk WhatsApp & Aplikasi Web
* **Kebutuhan Lapangan**: Peternak dan orang tua sering kali tidak sempat membuka menu laptop/browser saat bekerja di kandang. Mereka membutuhkan asisten pintar di WhatsApp dan aplikasi yang hafal seluruh buku catatan kandang (telur, pakan, piutang, keuangan, kesehatan ayam) dalam bahasa sehari-hari.
* **Fitur & Logika Bisnis**:
  1. **Dua Saluran (Web Chat & WhatsApp Fonnte)**:
     - Di aplikasi web (`/asisten`): Chat interaktif berbasis Alpine.js dengan pill contoh pertanyaan cepat, status pulsa waktu-nyata, dan kartu contekan siap cetak.
     - Di WhatsApp: Menerima pertanyaan dari nomor WA terdaftar melalui webhook Fonnte (`/webhook/asisten/{secret}`) dan membalas via background queue job (`AnswerAssistantWhatsApp`).
  2. **Model Ganda (Claude API Live & DemoLlm Offline)**:
     - Driver `claude`: Menggunakan Anthropic Claude (Haiku untuk tanya cepat, Sonnet untuk analisis mendalam).
     - Driver `demo`: Mesin demo offline cerdas yang meniru alur tool-calling tanpa memanggil API berbayar dan membaca data riil dari database peternakan.
  3. **Keamanan Multi-Tenant & Anti-Halusinasi Keras**:
     - `farm_id` selalu ditentukan dari user yang login atau nomor WhatsApp yang terdaftar di database, bukan dari isi prompt atau jawaban AI.
     - Seluruh data agregasi (panen, pakan, piutang, gaji, keuangan) dihitung oleh SQL tool di backend Laravel (`FarmTools`). AI dilarang keras mengarang angka di luar hasil alat.
     - Semua alat bersifat *read-only* (hanya membaca data).
  4. **Sistem Kuota ("Pulsa Asisten")**:
     - Ditampilkan sebagai persentase sederhana di layar pengguna (`Pulsa: 99% 🔋 ±623 pertanyaan lagi`).
     - Token input/output dihitung presisi dan dikonversi ke Rupiah (`Pricing::cost`).
     - Perintah gratis (menu, pulsa, salam) tidak memotong kuota dan tidak memanggil AI.
     - Jika pulsa habis, sistem otomatis beralih ke balasan pintasan (*shortcuts*) berbasis aturan tanpa gagal (*graceful fallback*).
  5. **Perintah CLI Artisan**:
     - Tersedia perintah terminal `php artisan hefam:asisten "{pertanyaan}" --driver=demo` untuk pengujian dan demo langsung.
* **File Terkait**:
  - `app/Assistant/Agent.php` & `app/Assistant/AgentAnswer.php`
  - `app/Assistant/FarmTools.php` & `app/Assistant/Shortcuts.php`
  - `app/Assistant/ClaudeLlm.php` & `app/Assistant/DemoLlm.php`
  - `app/Assistant/Quota.php` & `app/Assistant/Pricing.php`
  - `app/Http/Controllers/AssistantController.php` & `app/Http/Controllers/AssistantWebhookController.php`
  - `app/Jobs/AnswerAssistantWhatsApp.php`
  - `resources/views/assistant/index.blade.php`
  - `tests/Feature/AssistantTest.php`

---

## 3. Audit Keamanan & Perbaikan Bug (Security & Bug Fixes)

| Kerentanan / Area Audit | Resiko Semula | Solusi & Mitigasi yang Diterapkan | Status |
| :--- | :--- | :--- | :---: |
| **Pemisahan Multi-Tenant Asisten AI** | Percobaan prompt injection untuk membaca data peternakan lain via chat. | Tenant `farm_id` diikat kuat ke nomor WA terdaftar di database atau session auth aktif. Tool query otomatis terisolasi ke tenant aktif. | **AMAN** |
| **Autentikasi Webhook WhatsApp Asisten** | Akses unauthorized atau spoofing payload webhook Fonnte. | Validasi rahasia URL aman (`hash_equals`), pencocokan nomor device gateway, serta pengabaian pesan grup secara otomatis. | **AMAN** |
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
Tests:    247 passed (1297 assertions)
Duration: 69.12s
Status:   100% PASS
```

### Rincian Modul Pengujian Kunci:
1. `Tests\Feature\AssistantTest` (12 tests):
   - `paket standar dan pro tidak bisa mengakses asisten`: **PASS**
   - `paket entrepreneur bisa membuka halaman asisten`: **PASS**
   - `pemilik bisa bertanya via web dan mendapat jawaban akurat`: **PASS**
   - `deteksi mode otomatis untuk analisis mendalam`: **PASS**
   - `perintah gratis seperti menu dan pulsa tidak memanggil alat berat`: **PASS**
   - `pemilik bisa mendaftarkan dan menghapus nomor whatsapp`: **PASS**
   - `nomor whatsapp tidak bisa didaftarkan ganda antar peternakan`: **PASS**
   - `webhook whatsapp menolak rahasia url palsu`: **PASS**
   - `webhook whatsapp mengabaikan grup dan nomor tak dikenal`: **PASS**
   - `webhook whatsapp menerima pesan dari nomor terdaftar`: **PASS**
   - `job penjawab whatsapp mengirim balasan ke fonnte`: **PASS**
   - `asisten tetap merespon ramah jika pulsa habis`: **PASS**
2. `Tests\Feature\CustomerDebtReminderTest` (8 tests):
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
