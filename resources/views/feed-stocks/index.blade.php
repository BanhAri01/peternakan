@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-5">

    <!-- Header Section -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 text-uppercase fw-bold mb-2">
                    <i class="bi bi-box-seam me-1"></i> Inventaris Gudang
                </span>
                <h2 class="fw-black text-dark mb-1">Daftar Stok Bahan Pakan</h2>
                <p class="text-muted mb-0">Master data pakan ransum harian, sisa fisik gudang, dan harga modal beli rata-rata.</p>
            </div>
            <a href="{{ route('feed-stocks.create') }}" class="btn btn-primary btn-lg fw-bold">
                <i class="bi bi-plus-circle-fill me-2"></i> Tambah Pakan Baru
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center mb-4 rounded-3 p-3 shadow-sm border-0 bg-success-subtle text-success-emphasis" role="alert">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div class="fw-bold">{{ session('success') }}</div>
        </div>
    @endif

    <!-- Kartu Ringkasan Stok Gudang -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 border-top border-4 border-primary h-100">
                <div class="card-body p-4">
                    <span class="text-muted small text-uppercase fw-bold">Total Fisik Pakan Gudang</span>
                    <div class="fs-1 fw-black text-dark my-2">
                        {{ number_format($totalStockKg, 1) }} <span class="fs-5 fw-bold text-primary">KG</span>
                    </div>
                    <span class="text-muted small">Setara kurang lebih <strong>{{ number_format($totalStockKg / 50, 1) }} karung</strong> (@50kg)</span>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm border-0 border-top border-4 border-success h-100">
                <div class="card-body p-4">
                    <span class="text-muted small text-uppercase fw-bold">Estimasi Nilai Aset Pakan</span>
                    <div class="fs-1 fw-black text-success my-2">
                        Rp {{ number_format($totalStockValue, 0, ',', '.') }}
                    </div>
                    <span class="text-muted small">Berdasarkan perkalian sisa stok fisik & harga beli per kg</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Pakan -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
            <h4 class="fw-black text-dark mb-0">Rincian Stok Bahan Pakan</h4>
            <span class="badge bg-dark fs-6 px-3 py-2 rounded-pill">{{ $feedStocks->total() }} Jenis Pakan</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Nama Bahan Pakan</th>
                            <th class="text-center">Sisa Stok Fisik</th>
                            <th class="text-center">Estimasi Karung</th>
                            <th>Harga Modal / KG</th>
                            <th>Total Nilai Stok</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($feedStocks as $feed)
                            <tr>
                                <td class="ps-4">
                                    <div class="fs-5 fw-bold text-dark">{{ $feed->feed_name }}</div>
                                    <span class="text-muted small">ID: #{{ $feed->id }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="fs-5 fw-black {{ $feed->stock_kg <= 200 ? 'text-danger' : 'text-dark' }}">
                                        {{ number_format($feed->stock_kg, 1) }}
                                    </span>
                                    <span class="text-muted fw-bold">kg</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border fs-6 px-2.5 py-1">
                                        {{ number_format($feed->stock_kg / 50, 1) }} sak
                                    </span>
                                </td>
                                <td class="fw-bold text-dark fs-6">
                                    Rp {{ number_format($feed->cost_per_kg, 0, ',', '.') }}
                                </td>
                                <td class="fw-black text-success fs-6">
                                    Rp {{ number_format($feed->stock_kg * $feed->cost_per_kg, 0, ',', '.') }}
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('feed-stocks.edit', $feed->id) }}" class="btn btn-sm btn-outline-primary fw-bold">
                                            <i class="bi bi-pencil-square me-1"></i> Edit
                                        </a>
                                        <form action="{{ route('feed-stocks.destroy', $feed->id) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus bahan pakan {{ $feed->feed_name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger fw-bold">
                                                <i class="bi bi-trash3 me-1"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-box-seam fs-2 text-secondary d-block mb-2"></i>
                                    Belum ada jenis pakan yang didaftarkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white p-3 border-top">
            {{ $feedStocks->links() }}
        </div>
    </div>

</div>
@endsection