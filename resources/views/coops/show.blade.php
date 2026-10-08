@extends('layouts.app')

@section('title', $coop->name)

@php use App\Support\Format; $hdpWarn = \App\Models\Setting::num('hdp_warning'); @endphp

@section('content')
<x-page-header :title="$coop->name" :subtitle="($coop->strain ?: 'Ayam petelur') . ' · umur ' . $coop->ageInWeeks() . ' minggu · ayam masuk ' . Format::date($coop->chick_in_date)" icon="bi-house-heart-fill" :back="route('coops.index')" back-label="Semua kandang">
    <a href="{{ route('coops.edit', $coop) }}" class="btn btn-light"><i class="bi bi-pencil"></i> Ubah data</a>
    <a href="{{ route('daily-logs.index', ['coop_id' => $coop->id]) }}" class="btn btn-light"><i class="bi bi-journal-text"></i> Riwayat panen</a>
</x-page-header>

<x-alerts />

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><x-stat label="Jumlah ayam sekarang" :value="Format::number($coop->current_population)" unit="ekor" icon="bi-feather" tone="info" :hint="'Masuk ' . Format::number($coop->initial_population) . ' · berkurang ' . Format::number($totalLoss)" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat label="Rata-rata produksi" :value="Format::number($stats['avg_hdp'], 1) . '%'" icon="bi-graph-up-arrow" :tone="$stats['days'] && $stats['avg_hdp'] < $stats['avg_std'] - (\App\Models\Setting::num('hdp_tolerance') ?: 5) ? 'danger' : 'success'" :hint="'Standar ' . Format::number($stats['avg_std'], 1) . '% · tertinggi ' . Format::number($stats['best_hdp'], 1) . '%'" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat label="Telur terkumpul" :value="Format::number($stats['eggs'])" unit="butir" icon="bi-egg-fill" tone="egg" :hint="Format::number($stats['egg_kg'], 1) . ' kg dalam ' . $stats['days'] . ' laporan'" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat label="Efisiensi pakan (FCR)" :value="$stats['fcr'] ? Format::number($stats['fcr'], 2) : '-'" icon="bi-box-seam-fill" tone="brand" :hint="Format::number($stats['feed_kg']) . ' kg pakan · makin kecil makin hemat'" /></div>
</div>

<x-panel title="Grafik produksi" icon="bi-graph-up" :subtitle="'Garis hijau = produksi kandang (HDP). Garis putus-putus = standar ' . $coop->standardLabel() . ' sesuai umur ayam (sekarang ' . Format::number($coop->standardHdp(), 1) . '%).'">
    <x-slot:actions>
        @foreach([14, 30, 60, 90] as $d)
            <a href="{{ route('coops.show', [$coop, 'days' => $d]) }}" class="btn btn-sm {{ $days === $d ? 'btn-primary' : 'btn-light' }}">{{ $d }} hari</a>
        @endforeach
    </x-slot:actions>
    <div class="chart-box"><canvas id="coopChart" aria-label="Grafik produksi kandang"></canvas></div>
</x-panel>

<div class="row g-3">
    <div class="col-lg-7">
        <x-panel title="Laporan terakhir" icon="bi-journal-text" flush>
            @if($recentLogs->isEmpty())
                <x-empty icon="bi-journal-x" title="Belum ada laporan" />
            @else
                <div class="table-wrap">
                    <table class="tbl stack">
                        <thead><tr><th>Tanggal</th><th class="num">Telur</th><th class="num">HDP</th><th class="num">Pakan</th><th class="num">Mati/Afkir</th><th class="actions"></th></tr></thead>
                        <tbody>
                            @foreach($recentLogs as $log)
                                <tr>
                                    <td class="title-cell"><b>{{ Format::date($log->log_date, 'D, d M') }}</b></td>
                                    <td data-label="Telur" class="num">{{ Format::number($log->eggs_total_count) }}</td>
                                    <td data-label="HDP" class="num"><x-tag :tone="$log->hdp_percentage >= $hdpWarn ? 'success' : 'warning'">{{ Format::number($log->hdp_percentage, 1) }}%</x-tag></td>
                                    <td data-label="Pakan" class="num">{{ Format::number($log->feed_consumed_kg) }} kg</td>
                                    <td data-label="Mati/Afkir" class="num">{{ $log->mortality }} / {{ $log->cull }}</td>
                                    <td class="actions"><a href="{{ route('daily-logs.edit', $log) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Ubah</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-panel>
    </div>
    <div class="col-lg-5">
        <x-panel title="Riwayat vaksinasi" icon="bi-shield-plus" flush>
            <x-slot:actions>
                <a href="{{ route('vaccinations.create') }}" class="btn btn-light btn-sm"><i class="bi bi-plus-lg"></i> Catat vaksin</a>
            </x-slot:actions>
            @if($vaccinations->isEmpty())
                <x-empty icon="bi-shield" title="Belum ada vaksinasi" />
            @else
                <ul class="alert-list">
                    @foreach($vaccinations as $v)
                        <li>
                            <span class="dot success"><i class="bi bi-shield-check"></i></span>
                            <div class="txt">
                                <b>{{ $v->vaccine_name }}{{ $v->target_disease ? ' (' . $v->target_disease . ')' : '' }}</b>
                                <span>{{ Format::date($v->vaccination_date) }} · umur {{ $v->age_weeks }} minggu · {{ $v->method }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        var d = @json($chart);
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.font.size = 13;
        Chart.defaults.color = '#3c473d';
        new Chart(document.getElementById('coopChart'), {
            type: 'line',
            data: {
                labels: d.labels,
                datasets: [
                    { label: 'Produksi HDP (%)', data: d.hdp, borderColor: '#3f5a26', backgroundColor: 'rgba(63,90,38,.12)', fill: true, borderWidth: 3, tension: .3, pointRadius: 3, spanGaps: true },
                    { label: 'Standar strain', data: d.standard, borderColor: '#2f6f9f', borderDash: [6, 6], borderWidth: 2, pointRadius: 0, fill: false }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true } },
                    tooltip: { callbacks: { afterBody: function (items) { var i = items[0].dataIndex; return d.eggs[i] !== null ? 'Telur: ' + angka(d.eggs[i], 0) + ' butir\nMati/afkir: ' + d.loss[i] + ' ekor' : 'Belum dicatat'; } } }
                },
                scales: { y: { suggestedMin: 50, suggestedMax: 100, title: { display: true, text: '%' } }, x: { grid: { display: false } } }
            }
        });
    })();
</script>
@endpush
