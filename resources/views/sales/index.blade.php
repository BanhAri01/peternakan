@extends('layouts.app')

@section('title', 'Penjualan Telur')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Penjualan Telur" subtitle="Catat setiap telur yang dijual, lalu cetak nota atau kirim tagihan lewat WhatsApp." icon="bi-basket2-fill">
    <a href="{{ route('customers.index') }}" class="btn btn-light"><i class="bi bi-person-lines-fill"></i> Pelanggan & Piutang</a>
</x-page-header>

<x-alerts />

@if(session('new_sale_id'))
    <div class="notice notice-info">
        <i class="bi bi-printer-fill"></i>
        <div class="d-flex flex-wrap align-items-center gap-2 w-100">
            <span class="me-auto">Mau cetak nota untuk penjualan barusan?</span>
            <a href="{{ route('sales.print-receipt', session('new_sale_id')) }}" target="_blank" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Cetak Nota</a>
        </div>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-4"><x-stat label="Terjual hari ini" :value="Format::rupiah($summary['today_total'])" icon="bi-cash-coin" tone="success" :hint="$summary['today_count'] . ' transaksi · ' . Format::number($summary['today_kg'], 1) . ' kg'" /></div>
    <div class="col-md-4"><x-stat label="Terjual bulan ini" :value="Format::rupiah($summary['month_total'])" icon="bi-calendar-check" tone="info" /></div>
    <div class="col-md-4"><x-stat label="Piutang belum dibayar" :value="Format::rupiah($summary['debt'])" icon="bi-hourglass-split" :tone="$summary['debt'] > 0 ? 'warning' : 'success'" hint="Uang pelanggan yang belum masuk. Tekan untuk lihat siapa saja." :href="route('customers.index')" /></div>
</div>

{{-- ================= FORMULIR JUAL ================= --}}
        <x-panel title="Catat penjualan baru" icon="bi-plus-circle-fill" tone="egg">
            <form action="{{ route('sales.store') }}" method="POST"
                  x-data="saleForm({
                      unit: @js(old('unit_type', 'kg')),
                      qty: @js(old('quantity_unit', '')),
                      price: @js(old('price_per_unit', '')),
                      mode: @js(old('payment_mode', 'lunas')),
                      grade: @js((string) old('egg_grade_id', $grades->first()->id ?? '')),
                      lastPrices: @js($lastPrices),
                  })">
                @csrf
                <div class="row g-4">
                <div class="col-lg-6">

                <x-field label="Nama pembeli" name="customer_name" required hint="Ketik nama baru atau pilih dari daftar. Pembeli baru otomatis tersimpan.">
                    <input type="text" id="customer_name" name="customer_name" list="customer_list" value="{{ old('customer_name') }}" class="form-control" placeholder="Contoh: Bakul Bu Sari" autocomplete="off" required>
                    <datalist id="customer_list">
                        @foreach($customers as $c)
                            <option value="{{ $c->name }}">
                        @endforeach
                    </datalist>
                </x-field>

                <div class="row g-3">
                    <div class="col-sm-7">
                        <x-field label="Jenis telur" name="egg_grade_id" required>
                            <select id="egg_grade_id" name="egg_grade_id" class="form-select" x-model="grade" @change="fillPrice()" required>
                                @foreach($grades as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }} (stok {{ Format::number($g->stock_kg, 1) }} kg)</option>
                                @endforeach
                            </select>
                        </x-field>
                    </div>
                    <div class="col-sm-5">
                        <x-field label="Tanggal" name="sale_date" required>
                            <input type="date" id="sale_date" name="sale_date" value="{{ old('sale_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-control" required>
                        </x-field>
                    </div>
                </div>

                <div class="field">
                    <span class="field-label">Dijual per</span>
                    <div class="choices">
                        @foreach(['kg' => ['Kilo', 'bi-speedometer2', 'ditimbang'], 'krat' => ['Rak', 'bi-grid-3x3', 'isi 30 butir'], 'butir' => ['Butir', 'bi-egg', 'eceran']] as $val => [$lbl, $ico, $sub])
                            <label class="choice">
                                <input type="radio" name="unit_type" value="{{ $val }}" x-model="unit" @change="fillPrice()">
                                <span><i class="bi {{ $ico }}"></i>{{ $lbl }}<small>{{ $sub }}</small></span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-6">
                        <x-field name="quantity_unit" required>
                            <label class="field-label" for="quantity_unit">Jumlah <span x-text="unitLabel()"></span><span class="req">*</span></label>
                            <input type="number" id="quantity_unit" name="quantity_unit" step="any" min="0.01" inputmode="decimal" x-model="qty" class="form-control num-lg" placeholder="0" required>
                        </x-field>
                    </div>
                    <div class="col-6">
                        <x-field name="price_per_unit" required>
                            <label class="field-label" for="price_per_unit">Harga per <span x-text="unitLabel()"></span><span class="req">*</span></label>
                            <input type="number" id="price_per_unit" name="price_per_unit" step="1" min="1" inputmode="numeric" x-model="price" class="form-control num-lg" placeholder="0" required>
                            <div class="money-preview" x-show="price" x-text="'= ' + rupiah(price)"></div>
                        </x-field>
                    </div>
                </div>

                <div class="field" x-show="unit !== 'kg'" x-cloak>
                    <label class="field-label" for="weight_kg">Berat ditimbang (kg) <span class="opt">(boleh kosong)</span></label>
                    <input type="number" id="weight_kg" name="weight_kg" step="0.01" min="0" value="{{ old('weight_kg') }}" class="form-control" placeholder="Jika kosong, dihitung otomatis">
                    <div class="field-hint">Untuk mengurangi stok telur. Perkiraan: 1 rak ±1,9 kg, 1 butir ±0,06 kg.</div>
                </div>

                </div>
                <div class="col-lg-6">
                <div class="summary-bar" style="grid-template-columns: 1fr;">
                    <div><div class="k">Total yang harus dibayar</div><div class="v" style="font-size:1.7rem" x-text="rupiah(total())"></div></div>
                </div>

                <div class="field">
                    <span class="field-label">Pembayaran</span>
                    <div class="choices">
                        <label class="choice"><input type="radio" name="payment_mode" value="lunas" x-model="mode"><span><i class="bi bi-check-circle-fill"></i>Lunas<small>bayar penuh</small></span></label>
                        <label class="choice warning"><input type="radio" name="payment_mode" value="sebagian" x-model="mode"><span><i class="bi bi-pie-chart-fill"></i>Sebagian<small>bayar sebagian</small></span></label>
                        <label class="choice danger"><input type="radio" name="payment_mode" value="tempo" x-model="mode"><span><i class="bi bi-hourglass-split"></i>Belum bayar<small>utang / tempo</small></span></label>
                    </div>
                </div>

                <div x-show="mode === 'sebagian'" x-cloak>
                    <x-field label="Uang yang sudah diterima" name="paid_amount" required>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" id="paid_amount" name="paid_amount" min="0" step="1" value="{{ old('paid_amount') }}" x-model="paid" class="form-control num-lg">
                        </div>
                        <div class="money-preview" x-show="paid" x-text="'Sisa utang: ' + rupiah(Math.max(0, total() - (Number(paid) || 0)))"></div>
                    </x-field>
                </div>

                <div x-show="mode !== 'lunas'" x-cloak>
                    <x-field label="Tanggal janji bayar (jatuh tempo)" name="due_date" hint="Jika kosong, otomatis 7 hari dari tanggal jual.">
                        <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}" class="form-control">
                    </x-field>
                </div>

                <details class="mb-3">
                    <summary class="fw-bold text-muted" style="cursor:pointer; min-height:40px">Tambah catatan / nomor HP pembeli</summary>
                    <div class="pt-2">
                        <x-field label="Nomor HP / WhatsApp" name="customer_phone" optional>
                            <input type="tel" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" class="form-control" placeholder="08xxxxxxxxxx">
                        </x-field>
                        <x-field label="Catatan" name="notes" optional>
                            <textarea id="notes" name="notes" rows="2" class="form-control" maxlength="500">{{ old('notes') }}</textarea>
                        </x-field>
                    </div>
                </details>

                <button type="submit" class="btn btn-egg btn-xl w-100"><i class="bi bi-check2-circle"></i> Simpan Penjualan</button>
                </div>
                </div>
            </form>
        </x-panel>

    {{-- ================= RIWAYAT ================= --}}
        <x-panel title="Riwayat penjualan" icon="bi-clock-history" flush>
            <x-slot:actions>
                <form method="GET" class="d-flex gap-2 flex-wrap">
                    <select name="status" class="form-select" style="min-height:44px; width:auto" onchange="this.form.submit()" aria-label="Saring status">
                        <option value="all" @selected($status === 'all')>Semua</option>
                        <option value="unpaid" @selected($status === 'unpaid')>Belum lunas</option>
                        <option value="paid" @selected($status === 'paid')>Lunas</option>
                    </select>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari pembeli..." class="form-control" style="min-height:44px; width:170px" aria-label="Cari pembeli">
                </form>
            </x-slot:actions>

            @if($sales->isEmpty())
                <x-empty icon="bi-basket" title="Belum ada penjualan">Penjualan yang dicatat akan muncul di sini.</x-empty>
            @else
                <div class="table-wrap">
                    <table class="tbl stack">
                        <thead>
                            <tr><th>Pembeli</th><th>Telur</th><th class="num">Total</th><th>Status</th><th class="actions">Aksi</th></tr>
                        </thead>
                        <tbody>
                            @foreach($sales as $sale)
                                <tr>
                                    <td class="title-cell">
                                        <div>
                                            <a href="{{ route('customers.show', $sale->customer_id) }}" class="fw-800 text-decoration-none text-ink">{{ $sale->customer->name ?? '-' }}</a>
                                            <div class="text-muted small">{{ Format::date($sale->sale_date, 'D, d M Y') }}</div>
                                        </div>
                                    </td>
                                    <td data-label="Telur">
                                        {{ $sale->grade->name ?? '-' }}
                                        <div class="text-muted small">{{ Format::number($sale->quantity_unit, 2) }} {{ $sale->unit_label }} × @rupiah($sale->price_per_unit)</div>
                                    </td>
                                    <td data-label="Total" class="num"><b>@rupiah($sale->total_amount)</b></td>
                                    <td data-label="Status">
                                        <x-tag :tone="$sale->status_tone">{{ $sale->status_label }}</x-tag>
                                        @if($sale->debt_amount > 0)
                                            <div class="small text-danger fw-bold mt-1">Sisa @rupiah($sale->debt_amount)</div>
                                            @if($sale->due_date)
                                                <div class="small {{ $sale->due_date->isPast() ? 'text-danger' : 'text-muted' }}">Tempo {{ Format::date($sale->due_date) }}</div>
                                            @endif
                                        @endif
                                    </td>
                                    <td class="actions">
                                        <a href="{{ route('sales.print-receipt', $sale) }}" target="_blank" class="btn btn-light btn-sm" title="Cetak nota"><i class="bi bi-printer"></i> Nota</a>
                                        @if($sale->debt_amount > 0)
                                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#payModal"
                                                    data-action="{{ route('sales.pay-debt', $sale) }}" data-name="{{ $sale->customer->name ?? '' }}" data-debt="{{ (float) $sale->debt_amount }}">
                                                <i class="bi bi-cash"></i> Bayar
                                            </button>
                                        @endif
                                        <x-delete-button :action="route('sales.destroy', $sale)" icon-only title="Hapus penjualan ini?"
                                            :message="'Penjualan ke ' . ($sale->customer->name ?? '') . ' sebesar ' . Format::rupiah($sale->total_amount) . ' akan dihapus.'" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            @if($sales->hasPages())
                <x-slot:footer>{{ $sales->links() }}</x-slot:footer>
            @endif
        </x-panel>

@include('sales._pay-modal')
@endsection

@push('scripts')
<script>
    function saleForm(init) {
        return Object.assign({
            paid: @js(old('paid_amount', '')),
            unitLabel() { return { kg: 'kg', krat: 'rak', butir: 'butir' }[this.unit]; },
            total() { return (Number(this.qty) || 0) * (Number(this.price) || 0); },
            fillPrice() {
                var p = this.lastPrices[this.grade + '-' + this.unit];
                if (p) this.price = p;
            },
            init() { if (!this.price) this.fillPrice(); },
        }, init);
    }
</script>
@endpush
