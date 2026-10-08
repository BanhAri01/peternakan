@extends('layouts.app')

@section('title', 'Profil Peternakan')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Profil & Pengaturan Peternakan" subtitle="Nama dan alamat ini muncul di nota penjualan dan laporan PDF." icon="bi-gear-fill" />

<x-alerts />

<form action="{{ route('settings.update') }}" method="POST">
    @csrf
    @method('PUT')

    <x-panel title="Identitas peternakan" icon="bi-house-heart-fill">
        <x-field label="Nama peternakan" name="farm_name" required>
            <input type="text" id="farm_name" name="farm_name" value="{{ old('farm_name', $settings['farm_name']) }}" class="form-control" maxlength="100" required>
        </x-field>
        <div class="row g-3">
            <div class="col-md-6">
                <x-field label="Nama pemilik" name="farm_owner" optional>
                    <input type="text" id="farm_owner" name="farm_owner" value="{{ old('farm_owner', $settings['farm_owner']) }}" class="form-control">
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Nomor HP / WhatsApp" name="farm_phone" optional>
                    <input type="tel" id="farm_phone" name="farm_phone" value="{{ old('farm_phone', $settings['farm_phone']) }}" class="form-control">
                </x-field>
            </div>
        </div>
        <x-field label="Alamat" name="farm_address" optional class="mb-0">
            <textarea id="farm_address" name="farm_address" rows="2" class="form-control">{{ old('farm_address', $settings['farm_address']) }}</textarea>
        </x-field>
    </x-panel>

    <x-panel title="Angka acuan" icon="bi-sliders" subtitle="Dipakai untuk perhitungan otomatis dan peringatan di Beranda.">
        <div class="row g-3">
            <div class="col-md-6">
                <x-field label="Berat 1 karung pakan" name="sack_kg" required hint="Biasanya 50 kg.">
                    <div class="input-group">
                        <input type="number" id="sack_kg" name="sack_kg" step="0.1" min="1" value="{{ old('sack_kg', $settings['sack_kg']) }}" class="form-control" required>
                        <span class="input-group-text">kg</span>
                    </div>
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Harga telur acuan per kg" name="egg_price_per_kg" required hint="Dipakai untuk perkiraan untung jika belum ada data penjualan.">
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" id="egg_price_per_kg" name="egg_price_per_kg" step="1" min="0" value="{{ old('egg_price_per_kg', $settings['egg_price_per_kg']) }}" class="form-control" required data-rupiah>
                    </div>
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Batas produksi (HDP) aman" name="hdp_warning" required hint="Di bawah angka ini kandang ditandai merah. Umumnya 70–80%.">
                    <div class="input-group">
                        <input type="number" id="hdp_warning" name="hdp_warning" step="1" min="1" max="100" value="{{ old('hdp_warning', $settings['hdp_warning']) }}" class="form-control" required>
                        <span class="input-group-text">%</span>
                    </div>
                </x-field>
            </div>
            <div class="col-md-6">
                <x-field label="Peringatan stok pakan" name="low_feed_days" required hint="Beri peringatan jika pakan tinggal sekian hari.">
                    <div class="input-group">
                        <input type="number" id="low_feed_days" name="low_feed_days" step="1" min="1" max="60" value="{{ old('low_feed_days', $settings['low_feed_days']) }}" class="form-control" required>
                        <span class="input-group-text">hari</span>
                    </div>
                </x-field>
            </div>
        </div>
    </x-panel>

    <button type="submit" class="btn btn-primary btn-xl w-100"><i class="bi bi-check2-circle"></i> Simpan Pengaturan</button>
</form>
@endsection
