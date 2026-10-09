@extends('legal.layout')

@section('content')
    <p>Kebijakan ini menjelaskan data apa yang dikumpulkan {{ $biz['brand'] }}, untuk apa, dan bagaimana kami menjaganya.</p>

    <h2>1. Data yang kami kumpulkan</h2>
    <ul>
        <li><strong>Data akun:</strong> nama, no HP, email (jika diisi), dan nama peternakan.</li>
        <li><strong>Data pekerja:</strong> nama pekerja dan PIN yang tersimpan dalam bentuk terenkripsi (hash).</li>
        <li><strong>Data usaha:</strong> catatan panen, sortir, penjualan, pembeli, pakan, obat, absensi, gaji, dan pengeluaran yang Anda masukkan.</li>
        <li><strong>Data pembayaran:</strong> status, nominal, dan metode pembayaran langganan. Data kartu atau rekening <strong>tidak</strong> kami simpan karena diproses langsung oleh Midtrans.</li>
        <li><strong>Data teknis:</strong> alamat IP, jenis browser, dan catatan aktivitas (siapa mengubah apa dan kapan) untuk keamanan.</li>
    </ul>

    <h2>2. Kegunaan data</h2>
    <ul>
        <li>Menjalankan fitur aplikasi dan menampilkan laporan untuk peternakan Anda.</li>
        <li>Memproses dan mencatat pembayaran langganan.</li>
        <li>Mengirim pengingat atau informasi penting lewat WhatsApp sesuai pengaturan Anda.</li>
        <li>Membantu Anda saat menghubungi layanan pelanggan.</li>
        <li>Menjaga keamanan dan mencegah penyalahgunaan.</li>
    </ul>

    <h2>3. Berbagi data</h2>
    <p>Kami <strong>tidak menjual</strong> data Anda. Data hanya dibagikan seperlunya kepada:</p>
    <ul>
        <li><strong>Midtrans</strong>, untuk memproses pembayaran.</li>
        <li><strong>Penyedia layanan WhatsApp</strong>, untuk mengirim pesan yang Anda aktifkan.</li>
        <li><strong>Penyedia server (hosting)</strong> di Indonesia tempat aplikasi berjalan.</li>
        <li>Pihak berwenang jika diwajibkan oleh hukum.</li>
    </ul>

    <h2>4. Keamanan</h2>
    <ul>
        <li>Koneksi dienkripsi dengan HTTPS.</li>
        <li>Kata sandi dan PIN disimpan dalam bentuk hash, tidak bisa dibaca siapa pun, termasuk kami.</li>
        <li>Data setiap peternakan dipisahkan sehingga tidak bisa dilihat peternakan lain.</li>
        <li>Database dicadangkan otomatis setiap hari.</li>
    </ul>

    <h2>5. Penyimpanan dan penghapusan</h2>
    <p>Data disimpan selama akun Anda ada. Anda dapat meminta salinan data (ekspor Excel tersedia di paket Pro dan Entrepreneur) atau meminta penghapusan akun beserta seluruh datanya melalui kontak kami. Penghapusan diproses paling lambat 14 hari kerja.</p>

    <h2>6. Hak Anda</h2>
    <p>Sesuai Undang-Undang Pelindungan Data Pribadi, Anda berhak mengakses, memperbaiki, dan meminta penghapusan data pribadi Anda.</p>

    <h2>7. Perubahan kebijakan</h2>
    <p>Jika kebijakan ini berubah, tanggal pembaruan di atas akan diganti dan perubahan penting akan kami umumkan di dalam aplikasi.</p>

    <h2>8. Kontak</h2>
    <p>Pertanyaan tentang privasi dapat disampaikan melalui halaman <a href="{{ route('legal.about') }}">Tentang Kami & Kontak</a>.</p>
@endsection
