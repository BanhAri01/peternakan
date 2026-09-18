@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 700px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Tambah Kandang Baru</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('coops.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Nama Kandang <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Contoh: Kandang A1" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kapasitas Maksimal</label>
                        <input type="number" name="capacity" class="form-control" value="{{ old('capacity', 0) }}" min="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Populasi Awal Masuk <span class="text-danger">*</span></label>
                        <input type="number" name="initial_population" class="form-control" value="{{ old('initial_population', 0) }}" min="0" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Strain Ayam</label>
                        <input type="text" name="strain" class="form-control" value="{{ old('strain') }}" placeholder="Lohmann, Isa Brown, dll">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Chick-In <span class="text-danger">*</span></label>
                        <input type="date" name="chick_in_date" class="form-control" value="{{ old('chick_in_date', date('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Umur Masuk (Minggu)</label>
                        <input type="number" name="initial_age_weeks" class="form-control" value="{{ old('initial_age_weeks', 18) }}" min="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="empty" {{ old('status') == 'empty' ? 'selected' : '' }}>Empty</option>
                            <option value="culled" {{ old('status') == 'culled' ? 'selected' : '' }}>Culled / Afkir</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('coops.index') }}" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Kandang</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection