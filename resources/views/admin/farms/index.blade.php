@extends('layouts.app')

@section('title', 'Semua Peternakan')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Semua Peternakan" subtitle="Pelanggan HEFAM: masa coba, langganan, dan keaktifan pemakaian." icon="bi-buildings-fill">
    <a href="{{ route('admin.farms.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Peternakan</a>
</x-page-header>

<x-alerts />

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><x-stat label="Total peternakan" :value="$stats['total']" icon="bi-buildings" tone="info" :href="route('admin.farms.index')" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat label="Masa coba" :value="$stats['trial']" icon="bi-hourglass-split" tone="warning" :href="route('admin.farms.index', ['status' => 'trial'])" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat label="Berlangganan" :value="$stats['active']" icon="bi-patch-check-fill" tone="success" :href="route('admin.farms.index', ['status' => 'active'])" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat label="Habis / dibekukan" :value="$stats['attention']" icon="bi-exclamation-octagon-fill" tone="danger" :alert="$stats['attention'] > 0" :href="route('admin.farms.index', ['status' => 'expired'])" /></div>
</div>

<x-panel flush>
    <x-slot:actions>
        <form method="GET" class="d-flex gap-2 flex-wrap">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama / pemilik / HP..." class="form-control" style="min-height:44px; width:240px" aria-label="Cari peternakan">
            <button class="btn btn-light btn-sm" data-no-lock aria-label="Cari"><i class="bi bi-search"></i></button>
        </form>
    </x-slot:actions>

    @if($farms->isEmpty())
        <x-empty icon="bi-buildings" title="Tidak ada peternakan" />
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Peternakan</th><th>Pemilik</th><th>Status</th><th>Berlaku sampai</th><th class="num">Pekerja</th><th>Panen terakhir</th><th class="actions"></th></tr></thead>
                <tbody>
                    @foreach($farms as $farm)
                        @php $log = $lastLog->get($farm->id); @endphp
                        <tr>
                            <td class="title-cell"><div><a href="{{ route('admin.farms.edit', $farm) }}" class="fw-800 text-decoration-none text-ink">{{ $farm->name }}</a><div class="text-muted small">{{ $farm->city ?: 'Kota belum diisi' }} · daftar {{ Format::date($farm->created_at) }}</div></div></td>
                            <td data-label="Pemilik">
                                {{ $farm->owner_name ?: ($farm->owner->name ?? '-') }}
                                <div class="small">
                                    @if($farm->phone)<a href="https://wa.me/{{ Format::waNumber($farm->phone) }}" target="_blank" rel="noopener" class="text-decoration-none"><i class="bi bi-whatsapp"></i> {{ $farm->phone }}</a>@endif
                                </div>
                            </td>
                            <td data-label="Status"><x-tag :tone="$farm->status_tone">{{ $farm->status_label }}</x-tag><div class="text-muted small">{{ $farm->planLabel() }}</div></td>
                            <td data-label="Berlaku sampai">
                                @if($farm->accessEndsAt())
                                    {{ Format::date($farm->accessEndsAt()) }}
                                    <div class="small {{ $farm->daysLeft() < 0 ? 'text-danger' : 'text-muted' }}">{{ $farm->daysLeft() < 0 ? 'lewat ' . abs($farm->daysLeft()) . ' hari' : $farm->daysLeft() . ' hari lagi' }}</div>
                                @else
                                    {{ $farm->status === 'suspended' ? '-' : 'Tanpa batas' }}
                                @endif
                            </td>
                            <td data-label="Pekerja" class="num">{{ $farm->workers_count }}</td>
                            <td data-label="Panen terakhir">
                                {{ $log ? Format::date($log->last_log) : 'Belum pernah' }}
                                @if($log)<div class="text-muted small">{{ Format::number($log->logs) }} laporan</div>@endif
                            </td>
                            <td class="actions"><a href="{{ route('admin.farms.edit', $farm) }}" class="btn btn-light btn-sm">Kelola <i class="bi bi-arrow-right"></i></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-panel>
@endsection
