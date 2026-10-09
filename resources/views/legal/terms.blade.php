@extends('legal.layout')

@section('content')
    <p>Dengan mendaftar atau memakai {{ $biz['brand'] }}, Anda dianggap telah membaca dan menyetujui Syarat & Ketentuan berikut. Layanan ini disediakan oleh {{ $biz['name'] }} ("kami").</p>

    <h2>1. Layanan</h2>
    <p>{{ $biz['brand'] }} adalah aplikasi web berlangganan untuk pencatatan peternakan ayam petelur. Layanan diberikan secara online dan dapat diakses melalui browser. Kami dapat menambah, mengubah, atau memperbaiki fitur dari waktu ke waktu untuk meningkatkan layanan.</p>

    <h2>2. Akun</h2>
    <ul>
        <li>Anda wajib memberikan data yang benar saat mendaftar (nama, no HP, nama peternakan).</li>
        <li>Anda bertanggung jawab menjaga kerahasiaan kata sandi akun pemilik dan PIN pekerja.</li>
        <li>Satu akun pemilik dapat menambahkan pekerja sesuai batas paket yang dipilih.</li>
        <li>Segera hubungi kami jika ada penggunaan akun yang tidak Anda kenal.</li>
    </ul>

    <h2>3. Masa coba gratis</h2>
    <p>Pendaftaran baru mendapat masa coba gratis {{ $trialDays }} hari dengan fitur paket tertinggi. Tidak ada tagihan otomatis setelah masa coba berakhir; Anda bebas memilih untuk berlangganan atau tidak.</p>

    <h2>4. Paket, harga, dan pembayaran</h2>
    <ul>
        <li>Harga paket tercantum di halaman harga dan menu Langganan dalam Rupiah (IDR).</li>
        <li>Langganan dibayar di muka untuk 1, 3, 6, atau 12 bulan. <strong>Tidak ada perpanjangan atau penarikan dana otomatis</strong>; setiap perpanjangan dilakukan oleh Anda sendiri.</li>
        <li>Pembayaran diproses oleh Midtrans. Akun aktif otomatis setelah pembayaran dinyatakan berhasil.</li>
        <li>Perubahan harga tidak berlaku untuk masa langganan yang sudah dibayar.</li>
        <li>Saat berganti paket, sisa hari paket lama dikonversi sesuai nilainya ke paket baru, tidak hangus.</li>
    </ul>

    <h2>5. Data Anda</h2>
    <p>Data peternakan yang Anda catat adalah milik Anda. Kami menyimpan dan mencadangkannya setiap hari, serta memisahkan data setiap peternakan. Penjelasan lengkap ada di <a href="{{ route('legal.privacy') }}">Kebijakan Privasi</a>.</p>

    <h2>6. Larangan</h2>
    <ul>
        <li>Memakai layanan untuk kegiatan yang melanggar hukum.</li>
        <li>Mencoba membobol, mengganggu, atau mengakses data pengguna lain.</li>
        <li>Menjual kembali atau menyewakan akses akun tanpa izin tertulis dari kami.</li>
    </ul>
    <p>Akun yang melanggar dapat kami bekukan setelah pemberitahuan.</p>

    <h2>7. Ketersediaan layanan</h2>
    <p>Kami berusaha agar layanan aktif setiap saat, namun dapat terjadi gangguan atau pemeliharaan terjadwal. Jika gangguan dari pihak kami membuat layanan tidak bisa dipakai lebih dari 24 jam berturut-turut, masa langganan Anda akan kami perpanjang sesuai lama gangguan.</p>

    <h2>8. Batasan tanggung jawab</h2>
    <p>{{ $biz['brand'] }} adalah alat bantu pencatatan. Keputusan usaha tetap menjadi tanggung jawab Anda. Kami tidak bertanggung jawab atas kerugian akibat data yang salah dimasukkan, kelalaian menjaga kata sandi, atau gangguan di luar kendali kami (misalnya gangguan internet atau bencana).</p>

    <h2>9. Berhenti berlangganan</h2>
    <p>Anda dapat berhenti kapan saja dengan tidak memperpanjang langganan. Data tetap tersimpan dan dapat dipakai lagi saat berlangganan kembali. Pembayaran langganan tidak dapat dikembalikan, kecuali kesalahan pembayaran seperti diatur di <a href="{{ route('legal.refund') }}">Kebijakan Pengembalian Dana</a>.</p>

    <h2>10. Hukum yang berlaku</h2>
    <p>Syarat & Ketentuan ini tunduk pada hukum Republik Indonesia. Perselisihan diselesaikan secara musyawarah terlebih dahulu.</p>

    <h2>11. Kontak</h2>
    <p>Pertanyaan tentang Syarat & Ketentuan dapat disampaikan melalui halaman <a href="{{ route('legal.about') }}">Tentang Kami & Kontak</a>.</p>
@endsection
