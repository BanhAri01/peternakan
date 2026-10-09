@extends('legal.layout')

@section('content')
    <p>
        <strong>{{ $biz['brand'] }}</strong> adalah aplikasi web (perangkat lunak berlangganan / SaaS) untuk mencatat usaha
        peternakan ayam petelur: hasil panen telur, sortir, penjualan, piutang pembeli, stok dan pemakaian pakan, obat,
        absensi dan gaji pekerja, serta laporan laba rugi. Aplikasi dibuka lewat browser di HP atau laptop, tanpa perlu
        memasang aplikasi dari Play Store.
    </p>
    <p>
        {{ $biz['brand'] }} dibuat oleh tim Hermes Works di Bali, berawal dari kebutuhan peternakan ayam petelur keluarga kami
        sendiri, lalu dikembangkan untuk peternak lain di seluruh Indonesia.
    </p>

    <h2>Yang kami jual</h2>
    <p>
        Kami menjual <strong>langganan akses aplikasi {{ $biz['brand'] }}</strong>, bukan barang fisik. Setelah pembayaran diterima,
        akun langsung aktif secara otomatis sesuai paket dan lama langganan yang dipilih. Pendaftaran baru mendapat masa coba
        gratis {{ $trialDays }} hari tanpa perlu membayar.
    </p>
    <table>
        <thead><tr><th>Paket</th><th>Harga per bulan</th><th>Cocok untuk</th></tr></thead>
        <tbody>
            @foreach($tiers as $key => $tier)
                <tr>
                    <td><strong>{{ $tier['label'] }}</strong></td>
                    <td>{{ \App\Support\Format::rupiah($tier['price']) }}</td>
                    <td>{{ $tier['tagline'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p>Langganan 3 bulan hemat 5%, 6 bulan hemat 10%, dan 12 bulan cukup membayar 10 bulan. Semua harga dalam Rupiah (IDR) dan sudah final, tanpa biaya tersembunyi. Rincian fitur tiap paket ada di <a href="{{ url('/') }}#harga">halaman harga</a>.</p>

    <h2>Cara pembayaran</h2>
    <p>Pembayaran dilakukan dari menu <strong>Langganan</strong> di dalam aplikasi dan diproses oleh <strong>Midtrans</strong> (payment gateway resmi berizin Bank Indonesia): QRIS, GoPay, ShopeePay, dan transfer virtual account bank (BCA, BNI, BRI, Mandiri, Permata). Kami tidak menyimpan data kartu atau rekening Anda.</p>

    <h2>Kontak</h2>
    <div class="card">
        <dl>
            <dt>Nama usaha</dt>
            <dd>{{ $biz['name'] }}</dd>
            @if($biz['owner'])
                <dt>Penanggung jawab</dt>
                <dd>{{ $biz['owner'] }}</dd>
            @endif
            @if($biz['address'])
                <dt>Alamat</dt>
                <dd>{{ $biz['address'] }}</dd>
            @endif
            @if($adminWa)
                <dt>WhatsApp</dt>
                <dd><a href="https://wa.me/{{ $adminWa }}" target="_blank" rel="noopener">0{{ substr($adminWa, 2) }}</a></dd>
            @endif
            @if($biz['email'])
                <dt>Email</dt>
                <dd><a href="mailto:{{ $biz['email'] }}">{{ $biz['email'] }}</a></dd>
            @endif
            <dt>Jam layanan</dt>
            <dd>{{ $biz['hours'] }}</dd>
        </dl>
    </div>
@endsection
