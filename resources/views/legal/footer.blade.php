@php
    $biz = $biz ?? config('hefam.business');
    $footerWa = $adminWa ?? config('hefam.admin_whatsapp');
@endphp
<footer>
    <div class="wrap cols">
        <div>
            <strong style="color:var(--ink)">{{ $biz['name'] }}</strong><br>
            Aplikasi pencatatan peternakan ayam petelur berbasis langganan.<br>
            @if($biz['address']){{ $biz['address'] }}<br>@endif
            @if($footerWa)WhatsApp: <a href="https://wa.me/{{ $footerWa }}" target="_blank" rel="noopener">0{{ substr($footerWa, 2) }}</a><br>@endif
            @if($biz['email'])Email: <a href="mailto:{{ $biz['email'] }}">{{ $biz['email'] }}</a><br>@endif
            Layanan: {{ $biz['hours'] }}
        </div>
        <div class="links">
            <a href="{{ route('legal.about') }}">Tentang Kami & Kontak</a>
            <a href="{{ route('legal.terms') }}">Syarat & Ketentuan</a>
            <a href="{{ route('legal.privacy') }}">Kebijakan Privasi</a>
            <a href="{{ route('legal.refund') }}">Kebijakan Pengembalian Dana</a>
            <a href="{{ url('/') }}#harga">Harga</a>
            <div style="margin-top:8px">Pembayaran diproses oleh Midtrans (QRIS, e-wallet, virtual account bank).</div>
            <div>© {{ date('Y') }} {{ $biz['brand'] }} · Powered by HERMES</div>
        </div>
    </div>
</footer>
