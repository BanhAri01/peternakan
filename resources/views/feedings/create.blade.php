@extends('layouts.app')

@section('title', 'Beri Pakan')
@section('content-class', 'narrow')

@php
    use App\Support\Format;
    $isToday = $date->isToday();
@endphp

@section('content')
<x-page-header
    title="Beri Pakan"
    :subtitle="'Catat setiap selesai memberi makan. ' . ($isToday ? 'Hari ini' : Format::dayDate($date)) . '.'"
    icon="bi-basket2-fill">
    <a href="{{ route('feed-counts.create') }}" class="btn btn-light"><i class="bi bi-calculator"></i> Hitung Stok Gudang</a>
</x-page-header>

<x-alerts />

<x-panel>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('feedings.create', ['sesi' => $session]) }}" class="btn {{ $isToday ? 'btn-primary' : 'btn-light' }}">Hari ini</a>
        <a href="{{ route('feedings.create', ['date' => today()->subDay()->toDateString(), 'sesi' => $session]) }}" class="btn {{ $date->isYesterday() ? 'btn-primary' : 'btn-light' }}">Kemarin</a>
        @if($isOwner)
            <form method="GET" action="{{ route('feedings.create') }}">
                <input type="hidden" name="sesi" value="{{ $session }}">
                <input type="date" name="date" value="{{ $dateStr }}" max="{{ today()->toDateString() }}" class="form-control" onchange="this.form.submit()" aria-label="Pilih tanggal lain">
            </form>
        @endif
    </div>
    <div class="table-wrap">
        <table class="tbl">
            <thead><tr><th>Kandang</th>@foreach($sessions as $s)<th class="text-center">{{ $s['name'] }}</th>@endforeach</tr></thead>
            <tbody>
                @foreach($coops as $c)
                    <tr>
                        <td><b>{{ $c->name }}</b></td>
                        @foreach($sessions as $i => $s)
                            @php $entry = ($todayFeedings[$c->id . '-' . $i] ?? collect())->first(); @endphp
                            <td class="text-center">
                                @if($entry)
                                    @if($isOwner)<a href="{{ route('feedings.edit', $entry) }}" class="text-decoration-none">@endif
                                    <x-tag tone="success" icon="bi-check-circle-fill">{{ Format::number($entry->totalKg()) }} kg</x-tag>
                                    @if($isOwner)</a>@endif
                                @else
                                    <x-tag tone="neutral" icon="bi-circle">belum</x-tag>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-panel>

@if($coops->isEmpty() || $feedStocks->isEmpty())
    <x-panel>
        <x-empty icon="bi-box-seam" :title="$coops->isEmpty() ? 'Belum ada kandang aktif' : 'Belum ada jenis pakan'">
            {{ $isOwner ? 'Tambahkan dulu di menu Kandang atau Stok Pakan.' : 'Minta pemilik menambahkan data kandang dan pakan.' }}
        </x-empty>
    </x-panel>
@else
<form action="{{ route('feedings.store') }}" method="POST" data-offline="pakan"
      x-data="panenForm({
          sackKg: {{ (float) $sackKg }},
          coopId: @js((string) old('coop_id', $suggestedCoop)),
          session: @js((string) old('session', $session)),
          status: @js($status),
          populations: {},
          lastFeed: @js($lastFeedByCoop),
          feeds: @js(collect(old('feeds', [['feed_stock_id' => $lastFeedByCoop[$suggestedCoop] ?? $feedStocks->first()->id]]))->map(fn ($f) => ['id' => $f['feed_stock_id'] ?? '', 'sacks' => $f['sacks'] ?? '', 'extra' => $f['extra_kg'] ?? ''])->values()),
          feedIds: @js($feedStocks->pluck('id')->map(fn ($id) => (string) $id)->values()),
      })"
      x-init="$watch('session', () => { if ((status[coopId] || []).includes(Number(session))) coopId = ''; })">
    @csrf
    <input type="hidden" name="feed_date" value="{{ $dateStr }}">

    <x-panel title="Sesi ke berapa?" step="1">
        <div class="pick-grid">
            @foreach($sessions as $i => $s)
                <label class="pick">
                    <input type="radio" name="session" value="{{ $i }}" x-model="session" required>
                    <span><b>{{ $s['name'] }}</b><small>sekitar jam {{ $s['time'] }}</small></span>
                </label>
            @endforeach
        </div>
        @error('session')<div class="field-error mt-2"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>@enderror
    </x-panel>

    <x-panel title="Kandang mana?" step="2">
        <div class="pick-grid">
            @foreach($coops as $coop)
                <label class="pick" :style="(status[{{ $coop->id }}] || []).includes(Number(session)) ? 'opacity:.55' : ''">
                    <input type="radio" name="coop_id" value="{{ $coop->id }}" data-name="{{ $coop->name }}" x-model="coopId" @change="onCoopChange()" :disabled="(status[{{ $coop->id }}] || []).includes(Number(session))" required>
                    <span>
                        <b>{{ $coop->name }}</b>
                        <small>{{ Format::number($coop->current_population) }} ekor</small>
                        <span class="done" x-show="(status[{{ $coop->id }}] || []).includes(Number(session))"><i class="bi bi-check-circle-fill"></i> Sudah dicatat</span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('coop_id')<div class="field-error mt-2"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>@enderror
        @error('feed_date')<div class="field-error mt-2"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>@enderror
    </x-panel>

    <x-panel title="Pakan yang diberikan" step="3" tone="brand" :subtitle="'1 karung = ' . Format::number($sackKg) . ' kg.'">
        @include('daily_logs._feeds')
    </x-panel>

    <div class="sticky-actions">
        <button type="submit" class="btn btn-primary btn-xl w-100">
            <i class="bi bi-check2-circle"></i> Simpan Pakan <span x-show="feedKg() > 0" x-text="'(' + angka(feedKg()) + ' kg)'"></span>
        </button>
    </div>
</form>
@endif
@endsection

@push('scripts')
    @include('daily_logs._script')
@endpush
