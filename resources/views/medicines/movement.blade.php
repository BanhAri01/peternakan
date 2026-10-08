@extends('layouts.app')

@section('title', 'Catat ' . $medicine->name)
@section('content-class', 'narrow')

@php use App\Models\MedicineMovement; use App\Support\Format; @endphp

@section('content')
<x-page-header :title="$medicine->name" :subtitle="'Stok sekarang ' . Format::number($medicine->stock, 2) . ' ' . $medicine->unit . ' · harga rata-rata ' . Format::rupiah($medicine->average_cost) . ' per ' . $medicine->unit" icon="bi-capsule" :back="route('medicines.index')" back-label="Stok Obat" />

<x-alerts />

<form action="{{ route('medicines.movement.store', $medicine) }}" method="POST"
      x-data="{ dir: @js(old('direction', $direction)), qty: @js(old('quantity', '')), price: @js(old('unit_cost', '')), avg: {{ $medicine->average_cost }} }">
    @csrf

    <x-panel title="Kegiatan" step="1">
        <div class="choices">
            @foreach(MedicineMovement::DIRECTIONS as $key => $d)
                <label class="choice"><input type="radio" name="direction" value="{{ $key }}" x-model="dir" required><span><i class="bi {{ $d['icon'] }}"></i> {{ $d['label'] }}</span></label>
            @endforeach
        </div>
    </x-panel>

    <x-panel title="Jumlah" step="2">
        <div class="row g-3">
            <div class="col-md-4">
                <x-field label="Tanggal" name="movement_date" required>
                    <input type="date" id="movement_date" name="movement_date" max="{{ today()->toDateString() }}" value="{{ old('movement_date', today()->toDateString()) }}" class="form-control" required>
                </x-field>
            </div>
            <div class="col-md-4">
                <x-field :label="'Jumlah (' . $medicine->unit . ')'" name="quantity" required>
                    <input type="number" id="quantity" name="quantity" min="0.01" step="any" inputmode="decimal" x-model="qty" class="form-control num-lg" required>
                </x-field>
            </div>
            <div class="col-md-4" x-show="dir === 'masuk'">
                <x-field :label="'Harga per ' . $medicine->unit" name="unit_cost" required>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" id="unit_cost" name="unit_cost" min="0" step="1" inputmode="numeric" x-model="price" class="form-control num-lg" :required="dir === 'masuk'" :disabled="dir !== 'masuk'">
                    </div>
                </x-field>
            </div>
        </div>
        <div class="summary-bar mb-0" style="grid-template-columns:1fr">
            <div><div class="k" x-text="dir === 'masuk' ? 'Total pembelian' : 'Biaya pemakaian (harga rata-rata)'"></div>
                 <div class="v" style="font-size:1.5rem" x-text="rupiah((Number(qty) || 0) * (dir === 'masuk' ? (Number(price) || 0) : avg))"></div></div>
        </div>
    </x-panel>

    <x-panel title="Keterangan" step="3">
        <div x-show="dir === 'pakai'">
            <x-field label="Untuk kandang" name="coop_id" optional>
                <select id="coop_id" name="coop_id" class="form-select" :disabled="dir !== 'pakai'">
                    <option value="">— Semua kandang / umum —</option>
                    @foreach($coops as $coop)
                        <option value="{{ $coop->id }}" @selected((string) old('coop_id') === (string) $coop->id)>{{ $coop->name }}</option>
                    @endforeach
                </select>
            </x-field>
        </div>
        <div class="row g-3" x-show="dir === 'masuk'">
            <div class="col-md-7">
                <x-field label="Beli dari" name="supplier" optional>
                    <input type="text" id="supplier" name="supplier" value="{{ old('supplier') }}" class="form-control" placeholder="Contoh: Poultry Shop Bangli" :disabled="dir !== 'masuk'">
                </x-field>
            </div>
            <div class="col-md-5">
                <x-field label="Kedaluwarsa" name="expiry_date" optional>
                    <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date') }}" class="form-control" :disabled="dir !== 'masuk'">
                </x-field>
            </div>
        </div>
        <x-field label="Catatan" name="notes" optional class="mb-0">
            <textarea id="notes" name="notes" rows="2" maxlength="500" class="form-control">{{ old('notes') }}</textarea>
        </x-field>
    </x-panel>

    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('medicines.index') }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan</button>
    </div>
</form>
@endsection
