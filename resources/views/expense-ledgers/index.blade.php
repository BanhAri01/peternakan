@extends('layouts.app')

@section('title', 'Buku Kas')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Buku Kas" subtitle="Catat semua pengeluaran selain pakan: gaji, listrik, obat, tray, perbaikan, dan lainnya." icon="bi-wallet2">
    <a href="{{ route('expenses.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Catat Pengeluaran</a>
</x-page-header>

<x-alerts />

<x-panel>
    <form method="GET" class="filter-bar">
        <x-field label="Dari tanggal" for="start_date">
            <input type="date" id="start_date" name="start_date" value="{{ $startDate }}" class="form-control">
        </x-field>
        <x-field label="Sampai tanggal" for="end_date">
            <input type="date" id="end_date" name="end_date" value="{{ $endDate }}" class="form-control">
        </x-field>
        <x-field label="Kategori" for="category">
            <select id="category" name="category" class="form-select">
                <option value="">Semua kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </x-field>
        <div class="btns">
            <button type="submit" class="btn btn-primary" data-no-lock><i class="bi bi-search"></i> Tampilkan</button>
            <a href="{{ route('expenses.index') }}" class="btn btn-light" title="Atur ulang"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-panel>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><x-stat label="Uang masuk (penjualan)" :value="Format::rupiah($summary['cash_in'])" icon="bi-arrow-down-circle-fill" tone="success" :hint="'Nilai penjualan ' . Format::rupiah($summary['revenue'])" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat label="Uang keluar" :value="Format::rupiah($summary['cash_out'])" icon="bi-arrow-up-circle-fill" tone="danger" hint="Pakan + telur + biaya lain + vaksin" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat label="Biaya lain (Buku Kas)" :value="Format::rupiah($summary['expenses'])" icon="bi-receipt" tone="warning" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat :label="$summary['net_profit'] >= 0 ? 'Perkiraan untung' : 'Perkiraan rugi'" :value="Format::rupiah($summary['net_profit'])" icon="bi-piggy-bank-fill" :tone="$summary['net_profit'] >= 0 ? 'success' : 'danger'" :alert="$summary['net_profit'] < 0" :href="route('reports.index')" hint="Lihat Laporan Bulanan" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <x-panel title="Uang masuk & biaya per hari" icon="bi-bar-chart-fill" subtitle="Hijau = penjualan. Merah = pakan dimakan + biaya lain + vaksin.">
            <div class="chart-box"><canvas id="cashChart" aria-label="Grafik uang masuk dan biaya"></canvas></div>
        </x-panel>
    </div>
    <div class="col-lg-4">
        <x-panel title="Biaya per kategori" icon="bi-pie-chart-fill">
            @if($byCategory->isEmpty())
                <x-empty icon="bi-wallet2" title="Belum ada pengeluaran" class="py-3" />
            @else
                @php $catMax = max(1, $byCategory->max()); @endphp
                @foreach($byCategory as $cat => $total)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between gap-2 mb-1 small"><b>{{ $cat }}</b><span class="tabular fw-bold">@rupiah($total)</span></div>
                        <div class="progress-thin warn"><span style="width: {{ $total / $catMax * 100 }}%"></span></div>
                    </div>
                @endforeach
            @endif
        </x-panel>
    </div>
</div>

<x-panel title="Daftar pengeluaran" icon="bi-list-ul" :subtitle="'Total yang ditampilkan: ' . Format::rupiah($filteredTotal)" flush>
    @if($expenses->isEmpty())
        <x-empty icon="bi-receipt" title="Belum ada pengeluaran pada periode ini">
            <x-slot:action><a href="{{ route('expenses.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Catat Pengeluaran</a></x-slot:action>
        </x-empty>
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Tanggal</th><th>Uraian</th><th>Kategori</th><th class="num">Jumlah</th><th class="num">Total</th><th>Dibayar</th><th class="actions">Aksi</th></tr></thead>
                <tbody>
                    @foreach($expenses as $e)
                        <tr>
                            <td class="title-cell"><b>{{ Format::date($e->transaction_date) }}</b></td>
                            <td data-label="Uraian">
                                <b>{{ $e->item_name }}</b>
                                @if($e->supplier)<div class="text-muted small">{{ $e->supplier }}</div>@endif
                            </td>
                            <td data-label="Kategori"><x-tag tone="neutral">{{ $e->category }}</x-tag></td>
                            <td data-label="Jumlah" class="num">{{ Format::number($e->quantity, 2) }} {{ $e->unit }}<div class="text-muted small">× @rupiah($e->unit_price)</div></td>
                            <td data-label="Total" class="num"><b class="text-danger">@rupiah($e->total_amount)</b></td>
                            <td data-label="Dibayar">{{ $e->payment_method }}<div class="text-muted small">oleh {{ $e->officer }}</div></td>
                            <td class="actions">
                                <a href="{{ route('expenses.edit', $e) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Ubah</a>
                                <x-delete-button :action="route('expenses.destroy', $e)" icon-only title="Hapus pengeluaran ini?" :message="$e->item_name . ' sebesar ' . Format::rupiah($e->total_amount) . ' akan dihapus dari Buku Kas.'" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if($expenses->hasPages())
        <x-slot:footer>{{ $expenses->links() }}</x-slot:footer>
    @endif
</x-panel>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        var d = @json($chart);
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.font.size = 13;
        Chart.defaults.color = '#3c473d';
        new Chart(document.getElementById('cashChart'), {
            type: 'bar',
            data: {
                labels: d.labels,
                datasets: [
                    { label: 'Penjualan', data: d.income, backgroundColor: 'rgba(47,107,52,.8)', borderRadius: 5 },
                    { label: 'Biaya', data: d.cost, backgroundColor: 'rgba(179,38,30,.7)', borderRadius: 5 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true } },
                    tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + rupiah(c.raw); } } }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: function (v) { return v >= 1e6 ? (v / 1e6) + ' jt' : (v >= 1e3 ? (v / 1e3) + ' rb' : v); } } },
                    x: { grid: { display: false } }
                }
            }
        });
    })();
</script>
@endpush
