@extends('layouts.app')

@section('title', 'Gaji Pekerja')

@php use App\Models\Attendance; use App\Services\Payroll; use App\Support\Format; @endphp

@section('content')
<x-page-header title="Gaji Pekerja" :subtitle="'Rekap kehadiran dan gaji ' . $periodLabel . '. Gaji yang dibayar otomatis masuk Buku Kas.'" icon="bi-cash-stack">
    <a href="{{ route('attendance.index') }}" class="btn btn-light"><i class="bi bi-calendar-check-fill"></i> Isi Absensi</a>
</x-page-header>

<x-alerts />

<x-panel>
    <form method="GET" class="filter-bar">
        <x-field label="Bulan" for="bulan">
            <input type="month" id="bulan" name="bulan" value="{{ $period }}" max="{{ today()->format('Y-m') }}" class="form-control">
        </x-field>
        <div class="btns">
            <button type="submit" class="btn btn-primary" data-no-lock><i class="bi bi-search"></i> Tampilkan</button>
        </div>
    </form>
</x-panel>

<div class="row g-3 mb-3">
    <div class="col-sm-6"><x-stat label="Belum dibayar (perkiraan)" :value="Format::rupiah($totalBase)" icon="bi-hourglass-split" tone="warning" /></div>
    <div class="col-sm-6"><x-stat label="Sudah dibayar" :value="Format::rupiah($totalPaid)" icon="bi-check-circle-fill" tone="success" /></div>
</div>

@forelse($rows as $row)
    @php $w = $row['worker']; @endphp
    <x-panel :title="$w->name" icon="bi-person-fill"
             :subtitle="$w->wage_type ? (Payroll::WAGE_TYPES[$w->wage_type] . ' · ' . Format::rupiah($w->wage_amount)) : 'Besaran gaji belum diatur'"
             x-data="{ open: false }">
        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach(Attendance::STATUSES as $key => $s)
                @if($row['counts'][$key] > 0)
                    <x-tag :tone="$s['tone']" :icon="$s['icon']">{{ $s['label'] }}: {{ $row['counts'][$key] }}</x-tag>
                @endif
            @endforeach
            @if(array_sum($row['counts']) === 0)
                <span class="text-muted">Belum ada absensi bulan ini.</span>
            @endif
        </div>

        <div class="kv"><span class="k">Hari kerja dihitung</span><span class="v">{{ Format::number($row['days'], 1) }} hari</span></div>
        <div class="kv total"><span class="k">Gaji pokok bulan ini</span><span class="v">@rupiah($row['base'])</span></div>

        @if(!$w->wage_type)
            <div class="help-tip mt-3">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span>Atur besaran gaji {{ $w->name }} dulu. <a href="{{ route('users.edit', $w) }}" class="fw-bold">Buka pengaturan pekerja</a></span>
            </div>
        @endif

        <x-slot:footer>
            @if($row['paid'])
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <span><x-tag tone="success" icon="bi-check-circle-fill">Sudah dibayar</x-tag> <b>@rupiah($row['paid']->total_amount)</b> · {{ Format::date($row['paid']->transaction_date) }}</span>
                    <x-delete-button :action="route('expenses.destroy', $row['paid'])" label="Batalkan" title="Batalkan pembayaran gaji?" :message="'Catatan gaji ' . $w->name . ' dipindah ke Sampah dan bisa dipulihkan.'" />
                </div>
            @else
                <button type="button" class="btn btn-primary" @click="open = !open" x-show="!open"><i class="bi bi-cash-coin"></i> Bayar Gaji</button>
                <form action="{{ route('payroll.pay') }}" method="POST" x-show="open" x-cloak
                      x-data="{ bonus: '', cut: '', base: {{ (float) $row['base'] }} }">
                    @csrf
                    <input type="hidden" name="worker_id" value="{{ $w->id }}">
                    <input type="hidden" name="period" value="{{ $period }}">
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <x-field label="Bonus" name="bonus" optional>
                                <input type="number" name="bonus" min="0" step="1" inputmode="numeric" x-model="bonus" class="form-control" placeholder="0">
                            </x-field>
                        </div>
                        <div class="col-6 col-md-3">
                            <x-field label="Potongan" name="deduction" optional>
                                <input type="number" name="deduction" min="0" step="1" inputmode="numeric" x-model="cut" class="form-control" placeholder="0">
                            </x-field>
                        </div>
                        <div class="col-md-3">
                            <x-field label="Tanggal bayar" name="paid_at" required>
                                <input type="date" name="paid_at" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control" required>
                            </x-field>
                        </div>
                        <div class="col-md-3">
                            <x-field label="Cara bayar" name="payment_method" required>
                                <select name="payment_method" class="form-select" required>
                                    @foreach($methods as $m)
                                        <option value="{{ $m }}">{{ $m }}</option>
                                    @endforeach
                                </select>
                            </x-field>
                        </div>
                    </div>
                    <x-field label="Catatan" name="notes" optional>
                        <input type="text" name="notes" maxlength="300" class="form-control" placeholder="Contoh: bonus lebaran">
                    </x-field>
                    <div class="summary-bar" style="grid-template-columns:1fr">
                        <div><div class="k">Total dibayar</div><div class="v" style="font-size:1.5rem" x-text="rupiah(base + (Number(bonus) || 0) - (Number(cut) || 0))"></div></div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-light" @click="open = false">Batal</button>
                        <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-check2-circle"></i> Bayar & Catat di Buku Kas</button>
                    </div>
                </form>
            @endif
        </x-slot:footer>
    </x-panel>
@empty
    <x-panel>
        <x-empty icon="bi-people" title="Belum ada pekerja">
            <x-slot:action><a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus-fill"></i> Tambah Pekerja</a></x-slot:action>
        </x-empty>
    </x-panel>
@endforelse
@endsection
