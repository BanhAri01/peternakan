@extends('layouts.app')

@section('title', 'Laporan Bulanan')

@php
    use App\Support\Format;
    $change = function ($now, $before) {
        if ($before == 0) return null;
        return round(($now - $before) / abs($before) * 100);
    };
    $revChange = $change($summary['revenue'], $previous['revenue']);
@endphp

@section('content')
<x-page-header title="Laporan Bulanan" :subtitle="'Ringkasan keuangan dan hasil kandang bulan ' . $month->translatedFormat('F Y') . '.'" icon="bi-file-earmark-bar-graph-fill">
    <form method="GET" class="d-flex gap-2">
        <select name="month" class="form-select" style="min-width:210px" onchange="this.form.submit()" aria-label="Pilih bulan">
            @foreach($months as $m)
                <option value="{{ $m->format('Y-m') }}" @selected($m->format('Y-m') === $month->format('Y-m'))>{{ $m->translatedFormat('F Y') }}</option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('reports.monthly-pdf', ['month' => $month->format('Y-m')]) }}" class="btn btn-primary"><i class="bi bi-file-earmark-pdf-fill"></i> Unduh PDF</a>
    <button type="button" onclick="window.print()" class="btn btn-light"><i class="bi bi-printer"></i> Cetak</button>
</x-page-header>

<x-alerts />

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <x-stat label="Hasil penjualan" :value="Format::rupiah($summary['revenue'])" icon="bi-cash-stack" tone="success">
            @if($revChange !== null)
                <span class="{{ $revChange >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                    <i class="bi {{ $revChange >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i> {{ abs($revChange) }}% dibanding bulan lalu
                </span>
            @endif
        </x-stat>
    </div>
    <div class="col-md-4">
        <x-stat label="Total biaya" :value="Format::rupiah($summary['total_cost'])" icon="bi-receipt" tone="danger" hint="Pakan dimakan + biaya lain + vaksin + beli telur." />
    </div>
    <div class="col-md-4">
        <x-stat :label="$summary['net_profit'] >= 0 ? 'Untung bersih' : 'Rugi'" :value="Format::rupiah($summary['net_profit'])" icon="bi-piggy-bank-fill" :tone="$summary['net_profit'] >= 0 ? 'success' : 'danger'" :alert="$summary['net_profit'] < 0">
            Bulan lalu: @rupiah($previous['net_profit'])
        </x-stat>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <x-panel title="Untung & rugi" icon="bi-calculator">
            <div class="kv"><span class="k">Hasil penjualan telur</span><span class="v text-success">@rupiah($summary['revenue'])</span></div>
            <div class="kv"><span class="k">Pendapatan lain (afkir, kotoran, dll)</span><span class="v text-success">@rupiah($summary['other_income'])</span></div>
            <div class="divider-label">Dikurangi biaya</div>
            <div class="kv"><span class="k">Pakan yang dimakan ayam</span><span class="v">@rupiah($summary['feed_used'])</span></div>
            <div class="kv"><span class="k">Beli telur dari luar</span><span class="v">@rupiah($summary['egg_bought'])</span></div>
            <div class="kv"><span class="k">Biaya lain (Buku Kas)</span><span class="v">@rupiah($summary['expenses'])</span></div>
            <div class="kv"><span class="k">Vaksinasi</span><span class="v">@rupiah($summary['vaccines'])</span></div>
            <div class="kv"><span class="k">Obat & vitamin dipakai</span><span class="v">@rupiah($summary['medicine_used'])</span></div>
            <div class="kv total">
                <span class="k">{{ $summary['net_profit'] >= 0 ? 'Untung bersih' : 'Rugi' }}</span>
                <span class="v {{ $summary['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">@rupiah($summary['net_profit'])</span>
            </div>
        </x-panel>
    </div>
    <div class="col-lg-6">
        <x-panel title="Uang masuk & keluar (kas)" icon="bi-wallet2" subtitle="Uang yang benar-benar diterima dan dibayarkan.">
            <div class="kv"><span class="k">Uang diterima dari pembeli</span><span class="v text-success">@rupiah($summary['cash_in'])</span></div>
            <div class="kv"><span class="k">Pendapatan lain</span><span class="v text-success">@rupiah($summary['other_income'])</span></div>
            <div class="kv"><span class="k">Piutang baru (belum dibayar)</span><span class="v text-warning">@rupiah($summary['new_debt'])</span></div>
            <div class="divider-label">Uang keluar</div>
            <div class="kv"><span class="k">Beli pakan</span><span class="v">@rupiah($summary['feed_bought'])</span></div>
            <div class="kv"><span class="k">Beli telur</span><span class="v">@rupiah($summary['egg_bought'])</span></div>
            <div class="kv"><span class="k">Biaya lain + vaksin</span><span class="v">@rupiah($summary['expenses'] + $summary['vaccines'])</span></div>
            <div class="kv"><span class="k">Beli obat & vitamin</span><span class="v">@rupiah($summary['medicine_bought'])</span></div>
            <div class="kv total">
                <span class="k">Sisa uang kas</span>
                <span class="v {{ $summary['net_cash'] >= 0 ? 'text-success' : 'text-danger' }}">@rupiah($summary['net_cash'])</span>
            </div>
        </x-panel>
    </div>
</div>

<x-panel title="Hasil tiap kandang" icon="bi-house-heart-fill" flush>
    @if($performance->isEmpty())
        <x-empty icon="bi-house" title="Belum ada data kandang" />
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Kandang</th><th class="num">Hari dicatat</th><th class="num">Telur</th><th class="num">Berat</th><th class="num">Rata-rata HDP</th><th class="num">Pakan</th><th class="num">FCR</th><th class="num">Mati/afkir</th></tr></thead>
                <tbody>
                    @foreach($performance as $p)
                        <tr>
                            <td class="title-cell"><a href="{{ route('coops.show', $p['coop']) }}" class="fw-800 text-decoration-none text-ink">{{ $p['coop']->name }}</a></td>
                            <td data-label="Hari dicatat" class="num">{{ $p['days'] }}</td>
                            <td data-label="Telur" class="num">{{ Format::number($p['egg_count']) }} butir</td>
                            <td data-label="Berat" class="num">{{ Format::number($p['egg_kg'], 1) }} kg</td>
                            <td data-label="Rata-rata HDP" class="num"><b>{{ Format::number($p['avg_hdp'], 1) }}%</b></td>
                            <td data-label="Pakan" class="num">{{ Format::number($p['feed_kg']) }} kg</td>
                            <td data-label="FCR" class="num">{{ $p['fcr'] ? Format::number($p['fcr'], 2) : '-' }}</td>
                            <td data-label="Mati/afkir" class="num">{{ Format::number($p['loss']) }} ekor</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-panel>

<div class="row g-3">
    <div class="col-lg-6">
        <x-panel title="Penjualan per jenis telur" icon="bi-egg-fill" flush>
            @if($salesByGrade->isEmpty())
                <x-empty icon="bi-basket" title="Belum ada penjualan" />
            @else
                <table class="tbl">
                    <thead><tr><th>Jenis</th><th class="num">Berat</th><th class="num">Total</th></tr></thead>
                    <tbody>
                        @foreach($salesByGrade as $g)
                            <tr><td>{{ $g->name }}</td><td class="num">{{ Format::number($g->kg, 1) }} kg</td><td class="num"><b>@rupiah($g->total)</b></td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-panel>
    </div>
    <div class="col-lg-6">
        <x-panel title="Biaya lain per kategori" icon="bi-pie-chart-fill" flush>
            @if($expenseByCategory->isEmpty())
                <x-empty icon="bi-wallet2" title="Belum ada pengeluaran" />
            @else
                <table class="tbl">
                    <thead><tr><th>Kategori</th><th class="num">Total</th></tr></thead>
                    <tbody>
                        @foreach($expenseByCategory as $cat => $total)
                            <tr><td>{{ $cat }}</td><td class="num"><b>@rupiah($total)</b></td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-panel>
    </div>
</div>
@endsection
