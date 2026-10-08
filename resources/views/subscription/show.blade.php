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
            <span class="me-auto">Untuk berlangganan atau memperpanjang, hubungi admin HEFAM.</span>
            @if($adminWa)
                <a href="https://wa.me/{{ $adminWa }}?text={{ rawurlencode($msg) }}" target="_blank" rel="noopener" class="btn btn-wa"><i class="bi bi-whatsapp"></i> Chat admin HEFAM</a>
            @endif
        </div>
    </x-slot:footer>
</x-panel>

@if($farm->isAccessible())
    <a href="{{ route('owner.dashboard') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
@else
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button class="btn btn-light" data-no-lock><i class="bi bi-box-arrow-right"></i> Keluar</button>
    </form>
@endif
@endsection
