@extends('layouts.app')

@section('title', 'Catat Vaksinasi')
@section('content-class', 'narrow')

@php
    $vaccination = new \App\Models\Vaccination();
    $coopData = $coops->map(fn ($c) => [
        'id'      => (string) $c->id,
        'chickIn' => optional($c->chick_in_date)->toDateString(),
        'initial' => (int) $c->initial_age_weeks,
    ])->values();
@endphp

@section('content')
<x-page-header title="Catat Vaksinasi" subtitle="Pilih satu atau beberapa kandang yang divaksin pada hari yang sama." icon="bi-shield-plus" :back="route('vaccinations.index')" back-label="Riwayat vaksinasi" />

<x-alerts />

<form action="{{ route('vaccinations.store') }}" method="POST"
      x-data="vaccineForm({ coops: @js($coopData), selected: @js(array_map('strval', old('coop_ids', []))), date: @js(old('vaccination_date', today()->toDateString())), age: @js(old('age_weeks')) })">
    @csrf

    <x-panel title="Kandang & tanggal" step="1">
        <x-field label="Tanggal vaksin" name="vaccination_date" required>
            <input type="date" id="vaccination_date" name="vaccination_date" x-model="date" @change="calcAge()" class="form-control" style="max-width:260px" required>
        </x-field>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <span class="field-label mb-0">Kandang yang divaksin <span class="req">*</span></span>
            <button type="button" class="btn btn-light btn-sm" @click="toggleAll()" x-text="selected.length === coops.length ? 'Batal pilih semua' : 'Pilih semua kandang'"></button>
        </div>
        @if($coops->isEmpty())
            <x-empty icon="bi-house" title="Belum ada kandang aktif" class="py-3" />
        @else
            <div class="pick-grid">
                @foreach($coops as $coop)
                    <label class="pick">
                        <input type="checkbox" name="coop_ids[]" value="{{ $coop->id }}" x-model="selected" @change="calcAge()">
                        <span>
                            <b>{{ $coop->name }}</b>
                            <small>{{ \App\Support\Format::number($coop->current_population) }} ekor · umur {{ $coop->ageInWeeks() }} minggu</small>
                        </span>
                    </label>
                @endforeach
            </div>
        @endif
        @error('coop_ids')<div class="field-error mt-2"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>@enderror

        <div class="row g-3 mt-1 align-items-end">
            <div class="col-sm-6">
                <x-field label="Umur ayam saat divaksin" name="age_weeks" required class="mb-0" hint="Terisi otomatis dari kandang yang dipilih. Boleh diubah.">
                    <div class="input-group">
                        <input type="number" id="age_weeks" name="age_weeks" min="0" step="1" x-model="age" class="form-control num-lg" required>
                        <span class="input-group-text">minggu</span>
                    </div>
                </x-field>
            </div>
        </div>
    </x-panel>

    @include('vaccinations._details')

    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('vaccinations.index') }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1" :disabled="selected.length === 0">
            <i class="bi bi-check2-circle"></i> <span x-text="selected.length > 1 ? 'Simpan untuk ' + selected.length + ' kandang' : 'Simpan Vaksinasi'">Simpan Vaksinasi</span>
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function vaccineForm(init) {
        return Object.assign({
            toggleAll() {
                this.selected = this.selected.length === this.coops.length ? [] : this.coops.map(c => c.id);
                this.calcAge();
            },
            calcAge() {
                var date = new Date(this.date), max = null;
                this.coops.forEach(c => {
                    if (this.selected.indexOf(c.id) === -1) return;
                    var age = c.initial;
                    if (c.chickIn) age += Math.max(0, Math.floor((date - new Date(c.chickIn)) / 86400000 / 7));
                    if (max === null || age > max) max = age;
                });
                if (max !== null) this.age = max;
            },
            init() { if (this.age === null || this.age === '') this.calcAge(); },
        }, init);
    }
</script>
@endpush
