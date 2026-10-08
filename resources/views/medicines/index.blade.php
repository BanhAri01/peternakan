@extends('layouts.app')

@section('title', 'Stok Obat & Vitamin')

@php use App\Models\MedicineMovement; use App\Support\Format; @endphp

@section('content')
<x-page-header title="Stok Obat & Vitamin" subtitle="Catat obat masuk dan dipakai. Stok, biaya, dan kedaluwarsa dihitung otomatis." icon="bi-capsule">
    <a href="{{ route('medicines.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Obat</a>
</x-page-header>

<x-alerts />

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><x-stat label="Jenis obat" :value="$medicines->count()" icon="bi-capsule" tone="brand" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat label="Stok menipis" :value="$lowCount" icon="bi-exclamation-triangle-fill" :tone="$lowCount ? 'danger' : 'success'" :alert="$lowCount > 0" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat label="Kedaluwarsa / hampir" :value="$expCount" icon="bi-calendar-x-fill" :tone="$expCount ? 'warning' : 'success'" :hint="'dalam ' . \App\Models\Medicine::EXPIRY_WARNING_DAYS . ' hari'" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat label="Biaya obat dipakai bulan ini" :value="Format::rupiah($usedCost)" icon="bi-droplet-half" tone="info" :hint="'Pembelian ' . Format::rupiah($boughtCost)" /></div>
</div>

@if($medicines->isEmpty())
    <x-panel>
        <x-empty icon="bi-capsule" title="Belum ada obat atau vitamin">
            Tambahkan obat, vitamin, vaksin, atau desinfektan yang ada di gudang.
            <x-slot:action><a href="{{ route('medicines.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Obat</a></x-slot:action>
        </x-empty>
    </x-panel>
@else
    <div class="row g-3 mb-3">
        @foreach($medicines as $m)
            @php $exp = $m->expiryStatus(); @endphp
            <div class="col-md-6 col-xxl-4">
                <section class="panel mb-0">
                    <div class="panel-head">
                        <div>
                            <h3 class="panel-title">{{ $m->name }}</h3>
                            <p class="panel-sub">{{ $m->type_label }} @if($m->expiry_date)&middot; kedaluwarsa {{ Format::date($m->expiry_date) }}@endif</p>
                        </div>
                        @if($exp === 'expired')
                            <x-tag tone="danger" icon="bi-calendar-x-fill">Kedaluwarsa</x-tag>
                        @elseif($m->isLow())
                            <x-tag tone="danger" icon="bi-exclamation-triangle-fill">Menipis</x-tag>
                        @elseif($exp === 'soon')
                            <x-tag tone="warning" icon="bi-calendar-event">Segera kedaluwarsa</x-tag>
                        @else
                            <x-tag tone="success">Aman</x-tag>
                        @endif
                    </div>
                    <div class="panel-body">
                        <div class="metric-row mb-3">
                            <div class="metric"><span class="k">Stok</span><span class="v {{ $m->isLow() ? 'text-danger' : '' }}">{{ Format::number($m->stock, 2) }} {{ $m->unit }}</span></div>
                            <div class="metric"><span class="k">Minimum</span><span class="v">{{ $m->min_stock !== null ? Format::number($m->min_stock, 2) : '-' }}</span></div>
                            <div class="metric"><span class="k">Harga rata-rata</span><span class="v">@rupiah($m->average_cost)</span></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('medicines.movement', [$m, 'arah' => 'pakai']) }}" class="btn btn-primary btn-sm"><i class="bi bi-droplet-half"></i> Pakai</a>
                            <a href="{{ route('medicines.movement', [$m, 'arah' => 'masuk']) }}" class="btn btn-light btn-sm"><i class="bi bi-box-arrow-in-down"></i> Tambah stok</a>
                            <a href="{{ route('medicines.edit', $m) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Ubah</a>
                        </div>
                    </div>
                </section>
            </div>
        @endforeach
    </div>

    <x-panel title="Riwayat keluar-masuk" icon="bi-clock-history" flush>
        @if($movements->isEmpty())
            <x-empty icon="bi-clock" title="Belum ada catatan" />
        @else
            <div class="table-wrap">
                <table class="tbl stack">
                    <thead><tr><th>Tanggal</th><th>Obat</th><th>Kegiatan</th><th class="num">Jumlah</th><th class="num">Biaya</th><th class="actions">Aksi</th></tr></thead>
                    <tbody>
                        @foreach($movements as $mv)
                            @php $dir = MedicineMovement::DIRECTIONS[$mv->direction]; @endphp
                            <tr>
                                <td class="title-cell"><b>{{ Format::date($mv->movement_date) }}</b></td>
                                <td data-label="Obat"><b>{{ $mv->medicine->name ?? '-' }}</b>
                                    <div class="text-muted small">{{ collect([$mv->coop?->name, $mv->supplier, $mv->recorder?->name])->filter()->join(' · ') }}</div>
                                </td>
                                <td data-label="Kegiatan"><x-tag :tone="$dir['tone']" :icon="$dir['icon']">{{ $dir['label'] }}</x-tag></td>
                                <td data-label="Jumlah" class="num">{{ Format::number($mv->quantity, 2) }} {{ $mv->medicine->unit ?? '' }}</td>
                                <td data-label="Biaya" class="num">@rupiah($mv->total_cost)</td>
                                <td class="actions">
                                    <x-delete-button :action="route('medicines.movement.destroy', $mv)" icon-only title="Hapus catatan ini?" message="Catatan dipindah ke Sampah dan stok dihitung ulang." />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        @if($movements->hasPages())
            <x-slot:footer>{{ $movements->links() }}</x-slot:footer>
        @endif
    </x-panel>
@endif
@endsection
