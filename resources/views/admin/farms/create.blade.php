@extends('layouts.app')

@section('title', 'Tambah Peternakan')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Tambah Peternakan" subtitle="Buatkan akun untuk peternak yang mendaftar lewat Anda (misalnya lewat WhatsApp)." icon="bi-plus-circle-fill" :back="route('admin.farms.index')" back-label="Semua peternakan" />

<x-alerts />

<form action="{{ route('admin.farms.store') }}" method="POST" x-data="{ status: @js(old('status', 'trial')) }">
    @csrf
    <x-panel title="Peternakan" step="1">
        <x-field label="Nama peternakan" name="farm_name" required>
            <input type="text" id="farm_name" name="farm_name" value="{{ old('farm_name') }}" class="form-control" required>
        </x-field>
        <div class="row g-3">
            <div class="col-md-6">
                <x-field label="Nama pemilik" name="owner_name" required>
                    <input type="text" id="owner_name" name="owner_name" value="{{ old('owner_name') }}" class="form-control" required>
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Nomor HP / WhatsApp" name="phone" optional>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" class="form-control">
                </x-field>
            </div>
        </div>
        <x-field label="Kota / kabupaten" name="city" optional class="mb-0">
            <input type="text" id="city" name="city" value="{{ old('city') }}" class="form-control">
        </x-field>
    </x-panel>

    <x-panel title="Akun pemilik" step="2" subtitle="Berikan email & kata sandi ini ke pemilik peternakan. Pemilik bisa menambah pekerja sendiri.">
        <div class="row g-3">
            <div class="col-md-6">
                <x-field label="Email" name="email" required class="mb-0">
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control" autocomplete="off" required>
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Kata sandi awal" name="password" required hint="Minimal 8 huruf/angka." class="mb-0">
                    <input type="text" id="password" name="password" minlength="8" value="{{ old('password') }}" class="form-control" autocomplete="off" required>
                </x-field>
            </div>
        </div>
    </x-panel>

    <x-panel title="Langganan" step="3">
        <div class="choices mb-3">
            <label class="choice"><input type="radio" name="status" value="trial" x-model="status"><span>Masa coba<small>{{ \App\Models\Farm::TRIAL_DAYS }} hari gratis</small></span></label>
            <label class="choice"><input type="radio" name="status" value="active" x-model="status"><span>Langsung berlangganan<small>sudah bayar</small></span></label>
        </div>
        <div x-show="status === 'active'" x-cloak>
            <x-field label="Aktif sampai" name="active_until" optional hint="Kosongkan untuk tanpa batas waktu." class="mb-0">
                <input type="date" id="active_until" name="active_until" value="{{ old('active_until', today()->addMonth()->toDateString()) }}" class="form-control" style="max-width:240px">
            </x-field>
        </div>
    </x-panel>

    <button type="submit" class="btn btn-primary btn-xl w-100"><i class="bi bi-check2-circle"></i> Buat Peternakan</button>
</form>
@endsection
