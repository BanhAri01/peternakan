@extends('layouts.app')

@section('title', 'Ekspor Excel')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Ekspor ke Excel" subtitle="Unduh data peternakan dalam satu file Excel. Bisa dibuka di Excel, Google Sheets, atau WPS untuk laporan ke bank, koperasi, atau arsip." icon="bi-file-earmark-spreadsheet-fill" />

<x-alerts />

<form action="{{ route('exports.download') }}" method="GET" x-data="{ all: true }">
    <x-panel title="Periode" step="1">
        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach([
                'Bulan ini'   => [today()->startOfMonth(), today()],
                'Bulan lalu'  => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
                'Tahun ini'   => [today()->startOfYear(), today()],
                'Tahun lalu'  => [today()->subYear()->startOfYear(), today()->subYear()->endOfYear()],
            ] as $label => [$from, $to])
                <button type="button" class="btn btn-light btn-sm" data-no-lock
                        @click="$refs.start.value = '{{ $from->toDateString() }}'; $refs.end.value = '{{ $to->toDateString() }}'">{{ $label }}</button>
            @endforeach
        </div>
        <div class="row g-3">
            <div class="col-6">
                <x-field label="Dari tanggal" name="start" required>
                    <input type="date" id="start" name="start" x-ref="start" value="{{ old('start', $start->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-control" required>
                </x-field>
            </div>
            <div class="col-6">
                <x-field label="Sampai tanggal" name="end" required>
                    <input type="date" id="end" name="end" x-ref="end" value="{{ old('end', $end->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-control" required>
                </x-field>
            </div>
        </div>
    </x-panel>

    <x-panel title="Data yang diekspor" step="2" subtitle="Setiap data menjadi satu lembar (sheet) di file Excel.">
        <div class="choices" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr))">
            @foreach($sheets as $key => $label)
                <label class="choice"><input type="checkbox" name="sheets[]" value="{{ $key }}" checked><span>{{ $label }}</span></label>
            @endforeach
        </div>
        @error('sheets')<div class="field-error mt-2"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>@enderror
        <div class="help-tip mt-3"><i class="bi bi-info-circle-fill"></i><span>"Piutang belum lunas" selalu berisi semua utang yang masih berjalan, tidak tergantung periode.</span></div>
    </x-panel>

    <button type="submit" class="btn btn-primary btn-xl w-100" data-no-lock><i class="bi bi-download"></i> Unduh File Excel</button>
</form>
@endsection
