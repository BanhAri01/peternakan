@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 720px;">

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white p-4 border-bottom">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 text-uppercase fw-bold mb-2">
                Formulir Pakan Baru
            </span>
            <h3 class="fw-black text-dark mb-1">Tambah Bahan Pakan ke Gudang</h3>
            <p class="text-muted small mb-0">Daftarkan jenis pakan konsentrat, jagung giling, atau pakan jadi untuk kandang.</p>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('feed-stocks.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label class="form-label text-secondary small text-uppercase fw-bold">
                        Nama Bahan Pakan <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="feed_name" value="{{ old('feed_name') }}" placeholder="Contoh: Konsentrat 124, Jagung Giling Halus" class="form-control form-control-lg fw-bold @error('feed_name') is-invalid @enderror" required>
                    @error('feed_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label text-secondary small text-uppercase fw-bold">
                            Stok Fisik Awal (KG) <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.01" name="stock_kg" value="{{ old('stock_kg', 0) }}" class="form-control form-control-lg text-center fw-black fs-3 @error('stock_kg') is-invalid @enderror" min="0" required>
                        <span class="text-muted small mt-1 d-block">Masukkan 0 jika belum ada fisik di gudang.</span>
                        @error('stock_kg')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-secondary small text-uppercase fw-bold">
                            Harga Modal Beli / KG (Rp) <span class="text-danger">*</span>
                        </label>
                        <input type="number" step="0.01" name="cost_per_kg" value="{{ old('cost_per_kg') }}" placeholder="Misal: 6800" class="form-control form-control-lg text-center fw-black fs-3 @error('cost_per_kg') is-invalid @enderror" min="0" required>
                        <span class="text-muted small mt-1 d-block">Harga per kg untuk kalkulasi HPP harian.</span>
                        @error('cost_per_kg')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="{{ route('feed-stocks.index') }}" class="btn btn-outline-secondary fw-bold px-4">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg fw-bold px-5">
                        <i class="bi bi-save2-fill me-2"></i> Simpan Pakan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection