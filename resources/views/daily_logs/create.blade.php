@extends('layouts.app')

@section('title', 'Catat Panen')
@section('content-class', 'narrow')

@php
    $isOwner    = auth()->user()->isOwner();
    $dateStr    = $date->toDateString();
    $isToday    = $date->isToday();
    $populations = $coops->pluck('current_population', 'id');

    $oldGrades = collect(old('grades', []));
    $gradeInit = $eggGrades->values()->map(fn ($g, $i) => [
        'trays' => $oldGrades[$i]['trays_count'] ?? '',
        'extra' => $oldGrades[$i]['extra_eggs'] ?? '',
        'kg'    => $oldGrades[$i]['weight_kg'] ?? '',
    ]);
@endphp

@section('content')
<x-page-header
    title="Catat Panen Harian"
    :subtitle="'Laporan tanggal ' . \App\Support\Format::dayDate($date) . '. Isi langkah 1 sampai 4, lalu tekan Simpan.'"
    icon="bi-clipboard2-check-fill">
    @if($isOwner)
        <a href="{{ route('daily-logs.index') }}" class="btn btn-light"><i class="bi bi-journal-text"></i> Riwayat Panen</a>
    @endif
</x-page-header>

<x-alerts />

{{-- Status kandang yang sudah / belum dicatat --}}
<x-panel>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <div class="fw-800 fs-5">
                {{ count($loggedCoopIds) }} dari {{ $coops->count() }} kandang sudah dicatat
            </div>
            <div class="text-muted">{{ $isToday ? 'Hari ini' : \App\Support\Format::dayDate($date) }}</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('daily-logs.create') }}" class="btn {{ $isToday ? 'btn-primary' : 'btn-light' }}">Hari ini</a>
            <a href="{{ route('daily-logs.create', ['date' => today()->subDay()->toDateString()]) }}" class="btn {{ $date->isYesterday() ? 'btn-primary' : 'btn-light' }}">Kemarin</a>
            @if($isOwner)
                <form method="GET" action="{{ route('daily-logs.create') }}">
                    <input type="date" name="date" value="{{ $dateStr }}" max="{{ today()->toDateString() }}" class="form-control" onchange="this.form.submit()" aria-label="Pilih tanggal lain">
                </form>
            @endif
        </div>
    </div>
    @if($coops->isNotEmpty())
        <div class="d-flex flex-wrap gap-2 mt-3">
            @foreach($coops as $c)
                @if(in_array($c->id, $loggedCoopIds))
                    <x-tag tone="success" icon="bi-check-circle-fill">{{ $c->name }}</x-tag>
                @else
                    <x-tag tone="neutral" icon="bi-circle">{{ $c->name }}</x-tag>
                @endif
            @endforeach
        </div>
    @endif
</x-panel>

@if($coops->isEmpty())
    <x-panel>
        <x-empty icon="bi-house-heart" title="Belum ada kandang aktif">
            {{ $isOwner ? 'Tambahkan kandang terlebih dahulu sebelum mencatat panen.' : 'Minta pemilik menambahkan data kandang.' }}
            @if($isOwner)
                <x-slot:action><a href="{{ route('coops.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Kandang</a></x-slot:action>
            @endif
        </x-empty>
    </x-panel>
@elseif(count($loggedCoopIds) >= $coops->count())
    <x-panel>
        <x-empty icon="bi-check2-all" title="Semua kandang sudah dicatat">
            Terima kasih! Laporan {{ $isToday ? 'hari ini' : 'tanggal ini' }} sudah lengkap.
            @if($isOwner)
                Perubahan bisa dilakukan lewat <a href="{{ route('daily-logs.index') }}">Riwayat Panen</a>.
            @else
                Jika ada yang salah, sampaikan ke pemilik untuk diperbaiki.
            @endif
        </x-empty>
    </x-panel>
@else
<form action="{{ route('daily-logs.store') }}" method="POST" data-offline="panen"
      x-data="panenForm({
          grades: @js($gradeInit),
          feeds: @js(collect(old('feeds', [['feed_stock_id' => $lastFeedByCoop[$suggestedCoop] ?? ($feedStocks->first()->id ?? '')]]))->map(fn ($f) => ['id' => $f['feed_stock_id'] ?? '', 'sacks' => $f['sacks'] ?? '', 'extra' => $f['extra_kg'] ?? ''])->values()),
          feedIds: @js($feedStocks->pluck('id')->map(fn ($id) => (string) $id)->values()),
          sackKg: {{ (float) $sackKg }},
          coopId: @js((string) $suggestedCoop),
          populations: @js($populations),
          lastFeed: @js($lastFeedByCoop),
      })">
    @csrf
    <input type="hidden" name="log_date" value="{{ $dateStr }}">

    {{-- LANGKAH 1: KANDANG --}}
    <x-panel title="Pilih kandang" step="1" subtitle="Tekan nama kandang yang akan dicatat.">
        <div class="pick-grid">
            @foreach($coops as $coop)
                @php $done = in_array($coop->id, $loggedCoopIds); @endphp
                <label class="pick" @if($done) style="opacity:.55" @endif>
                    <input type="radio" name="coop_id" value="{{ $coop->id }}" data-name="{{ $coop->name }}" x-model="coopId" @change="onCoopChange()" @disabled($done) required>
                    <span>
                        <b>{{ $coop->name }}</b>
                        <small>{{ \App\Support\Format::number($coop->current_population) }} ekor &middot; umur {{ $coop->ageInWeeks($date) }} minggu</small>
                        @if($done)<span class="done"><i class="bi bi-check-circle-fill"></i> Sudah dicatat</span>@endif
                    </span>
                </label>
            @endforeach
        </div>
        @error('coop_id')<div class="field-error mt-2"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>@enderror
        @error('log_date')<div class="field-error mt-2"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>@enderror
    </x-panel>

    {{-- LANGKAH 2: TELUR CAMPUR --}}
    <x-panel title="Telur yang dikumpulkan" step="2" tone="egg" subtitle="Hitung semua telur dari kandang ini (belum disortir). 1 rak = 30 butir.">
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
                        <input id="g{{ $i }}t" type="number" inputmode="numeric" min="0" step="1" placeholder="0"
                               name="grades[{{ $i }}][trays_count]" x-model="grades[{{ $i }}].trays" class="form-control num-xl">
                    </div>
                    <div class="col-4">
                        <label class="mini-label" for="g{{ $i }}e">+ Butir lepas</label>
                        <input id="g{{ $i }}e" type="number" inputmode="numeric" min="0" step="1" placeholder="0"
                               name="grades[{{ $i }}][extra_eggs]" x-model="grades[{{ $i }}].extra" class="form-control num-xl">
                    </div>
                    <div class="col-4">
                        <label class="mini-label" for="g{{ $i }}k">Berat (kg)</label>
                        <input id="g{{ $i }}k" type="number" inputmode="decimal" min="0" step="0.01" placeholder="0"
                               name="grades[{{ $i }}][weight_kg]" x-model="grades[{{ $i }}].kg" class="form-control num-xl">
                    </div>
                </div>
            </div>
        @endforeach
        <div class="help-tip mt-3">
            <i class="bi bi-info-circle-fill"></i>
            <span>Telur dipilah menjadi besar, kecil, retak, dll nanti di menu <b>Sortir Telur</b>.</span>
        </div>
    </x-panel>

    {{-- LANGKAH 3: PAKAN --}}
    <x-panel title="Pakan yang diberikan" step="3" tone="brand" :subtitle="'1 karung = ' . \App\Support\Format::number($sackKg) . ' kg.'">
        @if($feedStocks->isEmpty())
            <x-empty icon="bi-box-seam" title="Belum ada data pakan">
                {{ $isOwner ? 'Tambahkan jenis pakan di menu Stok Pakan.' : 'Minta pemilik menambahkan jenis pakan.' }}
            </x-empty>
        @else
            @include('daily_logs._feeds')
        @endif
    </x-panel>

    {{-- LANGKAH 4: AYAM MATI / AFKIR --}}
    <x-panel title="Ayam mati atau diafkir" step="4" tone="danger" subtitle="Isi 0 jika tidak ada. Jumlah ayam di kandang akan berkurang otomatis.">
        <div class="row g-3">
            @foreach(['mortality' => 'Ayam mati', 'cull' => 'Ayam afkir (sakit/dikeluarkan)'] as $field => $label)
                <div class="col-sm-6">
                    <label class="field-label" for="{{ $field }}">{{ $label }}</label>
                    <div class="input-group">
                        <button type="button" class="btn btn-light btn-lg px-3" @click="step('{{ $field }}', -1)" aria-label="Kurangi"><i class="bi bi-dash-lg"></i></button>
                        <input id="{{ $field }}" type="number" inputmode="numeric" min="0" step="1" name="{{ $field }}" x-ref="{{ $field }}" value="{{ old($field, 0) }}" class="form-control num-xl">
                        <button type="button" class="btn btn-light btn-lg px-3" @click="step('{{ $field }}', 1)" aria-label="Tambah"><i class="bi bi-plus-lg"></i></button>
                    </div>
                    <div class="field-hint">Satuan: ekor</div>
                </div>
            @endforeach
        </div>
    </x-panel>

    <x-panel title="Catatan" icon="bi-chat-left-text" subtitle="Boleh dikosongkan. Contoh: ayam lesu, lampu mati, air macet.">
        <textarea name="notes" rows="2" maxlength="500" class="form-control" placeholder="Tulis catatan jika ada...">{{ old('notes') }}</textarea>
    </x-panel>

    {{-- RINGKASAN & SIMPAN --}}
    <div class="sticky-actions">
        <div class="summary-bar">
            <div><div class="k">Total telur</div><div class="v" x-text="angka(totalEggs(), 0) + ' butir'"></div></div>
            <div><div class="k">Dalam rak</div><div class="v" x-text="trayText()"></div></div>
            <div><div class="k">Berat telur</div><div class="v" x-text="angka(totalKg(), 2) + ' kg'"></div></div>
            <div><div class="k">Pakan</div><div class="v" x-text="angka(feedKg()) + ' kg'"></div></div>
            <div><div class="k">Produksi (HDP)</div><div class="v" x-text="hdp()"></div></div>
        </div>
        <button type="submit" class="btn btn-primary btn-xl w-100">
            <i class="bi bi-check2-circle"></i> Simpan Laporan Panen
        </button>
    </div>
</form>
@endif
@endsection

@push('scripts')
    @include('daily_logs._script')
@endpush
