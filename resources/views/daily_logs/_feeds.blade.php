<template x-for="(line, i) in feeds" :key="line.key">
    <div class="grade-box" :class="{ 'has-value': lineKg(i) > 0 }">
        <div class="grade-name" style="flex-wrap: nowrap; gap: .5rem">
            <span style="white-space: nowrap"><i class="bi bi-box-seam-fill me-1"></i> <span x-text="feeds.length > 1 ? 'Pakan ' + (i + 1) : 'Jenis pakan'"></span><span class="grade-total ms-2" x-show="lineKg(i) > 0" x-text="angka(lineKg(i)) + ' kg'"></span></span>
            <span x-show="feeds.length > 1"><button type="button" class="btn btn-light btn-sm" @click="removeFeed(i)"><i class="bi bi-x-lg"></i> Hapus</button></span>
        </div>
        <select class="form-select mb-2" :name="'feeds[' + i + '][feed_stock_id]'" x-model="line.id" required>
            @foreach($feedStocks as $feed)
                <option value="{{ $feed->id }}" :disabled="isFeedUsed('{{ $feed->id }}', i)">{{ $feed->feed_name }} — sisa {{ \App\Support\Format::number($feed->stock_kg) }} kg</option>
            @endforeach
        </select>
        <div class="row g-2">
            <div class="col-6">
                <label class="mini-label" :for="'feed' + i + 's'">Jumlah karung</label>
                <input :id="'feed' + i + 's'" type="number" inputmode="numeric" min="0" step="1" :name="'feeds[' + i + '][sacks]'" x-model="line.sacks" placeholder="0" class="form-control num-xl">
            </div>
            <div class="col-6">
                <label class="mini-label" :for="'feed' + i + 'k'">+ Tambahan (kg)</label>
                <input :id="'feed' + i + 'k'" type="number" inputmode="decimal" min="0" step="0.1" :name="'feeds[' + i + '][extra_kg]'" x-model="line.extra" placeholder="0" class="form-control num-xl">
            </div>
        </div>
    </div>
</template>
@if($errors->has('feeds') || $errors->has('feeds.*'))
    <div class="field-error mt-2"><i class="bi bi-exclamation-circle-fill"></i> {{ $errors->first('feeds') ?: collect($errors->get('feeds.*'))->flatten()->first() }}</div>
@endif
<div x-show="feeds.length < {{ \App\Services\DailyLogCalculator::MAX_FEEDS }} && feeds.length < {{ $feedStocks->count() }}">
    <button type="button" class="btn btn-light btn-lg w-100 mt-1" @click="addFeed()"><i class="bi bi-plus-lg"></i> Tambah jenis pakan lain</button>
</div>
<div class="field-hint mt-2" x-show="feedKg() > 0">Total pakan: <b x-text="angka(feedKg()) + ' kg'"></b><span x-show="feeds.length > 1" x-text="' dari ' + feeds.length + ' jenis'"></span></div>
