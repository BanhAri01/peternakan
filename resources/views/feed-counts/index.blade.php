@extends('layouts.app')

@section('title', 'Cek Stok Pakan per Tanggal')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Cek Stok Pakan per Tanggal" subtitle="Hasil hitung gudang dibandingkan dengan catatan aplikasi. Tanggal merah berarti tidak cocok." icon="bi-calendar-check-fill">
    <a href="{{ route('feed-counts.create') }}" class="btn btn-primary"><i class="bi bi-calculator"></i> Hitung Stok Sekarang</a>
</x-page-header>

<x-alerts />

<x-panel>
    <form method="GET" class="filter-bar">
        <x-field label="Bulan" for="bulan">
            <input type="month" id="bulan" name="bulan" value="{{ $month->format('Y-m') }}" max="{{ today()->format('Y-m') }}" class="form-control">
        </x-field>
        <div class="btns"><button type="submit" class="btn btn-primary" data-no-lock><i class="bi bi-search"></i> Tampilkan</button></div>
    </form>
    <div class="d-flex flex-wrap gap-2 mt-3">
        @if($offCount > 0)
            <x-tag tone="danger" icon="bi-exclamation-triangle-fill">{{ $offCount }} tanggal tidak cocok</x-tag>
        @else
            <x-tag tone="success" icon="bi-check-circle-fill">Tidak ada selisih bulan ini</x-tag>
        @endif
        <x-tag tone="neutral" icon="bi-info-circle">Dianggap cocok jika selisih ≤ {{ Format::number($tolerance, 1) }} kg</x-tag>
    </div>
</x-panel>

<x-panel flush>
    <div class="table-wrap">
        <table class="tbl stack">
            <thead><tr><th>Tanggal</th><th>Status</th><th>Pakan dipakai</th><th>Hasil hitung gudang</th></tr></thead>
            <tbody>
                @foreach($days as $day)
                    <tr @class(['row-danger' => $day['status'] === 'off'])>
                        <td class="title-cell"><b @class(['text-danger' => $day['status'] === 'off'])>{{ Format::dayDate($day['date']) }}</b></td>
                        <td data-label="Status">
                            @if($day['status'] === 'off')
                                <x-tag tone="danger" icon="bi-x-circle-fill">Tidak cocok</x-tag>
                            @elseif($day['status'] === 'ok')
                                <x-tag tone="success" icon="bi-check-circle-fill">Cocok</x-tag>
                            @else
                                <x-tag tone="neutral" icon="bi-dash-circle">Belum dihitung</x-tag>
                            @endif
                        </td>
                        <td data-label="Pakan dipakai">{{ $day['used'] > 0 ? Format::number($day['used']) . ' kg' : '-' }}</td>
                        <td data-label="Hasil hitung">
                            @forelse($day['counts'] as $c)
                                <div @class(['text-danger fw-bold' => !$c->isBalanced()])>
                                    {{ $c->feedStock->feed_name ?? '-' }}: tercatat {{ Format::number($c->expected_kg) }} kg, dihitung {{ Format::number($c->counted_kg) }} kg
                                    @unless($c->isBalanced())
                                        ({{ $c->difference_kg > 0 ? 'lebih' : 'kurang' }} {{ Format::number(abs((float) $c->difference_kg), 1) }} kg)
                                    @endunless
                                </div>
                                @if($loop->last && $c->recorder)<div class="text-muted small">oleh {{ $c->recorder->name }}</div>@endif
                            @empty
                                <span class="text-muted">-</span>
                            @endforelse
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-panel>
@endsection
