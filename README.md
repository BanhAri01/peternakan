# HEFAM — Sistem Manajemen Peternakan Ayam Petelur

Aplikasi web untuk mengelola peternakan ayam petelur: catat panen harian per kandang, stok pakan, penjualan telur dan piutang, vaksinasi, buku kas, sampai laporan untung-rugi bulanan.

Dirancang agar **mudah dipakai orang tua dan pekerja kandang**: huruf besar (bisa diperbesar lagi dengan tombol A / A / A), tombol besar, bahasa sehari-hari, dan hitungan otomatis yang langsung terlihat sebelum disimpan.

## Fitur

| Menu | Isi |
| --- | --- |
| **Beranda** | Telur hari ini, produksi (HDP), jumlah ayam, pakan, modal per kg, penjualan, piutang, daftar *Perlu perhatian* (kandang belum dicatat, produksi turun, pakan menipis, tagihan lewat tempo), grafik 14 hari, keadaan tiap kandang |
| **Catat Panen** | Langkah 1–4: pilih kandang → telur per jenis (rak + butir + kg) → pakan (karung + kg) → ayam mati/afkir. Ringkasan & HDP otomatis. Mencegah input ganda per kandang per tanggal |
| **Riwayat Panen** | Saring per tanggal/kandang, ubah atau hapus (jumlah ayam & stok pakan dikembalikan otomatis), tercatat siapa yang menginput |
| **Penjualan Telur** | Jual per kg / rak / butir, pilihan *Lunas / Sebagian / Belum bayar*, harga terakhir terisi otomatis, cetak nota PDF |
| **Pelanggan & Piutang** | Total utang tiap pelanggan, bayar cicilan, kirim tagihan lewat WhatsApp |
| **Kandang** | Kartu kandang, umur ayam otomatis, grafik produksi vs batas aman, riwayat vaksin |
| **Vaksinasi** | Satu catatan untuk beberapa kandang sekaligus, umur ayam dihitung otomatis |
| **Stok Pakan & Belanja** | Pakan datang menambah stok & menghitung harga modal rata-rata; perkiraan pakan cukup berapa hari; kulakan telur dari luar |
| **Buku Kas** | Pengeluaran selain pakan (gaji, listrik, obat, tray, dll) + grafik uang masuk vs biaya |
| **Laporan Bulanan** | Untung-rugi, arus kas, hasil tiap kandang, unduh PDF |
| **Pengguna** | Pemilik (email + kata sandi), pekerja (pilih nama + PIN 4–6 angka) |
| **Profil Peternakan** | Nama, alamat, HP (tampil di nota), berat karung, harga acuan, batas HDP, batas peringatan pakan |

Pekerja hanya bisa membuka halaman Catat Panen (hari ini atau kemarin). Login dibatasi 5 kali percobaan per menit.

## SaaS: banyak peternakan dalam satu aplikasi

- **Daftar sendiri** di `/daftar` → langsung masa coba gratis 14 hari, lengkap dengan jenis telur bawaan dan panduan *Langkah awal* di Beranda.
- **Data terpisah per peternakan**: semua model data memakai trait `App\Tenancy\BelongsToFarm` (scope otomatis, *fail-closed*). Validasi `exists/unique` memakai `App\Tenancy\FarmRule`.
- **Login pekerja tanpa email/kata sandi**: pemilik masuk sekali di HP kandang → menu *HP Kandang* → *Jadikan HP kandang* → *Serahkan ke pekerja*. Di HP itu pekerja cukup tekan nama + PIN. Di HP lain daftar nama tidak muncul.
- **Langganan**: status `trial` / `active` / `suspended`. Jika habis, pemilik diarahkan ke halaman *Langganan*, pekerja tidak bisa masuk. Data tetap aman.
- **Panel admin HEFAM** (`/admin/peternakan`): lihat semua peternakan, perpanjang langganan +1/3/6/12 bulan, bekukan, atau buatkan akun peternakan baru. Buat akun admin dengan `php artisan hefam:admin email@anda.com`.
- Nomor WhatsApp admin untuk tombol *Chat admin HEFAM*: isi `HEFAM_ADMIN_WA=628xxxxxxxxxx` di `.env`.

## Deploy (cPanel)

1. `git push cpanel main`
2. Di server (SSH, folder `public_html`):
   ```bash
   composer install --no-dev --optimize-autoloader   # ada paket baru: bacon/bacon-qr-code
   php artisan migrate --force                        # data lama otomatis jadi peternakan pertama
   php artisan hefam:admin email-admin@anda.com       # sekali saja, untuk panel admin
   php artisan config:clear && php artisan view:clear && php artisan cache:clear
   ```
3. Tambahkan `HEFAM_ADMIN_WA` dan (opsional) `APP_TIMEZONE=Asia/Makassar` di `.env` server.

## Teknologi

Laravel 12 · PHP 8.2+ · MySQL · Blade + Bootstrap 5 + Alpine.js (lewat CDN, tanpa proses build) · Chart.js · DomPDF

## Menjalankan di komputer sendiri

```bash
composer install
cp .env.example .env && php artisan key:generate
# atur DB_* di .env, lalu:
php artisan migrate
php artisan db:seed            # opsional: data contoh 60 hari (menolak jalan di production)
php artisan serve
```

Akun data contoh (kata sandi semua `password`):
- Admin HEFAM: `admin@hefam.id`
- Pemilik Sinar Abadi Farm (data 60 hari): `demo@hefam.id`, pekerja Wayan / Made / Komang dengan PIN `1234` (perlu HP kandang)
- Pemilik Telur Jaya Farm (masa coba, masih kosong): `jaya@hefam.id`

## Tes

```bash
php artisan test
# di XAMPP Windows yang belum mengaktifkan SQLite:
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit
```

## Catatan teknis

- Zona waktu bawaan `Asia/Makassar` (WITA). Ubah lewat `APP_TIMEZONE` di `.env`.
- Semua perhitungan keuangan ada di `app/Services/FarmFinance.php`, perhitungan panen di `app/Services/DailyLogCalculator.php`.
- Tampilan memakai komponen Blade di `resources/views/components` dan gaya di `resources/views/partials/styles.blade.php`.

© HEFAM · Powered by HERMES
