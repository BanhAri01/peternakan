@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-5">

    <!-- Header & Filter Periode Tanggal -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 text-uppercase fw-bold mb-2">
                    <i class="bi bi-wallet2 me-1"></i> Financial Audit
                </span>
                <h2 class="fw-black text-dark mb-1">Laba Rugi & Arus Kas Riil</h2>
                <p class="text-muted mb-0">Perbandingan antara laba di atas kertas (akrual) vs kas nyata yang telah diterima.</p>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <form method="GET" action="{{ route('financial.index') }}" class="d-flex align-items-center gap-2">
                    <input type="date" name="start_date" value="{{ $startDate }}" class="form-control fw-bold">
                    <span class="text-muted fw-bold">s/d</span>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="form-control fw-bold">
                    <button type="submit" class="btn btn-dark fw-bold">
                        <i class="bi bi-funnel-fill me-1"></i> Filter
                    </button>
                </form>

                @if(Route::has('reports.monthly-pdf'))
                    <a href="{{ route('reports.monthly-pdf', ['month' => date('Y-m', strtotime($startDate))]) }}" class="btn btn-primary fw-bold">
                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Ekspor PDF
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- 2 Kartu Utama: Laba Bersih vs Real Cash -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 border-top border-4 border-success h-100">
                <div class="card-body p-4">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 text-uppercase fw-bold">
                        Laba Bersih Operasional (P&L)
                    </span>
                    <div class="fs-1 fw-black my-3 {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp {{ number_format($netProfit, 0, ',', '.') }}
                    </div>
                    <p class="text-muted small mb-0">
                        Penjualan telur dikurangi biaya ransum pakan terpakai (Rp {{ number_format($feedConsumedCost, 0, ',', '.') }}), kulakan telur luar, dan biaya operasional.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 border-top border-4 border-primary h-100">
                <div class="card-body p-4">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 text-uppercase fw-bold">
                        Arus Kas Masuk Bersih (Real Cash)
                    </span>
                    <div class="fs-1 fw-black my-3 {{ $netCashFlow >= 0 ? 'text-primary' : 'text-warning' }}">
                        Rp {{ number_format($netCashFlow, 0, ',', '.') }}
                    </div>
                    <p class="text-muted small mb-0">
                        Uang kas yang <strong>sudah diterima lunas</strong> (Rp {{ number_format($totalCashIn, 0, ',', '.') }}) dikurangi kas belanja pakan gudang & operasional.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Rincian Pemasukan vs Pengeluaran -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white p-4 border-bottom">
                    <h5 class="fw-black text-success mb-0 text-uppercase">Pemasukan Penjualan Periode Ini</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Total Nilai Faktur Penjualan:</span>
                        <strong class="text-dark">Rp {{ number_format($totalSalesRevenue, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Sudah Diterima Tunai / Transfer:</span>
                        <strong class="text-success fs-6">Rp {{ number_format($totalCashIn, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">Menggantung Jadi Piutang Baru:</span>
                        <strong class="text-danger fs-6">Rp {{ number_format($totalNewDebt, 0, ',', '.') }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white p-4 border-bottom">
                    <h5 class="fw-black text-danger mb-0 text-uppercase">Pengeluaran Kas Nyata Periode Ini</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Belanja Restock Pakan:</span>
                        <strong class="text-dark">Rp {{ number_format($cashOutFeedPurchase, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Belanja Kulakan Telur Luar:</span>
                        <strong class="text-dark">Rp {{ number_format($cashOutEggPurchase, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Biaya Operasional (Gaji, Listrik, dll):</span>
                        <strong class="text-dark">Rp {{ number_format($operationalCost, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2.5 px-3 bg-light rounded-3 mt-2">
                        <span class="fw-bold text-dark">Total Kas Keluar:</span>
                        <strong class="text-danger fs-5">Rp {{ number_format($totalCashOut, 0, ',', '.') }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analisis Umur Piutang (Aging Receivables) -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white p-4 border-bottom">
            <h4 class="fw-black text-dark mb-1">Analisis Umur Piutang Bakul (Aging Receivables)</h4>
            <p class="text-muted small mb-0">Pantau piutang belum lunas untuk mencegah modal kerja macet di pedagang.</p>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="p-3 bg-success-subtle border border-success-subtle rounded-3">
                        <span class="text-uppercase fw-bold text-success small">{{ $agingBuckets['current']['label'] }}</span>
                        <div class="fs-2 fw-black text-success my-1">
                            Rp {{ number_format($agingBuckets['current']['total'], 0, ',', '.') }}
                        </div>
                        <span class="small text-muted">{{ $agingBuckets['current']['count'] }} nota tagihan</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-warning-subtle border border-warning-subtle rounded-3">
                        <span class="text-uppercase fw-bold text-warning-emphasis small">{{ $agingBuckets['warning']['label'] }}</span>
                        <div class="fs-2 fw-black text-warning-emphasis my-1">
                            Rp {{ number_format($agingBuckets['warning']['total'], 0, ',', '.') }}
                        </div>
                        <span class="small text-muted">{{ $agingBuckets['warning']['count'] }} nota tagihan (Harus diingatkan)</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-danger-subtle border border-danger-subtle rounded-3">
                        <span class="text-uppercase fw-bold text-danger small">{{ $agingBuckets['critical']['label'] }}</span>
                        <div class="fs-2 fw-black text-danger my-1">
                            Rp {{ number_format($agingBuckets['critical']['total'], 0, ',', '.') }}
                        </div>
                        <span class="small text-muted">{{ $agingBuckets['critical']['count'] }} nota tagihan (Prioritas penagihan!)</span>
                    </div>
                </div>
            </div>

            <!-- Tabel Daftar Piutang & Tombol WhatsApp -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Bakul</th>
                            <th>Total Nota</th>
                            <th>Sisa Piutang</th>
                            <th>Umur</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agedSalesList as $item)
                            <tr>
                                <td class="text-muted">{{ $item->sale_date }}</td>
                                <td class="fw-bold text-dark">{{ $item->customer->name }}</td>
                                <td>Rp {{ number_format($item->total_amount, 0, ',', '.') }}</td>
                                <td class="fw-black text-danger fs-6">Rp {{ number_format($item->debt_amount, 0, ',', '.') }}</td>
                                <td>
                                    <span class="badge {{ $item->age_status === 'current' ? 'bg-success' : ($item->age_status === 'warning' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                        {{ $item->days_past }} Hari Lalu
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if($item->customer->phone)
                                        @php
                                            $cleanPhone = preg_replace('/[^0-9]/', '', $item->customer->phone);
                                            if(str_starts_with($cleanPhone, '0')) {
                                                $cleanPhone = '62' . substr($cleanPhone, 1);
                                            }
                                            $msg = urlencode("Halo Pak/Bu " . $item->customer->name . ", kami menginformasikan sisa tagihan tempo telur tanggal " . $item->sale_date . " sebesar Rp " . number_format($item->debt_amount, 0, ',', '.') . ". Mohon konfirmasi jadwal pembayarannya. Terima kasih.");
                                        @endphp
                                        <a href="https://wa.me/{{ $cleanPhone }}?text={{ $msg }}" target="_blank" class="btn btn-sm btn-success fw-bold">
                                            <i class="bi bi-whatsapp me-1"></i> Tagih WhatsApp
                                        </a>
                                    @else
                                        <span class="text-muted small fst-italic">Tanpa Nomor HP</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-check-circle fs-3 d-block mb-1 text-success"></i>
                                    Semua piutang bakul telah lunas.
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