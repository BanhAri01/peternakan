@extends('layouts.auth')

@php $waText = rawurlencode('Halo admin HEFAM, saya pemilik peternakan dan lupa kata sandi. Mohon dibuatkan kata sandi baru. Email akun saya: '); @endphp

@section('content')
            <a href="{{ route('login') }}" class="fw-semibold text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke halaman masuk</a>
            <h2 class="fw-800 mb-1 mt-2" style="font-size:1.8rem">Lupa kata sandi?</h2>
            <p class="text-muted mb-4">Tenang, data Anda aman. Hubungi admin HEFAM lewat WhatsApp, nanti dibuatkan kata sandi baru.</p>

            <x-alerts :show-errors="false" />

            @if($adminWa)
                <a href="https://wa.me/{{ $adminWa }}?text={{ $waText }}" target="_blank" rel="noopener" class="btn btn-wa btn-xl w-100">
                    <i class="bi bi-whatsapp"></i> Chat Admin HEFAM
                </a>
                <p class="text-muted small mt-2 mb-0">Sebutkan nama peternakan dan email yang dipakai untuk masuk.</p>
            @else
                <div class="notice notice-info">
                    <i class="bi bi-telephone-fill"></i>
                    <div>Hubungi admin HEFAM yang membantu pendaftaran peternakan Anda untuk dibuatkan kata sandi baru.</div>
                </div>
            @endif

            <div class="help-tip mt-3">
                <i class="bi bi-person-badge-fill"></i>
                <span>Pekerja kandang tidak memakai kata sandi. Jika lupa PIN, minta pemilik mengatur PIN baru di menu <b>Pengguna</b>.</span>
            </div>
@endsection
