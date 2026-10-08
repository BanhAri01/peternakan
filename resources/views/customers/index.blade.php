@extends('layouts.app')

@section('title', 'Pelanggan & Piutang')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Pelanggan & Piutang" subtitle="Daftar pembeli telur dan siapa saja yang masih punya utang. Pembeli baru otomatis masuk saat mencatat penjualan." icon="bi-person-lines-fill">
    <a href="{{ route('sales.index') }}" class="btn btn-primary"><i class="bi bi-basket2-fill"></i> Catat Penjualan</a>
</x-page-header>

<x-alerts />

<div class="row g-3 mb-3">
    <div class="col-md-6"><x-stat label="Total piutang" :value="Format::rupiah($totalDebt)" icon="bi-hourglass-split" :tone="$totalDebt > 0 ? 'warning' : 'success'" hint="Jumlah uang dari semua pelanggan yang belum dibayar." /></div>
    <div class="col-md-6"><x-stat label="Pelanggan yang masih berutang" :value="$debtorCount" unit="orang" icon="bi-people-fill" tone="info" /></div>
</div>

<x-panel title="Daftar pelanggan" icon="bi-people" flush>
    <x-slot:actions>
        <form method="GET" class="d-flex gap-2">
            <input type="search" name="q" value="{{ $search }}" class="form-control" style="min-height:44px" placeholder="Cari nama..." aria-label="Cari nama pelanggan">
            <button class="btn btn-light btn-sm" data-no-lock aria-label="Cari"><i class="bi bi-search"></i></button>
        </form>
    </x-slot:actions>

    @if($customers->isEmpty())
        <x-empty icon="bi-person-x" title="Belum ada pelanggan">Pelanggan akan muncul setelah penjualan pertama dicatat.</x-empty>
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Nama</th><th>No. HP</th><th class="num">Total belanja</th><th class="num">Belum dibayar</th><th>Beli terakhir</th><th class="actions">Aksi</th></tr></thead>
                <tbody>
                    @foreach($customers as $c)
                        <tr>
                            <td class="title-cell"><a href="{{ route('customers.show', $c) }}" class="fw-800 text-decoration-none text-ink">{{ $c->name }}</a></td>
                            <td data-label="No. HP">{{ $c->phone ?: '-' }}</td>
                            <td data-label="Total belanja" class="num">@rupiah($c->total_bought ?? 0)<div class="text-muted small">{{ $c->invoices_count }} nota</div></td>
                            <td data-label="Belum dibayar" class="num">
                                @if($c->total_debt > 0)
                                    <b class="text-danger">@rupiah($c->total_debt)</b>
                                @else
                                    <x-tag tone="success" icon="bi-check-circle">Lunas</x-tag>
                                @endif
                            </td>
                            <td data-label="Beli terakhir">{{ $c->last_sale ? Format::date($c->last_sale) : '-' }}</td>
                            <td class="actions">
                                @if($c->total_debt > 0 && $c->wa_number)
                                    @php $msg = 'Halo ' . $c->name . ', kami dari ' . \App\Models\Setting::get('farm_name') . '. Mengingatkan tagihan telur yang belum dibayar sebesar ' . Format::rupiah($c->total_debt) . '. Terima kasih.'; @endphp
                                    <a href="https://wa.me/{{ $c->wa_number }}?text={{ rawurlencode($msg) }}" target="_blank" rel="noopener" class="btn btn-wa btn-sm"><i class="bi bi-whatsapp"></i> Tagih</a>
                                @endif
                                <a href="{{ route('customers.show', $c) }}" class="btn btn-light btn-sm">Detail <i class="bi bi-arrow-right"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if($customers->hasPages())
        <x-slot:footer>{{ $customers->links() }}</x-slot:footer>
    @endif
</x-panel>
@endsection
