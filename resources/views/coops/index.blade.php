@extends('layouts.app')

@section('title', 'Kandang')

@php use App\Support\Format; $hdpWarn = \App\Models\Setting::num('hdp_warning'); @endphp

@section('content')
<x-page-header title="Kandang" subtitle="Daftar kandang, jumlah ayam, dan umurnya. Jumlah ayam berkurang otomatis dari laporan ayam mati/afkir." icon="bi-house-heart-fill">
    <a href="{{ route('coops.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Kandang</a>
</x-page-header>

<x-alerts />

@if($coops->isEmpty())
    <x-panel>
        <x-empty icon="bi-house-add" title="Belum ada kandang">
            Mulai dengan menambahkan kandang pertama Anda.
            <x-slot:action><a href="{{ route('coops.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Kandang</a></x-slot:action>
        </x-empty>
    </x-panel>
@else
    <div class="row g-3">
        @foreach($coops as $coop)
            @php
                $last = $lastLogs->get($coop->id);
                $hdp  = $avgHdp[$coop->id] ?? null;
                $occ  = $coop->occupancy;
            @endphp
            <div class="col-md-6 col-xxl-4">
                <section class="panel coop-card mb-0" @if($coop->status !== 'active') style="opacity:.8" @endif>
                    <div class="panel-head">
                        <div>
                            <h2 class="panel-title">{{ $coop->name }}</h2>
                            <p class="panel-sub">{{ $coop->strain ?: 'Jenis ayam belum diisi' }}</p>
                        </div>
                        <x-tag :tone="$coop->status_tone">{{ $coop->status_label }}</x-tag>
                    </div>
                    <div class="panel-body">
                        <div class="metric-row mb-3">
                            <div class="metric"><span class="k">Jumlah ayam</span><span class="v">{{ Format::number($coop->current_population) }}</span></div>
                            <div class="metric"><span class="k">Umur</span><span class="v">{{ $coop->ageInWeeks() }} mg</span></div>
                            <div class="metric"><span class="k">HDP 7 hari</span><span class="v {{ $hdp !== null && $hdp < $hdpWarn ? 'text-danger' : '' }}">{{ $hdp !== null ? Format::number($hdp, 1) . '%' : '-' }}</span></div>
                        </div>
                        @if($occ !== null)
                            <div class="d-flex justify-content-between small mb-1"><span class="text-muted fw-bold">Isi kandang</span><span><b>{{ Format::number($occ, 0) }}%</b> dari {{ Format::number($coop->capacity) }}</span></div>
                            <div class="progress-thin mb-3"><span style="width: {{ min(100, $occ) }}%"></span></div>
                        @endif
                        <div class="kv"><span class="k">Ayam masuk</span><span class="v">{{ Format::number($coop->initial_population) }} ekor · {{ Format::date($coop->chick_in_date) }}</span></div>
                        <div class="kv"><span class="k">Laporan terakhir</span><span class="v">{{ $last ? Format::date($last->log_date) : 'Belum ada' }}</span></div>
                    </div>
                    <div class="panel-foot d-flex flex-wrap gap-2 justify-content-end">
                        <x-delete-button :action="route('coops.destroy', $coop)" title="Hapus kandang ini?"
                            :message="'Kandang ' . $coop->name . ' akan dihapus. Kandang yang sudah punya riwayat panen tidak bisa dihapus.'" />
                        <a href="{{ route('coops.edit', $coop) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Ubah</a>
                        <a href="{{ route('coops.show', $coop) }}" class="btn btn-primary btn-sm">Lihat detail <i class="bi bi-arrow-right"></i></a>
                    </div>
                </section>
            </div>
        @endforeach
    </div>
@endif
@endsection
