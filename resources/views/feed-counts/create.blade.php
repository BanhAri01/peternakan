@extends('layouts.app')

@section('title', 'Hitung Stok Gudang')
@section('content-class', 'narrow')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Hitung Stok Gudang" :subtitle="'Hitung karung pakan yang benar-benar ada di gudang sekarang. ' . Format::dayDate(today()) . '.'" icon="bi-calculator-fill">
    @if($isOwner)
        <a href="{{ route('feed-counts.index') }}" class="btn btn-light"><i class="bi bi-calendar-check"></i> Cek Stok per Tanggal</a>
    @endif
</x-page-header>

<x-alerts />

@if($feedStocks->isEmpty())
    <x-panel><x-empty icon="bi-box-seam" title="Belum ada jenis pakan" /></x-panel>
@else
<form action="{{ route('feed-counts.store') }}" method="POST">
    @csrf
    @foreach($feedStocks as $i => $feed)
        @php
            $existing = $counts[$feed->id] ?? null;
            $sacks    = $existing ? (int) floor((float) $existing->counted_kg / $sackKg) : null;
            $extra    = $existing ? round((float) $existing->counted_kg - $sacks * $sackKg, 2) : null;
        @endphp
        <x-panel :title="$feed->feed_name" icon="bi-box-seam-fill" tone="brand">
            <input type="hidden" name="counts[{{ $i }}][feed_stock_id]" value="{{ $feed->id }}">
            @if($isOwner)
                <div class="kv mb-2"><span class="k">Menurut aplikasi</span><span class="v">{{ Format::number($existing ? $existing->expected_kg : $feed->stock_kg) }} kg ({{ Format::number(($existing ? $existing->expected_kg : $feed->stock_kg) / $sackKg, 1) }} karung)</span></div>
            @endif
            @if($existing)
                <div class="help-tip mb-2"><i class="bi bi-check-circle-fill"></i><span>Sudah dihitung hari ini: {{ Format::number($existing->counted_kg) }} kg. Isi lagi hanya jika ingin menghitung ulang.</span></div>
            @endif
            <div class="row g-2">
                <div class="col-6">
                    <label class="mini-label" for="c{{ $i }}s">Jumlah karung</label>
                    <input id="c{{ $i }}s" type="number" inputmode="decimal" min="0" step="0.5" name="counts[{{ $i }}][sacks]" value="{{ old("counts.$i.sacks") }}" placeholder="{{ $sacks ?? '0' }}" class="form-control num-xl">
                </div>
                <div class="col-6">
                    <label class="mini-label" for="c{{ $i }}k">+ Sisa karung terbuka (kg)</label>
                    <input id="c{{ $i }}k" type="number" inputmode="decimal" min="0" step="0.1" name="counts[{{ $i }}][extra_kg]" value="{{ old("counts.$i.extra_kg") }}" placeholder="{{ $extra ?? '0' }}" class="form-control num-xl">
                </div>
            </div>
        </x-panel>
    @endforeach
    <div class="help-tip mb-3">
        <i class="bi bi-info-circle-fill"></i>
        <span>Kosongkan pakan yang tidak dihitung. Isi <b>0</b> jika pakan itu memang sudah habis. Stok di aplikasi akan disamakan dengan hasil hitung ini{{ $isOwner ? ', dan selisihnya tercatat di Cek Stok per Tanggal' : '' }}.</span>
    </div>
    <div class="sticky-actions">
        <button type="submit" class="btn btn-primary btn-xl w-100"><i class="bi bi-check2-circle"></i> Simpan Hasil Hitung</button>
    </div>
</form>
@endif
@endsection
