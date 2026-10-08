@extends('layouts.app')

@section('title', 'Beranda')

@php
    use App\Support\Format;
    $isToday  = $date->isToday();
    $eggDiff  = $eggCount - $yesterdayEggs;
    $hour     = (int) now()->format('H');
    $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
@endphp

@section('content')
<x-page-header
    :title="$greeting . ', ' . auth()->user()->name"
    :subtitle="($isToday ? 'Ringkasan peternakan hari ini, ' : 'Ringkasan peternakan tanggal ') . Format::dayDate($date) . '.'"
    icon="bi-house-door-fill">
    <a href="{{ route('owner.dashboard') }}" class="btn {{ $isToday ? 'btn-primary' : 'btn-light' }}">Hari ini</a>
    <a href="{{ route('owner.dashboard', ['date' => today()->subDay()->toDateString()]) }}" class="btn {{ $date->isYesterday() ? 'btn-primary' : 'btn-light' }}">Kemarin</a>
    <form method="GET">
        <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control" onchange="this.form.submit()" aria-label="Pilih tanggal">
    </form>
</x-page-header>

<x-alerts />

@if($onboardingLeft > 0)
    <x-panel title="Langkah awal memakai HEFAM" icon="bi-flag-fill" tone="egg" :subtitle="(count($onboarding) - $onboardingLeft) . ' dari ' . count($onboarding) . ' langkah selesai'">
        <div class="progress-thin mb-3"><span style="width: {{ (count($onboarding) - $onboardingLeft) / count($onboarding) * 100 }}%"></span></div>
        <div class="row g-2">
            @foreach($onboarding as $i => $step)
                <div class="col-md-6 col-xl-4">
                    <a href="{{ $step['url'] }}" class="d-flex gap-3 align-items-start p-3 rounded-3 border text-decoration-none text-reset h-100" style="background: {{ $step['done'] ? 'var(--success-soft)' : '#fff' }}">
                        <span class="step-no" style="{{ $step['done'] ? 'background:var(--success)' : '' }}">
                            @if($step['done'])<i class="bi bi-check-lg"></i>@else{{ $i + 1 }}@endif
                        </span>
                        <span>
                            <b class="d-block {{ $step['done'] ? 'text-decoration-line-through text-muted' : '' }}">{{ $step['title'] }}</b>
                            <small class="text-muted">{{ $step['text'] }}</small>
                        </span>
                    </a>
                </div>
            @endforeach
        </div>
    </x-panel>
@endif

{{-- ===================== ANGKA UTAMA ===================== --}}
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <x-stat label="Telur dikumpulkan" :value="Format::number($eggCount)" unit="butir" icon="bi-egg-fill" tone="egg">
            {{ Format::trays($eggCount) }} &middot; {{ Format::number($eggKg, 1) }} kg
            @if($yesterdayEggs > 0)
                <div class="{{ $eggDiff >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                    <i class="bi {{ $eggDiff >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i>
                    {{ Format::number(abs($eggDiff)) }} butir dibanding kemarin
                </div>
            @endif
        </x-stat>
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat label="Produksi (HDP)" :value="Format::number($hdp, 1) . '%'" icon="bi-graph-up-arrow"
                :tone="$logs->isEmpty() ? 'neutral' : ($hdp >= $hdpWarn ? 'success' : 'danger')" :alert="$logs->isNotEmpty() && $hdp < $hdpWarn">
            Persen ayam yang bertelur hari itu.
            <div>Batas aman: {{ Format::number($hdpWarn) }}% ke atas</div>
        </x-stat>
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat label="Jumlah ayam" :value="Format::number($population)" unit="ekor" icon="bi-feather" tone="info">
            Di {{ $activeCoops->count() }} kandang aktif
            @if($mortality + $cull > 0)
                <div class="text-danger fw-bold"><i class="bi bi-heartbreak-fill"></i> {{ $mortality }} mati, {{ $cull }} afkir</div>
            @else
                <div class="text-success fw-bold"><i class="bi bi-check-circle"></i> Tidak ada yang mati</div>
            @endif
        </x-stat>
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-stat label="Pakan dipakai" :value="Format::number($feedKg)" unit="kg" icon="bi-box-seam-fill" tone="brand">
            Biaya pakan @rupiah($feedCost)
            <div>Efisiensi (FCR): <b>{{ $fcr ? Format::number($fcr, 2) : '-' }}</b></div>
        </x-stat>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <x-stat label="Modal per kg telur" :value="$hppPerKg > 0 ? Format::rupiah($hppPerKg) : '-'" icon="bi-calculator-fill" tone="neutral">
            Pakan + biaya lain dibagi kg telur. Jual di atas angka ini agar untung.
        </x-stat>
    </div>
    <div class="col-md-4">
        <x-stat label="Penjualan hari ini" :value="Format::rupiah($salesToday)" icon="bi-basket2-fill" tone="success" :href="route('sales.index')">
            Harga rata-rata: @rupiah($avgPrice)/kg <span class="d-block small">({{ $priceSource }})</span>
        </x-stat>
    </div>
    <div class="col-md-4">
        <x-stat label="Piutang belum dibayar" :value="Format::rupiah($totalDebt)" icon="bi-hourglass-split" :tone="$totalDebt > 0 ? 'warning' : 'success'" :href="route('customers.index')">
            Uang pelanggan yang belum masuk. Tekan untuk melihat rinciannya.
        </x-stat>
    </div>
</div>

<div class="row g-3">
    {{-- ===================== PERLU PERHATIAN ===================== --}}
    <div class="col-lg-5">
        <x-panel title="Perlu perhatian" icon="bi-bell-fill" flush>
            @if(empty($alerts))
                <x-empty icon="bi-emoji-smile" title="Semua aman">Tidak ada masalah yang perlu ditangani.</x-empty>
            @else
                <ul class="alert-list">
                    @foreach($alerts as $a)
                        <li>
                            <span class="dot {{ $a['tone'] }}"><i class="bi {{ $a['icon'] }}"></i></span>
                            <div class="txt">
                                <b>{{ $a['title'] }}</b>
                                <span>{{ $a['text'] }}</span>
                            </div>
                            <a href="{{ $a['url'] }}" class="btn btn-light btn-sm flex-shrink-0">{{ $a['cta'] }}</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>

        <x-panel title="Keuangan bulan ini" icon="bi-wallet2" :subtitle="'1 ' . $date->translatedFormat('F') . ' s/d ' . Format::date($date)">
            <div class="kv"><span class="k">Hasil penjualan telur</span><span class="v text-success">@rupiah($month['revenue'])</span></div>
            @if($month['other_income'] > 0)
                <div class="kv"><span class="k">Pendapatan lain (afkir, kotoran, dll)</span><span class="v text-success">@rupiah($month['other_income'])</span></div>
            @endif
            <div class="kv"><span class="k">Pakan yang dimakan</span><span class="v">@rupiah($month['feed_used'])</span></div>
            <div class="kv"><span class="k">Biaya lain (gaji, listrik, obat, dll)</span><span class="v">@rupiah($month['expenses'] + $month['vaccines'])</span></div>
            @if($month['egg_bought'] > 0)
                <div class="kv"><span class="k">Beli telur dari luar</span><span class="v">@rupiah($month['egg_bought'])</span></div>
            @endif
            <div class="kv total">
                <span class="k">{{ $month['net_profit'] >= 0 ? 'Perkiraan untung' : 'Perkiraan rugi' }}</span>
                <span class="v {{ $month['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">@rupiah($month['net_profit'])</span>
            </div>
            <x-slot:footer>
                <a href="{{ route('reports.index') }}" class="fw-bold text-decoration-none">Lihat laporan lengkap <i class="bi bi-arrow-right"></i></a>
            </x-slot:footer>
        </x-panel>
    </div>

    {{-- ===================== GRAFIK ===================== --}}
    <div class="col-lg-7">
        <x-panel title="Hasil telur 14 hari terakhir" icon="bi-bar-chart-line-fill" subtitle="Batang = berat telur (kg). Garis = persen produksi (HDP).">
            <div class="chart-box"><canvas id="trendChart" aria-label="Grafik hasil telur 14 hari"></canvas></div>
        </x-panel>

        <x-panel title="Stok telur di gudang" icon="bi-egg-fill" subtitle="Telur campur menunggu disortir; jenis lain siap dijual." flush>
            <x-slot:actions>
                <a href="{{ route('sortings.create') }}" class="btn btn-light btn-sm"><i class="bi bi-funnel"></i> Sortir telur</a>
            </x-slot:actions>
            @if($eggStocks->isEmpty())
                <x-empty icon="bi-egg" title="Belum ada jenis telur" />
            @else
                <ul class="alert-list">
                    @foreach($eggStocks as $s)
                        <li>
                            <span class="dot {{ $s['grade']->is_mixed ? 'warning' : 'success' }}"><i class="bi {{ $s['grade']->is_mixed ? 'bi-basket-fill' : 'bi-egg-fill' }}"></i></span>
                            <div class="txt">
                                <b>{{ $s['grade']->name }}</b>
                                <span>{{ $s['grade']->is_mixed ? Format::number($mixedWaiting) . ' butir belum disortir' : 'siap dijual' }}</span>
                            </div>
                            <b class="tabular {{ $s['kg'] < 0 ? 'text-danger' : '' }}" style="font-size:1.15rem">{{ Format::number($s['kg'], 1) }} kg</b>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>

        <x-panel title="Hasil sortir hari ini" icon="bi-funnel-fill">
            @if($gradeMix->isEmpty())
                <x-empty icon="bi-funnel" title="Belum ada sortir pada tanggal ini" class="py-3" />
            @else
                @php $mixTotal = max(0.01, $gradeMix->sum('kg')); @endphp
                @foreach($gradeMix as $g)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <b>{{ $g->name }}</b>
                            <span class="tabular"><b>{{ Format::number($g->kg, 1) }} kg</b> <span class="text-muted">· {{ Format::number($g->eggs) }} butir · {{ Format::number($g->kg / $mixTotal * 100, 0) }}%</span></span>
                        </div>
                        <div class="progress-thin"><span style="width: {{ $g->kg / $mixTotal * 100 }}%; background: var(--egg)"></span></div>
                    </div>
                @endforeach
            @endif
        </x-panel>
    </div>
</div>

{{-- ===================== KANDANG ===================== --}}
<div class="d-flex align-items-center justify-content-between mt-2 mb-3 flex-wrap gap-2">
    <h2 class="h4 fw-800 mb-0"><i class="bi bi-house-heart-fill text-success me-1"></i> Keadaan tiap kandang</h2>
    <a href="{{ route('coops.index') }}" class="btn btn-light btn-sm">Kelola kandang <i class="bi bi-arrow-right"></i></a>
</div>

@if($coopCards->isEmpty())
    <x-panel>
        <x-empty icon="bi-house-add" title="Belum ada kandang aktif">
            <x-slot:action><a href="{{ route('coops.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Kandang</a></x-slot:action>
        </x-empty>
    </x-panel>
@else
    <div class="row g-3 mb-4">
        @foreach($coopCards as $card)
            @php $log = $card['log']; @endphp
            <div class="col-md-6 col-xxl-4">
                <section class="panel coop-card mb-0">
                    <div class="panel-head">
                        <div>
                            <h3 class="panel-title">{{ $card['coop']->name }}</h3>
                            <p class="panel-sub">{{ $card['coop']->strain ?: 'Ayam petelur' }} &middot; umur {{ $card['age'] }} minggu &middot; {{ Format::number($card['coop']->current_population) }} ekor</p>
                        </div>
                        <x-tag :tone="$card['status']['tone']">{{ $card['status']['text'] }}</x-tag>
                    </div>
                    <div class="panel-body">
                        @if($log)
                            <div class="metric-row mb-3">
                                <div class="metric"><span class="k">Telur</span><span class="v">{{ Format::number($log->eggs_total_count) }}</span></div>
                                <div class="metric"><span class="k">Produksi</span><span class="v {{ $log->hdp_percentage < $hdpWarn ? 'text-danger' : 'text-success' }}">{{ Format::number($log->hdp_percentage, 1) }}%</span></div>
                                <div class="metric"><span class="k">Berat</span><span class="v">{{ Format::number($log->eggs_total_kg, 1) }} kg</span></div>
                            </div>
                            <div class="kv"><span class="k">Dalam rak</span><span class="v">{{ Format::trays($log->eggs_total_count) }}</span></div>
                            <div class="kv"><span class="k">Pakan</span><span class="v">{{ Format::number($log->feed_consumed_kg, 1) }} kg @if($card['gramPerHen'])<span class="text-muted fw-normal">({{ $card['gramPerHen'] }} gr/ekor)</span>@endif</span></div>
                            <div class="kv"><span class="k">Mati / afkir</span><span class="v {{ $log->mortality + $log->cull > 0 ? 'text-danger' : '' }}">{{ $log->mortality }} / {{ $log->cull }} ekor</span></div>
                            <div class="kv"><span class="k">Perkiraan untung pakan</span><span class="v {{ $card['margin'] >= 0 ? 'text-success' : 'text-danger' }}">@rupiah($card['margin'])</span></div>
                            @if($log->notes)
                                <div class="help-tip mt-2"><i class="bi bi-chat-left-text"></i><span>{{ $log->notes }}</span></div>
                            @endif
                        @else
                            <x-empty icon="bi-clipboard" title="Belum ada laporan" class="py-3">
                                @if($isToday)
                                    <x-slot:action><a href="{{ route('daily-logs.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Catat sekarang</a></x-slot:action>
                                @endif
                            </x-empty>
                        @endif
                    </div>
                    <div class="panel-foot d-flex justify-content-between align-items-center gap-2">
                        <small class="text-muted">{{ $log && $log->recorder ? 'Dicatat oleh ' . $log->recorder->name : '' }}</small>
                        <span class="d-flex gap-2">
                            @if($log)
                                <a href="{{ route('daily-logs.edit', $log) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Ubah</a>
                            @endif
                            <a href="{{ route('coops.show', $card['coop']) }}" class="btn btn-light btn-sm">Detail <i class="bi bi-arrow-right"></i></a>
                        </span>
                    </div>
                </section>
            </div>
        @endforeach
    </div>
@endif

{{-- ===================== STOK PAKAN ===================== --}}
<x-panel title="Stok pakan di gudang" icon="bi-box-seam-fill" subtitle="Perkiraan hari dihitung dari pemakaian rata-rata 7 hari terakhir." flush>
    <x-slot:actions>
        <a href="{{ route('procurement.index') }}" class="btn btn-light btn-sm"><i class="bi bi-truck"></i> Catat pembelian pakan</a>
    </x-slot:actions>
    @if($feeds->isEmpty())
        <x-empty icon="bi-box-seam" title="Belum ada jenis pakan" />
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Jenis pakan</th><th class="num">Sisa stok</th><th class="num">Pemakaian/hari</th><th>Cukup untuk</th></tr></thead>
                <tbody>
                    @foreach($feeds as $f)
                        @php
                            $days = $f['days_left'];
                            $tone = $f['feed']->stock_kg < 0 ? 'danger' : ($days === null ? 'neutral' : ($days <= \App\Models\Setting::num('low_feed_days') ? 'warning' : 'success'));
                        @endphp
                        <tr>
                            <td class="title-cell"><b>{{ $f['feed']->feed_name }}</b></td>
                            <td data-label="Sisa stok" class="num">{{ Format::number($f['feed']->stock_kg) }} kg</td>
                            <td data-label="Pemakaian/hari" class="num">{{ $f['per_day'] > 0 ? Format::number($f['per_day']) . ' kg' : '-' }}</td>
                            <td data-label="Cukup untuk">
                                <x-tag :tone="$tone">{{ $days === null ? 'Belum dipakai' : '± ' . $days . ' hari' }}</x-tag>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-panel>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        var data = @json($chart);
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.font.size = 13;
        Chart.defaults.color = '#3c473d';

        new Chart(document.getElementById('trendChart'), {
            data: {
                labels: data.labels,
                datasets: [
                    { type: 'bar', label: 'Berat telur (kg)', data: data.eggs, backgroundColor: 'rgba(185, 119, 14, .75)', borderRadius: 6, yAxisID: 'y' },
                    { type: 'line', label: 'Produksi HDP (%)', data: data.hdp, borderColor: '#3f5a26', backgroundColor: '#3f5a26', borderWidth: 3, tension: .3, pointRadius: 4, spanGaps: true, yAxisID: 'y1' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } },
                    tooltip: {
                        callbacks: {
                            label: function (c) {
                                if (c.raw === null) return c.dataset.label + ': belum dicatat';
                                return c.dataset.label + ': ' + angka(c.raw, 1);
                            }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'kg' }, grid: { color: '#ebe7dc' } },
                    y1: { position: 'right', min: 0, max: 100, title: { display: true, text: '%' }, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    })();
</script>
@endpush
