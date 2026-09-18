@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 920px;">

    <!-- Header Section -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1.5 text-uppercase fw-bold mb-2">
                <i class="bi bi-sliders me-1"></i> Master Data
            </span>
            <h2 class="fw-black text-dark mb-1">Pengaturan Kategori Grade Telur</h2>
            <p class="text-muted mb-0">Atur kategori sortir telur yang digunakan pada formulir panen harian, stok gudang, dan nota penjualan.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center mb-4 rounded-3 p-3 shadow-sm border-0 bg-success-subtle text-success-emphasis" role="alert">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div class="fw-bold">{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4 rounded-3 p-3 shadow-sm border-0">
            <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Periksa isian Anda:</h6>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Tambah Grade Baru -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white p-4 border-bottom">
            <h5 class="fw-black text-dark mb-0">
                <i class="bi bi-plus-circle-fill text-primary me-2"></i>Tambah Kategori Grade Baru
            </h5>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('grades.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Nama Kategori Grade <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Grade Super, Putih, Bentes" class="form-control form-control-lg fw-bold" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Kode Singkat (Opsional)</label>
                        <input type="text" name="code" value="{{ old('code') }}" placeholder="Contoh: SP, PT, BTS" class="form-control form-control-lg fw-bold">
                    </div>
                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-primary btn-lg px-4 fw-bold">
                            <i class="bi bi-save-fill me-2"></i> Simpan Kategori Grade
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Daftar Grade Telur -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-black text-dark mb-1">Daftar Grade Telur</h5>
                <p class="text-muted small mb-0">Status aktif menentukan apakah grade muncul di formulir sortir panen dan penjualan.</p>
            </div>
            <span class="badge bg-dark fs-6 px-3 py-2 rounded-pill">
                {{ count($grades) }} Grade Terdaftar
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Nama Kategori</th>
                            <th>Kode Singkat</th>
                            <th>Status Pemakaian</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grades as $grade)
                            <tr>
                                <td class="ps-4">
                                    <span class="fs-5 fw-bold text-dark">{{ $grade->name }}</span>
                                </td>
                                <td>
                                    @if($grade->code)
                                        <span class="badge bg-light text-dark border font-monospace fs-6 px-2.5 py-1">
                                            {{ $grade->code }}
                                        </span>
                                    @else
                                        <span class="text-muted small fst-italic">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($grade->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
                                            <i class="bi bi-check-circle-fill me-1"></i> Aktif Digunakan
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border px-3 py-2 fs-6">
                                            <i class="bi bi-dash-circle-fill me-1"></i> Dinonaktifkan
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <form action="{{ route('grades.toggle', $grade->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $grade->is_active ? 'btn-outline-danger' : 'btn-outline-success' }} fw-bold px-3 py-1.5">
                                            @if($grade->is_active)
                                                <i class="bi bi-x-circle me-1"></i> Nonaktifkan
                                            @else
                                                <i class="bi bi-check2-circle me-1"></i> Aktifkan
                                            @endif
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-egg fs-2 text-secondary d-block mb-2"></i>
                                    Belum ada kategori grade telur yang dibuat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection