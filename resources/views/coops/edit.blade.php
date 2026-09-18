@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 700px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Edit Kandang: {{ $coop->name }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('coops.update', $coop->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nama Kandang <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $coop->name) }}" required>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Kapasitas Maks</label>
                        <input type="number" name="capacity" class="form-control" value="{{ old('capacity', $coop->capacity) }}" min="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Populasi Masuk</label>
                        <input type="number" name="initial_population" class="form-control" value="{{ old('initial_population', $coop->initial_population) }}" min="0">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Populasi Sekarang</label>
                        <input type="number" name="current_population" class="form-control" value="{{ old('current_population', $coop->current_population) }}" min="0">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Strain Ayam</label>
                        <input type="text" name="strain" class="form-control" value="{{ old('strain', $coop->strain) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Chick-In</label>
                        <input type="date" name="chick_in_date" class="form-control" value="{{ old('chick_in_date', $coop->chick_in_date->format('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Umur Masuk (Minggu)</label>
                        <input type="number" name="initial_age_weeks" class="form-control" value="{{ old('initial_age_weeks', $coop->initial_age_weeks) }}" min="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ old('status', $coop->status) == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="empty" {{ old('status', $coop->status) == 'empty' ? 'selected' : '' }}>Empty</option>
                            <option value="culled" {{ old('status', $coop->status) == 'culled' ? 'selected' : '' }}>Culled / Afkir</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('coops.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection