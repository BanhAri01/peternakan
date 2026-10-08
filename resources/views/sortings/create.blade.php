@extends('layouts.app')

@section('title', 'Sortir Telur')
@section('content-class', 'narrow')

@php
    use App\Support\Format;
    $isOwner   = auth()->user()->isOwner();
    $oldItems  = collect(old('items', []));
    $itemInit  = $grades->values()->map(fn ($g, $i) => [
        'trays' => $oldItems[$i]['trays_count'] ?? '',
        'extra' => $oldItems[$i]['extra_eggs'] ?? '',
        'kg'    => $oldItems[$i]['weight_kg'] ?? '',
    ]);
@endphp

@section('content')
<x-page-header title="Sortir Telur" subtitle="Pilah telur campur hasil panen menjadi telur besar, kecil, retak, dan lainnya. Stok tiap jenis bertambah otomatis." icon="bi-funnel-fill">
    @if($isOwner)
        <a href="{{ route('grades.index') }}" class="btn btn-light"><i class="bi bi-gear"></i> Atur jenis telur</a>
    @endif
</x-page-header>

<x-alerts />

<div class="row g-3 mb-3">
    <div class="col-sm-6"><x-stat label="Telur campur menunggu disortir" :value="Format::number($waiting['eggs'])" unit="butir" icon="bi-hourglass-split" tone="egg" :hint="Format::trays($waiting['eggs']) . ' · ' . Format::number($waiting['kg'], 1) . ' kg'" /></div>
    <div class="col-sm-6"><x-stat label="Jenis telur aktif" :value="$grades->count()" unit="jenis" icon="bi-egg-fill" tone="brand" :hint="$grades->pluck('name')->join(', ') ?: 'Belum ada'" /></div>
</div>

@if($grades->isEmpty())
    <x-panel>
        <x-empty icon="bi-egg" title="Belum ada jenis telur hasil sortir">
            {{ $isOwner ? 'Tambahkan jenis telur seperti Besar, Sedang, Kecil, Retak.' : 'Minta pemilik menambahkan jenis telur.' }}
            @if($isOwner)
                <x-slot:action><a href="{{ route('grades.index') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah jenis telur</a></x-slot:action>
            @endif
        </x-empty>
    </x-panel>
@else
<form action="{{ route('sortings.store') }}" method="POST" data-offline="sortir" x-data="sortForm({ items: @js($itemInit) })">
    @csrf

    <x-panel title="Tanggal sortir" step="1">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <input type="date" name="sort_date" value="{{ old('sort_date', today()->toDateString()) }}" max="{{ today()->toDateString() }}"
                   @unless($isOwner) min="{{ today()->subDay()->toDateString() }}" @endunless class="form-control" style="max-width:240px" required aria-label="Tanggal sortir">
        </div>
        @error('sort_date')<div class="field-error mt-2"><i class="bi bi-exclamation-circle-fill"></i> {{ $message }}</div>@enderror
    </x-panel>

    <x-panel title="Hasil sortir per jenis" step="2" tone="egg" subtitle="Isi jenis yang ada saja, sisanya biarkan kosong. 1 rak = 30 butir.">
        @foreach($grades as $i => $grade)
            <div class="grade-box" :class="{ 'has-value': eggs({{ $i }}) > 0 || num(items[{{ $i }}].kg) > 0 }">
                <div class="grade-name">
                    <span><i class="bi bi-egg-fill text-egg me-1"></i> {{ $grade->name }}</span>
                    <span class="grade-total" x-show="eggs({{ $i }}) > 0" x-text="'= ' + angka(eggs({{ $i }}), 0) + ' butir'"></span>
                </div>
                <input type="hidden" name="items[{{ $i }}][egg_grade_id]" value="{{ $grade->id }}">
                <div class="row g-2">
                    <div class="col-4">
                        <label class="mini-label" for="s{{ $i }}t">Jumlah rak</label>
                        <input id="s{{ $i }}t" type="number" inputmode="numeric" min="0" step="1" placeholder="0" name="items[{{ $i }}][trays_count]" x-model="items[{{ $i }}].trays" class="form-control num-lg">
                    </div>
                    <div class="col-4">
                        <label class="mini-label" for="s{{ $i }}e">+ Butir lepas</label>
                        <input id="s{{ $i }}e" type="number" inputmode="numeric" min="0" step="1" placeholder="0" name="items[{{ $i }}][extra_eggs]" x-model="items[{{ $i }}].extra" class="form-control num-lg">
                    </div>
                    <div class="col-4">
                        <label class="mini-label" for="s{{ $i }}k">Berat (kg)</label>
                        <input id="s{{ $i }}k" type="number" inputmode="decimal" min="0" step="0.01" placeholder="0" name="items[{{ $i }}][weight_kg]" x-model="items[{{ $i }}].kg" class="form-control num-lg">
                    </div>
                </div>
            </div>
        @endforeach
    </x-panel>

    <x-panel title="Catatan" icon="bi-chat-left-text" subtitle="Boleh dikosongkan.">
        <textarea name="notes" rows="2" maxlength="500" class="form-control" placeholder="Contoh: banyak telur kotor hari ini">{{ old('notes') }}</textarea>
    </x-panel>

    <div class="sticky-actions">
        <div class="summary-bar">
            <div><div class="k">Total disortir</div><div class="v" x-text="angka(totalEggs(), 0) + ' butir'"></div></div>
            <div><div class="k">Dalam rak</div><div class="v" x-text="trayText()"></div></div>
            <div><div class="k">Total berat</div><div class="v" x-text="angka(totalKg(), 2) + ' kg'"></div></div>
            <div><div class="k">Sisa telur campur</div><div class="v" x-text="angka(Math.max(0, {{ (int) $waiting['eggs'] }} - totalEggs()), 0) + ' butir'"></div></div>
        </div>
        <button type="submit" class="btn btn-egg btn-xl w-100"><i class="bi bi-check2-circle"></i> Simpan Hasil Sortir</button>
    </div>
</form>
@endif

<x-panel :title="$isOwner ? 'Riwayat sortir' : 'Sortir hari ini & kemarin'" icon="bi-clock-history" flush>
    @if($recent->isEmpty())
        <x-empty icon="bi-funnel" title="Belum ada catatan sortir" />
    @else
        <ul class="alert-list">
            @foreach($recent as $s)
                <li>
                    <span class="dot info"><i class="bi bi-funnel-fill"></i></span>
                    <div class="txt">
                        <b>{{ Format::date($s->sort_date, 'D, d M Y') }} · {{ Format::number($s->input_count) }} butir ({{ Format::number($s->input_kg, 1) }} kg)</b>
                        <span>
                            {{ $s->items->map(fn ($it) => ($it->grade->name ?? '-') . ' ' . Format::number($it->weight_kg, 1) . ' kg')->join(' · ') }}
                            @if($s->recorder) · oleh {{ $s->recorder->name }} @endif
                        </span>
                    </div>
                    @if($isOwner)
                        <x-delete-button :action="route('sortings.destroy', $s)" icon-only title="Hapus catatan sortir?" message="Telur pada catatan ini dikembalikan ke stok telur campur." />
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
    @if($recent->hasPages())
        <x-slot:footer>{{ $recent->links() }}</x-slot:footer>
    @endif
</x-panel>
@endsection

@push('scripts')
<script>
    function sortForm(init) {
        return Object.assign({
            num(v) { return Number(v) || 0; },
            eggs(i) { var it = this.items[i]; return this.num(it.trays) * 30 + this.num(it.extra); },
            totalEggs() { var t = 0; for (var i = 0; i < this.items.length; i++) t += this.eggs(i); return t; },
            totalKg() { var t = 0; this.items.forEach(it => t += this.num(it.kg)); return t; },
            trayText() { var e = this.totalEggs(); return Math.floor(e / 30) + ' rak' + (e % 30 ? ' + ' + (e % 30) : ''); },
        }, init);
    }
</script>
@endpush
