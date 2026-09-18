@extends('layouts.app')

@section('content')
<div class="container-fluid px-lg-5">

    <!-- Header & Filter Tanggal -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 text-uppercase fw-bold mb-2">
                    <i class="bi bi-graph-up-arrow me-1"></i> Analisis Harian Farm
                </span>
                <h2 class="fw-black text-dark mb-1">Ringkasan Operasional & Finansial</h2>
                <p class="text-muted mb-0">Evaluasi batas impas HPP, rasio konversi pakan (FCR), dan performa tiap kandang.</p>
            </div>

            <!-- Tombol Pintasan Tanggal -->
            <form method="GET" action="{{ route('owner.dashboard') }}" class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('owner.dashboard', ['date' => date('Y-m-d')]) }}" class="btn {{ $selectedDate === date('Y-m-d') ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Hari Ini
                </a>
                <a href="{{ route('owner.dashboard', ['date' => date('Y-m-d', strtotime('-1 day'))]) }}" class="btn {{ $selectedDate === date('Y-m-d', strtotime('-1 day')) ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Kemarin
                </a>
                <div class="input-group" style="width: 220px;">
                    <span class="input-group-text bg-white fw-bold text-muted"><i class="bi bi-calendar3"></i></span>
                    <input type="date" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()" class="form-control fw-bold">
                </div>
            </form>
        </div>
    </div>

    <!-- 4 KPI Cards Utama -->
    <div class="row g-3 mb-4">
        <!-- 1. HPP Telur -->
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 border-top border-4 border-success h-100">
                <div class="card-body p-4">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 text-uppercase fw-bold">
                        Modal Bersih (HPP)
                    </span>
                    <div class="my-3">
                        <span class="fs-1 fw-black text-dark">Rp {{ number_format($hppPerKg, 0, ',', '.') }}</span>
                        <span class="text-muted fw-bold fs-6">/ kg</span>
                    </div>
                    <div class="pt-2 border-top d-flex justify-content-between text-muted small">
                        <span>Batas Impas Jual:</span>
                        <span class="fw-bold text-success">Pakan + Biaya Ops</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Panen Telur -->
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 border-top border-4 border-warning h-100">
                <div class="card-body p-4">
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2.5 py-1 text-uppercase fw-bold">
                        Total Panen Telur
                    </span>
                    <div class="my-3">
                        <span class="fs-1 fw-black text-dark">{{ number_format($totalEggKg, 1) }}</span>
                        <span class="text-warning-emphasis fw-bold fs-5">KG</span>
                    </div>
                    <div class="pt-2 border-top d-flex justify-content-between text-muted small">
                        <span>Total Butir:</span>
                        <span class="fw-bold text-dark">{{ number_format($totalEggCount) }} <span class="fw-normal">({{ $totalEggTrays }} rak + {{ $totalEggExtra }} btr)</span></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Rata-rata HDP & FCR -->
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 border-top border-4 border-primary h-100">
                <div class="card-body p-4">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 text-uppercase fw-bold">
                        Performa Farm (HDP)
                    </span>
                    <div class="my-3">
                        <span class="fs-1 fw-black {{ $overallHdp >= 75 ? 'text-primary' : 'text-warning' }}">{{ $overallHdp }}%</span>
                        <span class="text-muted fw-bold fs-6">Rata-rata</span>
                    </div>
                    <div class="pt-2 border-top d-flex justify-content-between text-muted small">
                        <span>FCR: <strong class="text-dark">{{ $overallFcr > 0 ? $overallFcr : '-' }}</strong></span>
                        <span>Pakan: <strong class="text-dark">{{ number_format($totalFeedConsumedKg, 0) }} kg</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Mortalitas -->
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 border-top border-4 border-danger h-100">
                <div class="card-body p-4">
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 text-uppercase fw-bold">
                        Penyusutan Ayam
                    </span>
                    <div class="my-3">
                        <span class="fs-1 fw-black {{ ($totalMortality + $totalCull) > 5 ? 'text-danger' : 'text-dark' }}">{{ $totalMortality + $totalCull }}</span>
                        <span class="text-muted fw-bold fs-6">ekor</span>
                    </div>
                    <div class="pt-2 border-top d-flex justify-content-between text-muted small">
                        <span>Mati: <strong class="text-dark">{{ $totalMortality }}</strong></span>
                        <span>Afkir Sakit: <strong class="text-dark">{{ $totalCull }}</strong></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rekapitulasi Sortir Grade -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white p-4 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
            <div>
                <h4 class="fw-black text-dark mb-1">Hasil Panen Berdasarkan Grade (Hari Ini)</h4>
                <p class="text-muted small mb-0">Akumulasi seluruh kandang berdasarkan kategori sortir aktif.</p>
            </div>
            @if(Route::has('grades.index'))
                <a href="{{ route('grades.index') }}" class="btn btn-outline-primary btn-sm fw-bold">
                    <i class="bi bi-gear-fill me-1"></i> Kelola Kategori Grade
                </a>
            @endif
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                @forelse($gradeBreakdown as $gb)
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="p-3 bg-light rounded-3 border border-warning-subtle">
                            <span class="text-muted text-uppercase fw-bold small d-block">{{ $gb['name'] }}</span>
                            <div class="fs-2 fw-black text-dark my-1">
                                {{ number_format($gb['weight_kg'], 1) }} <span class="fs-6 fw-bold text-warning-emphasis">KG</span>
                            </div>
                            <div class="small text-muted">
                                Kontribusi: <strong>{{ $totalEggKg > 0 ? round(($gb['weight_kg'] / $totalEggKg) * 100, 1) : 0 }}%</strong>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="p-4 text-center text-muted bg-light rounded-3">
                            Belum ada data grading telur pada tanggal {{ date('d/m/Y', strtotime($selectedDate)) }}.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Bagian Grafik Tren & Proporsi -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white p-4 border-bottom">
                    <h5 class="fw-black text-dark mb-1">Tren Produksi & Konsumsi Ransum Pakan (7 Hari)</h5>
                    <p class="text-muted small mb-0">Perbandingan kilogram panen telur vs kilogram pakan.</p>
                </div>
                <div class="card-body p-4">
                    <div style="height: 300px;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white p-4 border-bottom">
                    <h5 class="fw-black text-dark mb-1">Proporsi Grade Telur</h5>
                    <p class="text-muted small mb-0">Persentase sortir panen hari ini.</p>
                </div>
                <div class="card-body p-4 d-flex align-items-center justify-content-center">
                    <div style="height: 280px; width: 100%;">
                        @if(count($gradeBreakdown) > 0)
                            <canvas id="gradeChart"></canvas>
                        @else
                            <div class="text-center text-muted small py-5">Belum ada data grading pada tanggal ini.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rincian Tiap Kandang -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-black text-dark mb-1">Performa Teknis & Finansial Tiap Kandang</h4>
                <p class="text-muted small mb-0">Sortir grade, efisiensi ransum pakan, dan kontribusi laba harian.</p>
            </div>
            <span class="badge bg-dark fs-6 px-3 py-2 rounded-pill">{{ count($coopDetails) }} Kandang Aktif</span>
        </div>
        <div class="card-body p-4">
            <div class="d-flex flex-column gap-4">
                @forelse($coopDetails as $item)
                    <div class="card border-2 shadow-none {{ $item['statusColor'] === 'rose' ? 'border-danger' : ($item['statusColor'] === 'amber' ? 'border-warning' : 'border-secondary-subtle') }}">
                        <div class="card-header bg-light py-3 px-4 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                            <div>
                                <h5 class="fw-black text-dark mb-0 d-inline-block me-2">{{ $item['coop']->name }}</h5>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Umur: {{ $item['age_weeks'] }} Minggu</span>
                                <div class="small fw-bold {{ $item['statusColor'] === 'rose' ? 'text-danger' : ($item['statusColor'] === 'amber' ? 'text-warning' : 'text-muted') }}">
                                    Status: {{ $item['statusNote'] }}
                                </div>
                            </div>
                            <div class="d-flex gap-4">
                                <div>
                                    <span class="text-muted small d-block">Populasi Aktif</span>
                                    <strong class="fs-5 text-dark">{{ number_format($item['coop']->current_population) }} ekor</strong>
                                </div>
                                @if($item['log'])
                                    <div>
                                        <span class="text-muted small d-block">Penyusutan</span>
                                        <strong class="fs-5 {{ ($item['log']->mortality + $item['log']->cull) > 0 ? 'text-danger' : 'text-dark' }}">
                                            {{ $item['log']->mortality + $item['log']->cull }} ekor
                                        </strong>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($item['log'])
                            <div class="card-body p-4">
                                <div class="row g-4 divide-lg-start">
                                    <!-- Kolom 1: Telur -->
                                    <div class="col-lg-4">
                                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle">Hasil Telur</span>
                                            <div>
                                                <span class="fs-4 fw-black text-dark">{{ $item['log']->eggs_total_kg }} kg</span>
                                                <span class="badge bg-primary ms-1">{{ $item['log']->hdp_percentage }}% HDP</span>
                                            </div>
                                        </div>
                                        <div class="p-2.5 bg-light rounded-3 mb-3 small">
                                            Total Butir: <strong class="text-dark">{{ number_format($item['log']->eggs_total_count) }}</strong>
                                            <span class="text-muted d-block mt-0.5">({{ floor($item['log']->eggs_total_count / 30) }} rak + {{ $item['log']->eggs_total_count % 30 }} btr)</span>
                                        </div>
                                        <div class="small">
                                            <span class="fw-bold text-muted text-uppercase d-block mb-1">Rincian Grade:</span>
                                            @forelse($item['log']->grades as $g)
                                                <div class="d-flex justify-content-between py-1 border-bottom">
                                                    <span>{{ $g->grade->name ?? 'Grade' }}</span>
                                                    <strong class="text-dark">{{ $g->trays_count }} rak + {{ $g->extra_eggs }} btr ({{ $g->weight_kg }} kg)</strong>
                                                </div>
                                            @empty
                                                <span class="text-muted fst-italic">Belum ada rincian grade.</span>
                                            @endforelse
                                        </div>
                                    </div>

                                    <!-- Kolom 2: Pakan -->
                                    <div class="col-lg-4 border-start border-light-subtle">
                                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Konsumsi Pakan</span>
                                            <span class="fs-4 fw-black text-dark">{{ $item['log']->feed_consumed_kg }} kg</span>
                                        </div>
                                        <div class="d-flex flex-column gap-2 small">
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span class="text-muted">Jenis Ransum:</span>
                                                <strong class="text-dark">{{ $item['log']->feedStock->feed_name ?? 'Pakan Campur' }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span class="text-muted">Takaran Rata-rata:</span>
                                                <strong class="text-dark">{{ $item['feedGramPerHen'] }} gr / ekor</strong>
                                            </div>
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span class="text-muted">FCR:</span>
                                                <strong class="{{ $item['log']->fcr > 2.3 ? 'text-warning' : 'text-success' }} fs-6">{{ $item['log']->fcr ?? '-' }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between py-1">
                                                <span class="text-muted">Biaya Pakan:</span>
                                                <strong class="text-dark">Rp {{ number_format($item['feedCostDaily'], 0, ',', '.') }}</strong>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Kolom 3: Margin Finansial -->
                                    <div class="col-lg-4 border-start border-light-subtle">
                                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                                            <span class="text-muted fw-bold small text-uppercase">Margin Finansial</span>
                                            <span class="badge {{ $item['marginDaily'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                {{ $item['marginDaily'] >= 0 ? 'SURPLUS' : 'DEFISIT' }}
                                            </span>
                                        </div>
                                        <div class="d-flex flex-column gap-2 small">
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span class="text-muted">Estimasi Nilai Telur:</span>
                                                <strong class="text-dark">Rp {{ number_format($item['eggRevenueEst'], 0, ',', '.') }}</strong>
                                            </div>
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span class="text-muted">Beban Pakan:</span>
                                                <strong class="text-danger">(Rp {{ number_format($item['feedCostDaily'], 0, ',', '.') }})</strong>
                                            </div>
                                            <div class="d-flex justify-content-between py-1 border-bottom">
                                                <span class="text-muted">Laba Harian:</span>
                                                <strong class="fs-6 {{ $item['marginDaily'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                    Rp {{ number_format($item['marginDaily'], 0, ',', '.') }}
                                                </strong>
                                            </div>
                                            <div class="p-2 bg-success-subtle border border-success-subtle rounded-3 d-flex justify-content-between align-items-center mt-1">
                                                <span class="fw-bold text-success-emphasis">Margin/Ekor:</span>
                                                <span class="fs-6 fw-black {{ $item['marginPerHen'] >= 100 ? 'text-success' : ($item['marginPerHen'] > 0 ? 'text-warning' : 'text-danger') }}">
                                                    Rp {{ number_format($item['marginPerHen'], 0, ',', '.') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="card-body p-4 text-center text-muted small">
                                Belum ada laporan panen tercatat untuk kandang ini pada tanggal terpilih.
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-muted py-4">Belum ada data kandang terdaftar.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Ketahanan Stok Pakan -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white p-4 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-black text-dark mb-1">Ketahanan Stok Ransum Pakan Gudang</h4>
                <p class="text-muted small mb-0">Estimasi sisa hari pemakaian sebelum batas aman pemesanan ulang.</p>
            </div>
            <a href="{{ route('procurement.index') }}" class="btn btn-outline-primary btn-sm fw-bold">
                Kelola Pengadaan &rarr;
            </a>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                @foreach($feedStocks as $feed)
                    <div class="col-md-6 col-lg-3">
                        <div class="p-3 rounded-3 border {{ $feed['days_left'] <= 3 ? 'bg-danger-subtle border-danger-subtle' : 'bg-light border-light-subtle' }}">
                            <div class="fw-bold text-dark text-truncate">{{ $feed['name'] }}</div>
                            <div class="small text-muted mt-1">Sisa Stok: {{ number_format($feed['stock_kg'], 0, ',', '.') }} kg</div>
                            <div class="pt-2 mt-2 border-top d-flex justify-content-between align-items-baseline">
                                <span class="small text-muted text-uppercase fw-bold">Estimasi Habis:</span>
                                <span class="fs-4 fw-black {{ $feed['days_left'] <= 3 ? 'text-danger' : 'text-primary' }}">
                                    {{ $feed['days_left'] }} Hari
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // 1. Grafik Tren 7 Hari (Panen vs Pakan)
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartDates) !!},
            datasets: [
                {
                    label: 'Panen Telur (Kg)',
                    data: {!! json_encode($chartEggKg) !!},
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.15)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 4,
                },
                {
                    label: 'Konsumsi Pakan (Kg)',
                    data: {!! json_encode($chartFeedKg) !!},
                    borderColor: '#2563eb',
                    borderDash: [5, 5],
                    borderWidth: 2,
                    fill: false,
                    tension: 0.35,
                    pointRadius: 3,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { grid: { color: '#e2e8f0' } },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Donut Chart Komposisi Grade
    @if(count($gradeBreakdown) > 0)
    const ctxGrade = document.getElementById('gradeChart').getContext('2d');
    new Chart(ctxGrade, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(collect($gradeBreakdown)->pluck('name')) !!},
            datasets: [{
                data: {!! json_encode(collect($gradeBreakdown)->pluck('weight_kg')) !!},
                backgroundColor: ['#f59e0b', '#2563eb', '#10b981', '#ec4899', '#8b5cf6'],
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
    @endif
</script>
@endpush