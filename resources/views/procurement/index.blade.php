@extends('layouts.app')

@section('title', 'Belanja Pakan & Telur')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Belanja Pakan & Telur" subtitle="Catat pakan yang datang ke gudang dan telur yang dibeli dari peternak lain. Stok bertambah otomatis." icon="bi-truck">
    <a href="{{ route('suppliers.index') }}" class="btn btn-light"><i class="bi bi-shop"></i> Daftar Pemasok</a>
    <a href="{{ route('feed-stocks.index') }}" class="btn btn-light"><i class="bi bi-box-seam"></i> Stok Pakan</a>
</x-page-header>

<x-alerts />

<div class="row g-3 mb-3">
    <div class="col-md-6"><x-stat label="Belanja pakan bulan ini" :value="Format::rupiah($monthFeed)" icon="bi-box-seam-fill" tone="brand" /></div>
    <div class="col-md-6"><x-stat label="Beli telur bulan ini" :value="Format::rupiah($monthEgg)" icon="bi-egg-fill" tone="egg" /></div>
</div>

<ul class="nav nav-pills gap-2 mb-3" role="tablist">
    <li class="nav-item">
        <a href="{{ route('procurement.index') }}" class="btn {{ $tab === 'pakan' ? 'btn-primary' : 'btn-light' }}"><i class="bi bi-box-seam-fill"></i> Pakan datang</a>
    </li>
    <li class="nav-item">
        <a href="{{ route('procurement.index', ['tab' => 'telur']) }}" class="btn {{ $tab === 'telur' ? 'btn-egg' : 'btn-light' }}"><i class="bi bi-egg-fill"></i> Beli telur dari luar</a>
    </li>
</ul>

<datalist id="supplier_list">
    @foreach($suppliers as $s)
        <option value="{{ $s->name }}">
    @endforeach
</datalist>

@if($tab === 'pakan')
    <div class="row g-3">
        <div class="col-xl-5">
            <x-panel title="Catat pakan yang datang" icon="bi-plus-circle-fill" tone="brand">
                @if($feedStocks->isEmpty())
                    <x-empty icon="bi-box-seam" title="Belum ada jenis pakan">
                        <x-slot:action><a href="{{ route('feed-stocks.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah jenis pakan</a></x-slot:action>
                    </x-empty>
                @else
                    <form action="{{ route('procurement.feed-purchase.store') }}" method="POST"
                          x-data="{ sacks: @js(old('sacks_count', '')), extra: @js(old('extra_kg', '')), price: @js(old('cost_per_kg', '')), sackKg: {{ (float) $sackKg }},
                                    kg() { return (Number(this.sacks) || 0) * this.sackKg + (Number(this.extra) || 0) },
                                    total() { return this.kg() * (Number(this.price) || 0) } }">
                        @csrf
                        <x-field label="Jenis pakan" name="feed_stock_id" required>
                            <select id="feed_stock_id" name="feed_stock_id" class="form-select" required>
                                @foreach($feedStocks as $feed)
                                    <option value="{{ $feed->id }}" @selected(old('feed_stock_id') == $feed->id)>{{ $feed->feed_name }} (sisa {{ Format::number($feed->stock_kg) }} kg)</option>
                                @endforeach
                            </select>
                        </x-field>
                        <div class="row g-3">
                            <div class="col-sm-7">
                                <x-field label="Dibeli dari" name="supplier_name" required>
                                    <input type="text" id="supplier_name" name="supplier_name" list="supplier_list" value="{{ old('supplier_name') }}" class="form-control" placeholder="Nama toko pakan" autocomplete="off" required>
                                </x-field>
                            </div>
                            <div class="col-sm-5">
                                <x-field label="Tanggal datang" name="purchase_date" required>
                                    <input type="date" id="purchase_date" name="purchase_date" value="{{ old('purchase_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-control" required>
                                </x-field>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="mini-label" for="sacks_count">Jumlah karung ({{ Format::number($sackKg) }} kg)</label>
                                <input type="number" id="sacks_count" name="sacks_count" min="0" step="1" x-model="sacks" class="form-control num-lg" placeholder="0">
                            </div>
                            <div class="col-6">
                                <label class="mini-label" for="extra_kg">+ Tambahan (kg)</label>
                                <input type="number" id="extra_kg" name="extra_kg" min="0" step="0.1" x-model="extra" class="form-control num-lg" placeholder="0">
                            </div>
                            @error('sacks_count')<div class="col-12"><div class="field-error"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div></div>@enderror
                        </div>
                        <x-field label="Harga beli per kg" name="cost_per_kg" required>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" id="cost_per_kg" name="cost_per_kg" min="1" step="1" x-model="price" class="form-control num-lg" placeholder="Contoh: 7400" required>
                            </div>
                        </x-field>
                        <div class="summary-bar">
                            <div><div class="k">Total pakan</div><div class="v" x-text="angka(kg()) + ' kg'"></div></div>
                            <div><div class="k">Total bayar</div><div class="v" x-text="rupiah(total())"></div></div>
                        </div>
                        <x-field label="Catatan" name="notes" optional>
                            <input type="text" id="notes" name="notes" value="{{ old('notes') }}" class="form-control" placeholder="Nomor nota, dll">
                        </x-field>
                        <button type="submit" class="btn btn-primary btn-xl w-100"><i class="bi bi-box-arrow-in-down"></i> Masukkan ke Gudang</button>
                    </form>
                @endif
            </x-panel>
        </div>
        <div class="col-xl-7">
            <x-panel title="Riwayat pakan datang" icon="bi-clock-history" flush>
                @php
                    $thisMonth = [now()->startOfMonth()->toDateString(), now()->toDateString()];
                    $lastMonth = [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()];
                @endphp
                <form method="GET" action="{{ route('procurement.index') }}" class="filter-bar p-3 border-bottom">
                    <input type="hidden" name="tab" value="pakan">
                    <x-field label="Dari tanggal" for="dari">
                        <input type="date" id="dari" name="dari" value="{{ $filter['dari'] ?? '' }}" max="{{ now()->toDateString() }}" class="form-control">
                    </x-field>
                    <x-field label="Sampai tanggal" for="sampai">
                        <input type="date" id="sampai" name="sampai" value="{{ $filter['sampai'] ?? '' }}" max="{{ now()->toDateString() }}" class="form-control">
                    </x-field>
                    <x-field label="Jenis pakan" for="pakan">
                        <select id="pakan" name="pakan" class="form-select">
                            <option value="">Semua pakan</option>
                            @foreach($feedStocks as $f)
                                <option value="{{ $f->id }}" @selected(($filter['pakan'] ?? null) == $f->id)>{{ $f->feed_name }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <div class="btns flex-wrap">
                        <button type="submit" class="btn btn-primary" data-no-lock><i class="bi bi-search"></i> Tampilkan</button>
                        <a href="{{ route('procurement.index', ['tab' => 'pakan', 'dari' => $thisMonth[0], 'sampai' => $thisMonth[1]]) }}" class="btn btn-light">Bulan ini</a>
                        <a href="{{ route('procurement.index', ['tab' => 'pakan', 'dari' => $lastMonth[0], 'sampai' => $lastMonth[1]]) }}" class="btn btn-light">Bulan lalu</a>
                        @if($feedFiltered)
                            <a href="{{ route('procurement.index', ['tab' => 'pakan']) }}" class="btn btn-light"><i class="bi bi-x-lg"></i> Hapus filter</a>
                        @endif
                    </div>
                </form>
                @if($feedFiltered)
                    <div class="kv p-3 border-bottom">
                        <span class="k">
                            {{ isset($filter['dari']) ? Format::date($filter['dari']) : 'Awal' }} – {{ isset($filter['sampai']) ? Format::date($filter['sampai']) : 'hari ini' }}
                            · {{ (int) $feedSummary->times }} kali datang
                        </span>
                        <span class="v">{{ Format::number($feedSummary->kg) }} kg · <b>@rupiah($feedSummary->cost)</b></span>
                    </div>
                @endif
                @if($feedPurchases->isEmpty())
                    <x-empty icon="bi-truck" :title="$feedFiltered ? 'Tidak ada pakan datang di tanggal ini' : 'Belum ada pembelian pakan'" />
                @else
                    <div class="table-wrap">
                        <table class="tbl stack">
                            <thead><tr><th>Tanggal</th><th>Pakan</th><th class="num">Jumlah</th><th class="num">Harga/kg</th><th class="num">Total</th><th></th></tr></thead>
                            <tbody>
                                @foreach($feedPurchases as $p)
                                    <tr>
                                        <td class="title-cell"><div><b>{{ Format::date($p->purchase_date) }}</b><div class="text-muted small">{{ $p->supplier->name ?? '-' }}</div></div></td>
                                        <td data-label="Pakan">{{ $p->feedStock->feed_name ?? '-' }}</td>
                                        <td data-label="Jumlah" class="num">{{ Format::number($p->quantity_kg) }} kg</td>
                                        <td data-label="Harga/kg" class="num">@rupiah($p->cost_per_kg)</td>
                                        <td data-label="Total" class="num"><b>@rupiah($p->total_cost)</b></td>
                                        <td class="actions">
                                            <a href="{{ route('procurement.feed-purchase.edit', $p) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> Ubah</a>
                                            <x-delete-button :action="route('procurement.feed-purchase.destroy', $p)" title="Hapus catatan pakan ini?"
                                                :message="Format::number($p->quantity_kg) . ' kg ' . ($p->feedStock->feed_name ?? 'pakan') . ' tanggal ' . Format::date($p->purchase_date) . ' akan dipindah ke Sampah. Stok pakan dikurangi lagi dan bisa dipulihkan dari menu Sampah.'" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if($feedPurchases->hasPages())
                    <x-slot:footer>{{ $feedPurchases->links() }}</x-slot:footer>
                @endif
            </x-panel>
        </div>
    </div>
@else
    <div class="row g-3">
        <div class="col-xl-5">
            <x-panel title="Catat telur yang dibeli" icon="bi-plus-circle-fill" tone="egg" subtitle="Menambah stok telur untuk dijual lagi, tanpa mengubah data kandang.">
                <form action="{{ route('procurement.egg-purchase.store') }}" method="POST"
                      x-data="{ unit: @js(old('unit_type', 'krat')), qty: @js(old('quantity_unit', '')), price: @js(old('price_per_unit', '')),
                                label() { return { kg: 'kg', krat: 'rak', butir: 'butir' }[this.unit] },
                                total() { return (Number(this.qty) || 0) * (Number(this.price) || 0) } }">
                    @csrf
                    <div class="row g-3">
                        <div class="col-sm-7">
                            <x-field label="Dibeli dari" name="supplier_name" required>
                                <input type="text" id="supplier_name" name="supplier_name" list="supplier_list" value="{{ old('supplier_name') }}" class="form-control" placeholder="Nama peternak" autocomplete="off" required>
                            </x-field>
                        </div>
                        <div class="col-sm-5">
                            <x-field label="Tanggal" name="purchase_date" required>
                                <input type="date" id="purchase_date" name="purchase_date" value="{{ old('purchase_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-control" required>
                            </x-field>
                        </div>
                    </div>
                    <x-field label="Jenis telur" name="egg_grade_id" required>
                        <select id="egg_grade_id" name="egg_grade_id" class="form-select" required>
                            @foreach($eggGrades as $g)
                                <option value="{{ $g->id }}" @selected(old('egg_grade_id') == $g->id)>{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </x-field>
                    <div class="field">
                        <span class="field-label">Dibeli per</span>
                        <div class="choices">
                            <label class="choice"><input type="radio" name="unit_type" value="krat" x-model="unit"><span>Rak<small>isi 30</small></span></label>
                            <label class="choice"><input type="radio" name="unit_type" value="kg" x-model="unit"><span>Kilo<small>ditimbang</small></span></label>
                            <label class="choice"><input type="radio" name="unit_type" value="butir" x-model="unit"><span>Butir<small>eceran</small></span></label>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="field-label" for="quantity_unit">Jumlah <span x-text="label()"></span> <span class="req">*</span></label>
                            <input type="number" id="quantity_unit" name="quantity_unit" min="0.01" step="any" x-model="qty" class="form-control num-lg" required>
                        </div>
                        <div class="col-6">
                            <label class="field-label" for="price_per_unit">Harga per <span x-text="label()"></span> <span class="req">*</span></label>
                            <input type="number" id="price_per_unit" name="price_per_unit" min="1" step="1" x-model="price" class="form-control num-lg" required>
                            <div class="money-preview" x-show="price" x-text="'= ' + rupiah(price)"></div>
                        </div>
                    </div>
                    <div class="field mt-3" x-show="unit !== 'kg'">
                        <label class="field-label" for="weight_kg">Berat ditimbang (kg) <span class="opt">(boleh kosong)</span></label>
                        <input type="number" id="weight_kg" name="weight_kg" min="0" step="0.01" value="{{ old('weight_kg') }}" class="form-control" placeholder="Jika kosong, dihitung otomatis">
                    </div>
                    <div class="summary-bar mt-3" style="grid-template-columns:1fr">
                        <div><div class="k">Total bayar</div><div class="v" x-text="rupiah(total())"></div></div>
                    </div>
                    <button type="submit" class="btn btn-egg btn-xl w-100"><i class="bi bi-box-arrow-in-down"></i> Masukkan ke Stok Telur</button>
                </form>
            </x-panel>
        </div>
        <div class="col-xl-7">
            <x-panel title="Riwayat beli telur" icon="bi-clock-history" flush>
                @if($eggPurchases->isEmpty())
                    <x-empty icon="bi-egg" title="Belum ada pembelian telur" />
                @else
                    <div class="table-wrap">
                        <table class="tbl stack">
                            <thead><tr><th>Tanggal</th><th>Telur</th><th class="num">Jumlah</th><th class="num">Total</th></tr></thead>
                            <tbody>
                                @foreach($eggPurchases as $p)
                                    <tr>
                                        <td class="title-cell"><div><b>{{ Format::date($p->purchase_date) }}</b><div class="text-muted small">{{ $p->supplier->name ?? '-' }}</div></div></td>
                                        <td data-label="Telur">{{ $p->grade->name ?? '-' }}</td>
                                        <td data-label="Jumlah" class="num">{{ Format::number($p->quantity_unit, 2) }} {{ ['krat' => 'rak', 'kg' => 'kg', 'butir' => 'butir'][$p->unit_type] ?? $p->unit_type }}<div class="text-muted small">{{ Format::number($p->weight_kg, 1) }} kg</div></td>
                                        <td data-label="Total" class="num"><b>@rupiah($p->total_cost)</b></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                @if($eggPurchases->hasPages())
                    <x-slot:footer>{{ $eggPurchases->links() }}</x-slot:footer>
                @endif
            </x-panel>
        </div>
    </div>
@endif
@endsection
