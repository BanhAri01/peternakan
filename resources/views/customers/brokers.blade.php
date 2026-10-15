@extends('layouts.app')

@section('title', 'Rekap Perantara')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Rekap Perantara" subtitle="Penjualan ke pembeli yang dibawa oleh perantara, per bulan. Bagian perantara dihitung dari yang sudah dibayar." icon="bi-person-badge" :back="route('customers.index')" back-label="Pelanggan & Piutang" />

<x-alerts />

<x-panel>
    <form method="GET" class="filter-bar">
        <x-field label="Bulan" for="bulan">
            <input type="month" id="bulan" name="bulan" value="{{ $month }}" max="{{ today()->format('Y-m') }}" class="form-control">
        </x-field>
        <div class="btns"><button type="submit" class="btn btn-primary" data-no-lock><i class="bi bi-search"></i> Tampilkan</button></div>
    </form>
</x-panel>

@forelse($groups as $group)
    <x-panel :title="'Dibawa oleh ' . $group['name'] . ' · ' . $start->translatedFormat('F Y')" icon="bi-person-badge" flush>
        <div class="row g-3 p-3">
            <div class="col-sm-6 col-lg-3"><x-stat label="Nota" :value="$group['invoices']" unit="nota" icon="bi-receipt" tone="info" /></div>
            <div class="col-sm-6 col-lg-3"><x-stat label="Total penjualan" :value="Format::rupiah($group['total'])" icon="bi-bag-check-fill" tone="info" :hint="Format::number($group['kg'], 2) . ' kg'" /></div>
            <div class="col-sm-6 col-lg-3"><x-stat label="Sudah dibayar" :value="Format::rupiah($group['paid'])" icon="bi-cash-coin" tone="success" /></div>
            <div class="col-sm-6 col-lg-3"><x-stat label="Belum dibayar" :value="Format::rupiah($group['debt'])" icon="bi-hourglass-split" :tone="$group['debt'] > 0 ? 'warning' : 'success'" /></div>
        </div>
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Pembeli</th><th class="num">Nota</th><th class="num">Berat</th><th class="num">Total</th><th class="num">Dibayar</th><th class="num">Belum dibayar</th></tr></thead>
                <tbody>
                    @foreach($group['customers'] as $c)
                        <tr>
                            <td class="title-cell"><a href="{{ route('customers.show', $c) }}" class="fw-800 text-decoration-none text-ink">{{ $c->name }}</a></td>
                            <td data-label="Nota" class="num">{{ (int) $c->invoice_count }}</td>
                            <td data-label="Berat" class="num">{{ Format::number($c->sold_kg ?? 0, 2) }} kg</td>
                            <td data-label="Total" class="num">@rupiah($c->sold_total ?? 0)</td>
                            <td data-label="Dibayar" class="num">@rupiah($c->sold_paid ?? 0)</td>
                            <td data-label="Belum dibayar" class="num">@if(($c->sold_debt ?? 0) > 0)<b class="text-danger">@rupiah($c->sold_debt)</b>@else - @endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-panel>
@empty
    <x-panel>
        <x-empty icon="bi-person-badge" title="Belum ada pembeli yang dibawa perantara">Buka data pelanggan, tekan <b>Ubah data</b>, lalu isi kolom <b>Dibawa oleh</b>.</x-empty>
    </x-panel>
@endforelse
@endsection
