@extends('layouts.app')

@section('title', 'Pemasok')

@php
    use App\Support\Format;
    $types = ['feed' => ['Pakan', 'brand'], 'egg' => ['Telur', 'egg'], 'both' => ['Pakan & Telur', 'info']];
@endphp

@section('content')
<x-page-header title="Pemasok" subtitle="Toko pakan dan peternak lain tempat Anda membeli. Pemasok baru otomatis tersimpan saat mencatat pembelian." icon="bi-shop" :back="route('procurement.index')" back-label="Belanja Pakan & Telur" />

<x-alerts />

<x-panel flush>
    @if($suppliers->isEmpty())
        <x-empty icon="bi-shop" title="Belum ada pemasok" />
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Nama</th><th>Jenis</th><th>No. HP</th><th class="num">Total beli pakan</th><th class="num">Total beli telur</th><th class="actions">Aksi</th></tr></thead>
                <tbody>
                    @foreach($suppliers as $s)
                        <tr>
                            <td class="title-cell"><b>{{ $s->name }}</b></td>
                            <td data-label="Jenis"><x-tag :tone="$types[$s->type][1] ?? 'neutral'">{{ $types[$s->type][0] ?? $s->type }}</x-tag></td>
                            <td data-label="No. HP">
                                @if($s->phone)
                                    <a href="https://wa.me/{{ Format::waNumber($s->phone) }}" target="_blank" rel="noopener" class="text-decoration-none"><i class="bi bi-whatsapp"></i> {{ $s->phone }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td data-label="Total beli pakan" class="num">@rupiah($s->feed_total ?? 0)</td>
                            <td data-label="Total beli telur" class="num">@rupiah($s->egg_total ?? 0)</td>
                            <td class="actions"><a href="{{ route('suppliers.edit', $s) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Ubah</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if($suppliers->hasPages())
        <x-slot:footer>{{ $suppliers->links() }}</x-slot:footer>
    @endif
</x-panel>
@endsection
