@extends('layouts.app')

@section('title', 'Ubah Vaksinasi')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Ubah Catatan Vaksinasi" :subtitle="$vaccination->vaccine_name . ' · ' . ($vaccination->coop->name ?? '')" icon="bi-pencil-square" :back="route('vaccinations.index')" back-label="Riwayat vaksinasi" />

<x-alerts />

<form action="{{ route('vaccinations.update', $vaccination) }}" method="POST">
    @csrf
    @method('PUT')

    <x-panel title="Kandang & tanggal" icon="bi-calendar-check">
        <div class="row g-3">
            <div class="col-md-5">
                <x-field label="Tanggal vaksin" name="vaccination_date" required>
                    <input type="date" id="vaccination_date" name="vaccination_date" value="{{ old('vaccination_date', $vaccination->vaccination_date->toDateString()) }}" class="form-control" required>
                </x-field>
            </div>
            <div class="col-md-7">
                <x-field label="Kandang" name="coop_id" required>
                    <select id="coop_id" name="coop_id" class="form-select" required>
                        @foreach($coops as $coop)
                            <option value="{{ $coop->id }}" @selected(old('coop_id', $vaccination->coop_id) == $coop->id)>{{ $coop->name }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>
        </div>
        <x-field label="Umur ayam saat divaksin" name="age_weeks" required class="mb-0">
            <div class="input-group" style="max-width:260px">
                <input type="number" id="age_weeks" name="age_weeks" min="0" step="1" value="{{ old('age_weeks', $vaccination->age_weeks) }}" class="form-control" required>
                <span class="input-group-text">minggu</span>
            </div>
        </x-field>
    </x-panel>

    @include('vaccinations._details')

    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('vaccinations.index') }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button>
    </div>
</form>
@endsection
