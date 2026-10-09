@extends('layouts.app')

@section('title', 'Ubah Pemberian Pakan')
@section('content-class', 'narrow')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Ubah Pemberian Pakan" :subtitle="($feeding->coop->name ?? '-') . ' · ' . $feeding->sessionName() . ' · ' . Format::dayDate($feeding->feed_date)" icon="bi-pencil-square" :back="route('feedings.create', ['date' => $feeding->feed_date->toDateString(), 'sesi' => $feeding->session])" back-label="Beri Pakan" />

<x-alerts />

<form action="{{ route('feedings.update', $feeding) }}" method="POST"
      x-data="panenForm({
          sackKg: {{ (float) $sackKg }},
          coopId: @js((string) old('coop_id', $feeding->coop_id)),
          feeds: @js(old('feeds') ? collect(old('feeds'))->map(fn ($f) => ['id' => $f['feed_stock_id'] ?? '', 'sacks' => $f['sacks'] ?? '', 'extra' => $f['extra_kg'] ?? ''])->values() : $feedLines),
          feedIds: @js($feedStocks->pluck('id')->map(fn ($id) => (string) $id)->values()),
      })">
    @csrf
    @method('PUT')
    <x-panel title="Kandang, tanggal & sesi" step="1">
        <div class="row g-3">
            <div class="col-md-5">
                <x-field label="Kandang" name="coop_id" required class="mb-0">
                    <select name="coop_id" id="coop_id" class="form-select" required>
                        @foreach($coops as $coop)
                            <option value="{{ $coop->id }}" @selected(old('coop_id', $feeding->coop_id) == $coop->id)>{{ $coop->name }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>
            <div class="col-md-4">
                <x-field label="Tanggal" name="feed_date" required class="mb-0">
                    <input type="date" id="feed_date" name="feed_date" value="{{ old('feed_date', $feeding->feed_date->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-control" required>
                </x-field>
            </div>
            <div class="col-md-3">
                <x-field label="Sesi" name="session" class="mb-0">
                    <select name="session" id="session" class="form-select">
                        @if($feeding->session === null)<option value="">Tanpa sesi</option>@endif
                        @foreach($sessions as $i => $s)
                            <option value="{{ $i }}" @selected((string) old('session', $feeding->session) === (string) $i)>{{ $s['name'] }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>
        </div>
    </x-panel>

    <x-panel title="Pakan yang diberikan" step="2" tone="brand" :subtitle="'1 karung = ' . Format::number($sackKg) . ' kg. Stok pakan dihitung ulang otomatis.'">
        @include('daily_logs._feeds')
    </x-panel>

    <div class="d-flex gap-2 flex-wrap mb-3">
        <a href="{{ route('feedings.create', ['date' => $feeding->feed_date->toDateString()]) }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button>
    </div>
</form>

<x-panel>
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
        <span class="text-muted">Dicatat {{ $feeding->recorder?->name ? 'oleh ' . $feeding->recorder->name : '' }} · {{ Format::dayDate($feeding->created_at) }}</span>
        <x-delete-button :action="route('feedings.destroy', $feeding)" label="Hapus catatan ini" :small="false" title="Hapus pemberian pakan ini?" message="Stok pakan akan dikembalikan. Catatan bisa dipulihkan dari menu Sampah." />
    </div>
</x-panel>
@endsection

@push('scripts')
    @include('daily_logs._script')
@endpush
