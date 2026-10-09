@extends('layouts.app')

@section('title', 'Langganan')
@section('content-class', 'narrow')

@php
    use App\Support\Format;
    $end  = $farm->accessEndsAt();
    $days = $farm->daysLeft();
    $msg  = 'Halo admin HEFAM, saya ' . ($farm->owner_name ?: auth()->user()->name) . ' dari ' . $farm->name . '. Saya ingin ' . ($farm->status === 'trial' ? 'berlangganan' : 'memperpanjang langganan') . ' HEFAM.';
@endphp

@section('content')
<x-page-header title="Langganan HEFAM" :subtitle="$farm->name" icon="bi-patch-check-fill" />

<x-alerts />

@unless($farm->isAccessible())
    <div class="notice notice-danger">
        <i class="bi bi-lock-fill"></i>
        <div>
            <span class="notice-title">{{ $farm->blockedMessage() }}</span>
            Data Anda tetap aman tersimpan. Setelah diperpanjang, semua menu bisa dipakai lagi seperti biasa.
        </div>
    </div>
@endunless

<x-panel>
    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
        <x-tag :tone="$farm->status_tone" style="font-size:1rem; padding:.45rem .9rem">{{ $farm->status_label }}</x-tag>
        @if($end)
            <span class="fw-bold">
                {{ $farm->status === 'trial' ? 'Masa coba' : 'Aktif' }} sampai {{ Format::date($end, 'd F Y') }}
                @if($days !== null && $days >= 0)<span class="text-muted">({{ $days }} hari lagi)</span>@endif
            </span>
        @elseif($farm->status === 'active')
            <span class="fw-bold">Aktif tanpa batas waktu</span>
        @endif
    </div>

    <div class="kv"><span class="k">Paket</span><span class="v">{{ $farm->planLabel() }}</span></div>
    <div class="kv"><span class="k">Nama peternakan</span><span class="v">{{ $farm->name }}</span></div>
    <div class="kv"><span class="k">Pemilik</span><span class="v">{{ $farm->owner_name ?: '-' }}</span></div>
    <div class="kv"><span class="k">Terdaftar sejak</span><span class="v">{{ Format::date($farm->created_at, 'd F Y') }}</span></div>

    <x-slot:footer>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="me-auto">{{ $canPay ? 'Ada pertanyaan? Hubungi admin HEFAM.' : 'Untuk berlangganan atau memperpanjang, hubungi admin HEFAM.' }}</span>
            @if($adminWa)
                <a href="https://wa.me/{{ $adminWa }}?text={{ rawurlencode($msg) }}" target="_blank" rel="noopener" class="btn btn-wa"><i class="bi bi-whatsapp"></i> Chat admin HEFAM</a>
            @endif
        </div>
    </x-slot:footer>
</x-panel>

@if($pending)
    <div class="notice notice-warning">
        <i class="bi bi-hourglass-split"></i>
        <div>
            <span class="notice-title">Ada tagihan {{ Format::rupiah($pending->amount) }} ({{ $pending->months }} bulan) yang belum dibayar.</span>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <a href="{{ $pending->redirect_url }}" class="btn btn-primary btn-sm"><i class="bi bi-credit-card-fill"></i> Lanjutkan pembayaran</a>
                <a href="{{ route('subscription.finish', ['ref' => $pending->reference]) }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-repeat"></i> Saya sudah bayar, cek lagi</a>
            </div>
        </div>
    </div>
@endif

@php
    $prices = [];
    foreach ($tiers as $key => $tier) {
        foreach ($durations as $m => $d) {
            $prices[$key][$m] = ['total' => \App\Services\Plans::price($key, $m), 'saving' => \App\Services\Plans::saving($key, $m)];
        }
    }
    $limitText = fn ($v, $unit) => $v === null ? 'Tanpa batas ' . $unit : 'Maks ' . $v . ' ' . $unit;
@endphp

<x-panel title="Paket langganan" icon="bi-stars" :subtitle="$canPay ? 'Pilih lama langganan, lalu pilih paket. Bayar lewat QRIS, GoPay, ShopeePay, atau transfer bank. Aktif otomatis setelah pembayaran diterima.' : 'Perbandingan paket HEFAM. Untuk berlangganan, hubungi admin HEFAM.'"
>
    <div x-data="{ months: 12, prices: @js($prices) }">
    <div class="choices mb-3" style="grid-template-columns: repeat(auto-fit, minmax(120px, 1fr))">
        @foreach($durations as $m => $d)
            <label class="choice"><input type="radio" value="{{ $m }}" x-model.number="months"><span>{{ $d['label'] }}@if($d['discount'] > 0)<small>hemat {{ round($d['discount'] * 100) }}%</small>@endif</span></label>
        @endforeach
    </div>

    <div class="row g-3">
        @foreach($tiers as $key => $tier)
            @php $isCurrent = $farm->planKey() === $key && $farm->status === 'active'; @endphp
            <div class="col-lg-4">
                <div class="plan-card {{ $key === 'pro' ? 'is-best' : '' }} {{ $isCurrent ? 'is-current' : '' }}">
                    @if($isCurrent)<span class="plan-badge">Paket Anda</span>@elseif($key === 'pro')<span class="plan-badge">Paling laris</span>@endif
                    <div class="plan-months">{{ $tier['label'] }}</div>
                    <div class="plan-note mb-2">{{ $tier['tagline'] }}</div>
                    <div class="plan-price" x-text="rupiah(prices['{{ $key }}'][months].total)">@rupiah($tier['price'])</div>
                    <div class="plan-note">
                        <span x-text="months === 1 ? 'per bulan' : '≈ ' + rupiah(Math.round(prices['{{ $key }}'][months].total / months)) + ' / bulan'"></span>
                        <b class="text-success d-block" x-show="prices['{{ $key }}'][months].saving > 0" x-text="'Hemat ' + rupiah(prices['{{ $key }}'][months].saving)"></b>
                    </div>

                    <ul class="plan-list">
                        <li><i class="bi bi-house-heart-fill"></i> {{ $limitText($tier['limits']['coops'], 'kandang aktif') }}</li>
                        <li><i class="bi bi-people-fill"></i> {{ $limitText($tier['limits']['workers'], 'pekerja') }}</li>
                        <li><i class="bi bi-phone-fill"></i> {{ $limitText($tier['limits']['devices'], 'HP kandang') }}</li>
                        <li>
                            @if($tier['limits']['wa_monthly'] > 0)
                                <i class="bi bi-whatsapp"></i> Pengingat WA {{ $tier['limits']['wa_monthly'] }} pesan/bulan
                            @else
                                <i class="bi bi-x-circle text-muted"></i> <span class="text-muted">Tanpa pengingat WA</span>
                            @endif
                        </li>
                        <li><i class="bi bi-check-circle-fill"></i> Panen, sortir, penjualan, piutang, nota</li>
                        <li><i class="bi bi-check-circle-fill"></i> Tanpa sinyal, laporan & PDF, backup harian</li>
                        @foreach(config('hefam.features') as $feature => $label)
                            @if(in_array($feature, $tier['features'], true))
                                <li><i class="bi bi-check-circle-fill"></i> {{ $label }}</li>
                            @else
                                <li class="text-muted"><i class="bi bi-x-circle"></i> {{ $label }}</li>
                            @endif
                        @endforeach
                    </ul>

                    @if($canPay)
                        <form action="{{ route('subscription.pay') }}" method="POST" class="mt-auto">
                            @csrf
                            <input type="hidden" name="plan" value="{{ $key }}">
                            <input type="hidden" name="months" :value="months">
                            <button type="submit" class="btn {{ $key === 'pro' ? 'btn-primary' : 'btn-light' }} w-100">
                                <i class="bi bi-cart-check-fill"></i> {{ $isCurrent ? 'Perpanjang' : 'Pilih ' . $tier['label'] }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    </div>
    @error('months')<div class="field-error mt-3"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>@enderror
    @if($farm->status === 'active' && $farm->active_until)
        <div class="help-tip mt-3"><i class="bi bi-arrow-left-right"></i><span>Ganti paket? Sisa hari paket sekarang tidak hangus, tetapi dikonversi sesuai nilainya ke paket baru.</span></div>
    @endif
</x-panel>
@if($payments->isNotEmpty())
    <x-panel title="Riwayat pembayaran" icon="bi-receipt" flush>
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Tanggal</th><th>Paket</th><th class="num">Jumlah</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($payments as $p)
                        <tr>
                            <td class="title-cell"><b>{{ $p->created_at->translatedFormat('d M Y, H:i') }}</b><div class="text-muted small">{{ $p->reference }}</div></td>
                            <td data-label="Paket">{{ \App\Services\Plans::label($p->plan) }} · {{ $p->months }} bulan @if($p->period_until)<div class="text-muted small">aktif s/d {{ Format::date($p->period_until) }}</div>@endif</td>
                            <td data-label="Jumlah" class="num">@rupiah($p->amount)</td>
                            <td data-label="Status"><x-tag :tone="$p->status_tone">{{ $p->status_label }}</x-tag>@if($p->method)<div class="text-muted small">{{ $p->method }}</div>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($canPay)
            <p class="text-muted small mt-3 mb-0">Dengan membayar, Anda menyetujui <a href="{{ route('legal.terms') }}" target="_blank">Syarat & Ketentuan</a> dan <a href="{{ route('legal.refund') }}" target="_blank">Kebijakan Pengembalian Dana</a>. Pembayaran diproses aman oleh Midtrans, tidak dapat dikembalikan, dan tidak ada tagihan otomatis.</p>
        @endif
    </x-panel>
@endif

@if($farm->isAccessible())
    <a href="{{ route('owner.dashboard') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
@else
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button class="btn btn-light" data-no-lock><i class="bi bi-box-arrow-right"></i> Keluar</button>
    </form>
@endif
@endsection
