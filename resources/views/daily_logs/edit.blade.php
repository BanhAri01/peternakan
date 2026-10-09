@extends('layouts.app')

@section('title', 'Ubah Laporan Panen')
@section('content-class', 'narrow')

@php
    $oldGrades = old('grades');
    $gradeInit = $eggGrades->values()->map(function ($g, $i) use ($dailyLog, $oldGrades) {
        $existing = $dailyLog->grades->firstWhere('egg_grade_id', $g->id);
        return [
            'trays' => $oldGrades[$i]['trays_count'] ?? ($existing->trays_count ?? ''),
            'extra' => $oldGrades[$i]['extra_eggs'] ?? ($existing->extra_eggs ?? ''),
            'kg'    => $oldGrades[$i]['weight_kg'] ?? ($existing->weight_kg ?? ''),
        ];
    });
    // Populasi sebelum penyusutan hari itu, untuk perkiraan HDP
    $populations = $coops->mapWithKeys(fn ($c) => [
        $c->id => $c->current_population + ($c->id === $dailyLog->coop_id ? $dailyLog->mortality + $dailyLog->cull : 0),
    ]);
@endphp

@section('content')
<x-page-header
    title="Ubah Laporan Panen"
    :subtitle="$dailyLog->coop->name . ' · ' . \App\Support\Format::dayDate($dailyLog->log_date) . ($dailyLog->recorder ? ' · dicatat oleh ' . $dailyLog->recorder->name : '')"
    icon="bi-pencil-square"
    :back="route('daily-logs.index')"
    back-label="Kembali ke Riwayat Panen" />

<x-alerts />

<div class="notice notice-info">
    <i class="bi bi-info-circle-fill"></i>
    <div>Saat disimpan, jumlah ayam dan stok pakan akan dihitung ulang secara otomatis sesuai angka yang baru.</div>
</div>

<form action="{{ route('daily-logs.update', $dailyLog) }}" method="POST"
      x-data="panenForm({
          grades: @js($gradeInit),
          sackKg: {{ (float) $sackKg }},
          coopId: @js((string) old('coop_id', $dailyLog->coop_id)),
          populations: @js($populations),
          lastFeed: {},
      })">
    @csrf
    @method('PUT')

    <x-panel title="Kandang & tanggal" step="1">
        <div class="row g-3">
            <div class="col-md-7">
                <x-field label="Kandang" name="coop_id" required class="mb-0">
                    <select name="coop_id" id="coop_id" class="form-select" x-model="coopId" required>
                        @foreach($coops as $coop)
                            <option value="{{ $coop->id }}">{{ $coop->name }}{{ $coop->status !== 'active' ? ' (' . $coop->status_label . ')' : '' }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>
            <div class="col-md-5">
                <x-field label="Tanggal panen" name="log_date" required class="mb-0">
                    <input type="date" id="log_date" name="log_date" class="form-control" max="{{ today()->toDateString() }}"
                           value="{{ old('log_date', $dailyLog->log_date->toDateString()) }}" required>
                </x-field>
            </div>
        </div>
    </x-panel>

    <x-panel title="Telur yang dikumpulkan" step="2" tone="egg" subtitle="1 rak = 30 butir.">
        @foreach($eggGrades as $i => $grade)
            <div class="grade-box" :class="{ 'has-value': gradeEggs({{ $i }}) > 0 || num(grades[{{ $i }}].kg) > 0 }">
                <div class="grade-name">
                    <span><i class="bi bi-egg-fill text-egg me-1"></i> {{ $grade->name }}</span>
                    <span class="grade-total" x-show="gradeEggs({{ $i }}) > 0" x-text="'= ' + angka(gradeEggs({{ $i }}), 0) + ' butir'"></span>
                </div>
                <input type="hidden" name="grades[{{ $i }}][egg_grade_id]" value="{{ $grade->id }}">
                <div class="row g-2">
                    <div class="col-4">
                        <label class="mini-label" for="g{{ $i }}t">Jumlah rak</label>
                        <input id="g{{ $i }}t" type="number" inputmode="numeric" min="0" step="1" placeholder="0" name="grades[{{ $i }}][trays_count]" x-model="grades[{{ $i }}].trays" class="form-control num-lg">
                    </div>
                    <div class="col-4">
                        <label class="mini-label" for="g{{ $i }}e">+ Butir lepas</label>
                        <input id="g{{ $i }}e" type="number" inputmode="numeric" min="0" step="1" placeholder="0" name="grades[{{ $i }}][extra_eggs]" x-model="grades[{{ $i }}].extra" class="form-control num-lg">
                    </div>
                    <div class="col-4">
                        <label class="mini-label" for="g{{ $i }}k">Berat (kg)</label>
                        <input id="g{{ $i }}k" type="number" inputmode="decimal" min="0" step="0.01" placeholder="0" name="grades[{{ $i }}][weight_kg]" x-model="grades[{{ $i }}].kg" class="form-control num-lg">
                    </div>
                </div>
            </div>
        @endforeach
    </x-panel>

    <x-panel title="Pakan yang diberikan" step="3" tone="brand" subtitle="Dicatat per sesi lewat menu Beri Pakan. Tekan Ubah jika ada yang salah.">
        @forelse($feedings as $f)
            <div class="kv">
                <span class="k">
                    <b>{{ $f->sessionName() }}</b>
                    <span class="d-block small">{{ $f->items->map(fn ($i) => ($i->feedStock->feed_name ?? '-') . ' ' . \App\Support\Format::number($i->feed_kg) . ' kg')->implode(' + ') }}</span>
                    @if($f->recorder)<span class="d-block small text-muted">oleh {{ $f->recorder->name }}</span>@endif
                </span>
                <span class="v d-flex gap-1 flex-wrap justify-content-end">
                    <a href="{{ route('feedings.edit', $f) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Ubah</a>
                </span>
            </div>
        @empty
            <x-empty icon="bi-basket2" title="Belum ada pakan dicatat untuk kandang & tanggal ini" />
        @endforelse
        <a href="{{ route('feedings.create', ['date' => $dailyLog->log_date->toDateString(), 'coop' => $dailyLog->coop_id]) }}" class="btn btn-light w-100 mt-2"><i class="bi bi-plus-lg"></i> Tambah pemberian pakan</a>
    </x-panel>

    <x-panel title="Ayam mati atau diafkir" step="4" tone="danger">
        <div class="row g-3">
            @foreach(['mortality' => 'Ayam mati', 'cull' => 'Ayam afkir'] as $field => $label)
                <div class="col-sm-6">
                    <label class="field-label" for="{{ $field }}">{{ $label }} (ekor)</label>
                    <div class="input-group">
                        <button type="button" class="btn btn-light btn-lg px-3" @click="step('{{ $field }}', -1)" aria-label="Kurangi"><i class="bi bi-dash-lg"></i></button>
                        <input id="{{ $field }}" type="number" inputmode="numeric" min="0" step="1" name="{{ $field }}" x-ref="{{ $field }}" value="{{ old($field, $dailyLog->$field) }}" class="form-control num-xl">
                        <button type="button" class="btn btn-light btn-lg px-3" @click="step('{{ $field }}', 1)" aria-label="Tambah"><i class="bi bi-plus-lg"></i></button>
                    </div>
                </div>
            @endforeach
        </div>
    </x-panel>

    <x-panel title="Catatan" icon="bi-chat-left-text">
        <textarea name="notes" rows="2" maxlength="500" class="form-control">{{ old('notes', $dailyLog->notes) }}</textarea>
    </x-panel>

    <div class="sticky-actions">
        <div class="summary-bar">
            <div><div class="k">Total telur</div><div class="v" x-text="angka(totalEggs(), 0) + ' butir'"></div></div>
            <div><div class="k">Dalam rak</div><div class="v" x-text="trayText()"></div></div>
            <div><div class="k">Berat telur</div><div class="v" x-text="angka(totalKg(), 2) + ' kg'"></div></div>
            <div><div class="k">Produksi (HDP)</div><div class="v" x-text="hdp()"></div></div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('daily-logs.index') }}" class="btn btn-light btn-xl">Batal</a>
            <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
    @include('daily_logs._script')
@endpush
