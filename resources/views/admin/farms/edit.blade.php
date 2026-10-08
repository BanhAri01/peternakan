@extends('layouts.app')

@section('title', $farm->name)

@php use App\Support\Format; @endphp

@section('content')
<x-page-header :title="$farm->name" :subtitle="'Terdaftar ' . Format::date($farm->created_at, 'd F Y') . ($farm->city ? ' · ' . $farm->city : '')" icon="bi-building-fill" :back="route('admin.farms.index')" back-label="Semua peternakan">
    @if($farm->phone)
        <a href="https://wa.me/{{ Format::waNumber($farm->phone) }}" target="_blank" rel="noopener" class="btn btn-wa"><i class="bi bi-whatsapp"></i> Chat pemilik</a>
    @endif
</x-page-header>

<x-alerts />

@if(session('new_password'))
    <div class="notice notice-warning" role="alert">
        <i class="bi bi-key-fill"></i>
        <div>
            <span class="notice-title">Kata sandi baru untuk {{ session('new_password')['email'] }}</span>
            <code class="fs-5 user-select-all">{{ session('new_password')['password'] }}</code>
            <div class="small mt-1">Hanya ditampilkan sekali. Kirim ke pemilik lewat WhatsApp, lalu minta ia menggantinya di menu Pengguna.</div>
        </div>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><x-stat label="Status" :value="$farm->status_label" icon="bi-patch-check-fill" :tone="$farm->status_tone" :hint="$farm->accessEndsAt() ? 'sampai ' . Format::date($farm->accessEndsAt()) : 'tanpa batas'" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat label="Pengguna" :value="$users->count()" unit="akun" icon="bi-people-fill" tone="info" :hint="$users->where('role', 'worker')->count() . ' pekerja'" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat label="Laporan panen" :value="Format::number($activity->logs ?? 0)" icon="bi-journal-text" tone="egg" :hint="'terakhir ' . ($activity->last_log ? Format::date($activity->last_log) : '-')" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat label="HP kandang" :value="$devices->where('is_active', true)->count()" icon="bi-phone-fill" tone="brand" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <form action="{{ route('admin.farms.update', $farm) }}" method="POST">
            @csrf
            @method('PUT')
            <x-panel title="Data & status langganan" icon="bi-sliders">
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-field label="Nama peternakan" name="name" required>
                            <input type="text" id="name" name="name" value="{{ old('name', $farm->name) }}" class="form-control" required>
                        </x-field>
                    </div>
                    <div class="col-md-6">
                        <x-field label="Nama pemilik" name="owner_name" optional>
                            <input type="text" id="owner_name" name="owner_name" value="{{ old('owner_name', $farm->owner_name) }}" class="form-control">
                        </x-field>
                    </div>
                    <div class="col-md-6">
                        <x-field label="Nomor HP" name="phone" optional>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone', $farm->phone) }}" class="form-control">
                        </x-field>
                    </div>
                    <div class="col-md-6">
                        <x-field label="Kota" name="city" optional>
                            <input type="text" id="city" name="city" value="{{ old('city', $farm->city) }}" class="form-control">
                        </x-field>
                    </div>
                </div>
                <div class="field">
                    <span class="field-label">Status</span>
                    <div class="choices">
                        @foreach(\App\Models\Farm::STATUSES as $val => $lbl)
                            <label class="choice {{ $val === 'suspended' ? 'danger' : '' }}"><input type="radio" name="status" value="{{ $val }}" @checked(old('status', $farm->status) === $val)><span>{{ $lbl }}</span></label>
                        @endforeach
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-field label="Masa coba sampai" name="trial_ends_at" optional>
                            <input type="date" id="trial_ends_at" name="trial_ends_at" value="{{ old('trial_ends_at', optional($farm->trial_ends_at)->toDateString()) }}" class="form-control">
                        </x-field>
                    </div>
                    <div class="col-md-6">
                        <x-field label="Langganan sampai" name="active_until" optional hint="Kosong = tanpa batas.">
                            <input type="date" id="active_until" name="active_until" value="{{ old('active_until', optional($farm->active_until)->toDateString()) }}" class="form-control">
                        </x-field>
                    </div>
                </div>
                <x-field label="Catatan admin" name="admin_notes" optional hint="Hanya terlihat oleh admin HEFAM." class="mb-0">
                    <textarea id="admin_notes" name="admin_notes" rows="3" class="form-control" placeholder="Contoh: bayar via transfer BRI 10 Okt, paket 3 bulan">{{ old('admin_notes', $farm->admin_notes) }}</textarea>
                </x-field>
                <x-slot:footer>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Simpan</button>
                </x-slot:footer>
            </x-panel>
        </form>
    </div>

    <div class="col-lg-5">
        <x-panel title="Perpanjang langganan" icon="bi-calendar-plus-fill" tone="egg" subtitle="Status otomatis menjadi Berlangganan.">
            <div class="d-grid gap-2" style="grid-template-columns: 1fr 1fr">
                @foreach([1, 3, 6, 12] as $m)
                    <form action="{{ route('admin.farms.extend', $farm) }}" method="POST" data-confirm="Langganan {{ $farm->name }} diperpanjang {{ $m }} bulan." data-confirm-title="Perpanjang {{ $m }} bulan?" data-confirm-button="Ya, perpanjang">
                        @csrf
                        <input type="hidden" name="months" value="{{ $m }}">
                        <button class="btn btn-light w-100">+ {{ $m }} bulan</button>
                    </form>
                @endforeach
            </div>
        </x-panel>

        <x-panel title="Pembayaran online" icon="bi-credit-card-fill" flush>
            @if($payments->isEmpty())
                <x-empty icon="bi-receipt" title="Belum ada pembayaran online" class="py-3" />
            @else
                <ul class="alert-list">
                    @foreach($payments as $p)
                        <li>
                            <span class="dot {{ $p->status === 'paid' ? 'success' : ($p->status === 'pending' ? 'warning' : 'danger') }}"><i class="bi bi-credit-card"></i></span>
                            <div class="txt"><b>@rupiah($p->amount) · {{ $p->months }} bulan</b><span>{{ $p->status_label }} · {{ $p->created_at->translatedFormat('d M Y H:i') }}{{ $p->method ? ' · ' . $p->method : '' }}</span></div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>

        <x-panel title="Pengguna" icon="bi-people-fill" flush>
            <ul class="alert-list">
                @foreach($users as $u)
                    <li>
                        <span class="dot {{ $u->isOwner() ? 'success' : 'info' }}"><i class="bi {{ $u->isOwner() ? 'bi-shield-lock-fill' : 'bi-person-badge-fill' }}"></i></span>
                        <div class="txt"><b>{{ $u->name }}</b><span>{{ $u->isOwner() ? 'Pemilik · ' . $u->email : 'Pekerja · ' . ($u->hasPin() ? 'PIN sudah diatur' : 'belum ada PIN') }}</span></div>
                    </li>
                @endforeach
            </ul>
            @if($farm->owner)
                <x-slot:footer>
                    <form action="{{ route('admin.farms.reset-password', $farm) }}" method="POST" data-confirm="Kata sandi lama {{ $farm->owner->name }} tidak bisa dipakai lagi." data-confirm-title="Buat kata sandi baru?" data-confirm-button="Ya, buat baru">
                        @csrf
                        <button type="submit" class="btn btn-light btn-sm"><i class="bi bi-key-fill"></i> Buat kata sandi baru untuk pemilik</button>
                    </form>
                </x-slot:footer>
            @endif
        </x-panel>
    </div>
</div>
@endsection
