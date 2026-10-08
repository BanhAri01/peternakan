@extends('layouts.app')

@section('title', 'Riwayat Panen')

@php use App\Support\Format; $hdpWarn = \App\Models\Setting::num('hdp_warning'); @endphp

@section('content')
<x-page-header title="Riwayat Panen" subtitle="Semua laporan harian dari setiap kandang. Tekan Ubah untuk memperbaiki angka yang salah." icon="bi-journal-text">
    <a href="{{ route('daily-logs.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle-fill"></i> Catat Panen</a>
</x-page-header>

<x-alerts />

<x-panel>
    <form method="GET" class="filter-bar">
        <x-field label="Dari tanggal" for="start">
            <input type="date" id="start" name="start" value="{{ $start->toDateString() }}" class="form-control">
        </x-field>
        <x-field label="Sampai tanggal" for="end">
            <input type="date" id="end" name="end" value="{{ $end->toDateString() }}" class="form-control">
        </x-field>
        <x-field label="Kandang" for="coop_id">
            <select id="coop_id" name="coop_id" class="form-select">
                <option value="">Semua kandang</option>
                @foreach($coops as $c)
                    <option value="{{ $c->id }}" @selected($coopId == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </x-field>
        <div class="btns">
            <button type="submit" class="btn btn-primary" data-no-lock><i class="bi bi-search"></i> Tampilkan</button>
            <a href="{{ route('daily-logs.index') }}" class="btn btn-light" title="Atur ulang"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-panel>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-lg-3">
        <x-stat label="Total telur" :value="Format::number($totals->eggs ?? 0)" unit="butir" icon="bi-egg-fill" tone="egg" :hint="Format::trays((int) ($totals->eggs ?? 0))" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-stat label="Berat telur" :value="Format::number($totals->kg ?? 0, 1)" unit="kg" icon="bi-speedometer2" tone="egg" :hint="($totals->n ?? 0) . ' laporan'" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-stat label="Rata-rata produksi" :value="Format::number($totals->hdp ?? 0, 1) . '%'" icon="bi-graph-up" tone="success" hint="HDP: telur ÷ jumlah ayam" />
    </div>
    <div class="col-sm-6 col-lg-3">
        <x-stat label="Ayam mati/afkir" :value="Format::number($totals->loss ?? 0)" unit="ekor" icon="bi-heartbreak-fill" tone="danger" :hint="'Pakan: ' . Format::number($totals->feed ?? 0) . ' kg'" />
    </div>
</div>

<x-panel :title="'Laporan ' . Format::date($start) . ' – ' . Format::date($end)" icon="bi-list-ul" flush>
    @if($logs->isEmpty())
        <x-empty icon="bi-journal-x" title="Tidak ada laporan pada periode ini">
            Coba ubah tanggal atau pilih semua kandang.
        </x-empty>
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kandang</th>
                        <th class="num">Telur</th>
                        <th class="num">Berat</th>
                        <th class="num">HDP</th>
                        <th class="num">Pakan</th>
                        <th class="num">FCR</th>
                        <th class="num">Mati/Afkir</th>
                        <th>Dicatat oleh</th>
                        <th class="actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td class="title-cell"><b>{{ Format::date($log->log_date, 'D, d M Y') }}</b></td>
                            <td data-label="Kandang">{{ $log->coop->name ?? '-' }}</td>
                            <td data-label="Telur" class="num">
                                <b>{{ Format::number($log->eggs_total_count) }}</b> butir
                                <div class="text-muted small">{{ Format::trays($log->eggs_total_count) }}</div>
                            </td>
                            <td data-label="Berat" class="num">{{ Format::number($log->eggs_total_kg, 2) }} kg</td>
                            <td data-label="HDP" class="num">
                                <x-tag :tone="$log->hdp_percentage >= $hdpWarn ? 'success' : 'warning'">{{ Format::number($log->hdp_percentage, 1) }}%</x-tag>
                            </td>
                            <td data-label="Pakan" class="num">{{ Format::number($log->feed_consumed_kg, 1) }} kg</td>
                            <td data-label="FCR" class="num">{{ $log->fcr ? Format::number($log->fcr, 2) : '-' }}</td>
                            <td data-label="Mati/Afkir" class="num {{ ($log->mortality + $log->cull) > 0 ? 'text-danger fw-bold' : '' }}">{{ $log->mortality }} / {{ $log->cull }}</td>
                            <td data-label="Dicatat oleh">{{ $log->recorder->name ?? '-' }}</td>
                            <td class="actions">
                                <a href="{{ route('daily-logs.edit', $log) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Ubah</a>
                                <x-delete-button :action="route('daily-logs.destroy', $log)"
                                    title="Hapus laporan panen ini?"
                                    :message="'Laporan ' . ($log->coop->name ?? '') . ' tanggal ' . Format::date($log->log_date) . ' akan dihapus. Jumlah ayam dan stok pakan dikembalikan seperti semula.'" />
                            </td>
                        </tr>
                        @if($log->notes)
                            <tr>
                                <td colspan="10" class="pt-0 text-muted small"><i class="bi bi-chat-left-text me-1"></i> {{ $log->notes }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if($logs->hasPages())
        <x-slot:footer>{{ $logs->links() }}</x-slot:footer>
    @endif
</x-panel>
@endsection
