@extends('layouts.app')

@section('title', 'Sampah')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Sampah" subtitle="Data yang dihapus tidak langsung hilang. Tekan Pulihkan untuk mengembalikannya seperti semula." icon="bi-trash3" />

<x-alerts />

<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach($types as $key => $type)
        <a href="{{ route('trash.index', ['jenis' => $key]) }}" class="btn {{ $active === $key ? 'btn-primary' : 'btn-light' }}">
            <i class="bi {{ $type['icon'] }}"></i> {{ $type['label'] }}
            <span class="badge {{ $active === $key ? 'text-bg-light' : 'text-bg-secondary' }} ms-1">{{ $counts[$key] }}</span>
        </a>
    @endforeach
</div>

<x-panel :title="$types[$active]['label'] . ' yang dihapus'" icon="bi-trash3" flush>
    @if($items->isEmpty())
        <x-empty icon="bi-check2-circle" title="Tidak ada data di sampah">
            Semua {{ strtolower($types[$active]['label']) }} masih tersimpan dengan aman.
        </x-empty>
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Data</th><th>Keterangan</th><th>Dihapus</th><th class="actions">Aksi</th></tr></thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td class="title-cell">
                                @switch($active)
                                    @case('nota')
                                        <div><b>{{ $item->number }}</b><div class="text-muted small">{{ $item->customer->name ?? '-' }}</div></div>
                                        @break
                                    @case('panen')
                                        <div><b>{{ $item->coop->name ?? '-' }}</b><div class="text-muted small">{{ Format::date($item->log_date) }}</div></div>
                                        @break
                                    @case('sortir')
                                        <div><b>Sortir {{ Format::date($item->sort_date) }}</b></div>
                                        @break
                                    @case('pengeluaran')
                                        <div><b>{{ $item->item_name }}</b><div class="text-muted small">{{ $item->category }}</div></div>
                                        @break
                                    @case('vaksin')
                                        <div><b>{{ $item->vaccine_name }}</b><div class="text-muted small">{{ $item->coop->name ?? '-' }}</div></div>
                                        @break
                                @endswitch
                            </td>
                            <td data-label="Keterangan">
                                @switch($active)
                                    @case('nota')
                                        {{ Format::date($item->sale_date) }} · <b>@rupiah($invoiceTotals[$item->id] ?? 0)</b>
                                        @break
                                    @case('panen')
                                        {{ Format::number($item->eggs_total_count) }} butir · {{ Format::number($item->eggs_total_kg, 1) }} kg
                                        @if($item->mortality + $item->cull > 0)<div class="text-muted small">{{ $item->mortality + $item->cull }} ekor mati/afkir</div>@endif
                                        @break
                                    @case('sortir')
                                        {{ Format::number($item->input_count) }} butir · {{ Format::number($item->input_kg, 1) }} kg
                                        @break
                                    @case('pengeluaran')
                                        {{ Format::date($item->transaction_date) }} · <b>@rupiah($item->total_amount)</b>
                                        @break
                                    @case('vaksin')
                                        {{ Format::date($item->vaccination_date) }} · <b>@rupiah($item->cost)</b>
                                        @break
                                @endswitch
                            </td>
                            <td data-label="Dihapus">
                                {{ $item->deleted_at->translatedFormat('d M Y, H:i') }}
                                <div class="text-muted small">oleh {{ $deletedBy[$item->id] ?? '-' }}</div>
                            </td>
                            <td class="actions">
                                <form action="{{ route('trash.restore', [$active, $item->id]) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Pulihkan</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if($items->hasPages())
        <x-slot:footer>{{ $items->links() }}</x-slot:footer>
    @endif
</x-panel>
@endsection
