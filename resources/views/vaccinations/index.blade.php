@extends('layouts.app')

@section('title', 'Vaksinasi')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Vaksinasi" subtitle="Catatan vaksin dan obat untuk setiap kandang. Biaya vaksin otomatis masuk ke laporan keuangan." icon="bi-shield-plus">
    <a href="{{ route('vaccinations.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Catat Vaksinasi</a>
</x-page-header>

<x-alerts />

<div class="row g-3 mb-3">
    <div class="col-md-6"><x-stat label="Jumlah catatan vaksin" :value="Format::number($vaccinations->total())" unit="kali" icon="bi-shield-check" tone="success" /></div>
    <div class="col-md-6"><x-stat label="Total biaya vaksin" :value="Format::rupiah($totalVaccineCost)" icon="bi-cash-stack" tone="warning" /></div>
</div>

<x-panel title="Riwayat vaksinasi" icon="bi-clock-history" flush>
    @if($vaccinations->isEmpty())
        <x-empty icon="bi-shield" title="Belum ada catatan vaksinasi">
            <x-slot:action><a href="{{ route('vaccinations.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Catat Vaksinasi</a></x-slot:action>
        </x-empty>
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Tanggal</th><th>Kandang</th><th>Vaksin</th><th>Cara & dosis</th><th>Petugas</th><th class="num">Biaya</th><th class="actions">Aksi</th></tr></thead>
                <tbody>
                    @foreach($vaccinations as $v)
                        <tr>
                            <td class="title-cell"><div><b>{{ Format::date($v->vaccination_date) }}</b><div class="text-muted small">{{ $v->vaccination_date->diffForHumans() }}</div></div></td>
                            <td data-label="Kandang">{{ $v->coop->name ?? '-' }}<div class="text-muted small">umur {{ $v->age_weeks }} minggu</div></td>
                            <td data-label="Vaksin"><b>{{ $v->vaccine_name }}</b>@if($v->target_disease)<div class="text-muted small">{{ $v->target_disease }}</div>@endif</td>
                            <td data-label="Cara & dosis">{{ $v->method }}<div class="text-muted small">{{ $v->dosage }}</div></td>
                            <td data-label="Petugas">{{ $v->officer }}</td>
                            <td data-label="Biaya" class="num">@rupiah($v->cost)</td>
                            <td class="actions">
                                <a href="{{ route('vaccinations.edit', $v) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Ubah</a>
                                <x-delete-button :action="route('vaccinations.destroy', $v)" icon-only title="Hapus catatan vaksin?" :message="'Catatan ' . $v->vaccine_name . ' di ' . ($v->coop->name ?? '') . ' akan dihapus.'" />
                            </td>
                        </tr>
                        @if($v->notes)
                            <tr><td colspan="7" class="pt-0 text-muted small"><i class="bi bi-chat-left-text me-1"></i> {{ $v->notes }}</td></tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if($vaccinations->hasPages())
        <x-slot:footer>{{ $vaccinations->links() }}</x-slot:footer>
    @endif
</x-panel>
@endsection
