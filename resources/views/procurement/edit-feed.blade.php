@extends('layouts.app')

@section('title', 'Ubah Pakan Datang')
@section('content-class', 'narrow')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Ubah Pakan Datang" :subtitle="Format::date($feedPurchase->purchase_date) . ' · ' . ($feedPurchase->feedStock->feed_name ?? '-')" icon="bi-pencil-square" :back="route('procurement.index')" back-label="Belanja Pakan" />

<x-alerts />

<datalist id="supplier_list">
    @foreach($suppliers as $s)
        <option value="{{ $s->name }}">
    @endforeach
</datalist>

<form action="{{ route('procurement.feed-purchase.update', $feedPurchase) }}" method="POST"
      x-data="{ sacks: @js(old('sacks_count', $sacks ?: '')), extra: @js(old('extra_kg', $extraKg ?: '')), price: @js(old('cost_per_kg', (float) $feedPurchase->cost_per_kg)), sackKg: {{ (float) $sackKg }},
                kg() { return (Number(this.sacks) || 0) * this.sackKg + (Number(this.extra) || 0) },
                total() { return this.kg() * (Number(this.price) || 0) } }">
    @csrf
    @method('PUT')
    <x-panel title="Data pakan datang" icon="bi-box-seam-fill" tone="brand" subtitle="Stok dan harga modal rata-rata dihitung ulang otomatis setelah disimpan.">
        <x-field label="Jenis pakan" name="feed_stock_id" required>
            <select id="feed_stock_id" name="feed_stock_id" class="form-select" required>
                @foreach($feedStocks as $feed)
                    <option value="{{ $feed->id }}" @selected(old('feed_stock_id', $feedPurchase->feed_stock_id) == $feed->id)>{{ $feed->feed_name }} (sisa {{ Format::number($feed->stock_kg) }} kg)</option>
                @endforeach
            </select>
        </x-field>
        <div class="row g-3">
            <div class="col-sm-7">
                <x-field label="Dibeli dari" name="supplier_name" required>
                    <input type="text" id="supplier_name" name="supplier_name" list="supplier_list" value="{{ old('supplier_name', $feedPurchase->supplier->name ?? '') }}" class="form-control" autocomplete="off" required>
                </x-field>
            </div>
            <div class="col-sm-5">
                <x-field label="Tanggal datang" name="purchase_date" required>
                    <input type="date" id="purchase_date" name="purchase_date" value="{{ old('purchase_date', $feedPurchase->purchase_date?->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-control" required>
                </x-field>
            </div>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="mini-label" for="sacks_count">Jumlah karung ({{ Format::number($sackKg) }} kg)</label>
                <input type="number" id="sacks_count" name="sacks_count" min="0" step="1" x-model="sacks" class="form-control num-lg" placeholder="0">
            </div>
            <div class="col-6">
                <label class="mini-label" for="extra_kg">+ Tambahan (kg)</label>
                <input type="number" id="extra_kg" name="extra_kg" min="0" step="0.1" x-model="extra" class="form-control num-lg" placeholder="0">
            </div>
            @error('sacks_count')<div class="col-12"><div class="field-error"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div></div>@enderror
        </div>
        <x-field label="Harga beli per kg" name="cost_per_kg" required>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="number" id="cost_per_kg" name="cost_per_kg" min="1" step="0.01" x-model="price" class="form-control num-lg" required>
            </div>
        </x-field>
        <div class="summary-bar">
            <div><div class="k">Sebelumnya</div><div class="v">{{ Format::number($feedPurchase->quantity_kg) }} kg · @rupiah($feedPurchase->total_cost)</div></div>
            <div><div class="k">Menjadi</div><div class="v" x-text="angka(kg()) + ' kg · ' + rupiah(total())"></div></div>
        </div>
        <x-field label="Catatan" name="notes" optional class="mb-0">
            <input type="text" id="notes" name="notes" value="{{ old('notes', $feedPurchase->notes) }}" class="form-control" placeholder="Nomor nota, dll">
        </x-field>
    </x-panel>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('procurement.index') }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button>
    </div>
</form>
@endsection
