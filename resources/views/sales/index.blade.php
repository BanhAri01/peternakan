@extends('layouts.app')

@section('title', 'Penjualan Telur')

@php
    use App\Support\Format;
    $firstGrade = $grades->first(fn ($g) => !$g->is_mixed) ?? $grades->first();
    $oldLines   = old('lines', [['egg_grade_id' => $firstGrade->id ?? '', 'unit_type' => 'kg', 'quantity_unit' => '', 'price_per_unit' => '', 'weight_kg' => '']]);
    $gradeInfo  = $grades->mapWithKeys(fn ($g) => [$g->id => ['name' => $g->name, 'stock' => $stock[$g->id] ?? 0]]);
@endphp

@section('content')
<x-page-header title="Penjualan Telur" subtitle="Satu nota bisa berisi beberapa jenis telur. Setelah disimpan, nota bisa langsung dicetak." icon="bi-basket2-fill">
    <a href="{{ route('customers.index') }}" class="btn btn-light"><i class="bi bi-person-lines-fill"></i> Pelanggan & Piutang</a>
</x-page-header>

<x-alerts />

@if(session('new_invoice_id'))
    <div class="notice notice-info">
        <i class="bi bi-printer-fill"></i>
        <div class="d-flex flex-wrap align-items-center gap-2 w-100">
            <span class="me-auto">Cetak nota untuk penjualan barusan?</span>
            <a href="{{ route('sales.print-receipt', session('new_invoice_id')) }}" target="_blank" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Cetak Nota</a>
        </div>
    </div>
@endif

<div class="row g-3 mb-3">
    <div class="col-md-4"><x-stat label="Terjual hari ini" :value="Format::rupiah($summary['today_total'])" icon="bi-cash-coin" tone="success" :hint="$summary['today_count'] . ' nota · ' . Format::number($summary['today_kg'], 1) . ' kg'" /></div>
    <div class="col-md-4"><x-stat label="Terjual bulan ini" :value="Format::rupiah($summary['month_total'])" icon="bi-calendar-check" tone="info" /></div>
    <div class="col-md-4"><x-stat label="Piutang belum dibayar" :value="Format::rupiah($summary['debt'])" icon="bi-hourglass-split" :tone="$summary['debt'] > 0 ? 'warning' : 'success'" hint="Tekan untuk lihat siapa saja." :href="route('customers.index')" /></div>
</div>

{{-- ================= FORMULIR NOTA BARU ================= --}}
<x-panel title="Buat nota penjualan" icon="bi-plus-circle-fill" tone="egg">
    <form action="{{ route('sales.store') }}" method="POST"
          x-data="saleForm({
              lines: @js(array_values($oldLines)),
              mode: @js(old('payment_mode', 'lunas')),
              paid: @js(old('paid_amount', '')),
              lastPrices: @js($lastPrices),
              gradeInfo: @js($gradeInfo),
              defaultGrade: @js((string) ($firstGrade->id ?? '')),
          })">
        @csrf

        <div class="row g-3">
            <div class="col-md-7">
                <x-field label="Nama pembeli" name="customer_name" required hint="Ketik nama baru atau pilih dari daftar. Pembeli baru otomatis tersimpan.">
                    <input type="text" id="customer_name" name="customer_name" list="customer_list" value="{{ old('customer_name') }}" class="form-control" placeholder="Contoh: Bakul Bu Sari" autocomplete="off" required>
                    <datalist id="customer_list">
                        @foreach($customers as $c)
                            <option value="{{ $c->name }}">
                        @endforeach
                    </datalist>
                </x-field>
            </div>
            <div class="col-md-5">
                <x-field label="Tanggal" name="sale_date" required>
                    <input type="date" id="sale_date" name="sale_date" value="{{ old('sale_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}" class="form-control" required>
                </x-field>
            </div>
        </div>

        <div class="divider-label">Telur yang dibeli</div>

        <template x-for="(line, i) in lines" :key="i">
            <div class="grade-box mb-3" :class="{ 'has-value': subtotal(line) > 0 }">
                <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                    <b x-text="'Baris ' + (i + 1)"></b>
                    <button type="button" class="btn btn-ghost-danger btn-sm" x-show="lines.length > 1" @click="lines.splice(i, 1)"><i class="bi bi-x-lg"></i> Hapus baris</button>
                </div>
                <div class="row g-2">
                    <div class="col-md-5">
                        <label class="mini-label text-start">Jenis telur</label>
                        <select class="form-select" :name="'lines[' + i + '][egg_grade_id]'" x-model="line.egg_grade_id" @change="fillPrice(line)" required>
                            @foreach($grades as $g)
                                <option value="{{ $g->id }}">{{ $g->name }} (stok {{ Format::number($stock[$g->id] ?? 0, 1) }} kg)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="mini-label text-start">Dijual per</label>
                        <div class="d-flex gap-2">
                            <template x-for="u in units" :key="u.v">
                                <button type="button" class="btn flex-fill" :class="line.unit_type === u.v ? 'btn-primary' : 'btn-light'" @click="line.unit_type = u.v; fillPrice(line)" x-text="u.l"></button>
                            </template>
                        </div>
                        <input type="hidden" :name="'lines[' + i + '][unit_type]'" :value="line.unit_type">
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="mini-label" x-text="'Jumlah (' + unitLabel(line) + ')'"></label>
                        <input type="number" step="any" min="0.01" inputmode="decimal" class="form-control num-lg" :name="'lines[' + i + '][quantity_unit]'" x-model="line.quantity_unit" placeholder="0" required>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="mini-label" x-text="'Harga per ' + unitLabel(line)"></label>
                        <input type="number" step="1" min="1" inputmode="numeric" class="form-control num-lg" :name="'lines[' + i + '][price_per_unit]'" x-model="line.price_per_unit" placeholder="0" required>
                    </div>
                    <div class="col-md-4" x-show="line.unit_type !== 'kg'">
                        <label class="mini-label">Berat ditimbang (kg)</label>
                        <input type="number" step="0.01" min="0" class="form-control num-lg" :name="'lines[' + i + '][weight_kg]'" x-model="line.weight_kg" placeholder="otomatis">
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-2">
                    <span class="money-preview" x-text="'Subtotal: ' + rupiah(subtotal(line))"></span>
                </div>
            </div>
        </template>

        <button type="button" class="btn btn-light w-100 mb-3" @click="addLine()" x-show="lines.length < 20">
            <i class="bi bi-plus-lg"></i> Tambah jenis telur lain
        </button>

        <div class="summary-bar" style="grid-template-columns: 1fr">
            <div><div class="k">Total yang harus dibayar</div><div class="v" style="font-size:1.8rem" x-text="rupiah(total())"></div></div>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="field">
                    <span class="field-label">Pembayaran</span>
                    <div class="choices">
                        <label class="choice"><input type="radio" name="payment_mode" value="lunas" x-model="mode"><span><i class="bi bi-check-circle-fill"></i>Lunas<small>bayar penuh</small></span></label>
                        <label class="choice warning"><input type="radio" name="payment_mode" value="sebagian" x-model="mode"><span><i class="bi bi-pie-chart-fill"></i>Sebagian<small>bayar sebagian</small></span></label>
                        <label class="choice danger"><input type="radio" name="payment_mode" value="tempo" x-model="mode"><span><i class="bi bi-hourglass-split"></i>Belum bayar<small>utang / tempo</small></span></label>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div x-show="mode === 'sebagian'" x-cloak>
                    <x-field label="Uang yang sudah diterima" name="paid_amount" required>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" id="paid_amount" name="paid_amount" min="0" step="1" x-model="paid" class="form-control num-lg">
                        </div>
                        <div class="money-preview" x-show="paid" x-text="'Sisa utang: ' + rupiah(Math.max(0, total() - (Number(paid) || 0)))"></div>
                    </x-field>
                </div>
                <div x-show="mode !== 'lunas'" x-cloak>
                    <x-field label="Tanggal janji bayar" name="due_date" hint="Jika kosong, otomatis 7 hari dari tanggal jual.">
                        <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}" class="form-control">
                    </x-field>
                </div>
            </div>
        </div>

        <details class="mb-3">
            <summary class="fw-bold text-muted" style="cursor:pointer; min-height:40px">Tambah catatan / nomor HP pembeli</summary>
            <div class="row g-3 pt-2">
                <div class="col-md-5">
                    <x-field label="Nomor HP / WhatsApp" name="customer_phone" optional>
                        <input type="tel" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" class="form-control" placeholder="08xxxxxxxxxx">
                    </x-field>
                </div>
                <div class="col-md-7">
                    <x-field label="Catatan di nota" name="notes" optional>
                        <input type="text" id="notes" name="notes" value="{{ old('notes') }}" class="form-control" maxlength="500">
                    </x-field>
                </div>
            </div>
        </details>

        <button type="submit" class="btn btn-egg btn-xl w-100"><i class="bi bi-check2-circle"></i> Simpan Nota</button>
    </form>
</x-panel>

{{-- ================= RIWAYAT NOTA ================= --}}
<x-panel title="Riwayat nota penjualan" icon="bi-clock-history" flush>
    <x-slot:actions>
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <select name="status" class="form-select" style="min-height:44px; width:auto" onchange="this.form.submit()" aria-label="Saring status">
                <option value="all" @selected($status === 'all')>Semua</option>
                <option value="unpaid" @selected($status === 'unpaid')>Belum lunas</option>
                <option value="paid" @selected($status === 'paid')>Lunas</option>
            </select>
            <input type="search" name="q" value="{{ $search }}" placeholder="Cari pembeli / no. nota..." class="form-control" style="min-height:44px; width:220px" aria-label="Cari">
        </form>
    </x-slot:actions>

    @if($invoices->isEmpty())
        <x-empty icon="bi-receipt" title="Belum ada nota">Nota yang dibuat akan muncul di sini.</x-empty>
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead>
                    <tr><th>Nota</th><th>Pembeli</th><th>Isi</th><th class="num">Total</th><th>Status</th><th class="actions">Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach($invoices as $inv)
                        <tr>
                            <td class="title-cell"><div><b>{{ $inv->number }}</b><div class="text-muted small">{{ Format::date($inv->sale_date, 'D, d M Y') }}</div></div></td>
                            <td data-label="Pembeli"><a href="{{ route('customers.show', $inv->customer_id) }}" class="fw-800 text-decoration-none text-ink">{{ $inv->customer->name ?? '-' }}</a></td>
                            <td data-label="Isi">
                                @foreach($inv->lines as $line)
                                    <div class="small">{{ $line->grade->name ?? '-' }} · {{ Format::number($line->quantity_unit, 2) }} {{ $line->unit_label }} × @rupiah($line->price_per_unit)</div>
                                @endforeach
                            </td>
                            <td data-label="Total" class="num"><b>@rupiah($inv->total)</b></td>
                            <td data-label="Status">
                                <x-tag :tone="$inv->status_tone">{{ $inv->status_label }}</x-tag>
                                @if($inv->debt > 0)
                                    <div class="small text-danger fw-bold mt-1">Sisa @rupiah($inv->debt)</div>
                                    @if($inv->due_date)
                                        <div class="small {{ $inv->due_date->isPast() ? 'text-danger' : 'text-muted' }}">Tempo {{ Format::date($inv->due_date) }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="actions">
                                <a href="{{ route('sales.print-receipt', $inv) }}" target="_blank" class="btn btn-light btn-sm"><i class="bi bi-printer"></i> Nota</a>
                                @if($inv->debt > 0)
                                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#payModal"
                                            data-action="{{ route('sales.pay-debt', $inv) }}" data-name="{{ ($inv->customer->name ?? '') . ' · ' . $inv->number }}" data-debt="{{ $inv->debt }}">
                                        <i class="bi bi-cash"></i> Bayar
                                    </button>
                                @endif
                                <x-delete-button :action="route('sales.destroy', $inv)" icon-only title="Hapus nota ini?"
                                    :message="'Nota ' . $inv->number . ' (' . ($inv->customer->name ?? '') . ', ' . Format::rupiah($inv->total) . ') akan dihapus.'" />
                            </td>
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

@push('scripts')
<script>
    function saleForm(init) {
        return Object.assign({
            units: [{ v: 'kg', l: 'Kilo' }, { v: 'krat', l: 'Rak' }, { v: 'butir', l: 'Butir' }],
            unitLabel(line) { return { kg: 'kg', krat: 'rak', butir: 'butir' }[line.unit_type]; },
            subtotal(line) { return (Number(line.quantity_unit) || 0) * (Number(line.price_per_unit) || 0); },
            total() { return this.lines.reduce((t, l) => t + this.subtotal(l), 0); },
            fillPrice(line) {
                var p = this.lastPrices[line.egg_grade_id + '-' + line.unit_type];
                if (p) line.price_per_unit = p;
            },
            addLine() {
                var line = { egg_grade_id: this.defaultGrade, unit_type: 'kg', quantity_unit: '', price_per_unit: '', weight_kg: '' };
                this.fillPrice(line);
                this.lines.push(line);
            },
            init() {
                this.lines.forEach(l => { l.egg_grade_id = String(l.egg_grade_id); if (!l.price_per_unit) this.fillPrice(l); });
            },
        }, init);
    }
</script>
@endpush
