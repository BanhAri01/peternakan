@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-5">

    <!-- Header Section -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <div>
                <span class="badge bg-teal-subtle text-success border border-success-subtle px-3 py-1.5 text-uppercase fw-bold mb-2">
                    <i class="bi bi-shield-plus me-1"></i> Kesehatan & Biosekuriti
                </span>
                <h2 class="fw-black text-dark mb-1">Jadwal & Riwayat Vaksinasi</h2>
                <p class="text-muted mb-0">Pencatatan pemberian vaksin, metode aplikasi, dosis, umur ayam, dan biaya medis kandang.</p>
            </div>
            <a href="{{ route('vaccinations.create') }}" class="btn btn-primary btn-lg fw-bold">
                <i class="bi bi-plus-circle-fill me-2"></i> Tambah Vaksinasi Baru
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center mb-4 rounded-3 p-3 shadow-sm border-0 bg-success-subtle text-success-emphasis" role="alert">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div class="fw-bold">{{ session('success') }}</div>
        </div>
    @endif

    <!-- Kartu Ringkasan -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 border-top border-4 border-primary h-100">
                <div class="card-body p-4">
                    <span class="text-muted small text-uppercase fw-bold">Total Riwayat Aplikasi</span>
                    <div class="fs-1 fw-black text-dark my-2">
                        {{ $vaccinations->total() }} <span class="fs-5 fw-bold text-muted">Kali Tindakan</span>
                    </div>
                    <span class="text-muted small">Tercatat di seluruh kandang aktif</span>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm border-0 border-top border-4 border-success h-100">
                <div class="card-body p-4">
                    <span class="text-muted small text-uppercase fw-bold">Total Pengeluaran Vaksin</span>
                    <div class="fs-1 fw-black text-success my-2">
                        Rp {{ number_format($totalVaccineCost, 0, ',', '.') }}
                    </div>
                    <span class="text-muted small">Akumulasi biaya obat & jasa vaksinasi</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Data Vaksinasi -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
            <h4 class="fw-black text-dark mb-0">Daftar Pelaksanaan Vaksin</h4>
            <span class="badge bg-dark fs-6 px-3 py-2 rounded-pill">{{ $vaccinations->total() }} Catatan</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Tanggal</th>
                            <th>Kandang</th>
                            <th>Umur Ayam</th>
                            <th>Nama & Jenis Vaksin</th>
                            <th>Metode & Dosis</th>
                            <th>Petugas</th>
                            <th>Biaya</th>
                            <th class="text-end pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vaccinations as $item)
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark">{{ $item->vaccination_date->format('d M Y') }}</span>
                                    <span class="text-muted small d-block">{{ $item->vaccination_date->diffForHumans() }}</span>
                                </td>
                                <td>
                                    <span class="fs-6 fw-bold text-dark">{{ $item->coop->name ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 fs-6">
                                        {{ $item->age_weeks }} Minggu
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-black text-dark fs-6">{{ $item->vaccine_name }}</div>
                                    @if($item->target_disease)
                                        <span class="text-muted small">Target: {{ $item->target_disease }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border me-1">{{ $item->method }}</span>
                                    <span class="small text-muted">{{ $item->dosage }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark">{{ $item->officer }}</span>
                                </td>
                                <td>
                                    <span class="fw-black text-success fs-6">
                                        Rp {{ number_format($item->cost, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-2">
                                        <a href="{{ route('vaccinations.edit', $item->id) }}" class="btn btn-sm btn-outline-primary fw-bold">
                                            <i class="bi bi-pencil-square me-1"></i> Edit
                                        </a>
                                        <form action="{{ route('vaccinations.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus catatan vaksin {{ $item->vaccine_name }} di {{ $item->coop->name }}?')">
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
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-shield-x fs-2 text-secondary d-block mb-2"></i>
                                    Belum ada catatan vaksinasi yang tersimpan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white p-3 border-top">
            {{ $vaccinations->links() }}
        </div>
    </div>

</div>
@endsection