@extends('legal.layout')

@section('content')
    <div class="note">
        Karena tersedia <strong>masa coba gratis {{ $trialDays }} hari</strong> dengan semua fitur, kami menyarankan Anda mencoba {{ $biz['brand'] }} terlebih dahulu sebelum membayar langganan.
    </div>

    <h2>1. Produk yang dibeli</h2>
    <p>Yang Anda bayar adalah <strong>langganan akses aplikasi (layanan digital)</strong> yang langsung aktif setelah pembayaran berhasil. Tidak ada pengiriman barang fisik.</p>

    <h2>2. Langganan tidak dapat dikembalikan</h2>
    <p>Semua pembayaran langganan {{ $biz['brand'] }} (paket dan lama langganan apa pun) bersifat <strong>final dan tidak dapat dikembalikan (non-refundable)</strong>, baik sebagian maupun seluruhnya, termasuk bila berhenti di tengah masa langganan. Akun tetap aktif sampai masa langganan berakhir.</p>
    <p>Pengembalian dana hanya dilakukan untuk <strong>kesalahan pembayaran</strong> berikut:</p>
    <table>
        <thead><tr><th>Keadaan</th><th>Penyelesaian</th></tr></thead>
        <tbody>
            <tr><td>Pembayaran terpotong dua kali / dobel untuk tagihan yang sama</td><td>Kelebihan bayar dikembalikan penuh</td></tr>
            <tr><td>Sudah membayar tetapi langganan tidak aktif dan tidak dapat kami aktifkan dalam 2 x 24 jam</td><td>Dikembalikan penuh</td></tr>
            <tr><td>Salah memilih paket atau lama langganan, dilaporkan paling lambat 3 hari setelah membayar</td><td>Ditukar ke paket senilai sisa pembayaran (tidak dalam bentuk uang)</td></tr>
            <tr><td>Layanan tidak dapat dipakai lebih dari 7 hari berturut-turut karena gangguan dari pihak kami</td><td>Masa langganan diperpanjang sesuai lama gangguan</td></tr>
        </tbody>
    </table>

    <h2>3. Cara mengajukan</h2>
    <ol>
        <li>Hubungi kami lewat WhatsApp @if($adminWa)<a href="https://wa.me/{{ $adminWa }}" target="_blank" rel="noopener">0{{ substr($adminWa, 2) }}</a>@endif @if($biz['email'])atau email <a href="mailto:{{ $biz['email'] }}">{{ $biz['email'] }}</a>@endif paling lambat <strong>7 hari</strong> setelah pembayaran.</li>
        <li>Sertakan nama peternakan, no HP akun, kode pembayaran (terlihat di menu Langganan, contoh HEFAM-12-...), dan alasan pengajuan.</li>
        <li>Kami memeriksa dan memberi jawaban paling lambat <strong>3 hari kerja</strong>.</li>
    </ol>

    <h2>4. Proses pengembalian dana</h2>
    <ul>
        <li>Pembayaran lewat <strong>QRIS, GoPay, atau ShopeePay</strong> dikembalikan ke sumber dana yang sama melalui Midtrans.</li>
        <li>Pembayaran lewat <strong>virtual account bank</strong> dikembalikan dengan transfer ke rekening atas nama pemohon.</li>
        <li>Dana diterima paling lambat <strong>14 hari kerja</strong> setelah pengajuan disetujui, tergantung bank atau penyedia e-wallet.</li>
        <li>Setelah dana dikembalikan, masa langganan yang terkait dibatalkan.</li>
    </ul>

    <h2>5. Pembatalan</h2>
    <p>Tidak ada tagihan otomatis. Untuk berhenti berlangganan, cukup tidak memperpanjang. Data Anda tetap tersimpan dan dapat dipakai lagi saat berlangganan kembali.</p>
@endsection
