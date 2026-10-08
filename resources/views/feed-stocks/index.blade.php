@extends('layouts.app')

@section('title', 'Stok Pakan')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Stok Pakan" subtitle="Sisa pakan di gudang. Stok berkurang otomatis dari laporan panen dan bertambah dari pembelian." icon="bi-box-seam-fill">
    <a href="{{ route('procurement.index') }}" class="btn btn-primary"><i class="bi bi-truck"></i> Catat Pakan Datang</a>
    <a href="{{ route('feed-stocks.create') }}" class="btn btn-light"><i class="bi bi-plus-lg"></i> Jenis Pakan Baru</a>
</x-page-header>

<x-alerts />

<div class="row g-3 mb-3">
    <div class="col-md-6"><x-stat label="Total pakan di gudang" :value="Format::number($totalStockKg)" unit="kg" icon="bi-box-seam-fill" tone="brand" :hint="'Sekitar ' . Format::number($totalStockKg / max(1, $sackKg), 1) . ' karung'" /></div>
    <div class="col-md-6"><x-stat label="Nilai pakan di gudang" :value="Format::rupiah($totalStockValue)" icon="bi-cash-stack" tone="success" hint="Sisa stok × harga modal rata-rata." /></div>
</div>

<x-panel title="Daftar jenis pakan" icon="bi-list-ul" flush>
    @if($feedStocks->isEmpty())
        <x-empty icon="bi-box-seam" title="Belum ada jenis pakan">
            <x-slot:action><a href="{{ route('feed-stocks.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah jenis pakan</a></x-slot:action>
        </x-empty>
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Jenis pakan</th><th class="num">Sisa stok</th><th class="num">Harga modal/kg</th><th class="num">Nilai stok</th><th>Cukup untuk</th><th class="actions">Aksi</th></tr></thead>
                <tbody>
                    @foreach($feedStocks as $feed)
                        @php
                            $perDay = (float) ($usage[$feed->id] ?? 0);
                            $days   = $perDay > 0 ? (int) floor(max(0, $feed->stock_kg) / $perDay) : null;
                            $tone   = $feed->stock_kg < 0 ? 'danger' : ($days === null ? 'neutral' : ($days <= $lowDays ? 'warning' : 'success'));
                        @endphp
                        <tr>
                            <td class="title-cell"><b>{{ $feed->feed_name }}</b></td>
                            <td data-label="Sisa stok" class="num {{ $feed->stock_kg < 0 ? 'text-danger' : '' }}">
                                <b>{{ Format::number($feed->stock_kg) }} kg</b>
                                <div class="text-muted small">± {{ Format::number($feed->stock_kg / max(1, $sackKg), 1) }} karung</div>
                            </td>
                            <td data-label="Harga modal/kg" class="num">@rupiah($feed->cost_per_kg)</td>
                            <td data-label="Nilai stok" class="num">@rupiah(max(0, $feed->stock_kg) * $feed->cost_per_kg)</td>
                            <td data-label="Cukup untuk">
                                <x-tag :tone="$tone">{{ $feed->stock_kg < 0 ? 'Stok minus' : ($days === null ? 'Belum dipakai' : '± ' . $days . ' hari') }}</x-tag>
                                @if($perDay > 0)<div class="text-muted small mt-1">pakai ± {{ Format::number($perDay) }} kg/hari</div>@endif
                            </td>
                            <td class="actions">
                                <a href="{{ route('feed-stocks.edit', $feed) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Ubah</a>
                                <x-delete-button :action="route('feed-stocks.destroy', $feed)" icon-only title="Hapus jenis pakan ini?"
                                    :message="'Pakan ' . $feed->feed_name . ' akan dihapus. Pakan yang sudah punya riwayat tidak bisa dihapus.'" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-panel>

<div class="help-tip">
    <i class="bi bi-lightbulb-fill"></i>
    <span>Pakan datang? Catat lewat <a href="{{ route('procurement.index') }}">Belanja Pakan & Telur</a> agar stok dan harga modal rata-rata terhitung otomatis. Gunakan tombol <b>Ubah</b> hanya untuk membetulkan stok setelah dihitung ulang di gudang.</span>
</div>
@endsection
