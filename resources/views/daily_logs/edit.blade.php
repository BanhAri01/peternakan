@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-5 py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white p-4 border-bottom">
            <h4 class="fw-black text-dark mb-1">Edit Data Panen</h4>
            <p class="text-muted small mb-0">
                {{ $dailyLog->coop->name ?? 'Kandang' }} -
                {{ \Carbon\Carbon::parse($dailyLog->log_date)->format('d/m/Y') }}
            </p>
        </div>

        <div class="card-body p-4">
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('daily-logs.update', $dailyLog->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Kandang</label>
                        <select name="coop_id" class="form-select" required>
                            @foreach($coops as $coop)
                                <option value="{{ $coop->id }}" {{ old('coop_id', $dailyLog->coop_id) == $coop->id ? 'selected' : '' }}>
                                    {{ $coop->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tanggal Panen</label>
                        <input type="date" name="log_date" class="form-control" value="{{ old('log_date', \Carbon\Carbon::parse($dailyLog->log_date)->format('Y-m-d')) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Jenis Pakan</label>
                        <select name="feed_stock_id" class="form-select" required>
                            @foreach($feedStocks as $feed)
                                <option value="{{ $feed->id }}" {{ old('feed_stock_id', $dailyLog->feed_stock_id) == $feed->id ? 'selected' : '' }}>
                                    {{ $feed->feed_name }} - Rp {{ number_format($feed->cost_per_kg, 0, ',', '.') }}/kg
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Pakan (Karung)</label>
                        <input type="number" name="feed_sacks" class="form-control" min="0" value="{{ old('feed_sacks', $feedSacks) }}">
                        <small class="text-muted">1 karung = 50 kg</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Pakan Tambahan (KG)</label>
                        <input type="number" step="0.01" name="extra_feed_kg" class="form-control" min="0" value="{{ old('extra_feed_kg', $extraFeedKg) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Ayam Mati</label>
                        <input type="number" name="mortality" class="form-control" min="0" value="{{ old('mortality', $dailyLog->mortality) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Ayam Afkir</label>
                        <input type="number" name="cull" class="form-control" min="0" value="{{ old('cull', $dailyLog->cull) }}">
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="fw-black text-dark mb-3">Rincian Grade Telur</h5>

                @foreach($eggGrades as $index => $grade)
                    @php
                        $existing = $dailyLog->grades->firstWhere('egg_grade_id', $grade->id);
                    @endphp

                    <div class="card bg-light border mb-3">
                        <div class="card-body">
                            <h6 class="fw-bold text-dark mb-3">{{ $grade->name }}</h6>

                            <input type="hidden" name="grades[{{ $index }}][egg_grade_id]" value="{{ $grade->id }}">

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Jumlah Rak</label>
                                    <input type="number" name="grades[{{ $index }}][trays_count]" class="form-control" min="0" value="{{ old("grades.$index.trays_count", $existing->trays_count ?? 0) }}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Telur Lepas</label>
                                    <input type="number" name="grades[{{ $index }}][extra_eggs]" class="form-control" min="0" value="{{ old("grades.$index.extra_eggs", $existing->extra_eggs ?? 0) }}">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Berat (KG)</label>
                                    <input type="number" name="grades[{{ $index }}][weight_kg]" class="form-control" step="0.01" min="0" value="{{ old("grades.$index.weight_kg", $existing->weight_kg ?? 0) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="mb-4">
                    <label class="form-label fw-bold">Catatan</label>
                    <textarea name="notes" class="form-control" rows="4" maxlength="500">{{ old('notes', $dailyLog->notes) }}</textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('owner.dashboard', ['date' => \Carbon\Carbon::parse($dailyLog->log_date)->format('Y-m-d')]) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>

                    <button type="submit" class="btn btn-primary fw-bold">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection