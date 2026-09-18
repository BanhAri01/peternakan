@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 860px;">

    <!-- Judul Halaman -->
    <div class="mb-4">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 text-uppercase fw-bold mb-2">
            <i class="bi bi-clipboard2-check-fill me-1"></i> Formulir Operasional Kandang
        </span>
        <h2 class="fw-black text-dark mb-1">Pencatatan Panen, Pakan & Populasi</h2>
        <p class="text-muted fs-6 mb-0">Masukkan data harian per kandang dengan teliti untuk kalkulasi otomatis HDP dan FCR.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center mb-4 rounded-3 p-3 shadow-sm border-0 bg-success-subtle text-success-emphasis" role="alert">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div class="fw-bold">{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4 rounded-3 p-3 shadow-sm border-0">
            <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Tolong periksa kembali isian form:</h6>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('daily-logs.store') }}" method="POST">
        @csrf

        <!-- KARTU 1: Pilih Kandang & Tanggal -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label text-secondary fs-6 text-uppercase fw-bold">Pilih Kandang</label>
                        <select name="coop_id" class="form-select form-select-lg fw-bold" required>
                            @foreach($coops as $coop)
                                <option value="{{ $coop->id }}" {{ old('coop_id') == $coop->id ? 'selected' : '' }}>
                                    {{ $coop->name }} (Populasi: {{ number_format($coop->current_population) }} ekor)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label text-secondary fs-6 text-uppercase fw-bold">Tanggal Laporan</label>
                        <input type="date" name="log_date" value="{{ old('log_date', date('Y-m-d')) }}" class="form-control form-control-lg fw-bold" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- KARTU 2: Hasil Telur (Sortir Grade) -->
        <div class="card shadow-sm border-0 mb-4 border-start border-4 border-warning">
            <div class="card-header bg-warning-subtle py-3 px-4 d-flex justify-content-between align-items-center border-0">
                <span class="fw-bold fs-6 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-egg-fill text-warning fs-5"></i> 1. Hasil Panen Telur (Sortir Grade)
                </span>
                @if(Route::has('grades.index'))
                    <a href="{{ route('grades.index') }}" class="btn btn-sm btn-outline-dark fw-bold bg-white">
                        <i class="bi bi-gear-fill me-1"></i> Atur Grade
                    </a>
                @endif
            </div>
            <div class="card-body p-4">
                <div class="d-flex flex-column gap-3">
                    @forelse($eggGrades as $index => $grade)
                        <div class="p-3 rounded-3 border bg-light">
                            <div class="fw-bold fs-5 text-dark mb-2">
                                {{ $grade->name }}
                                <input type="hidden" name="grades[{{ $index }}][egg_grade_id]" value="{{ $grade->id }}">
                            </div>
                            <div class="row g-2 align-items-center">
                                <div class="col-4">
                                    <label class="form-label text-muted small fw-bold mb-1 text-center d-block">Jumlah Rak (30)</label>
                                    <input type="number" inputmode="numeric" name="grades[{{ $index }}][trays_count]" value="{{ old("grades.$index.trays_count") }}" placeholder="0" class="form-control form-control-lg text-center fw-bold fs-4 bg-white" min="0">
                                </div>
                                <div class="col-4">
                                    <label class="form-label text-muted small fw-bold mb-1 text-center d-block">+ Butir Lepas</label>
                                    <input type="number" inputmode="numeric" name="grades[{{ $index }}][extra_eggs]" value="{{ old("grades.$index.extra_eggs") }}" placeholder="0" class="form-control form-control-lg text-center fw-bold fs-4 bg-white" min="0">
                                </div>
                                <div class="col-4">
                                    <label class="form-label text-warning-emphasis small fw-bold mb-1 text-center d-block">Timbangan (KG)</label>
                                    <input type="number" step="0.1" inputmode="decimal" name="grades[{{ $index }}][weight_kg]" value="{{ old("grades.$index.weight_kg") }}" placeholder="0.0" class="form-control form-control-lg text-center fw-bold fs-4 bg-white border-2 border-warning" min="0">
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-muted bg-light rounded-3">
                            <i class="bi bi-info-circle fs-3 d-block mb-2 text-secondary"></i>
                            Belum ada kategori grade telur yang aktif. Silakan tambahkan melalui menu kategori grade.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- KARTU 3: Konsumsi Pakan -->
        <div class="card shadow-sm border-0 mb-4 border-start border-4 border-primary">
            <div class="card-header bg-primary-subtle py-3 px-4 border-0">
                <span class="fw-bold fs-6 text-primary-emphasis d-flex align-items-center gap-2">
                    <i class="bi bi-box-seam-fill text-primary fs-5"></i> 2. Konsumsi Pakan
                </span>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label text-secondary fs-6 fw-bold">Pilih Pakan Yang Dituang</label>
                    <select name="feed_stock_id" class="form-select form-select-lg fw-bold" required>
                        @foreach($feedStocks as $feed)
                            <option value="{{ $feed->id }}" {{ old('feed_stock_id') == $feed->id ? 'selected' : '' }}>
                                {{ $feed->feed_name }} (Sisa Stok: {{ number_format($feed->stock_kg, 0) }} kg)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold mb-1 text-center d-block">Jumlah Karung (@50kg)</label>
                        <input type="number" inputmode="numeric" name="feed_sacks" value="{{ old('feed_sacks') }}" placeholder="0" class="form-control form-control-lg text-center fw-bold fs-3" min="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold mb-1 text-center d-block">+ Sisa Ember (KG)</label>
                        <input type="number" step="0.5" inputmode="decimal" name="extra_feed_kg" value="{{ old('extra_feed_kg') }}" placeholder="0" class="form-control form-control-lg text-center fw-bold fs-3" min="0">
                    </div>
                </div>
            </div>
        </div>

        <!-- KARTU 4: Penyusutan Populasi -->
        <div class="card shadow-sm border-0 mb-4 border-start border-4 border-danger">
            <div class="card-header bg-danger-subtle py-3 px-4 border-0">
                <span class="fw-bold fs-6 text-danger-emphasis d-flex align-items-center gap-2">
                    <i class="bi bi-heartbreak-fill text-danger fs-5"></i> 3. Penyusutan Populasi
                </span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <label class="form-label text-danger small fw-bold text-center d-block mb-1">Ayam Mati (Ekor)</label>
                            <input type="number" inputmode="numeric" name="mortality" value="{{ old('mortality', 0) }}" placeholder="0" class="form-control form-control-lg text-center fw-bold fs-3 text-danger" min="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <label class="form-label text-danger small fw-bold text-center d-block mb-1">Afkir Sakit (Ekor)</label>
                            <input type="number" inputmode="numeric" name="cull" value="{{ old('cull', 0) }}" placeholder="0" class="form-control form-control-lg text-center fw-bold fs-3 text-danger" min="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Simpan -->
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold fs-5 shadow">
                <i class="bi bi-save2-fill me-2"></i> SIMPAN LAPORAN HARIAN KANDANG
            </button>
        </div>
    </form>
</div>
@endsection