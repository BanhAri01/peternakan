@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-5">

    <!-- Header Section -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
            <div>
                <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-3 py-1.5 text-uppercase fw-bold mb-2">
                    <i class="bi bi-cart-check-fill me-1"></i> Modul Komersial
                </span>
                <h2 class="fw-black text-dark mb-1">Penjualan Telur & Buku Piutang</h2>
                <p class="text-muted mb-0">Kelola transaksi grosir kiloan, krat borongan, eceran butir, dan kartu tempo bakul.</p>
            </div>
            @if(Route::has('grades.index'))
                <a href="{{ route('grades.index') }}" class="btn btn-outline-dark fw-bold">
                    <i class="bi bi-tags-fill me-1"></i> Atur Kategori Grade
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center mb-4 rounded-3 p-3 shadow-sm border-0 bg-success-subtle text-success-emphasis" role="alert">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div class="fw-bold">{{ session('success') }}</div>
        </div>
    @endif

    <!-- Sisa Stok Fisik Per Grade & Total Piutang -->
    <div class="row g-3 mb-4">
        @foreach($grades as $g)
            <div class="col-6 col-md-3">
                <div class="card shadow-sm border-0 bg-white h-100">
                    <div class="card-body p-3">
                        <span class="text-muted small text-uppercase fw-bold text-truncate d-block">{{ $g->name }}</span>
                        <div class="fs-2 fw-black text-dark my-1">
                            {{ number_format($g->stock_kg, 1) }} <span class="fs-6 fw-bold text-warning-emphasis">KG</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
        <div class="col-6 col-md-3">
            <div class="card shadow-sm border-0 border-start border-4 border-danger bg-danger-subtle h-100">
                <div class="card-body p-3">
                    <span class="text-danger small text-uppercase fw-bold d-block">Piutang Belum Lunas</span>
                    <div class="fs-2 fw-black text-danger my-1">
                        Rp {{ number_format($totalOutstandingDebt, 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Transaksi Penjualan -->
    <div class="card shadow-sm border-0 mb-4" x-data="{ unit: 'kg' }">
        <div class="card-header bg-white p-4 border-bottom">
            <h4 class="fw-black text-dark mb-1">Catat Transaksi Penjualan Keluar</h4>
            <p class="text-muted small mb-0">Pilih satuan, pembeli, dan status pembayaran (lunas / tempo).</p>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('sales.store') }}" method="POST">
                @csrf

                <!-- Satuan Jual Tombol Besar -->
                <div class="mb-4">
                    <label class="form-label text-secondary small text-uppercase fw-bold d-block">Metode / Satuan Penjualan</label>
                    <div class="btn-group w-100" role="group">
                        <button type="button" @click="unit = 'kg'" :class="unit === 'kg' ? 'btn-primary active' : 'btn-outline-secondary'" class="btn py-2.5 fw-bold">
                            Timbangan Kiloan (Kg)
                        </button>
                        <button type="button" @click="unit = 'krat'" :class="unit === 'krat' ? 'btn-primary active' : 'btn-outline-secondary'" class="btn py-2.5 fw-bold">
                            Per Krat / Tray (30 Butir)
                        </button>
                        <button type="button" @click="unit = 'butir'" :class="unit === 'butir' ? 'btn-primary active' : 'btn-outline-secondary'" class="btn py-2.5 fw-bold">
                            Eceran Butir
                        </button>
                    </div>
                    <input type="hidden" name="unit_type" :value="unit">
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Pilih Grade Telur</label>
                        <select name="egg_grade_id" class="form-select form-select-lg fw-bold" required>
                            @foreach($grades as $g)
                                <option value="{{ $g->id }}">{{ $g->name }} (Sisa: {{ number_format($g->stock_kg, 1) }} kg)</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Nama Pembeli / Bakul</label>
                        <input list="customer_list" name="customer_id" placeholder="Ketik atau pilih pembeli..." class="form-control form-control-lg fw-bold" required>
                        <datalist id="customer_list">
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-secondary small text-uppercase fw-bold">Tanggal Transaksi</label>
                        <input type="date" name="sale_date" value="{{ date('Y-m-d') }}" class="form-control form-control-lg fw-bold" required>
                    </div>
                </div>

                <!-- Input Angka Utama -->
                <div class="p-3 bg-light rounded-3 border mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-primary small text-uppercase fw-bold text-center d-block mb-1" x-text="unit === 'kg' ? 'Kuantitas (KG)' : (unit === 'krat' ? 'Kuantitas (Krat)' : 'Kuantitas (Butir)')"></label>
                            <input type="number" step="any" name="quantity_unit" placeholder="0" class="form-control form-control-lg text-center fw-black fs-3 bg-white" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-primary small text-uppercase fw-bold text-center d-block mb-1" x-text="unit === 'kg' ? 'Harga Jual / KG (Rp)' : (unit === 'krat' ? 'Harga Jual / Krat (Rp)' : 'Harga Jual / Butir (Rp)')"></label>
                            <input type="number" name="price_per_unit" placeholder="0" class="form-control form-control-lg text-center fw-black fs-3 bg-white" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary small text-uppercase fw-bold text-center d-block mb-1">Uang Muka / Cash Diterima (Rp)</label>
                            <input type="number" name="paid_amount" placeholder="Kosongkan jika tempo" class="form-control form-control-lg text-center fw-bold fs-4 bg-white">
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg py-3 fw-bold fs-5 shadow-sm">
                        <i class="bi bi-save2-fill me-2"></i> SIMPAN TRANSAKSI PENJUALAN
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Riwayat Penjualan & Status Piutang -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white p-4 border-bottom">
            <h4 class="fw-black text-dark mb-1">Riwayat Penjualan & Status Piutang</h4>
            <p class="text-muted small mb-0">Rincian faktur penjualan, pelunasan bertahap, dan cetak struk.</p>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Pembeli</th>
                            <th>Grade</th>
                            <th>Jumlah</th>
                            <th>Total Tagihan</th>
                            <th>Status</th>
                            <th>Sisa Piutang</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                            <tr>
                                <td class="text-muted">{{ $sale->sale_date }}</td>
                                <td class="fw-bold text-dark">{{ $sale->customer->name }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        {{ $sale->grade->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="fw-bold">
                                    {{ $sale->quantity_unit }} {{ $sale->unit_type }} <span class="text-muted small fw-normal">({{ $sale->weight_kg }} kg)</span>
                                </td>
                                <td class="fw-black text-dark fs-6">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge {{ $sale->payment_status === 'paid' ? 'bg-success' : ($sale->payment_status === 'partial' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                        {{ $sale->payment_status === 'paid' ? 'Lunas' : ($sale->payment_status === 'partial' ? 'Sebagian' : 'Tempo') }}
                                    </span>
                                </td>
                                <td class="fw-black {{ $sale->debt_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                    Rp {{ number_format($sale->debt_amount, 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex align-items-center gap-2">
                                        @if(Route::has('sales.print-receipt'))
                                            <a href="{{ route('sales.print-receipt', $sale->id) }}" target="_blank" class="btn btn-sm btn-outline-dark fw-bold">
                                                <i class="bi bi-printer me-1"></i> Cetak
                                            </a>
                                        @endif
                                        @if($sale->debt_amount > 0 && Route::has('sales.pay-debt'))
                                            <form action="{{ route('sales.pay-debt', $sale->id) }}" method="POST" class="d-inline-flex align-items-center gap-1">
                                                @csrf
                                                <input type="number" name="payment_add" placeholder="Nominal" max="{{ $sale->debt_amount }}" class="form-control form-control-sm fw-bold text-end" style="width: 110px;" required>
                                                <button type="submit" class="btn btn-sm btn-success fw-bold">Bayar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">Belum ada transaksi penjualan yang tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white p-3 border-top">
            {{ $sales->links() }}
        </div>
    </div>

</div>
@endsection