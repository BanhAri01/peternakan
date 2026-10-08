@extends('layouts.app')

@section('title', $customer->name)

@php
    use App\Support\Format;
    $farm   = \App\Models\Setting::get('farm_name');
    $lines  = $unpaid->map(fn ($s) => '- ' . Format::date($s->sale_date) . ': ' . Format::rupiah($s->debt_amount))->join("\n");
    $waText = "Halo {$customer->name}, kami dari {$farm}.\nBerikut tagihan telur yang belum dibayar:\n{$lines}\nTotal: " . Format::rupiah($stats['total_debt']) . "\nTerima kasih.";
    $contact = collect([$customer->phone ? 'HP ' . $customer->phone : null, $customer->address])->filter()->join(' · ');
@endphp

@section('content')
<x-page-header :title="$customer->name" :subtitle="$contact ?: 'Data kontak belum diisi.'" icon="bi-person-circle" :back="route('customers.index')" back-label="Semua pelanggan">
    @if($stats['total_debt'] > 0 && $customer->wa_number)
        <a href="https://wa.me/{{ $customer->wa_number }}?text={{ rawurlencode($waText) }}" target="_blank" rel="noopener" class="btn btn-wa"><i class="bi bi-whatsapp"></i> Kirim tagihan WA</a>
    @endif
    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-light"><i class="bi bi-pencil"></i> Ubah data</a>
</x-page-header>

<x-alerts />

@if($stats['total_debt'] > 0 && !$customer->wa_number)
    <div class="notice notice-info"><i class="bi bi-info-circle-fill"></i><div>Isi nomor HP pelanggan ini agar bisa mengirim tagihan lewat WhatsApp. <a href="{{ route('customers.edit', $customer) }}">Isi sekarang</a></div></div>
@endif

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-lg-3"><x-stat label="Total belanja" :value="Format::rupiah($stats['total_bought'])" icon="bi-bag-check-fill" tone="info" /></div>
    <div class="col-sm-6 col-lg-3"><x-stat label="Sudah dibayar" :value="Format::rupiah($stats['total_paid'])" icon="bi-cash-coin" tone="success" /></div>
    <div class="col-sm-6 col-lg-3"><x-stat label="Belum dibayar" :value="Format::rupiah($stats['total_debt'])" icon="bi-hourglass-split" :tone="$stats['total_debt'] > 0 ? 'danger' : 'success'" :alert="$stats['total_debt'] > 0" /></div>
    <div class="col-sm-6 col-lg-3"><x-stat label="Total telur dibeli" :value="Format::number($stats['total_kg'], 1)" unit="kg" icon="bi-egg-fill" tone="egg" /></div>
</div>

@if($unpaid->isNotEmpty())
    <x-panel title="Nota yang belum lunas" icon="bi-exclamation-circle-fill" tone="danger" flush>
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Nota</th><th class="num">Total</th><th class="num">Sisa</th><th>Jatuh tempo</th><th class="actions">Aksi</th></tr></thead>
                <tbody>
                    @foreach($unpaid as $inv)
                        <tr>
                            <td class="title-cell"><div><b>{{ $inv->number }}</b><div class="text-muted small">{{ Format::date($inv->sale_date) }}</div></div></td>
                            <td data-label="Total" class="num">@rupiah($inv->total)</td>
                            <td data-label="Sisa" class="num"><b class="text-danger">@rupiah($inv->debt)</b></td>
                            <td data-label="Jatuh tempo">
                                @if($inv->due_date)
                                    <x-tag :tone="$inv->due_date->isPast() ? 'danger' : 'neutral'">{{ Format::date($inv->due_date) }}{{ $inv->due_date->isPast() ? ' (lewat)' : '' }}</x-tag>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="actions">
                                <a href="{{ route('sales.print-receipt', $inv) }}" target="_blank" class="btn btn-light btn-sm"><i class="bi bi-printer"></i> Nota</a>
                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#payModal"
                                        data-action="{{ route('sales.pay-debt', $inv) }}" data-name="{{ $customer->name . ' · ' . $inv->number }}" data-debt="{{ $inv->debt }}">
                                    <i class="bi bi-cash"></i> Bayar
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-panel>
@endif

<x-panel title="Riwayat nota" icon="bi-clock-history" flush>
    @if($invoices->isEmpty())
        <x-empty icon="bi-basket" title="Belum ada pembelian" />
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Nota</th><th>Isi</th><th class="num">Total</th><th>Status</th><th class="actions">Cetak</th></tr></thead>
                <tbody>
                    @foreach($invoices as $inv)
                        <tr>
                            <td class="title-cell"><div><b>{{ $inv->number }}</b><div class="text-muted small">{{ Format::date($inv->sale_date, 'D, d M Y') }}</div></div></td>
                            <td data-label="Isi">
                                @foreach($inv->lines as $line)
                                    <div class="small">{{ $line->grade->name ?? '-' }} · {{ Format::number($line->quantity_unit, 2) }} {{ $line->unit_label }}</div>
                                @endforeach
                            </td>
                            <td data-label="Total" class="num"><b>@rupiah($inv->total)</b></td>
                            <td data-label="Status"><x-tag :tone="$inv->status_tone">{{ $inv->status_label }}</x-tag></td>
                            <td class="actions"><a href="{{ route('sales.print-receipt', $inv) }}" target="_blank" class="btn btn-light btn-sm"><i class="bi bi-printer"></i> Nota</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if($invoices->hasPages())
        <x-slot:footer>{{ $invoices->links() }}</x-slot:footer>
    @endif
</x-panel>

@include('sales._pay-modal')
@endsection
