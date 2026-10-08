@extends('layouts.app')

@section('title', 'Pendapatan Lain')

@php use App\Models\OtherIncome; use App\Support\Format; @endphp

@section('content')
<x-page-header title="Pendapatan Lain" subtitle="Uang masuk selain jual telur. Otomatis ikut dihitung di laba dan uang kas." icon="bi-cash-coin">
    <a href="{{ route('other-incomes.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Catat Pendapatan</a>
</x-page-header>

<x-alerts />

<x-panel>
    <form method="GET" class="filter-bar">
        <x-field label="Dari tanggal" for="start_date">
            <input type="date" id="start_date" name="start_date" value="{{ $start->toDateString() }}" class="form-control">
        </x-field>
        <x-field label="Sampai tanggal" for="end_date">
            <input type="date" id="end_date" name="end_date" value="{{ $end->toDateString() }}" class="form-control">
        </x-field>
        <x-field label="Jenis" for="category">
            <select id="category" name="category" class="form-select">
                <option value="">Semua jenis</option>
                @foreach(OtherIncome::CATEGORIES as $key => $c)
                    <option value="{{ $key }}" @selected($category === $key)>{{ $c['label'] }}</option>
                @endforeach
            </select>
        </x-field>
        <div class="btns">
            <button type="submit" class="btn btn-primary" data-no-lock><i class="bi bi-search"></i> Tampilkan</button>
            <a href="{{ route('other-incomes.index') }}" class="btn btn-light" title="Atur ulang"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-panel>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><x-stat label="Total pendapatan lain" :value="Format::rupiah($total)" icon="bi-cash-coin" tone="success" :hint="Format::date($start) . ' – ' . Format::date($end)" /></div>
    @foreach(OtherIncome::CATEGORIES as $key => $c)
        @if(($byCategory[$key] ?? 0) > 0)
            <div class="col-sm-6 col-xl-3"><x-stat :label="$c['label']" :value="Format::rupiah($byCategory[$key])" :icon="$c['icon']" tone="brand" /></div>
        @endif
    @endforeach
</div>

<x-panel title="Daftar pendapatan" icon="bi-list-ul" flush>
    @if($incomes->isEmpty())
        <x-empty icon="bi-cash-coin" title="Belum ada pendapatan lain pada periode ini">
            Jual ayam afkir, kotoran ayam, atau karung bekas? Catat di sini agar laba terhitung lengkap.
            <x-slot:action><a href="{{ route('other-incomes.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Catat Pendapatan</a></x-slot:action>
        </x-empty>
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Tanggal</th><th>Uraian</th><th>Jenis</th><th class="num">Jumlah</th><th class="num">Total</th><th class="actions">Aksi</th></tr></thead>
                <tbody>
                    @foreach($incomes as $i)
                        <tr>
                            <td class="title-cell"><b>{{ Format::date($i->income_date) }}</b></td>
                            <td data-label="Uraian">
                                <b>{{ $i->item_name }}</b>
                                @if($i->buyer || $i->coop)<div class="text-muted small">{{ collect([$i->buyer, $i->coop?->name])->filter()->join(' · ') }}</div>@endif
                            </td>
                            <td data-label="Jenis"><x-tag tone="brand" :icon="OtherIncome::CATEGORIES[$i->category]['icon'] ?? null">{{ $i->category_label }}</x-tag></td>
                            <td data-label="Jumlah" class="num">{{ Format::number($i->quantity, 2) }} {{ $i->unit }}<div class="text-muted small">× @rupiah($i->unit_price)</div></td>
                            <td data-label="Total" class="num"><b class="text-success">@rupiah($i->total_amount)</b></td>
                            <td class="actions">
                                <a href="{{ route('other-incomes.edit', $i) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Ubah</a>
                                <x-delete-button :action="route('other-incomes.destroy', $i)" icon-only title="Hapus pendapatan ini?" :message="$i->item_name . ' sebesar ' . Format::rupiah($i->total_amount) . ' akan dipindah ke Sampah.'" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if($incomes->hasPages())
        <x-slot:footer>{{ $incomes->links() }}</x-slot:footer>
    @endif
</x-panel>
@endsection
