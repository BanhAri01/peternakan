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

@if($canPay)
    <x-panel title="Bayar langganan" icon="bi-credit-card-fill" subtitle="Bayar lewat QRIS, GoPay, ShopeePay, atau transfer bank (BCA, BNI, BRI, Mandiri, Permata). Langganan aktif otomatis setelah pembayaran diterima.">
        @php $monthly = $plans[1] ?? null; @endphp
        <div class="row g-3">
            @foreach($plans as $months => $price)
                @php $saving = $monthly ? max(0, $monthly * $months - $price) : 0; @endphp
                <div class="col-sm-6">
                    <form action="{{ route('subscription.pay') }}" method="POST" class="plan-card {{ $months === 12 ? 'is-best' : '' }}">
                        @csrf
                        <input type="hidden" name="months" value="{{ $months }}">
                        @if($months === 12)<span class="plan-badge">Paling hemat</span>@endif
                        <div class="plan-months">{{ $months }} bulan</div>
                        <div class="plan-price">@rupiah($price)</div>
                        <div class="plan-note">
                            ≈ @rupiah(round($price / $months)) / bulan
                            @if($saving > 0)<br><b class="text-success">Hemat @rupiah($saving)</b>@endif
                        </div>
                        <button type="submit" class="btn {{ $months === 12 ? 'btn-primary' : 'btn-light' }} w-100 mt-2"><i class="bi bi-cart-check-fill"></i> Pilih & Bayar</button>
                    </form>
                </div>
            @endforeach
        </div>
        @error('months')<div class="field-error mt-3"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>@enderror
    </x-panel>
@endif

@if($payments->isNotEmpty())
    <x-panel title="Riwayat pembayaran" icon="bi-receipt" flush>
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Tanggal</th><th>Paket</th><th class="num">Jumlah</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach($payments as $p)
                        <tr>
                            <td class="title-cell"><b>{{ $p->created_at->translatedFormat('d M Y, H:i') }}</b><div class="text-muted small">{{ $p->reference }}</div></td>
                            <td data-label="Paket">{{ $p->months }} bulan @if($p->period_until)<div class="text-muted small">aktif s/d {{ Format::date($p->period_until) }}</div>@endif</td>
                            <td data-label="Jumlah" class="num">@rupiah($p->amount)</td>
                            <td data-label="Status"><x-tag :tone="$p->status_tone">{{ $p->status_label }}</x-tag>@if($p->method)<div class="text-muted small">{{ $p->method }}</div>@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
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
