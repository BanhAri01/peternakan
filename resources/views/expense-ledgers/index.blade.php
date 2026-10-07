@extends('layouts.app')

@section('content')

<div class="container-fluid px-lg-5">


<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 text-uppercase fw-bold mb-2">
                <i class="bi bi-graph-up-arrow me-1"></i> Financial Dashboard & Buku Kas
            </span>
            <h2 class="fw-black text-dark mb-1">Buku Keuangan & Laba Rugi Farm</h2>
            <p class="text-muted mb-0">Ringkasan analitik penjualan, pengeluaran kas, beban operasional, vaksinasi, dan arus kas riil.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('expenses.create') }}" class="btn btn-danger btn-lg fw-bold">
                <i class="bi bi-dash-circle-fill me-2"></i> Catat Pengeluaran Baru
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center mb-4 rounded-3 p-3 shadow-sm border-0 bg-success-subtle text-success-emphasis" role="alert">
        <i class="bi bi-check-circle-fill fs-4 me-3"></i>
        <div class="fw-bold">{{ session('success') }}</div>
    </div>
@endif

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-3 p-lg-4">
        <form method="GET" action="{{ route('expenses.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label text-secondary small text-uppercase fw-bold mb-1">Mulai Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="form-control form-control-lg fw-bold">
            </div>
            <div class="col-md-3">
                <label class="form-label text-secondary small text-uppercase fw-bold mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="form-control form-control-lg fw-bold">
            </div>
            <div class="col-md-4">
                <label class="form-label text-secondary small text-uppercase fw-bold mb-1">Filter Kategori Buku Kas</label>
                <select name="category" class="form-select form-select-lg fw-bold">
                    <option value="">-- Semua Kategori Pengeluaran --</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                            {{ $cat }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-lg fw-bold w-100">
                    <i class="bi bi-funnel-fill me-1"></i> Terapkan
                </button>
                <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary btn-lg fw-bold" title="Reset Periode">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- METRIK UTAMA -->
<div class="row g-3 mb-4">

    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm border-0 border-top border-4 border-primary h-100">
            <div class="card-body p-4">
                <span class="text-muted small text-uppercase fw-bold d-block">Omzet Penjualan Telur</span>
                <div class="fs-2 fw-black text-primary my-2">
                    Rp {{ number_format($totalSalesRevenue, 0, ',', '.') }}
                </div>
                <span class="text-muted small">
                    Kas Diterima:
                    <strong>Rp {{ number_format($totalCashIn, 0, ',', '.') }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm border-0 border-top border-4 border-danger h-100">
            <div class="card-body p-4">
                <span class="text-muted small text-uppercase fw-bold d-block">Total Beban</span>
                <div class="fs-2 fw-black text-danger my-2">
                    Rp {{ number_format($totalExpense + $vaccineCost, 0, ',', '.') }}
                </div>
                <span class="text-muted small">
                    Pengeluaran Kas:
                    <strong>Rp {{ number_format($totalExpense, 0, ',', '.') }}</strong>
                </span>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm border-0 border-top border-4 border-warning h-100">
            <div class="card-body p-4">
                <span class="text-muted small text-uppercase fw-bold d-block">Biaya Vaksinasi</span>
                <div class="fs-2 fw-black text-warning my-2">
                    Rp {{ number_format($vaccineCost, 0, ',', '.') }}
                </div>
                <span class="text-muted small">
                    Total biaya vaksin periode ini
                </span>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm border-0 border-top border-4 {{ $netProfit >= 0 ? 'border-success' : 'border-danger' }} h-100">
            <div class="card-body p-4">
                <span class="text-muted small text-uppercase fw-bold d-block">Estimasi Laba Bersih</span>
                <div class="fs-2 fw-black {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }} my-2">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </div>
                <span class="text-muted small">
                    {{ $netProfit >= 0 ? 'Surplus Penjualan Telur' : 'Defisit Operasional Periode Ini' }}
                </span>
            </div>
        </div>
    </div>

</div>

<!-- RINGKASAN BIAYA -->
<div class="row g-3 mb-4">

    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <span class="text-muted small text-uppercase fw-bold">Beban Pakan</span>
                <h4 class="fw-black text-dark mt-2 mb-0">
                    Rp {{ number_format($feedConsumedCost, 0, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <span class="text-muted small text-uppercase fw-bold">Pembelian Pakan</span>
                <h4 class="fw-black text-dark mt-2 mb-0">
                    Rp {{ number_format($cashOutFeedPurchase, 0, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <span class="text-muted small text-uppercase fw-bold">Pembelian Telur</span>
                <h4 class="fw-black text-dark mt-2 mb-0">
                    Rp {{ number_format($cashOutEggPurchase, 0, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-4">
                <span class="text-muted small text-uppercase fw-bold">Arus Kas Bersih</span>
                <h4 class="fw-black {{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }} mt-2 mb-0">
                    Rp {{ number_format($netCashFlow, 0, ',', '.') }}
                </h4>
            </div>
        </div>
    </div>

</div>

<!-- GRAFIK -->
<div class="row g-4 mb-4">

    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-black text-dark mb-0">Tren Finansial Harian</h5>
                    <p class="text-muted small mb-0">
                        Penjualan, pengeluaran, dan laba bersih termasuk biaya vaksinasi.
                    </p>
                </div>
                <span class="badge bg-light text-dark border px-3 py-2">
                    {{ Carbon\Carbon::parse($startDate)->format('d M') }}
                    -
                    {{ Carbon\Carbon::parse($endDate)->format('d M Y') }}
                </span>
            </div>

            <div class="card-body p-4">
                <div style="height: 320px;">
                    <canvas id="financialTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white p-4 border-bottom">
                <h5 class="fw-black text-dark mb-0">Komposisi Pengeluaran</h5>
                <p class="text-muted small mb-0">
                    Termasuk biaya vaksinasi.
                </p>
            </div>

            <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center">
                <div style="width: 100%; height: 270px;">
                    <canvas id="categoryDonutChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- INFO PIUTANG -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <div class="row align-items-center">

            <div class="col-md-4">
                <span class="text-muted small text-uppercase fw-bold d-block">
                    Sisa Piutang Bakul
                </span>
                <div class="fs-3 fw-black text-warning mt-1">
                    Rp {{ number_format($totalAllDebt, 0, ',', '.') }}
                </div>
            </div>

            <div class="col-md-4">
                <span class="text-muted small text-uppercase fw-bold d-block">
                    Piutang Baru Periode
                </span>
                <div class="fs-3 fw-black text-danger mt-1">
                    Rp {{ number_format($totalNewDebt, 0, ',', '.') }}
                </div>
            </div>

            <div class="col-md-4">
                <span class="text-muted small text-uppercase fw-bold d-block">
                    Arus Kas Bersih
                </span>
                <div class="fs-3 fw-black {{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }} mt-1">
                    Rp {{ number_format($netCashFlow, 0, ',', '.') }}
                </div>
            </div>

        </div>
    </div>
</div>

<!-- TABEL PENGELUARAN -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-black text-dark mb-0">Buku Catatan Pengeluaran Kas</h4>
            <p class="text-muted small mb-0">
                Transaksi yang tercatat melalui modul buku kas.
            </p>
        </div>

        <span class="badge bg-dark fs-6 px-3 py-2 rounded-pill">
            {{ $expenses->total() }} Baris Data
        </span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Tanggal</th>
                        <th>Uraian / Nama Barang</th>
                        <th>Kategori & Jenis</th>
                        <th>Supplier / Penerima</th>
                        <th>Qty</th>
                        <th>Harga Satuan</th>
                        <th>Total Nominal</th>
                        <th>Metode & PIC</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($expenses as $item)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-dark">
                                    {{ \Carbon\Carbon::parse($item->transaction_date)->format('d/m/Y') }}
                                </span>
                            </td>

                            <td>
                                <div class="fs-6 fw-bold text-dark">
                                    {{ $item->item_name }}
                                </div>

                                @if($item->notes)
                                    <span class="text-muted small fst-italic">
                                        {{ Str::limit($item->notes, 35) }}
                                    </span>
                                @endif
                            </td>

                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $item->category }}
                                </span>

                                <span class="text-muted small d-block">
                                    {{ $item->expense_type }}
                                </span>
                            </td>

                            <td>
                                <span class="fw-semibold text-dark">
                                    {{ $item->supplier ?: '-' }}
                                </span>
                            </td>

                            <td>
                                <span class="fw-bold">
                                    {{ number_format($item->quantity, 1) }}
                                </span>

                                <span class="text-muted small">
                                    {{ $item->unit }}
                                </span>
                            </td>

                            <td>
                                Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                            </td>

                            <td>
                                <span class="fs-6 fw-black text-danger">
                                    Rp {{ number_format($item->total_amount, 0, ',', '.') }}
                                </span>
                            </td>

                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border">
                                    {{ $item->payment_method }}
                                </span>

                                <span class="text-muted small d-block">
                                    PIC: {{ $item->officer }}
                                </span>
                            </td>

                            <td class="text-end pe-4">
                                <div class="d-inline-flex gap-2">
                                    <a href="{{ route('expenses.edit', $item->id) }}" class="btn btn-sm btn-outline-primary fw-bold">
                                        <i class="bi bi-pencil-square me-1"></i> Edit
                                    </a>

                                    <form action="{{ route('expenses.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus catatan pengeluaran {{ $item->item_name }}?')">
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
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-wallet2 fs-2 text-secondary d-block mb-2"></i>
                                Belum ada transaksi pengeluaran yang tercatat pada filter aktif.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer bg-white p-3 border-top">
        {{ $expenses->links() }}
    </div>
</div>
```

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const trendElement = document.getElementById('financialTrendChart');

    if (trendElement) {
        new Chart(trendElement.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Penjualan Telur (Rp)',
                        data: @json($chartSalesData),
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        borderWidth: 3,
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Beban Pengeluaran + Vaksinasi (Rp)',
                        data: @json($chartExpenseData),
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.05)',
                        borderWidth: 2,
                        tension: 0.3,
                        borderDash: [5, 5]
                    },
                    {
                        label: 'Laba Bersih Harian (Rp)',
                        data: @json($chartProfitData),
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22, 163, 74, 0.15)',
                        borderWidth: 2.5,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                family: 'Plus Jakarta Sans',
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.dataset.label + ': Rp ' +
                                    context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return 'Rp ' +
                                    (value / 1000).toLocaleString('id-ID') +
                                    'k';
                            }
                        }
                    }
                }
            }
        });
    }

    const donutElement = document.getElementById('categoryDonutChart');

    const catLabels = @json($categoryLabels);
    const catData = @json($categoryData);

    if (donutElement && catData.length > 0) {
        new Chart(donutElement.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catData,
                    backgroundColor: [
                        '#2563eb',
                        '#16a34a',
                        '#d97706',
                        '#dc2626',
                        '#8b5cf6',
                        '#06b6d4',
                        '#ec4899',
                        '#64748b',
                        '#f59e0b'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: {
                                size: 11,
                                family: 'Plus Jakarta Sans',
                                weight: '600'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.label + ': Rp ' +
                                    context.parsed.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }
});
</script>

@endsection
