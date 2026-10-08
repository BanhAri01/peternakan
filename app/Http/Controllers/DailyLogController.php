<?php

namespace App\Http\Controllers;

use App\Tenancy\FarmRule;
use App\Models\Coop;
use App\Models\DailyLog;
use App\Models\EggGrade;
use App\Models\FeedStock;
use App\Models\Setting;
use App\Services\DailyLogCalculator;
use App\Support\Format;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DailyLogController extends Controller
{
    // Riwayat semua laporan panen (khusus owner)
    public function index(Request $request)
    {
        $request->validate(['start' => 'nullable|date', 'end' => 'nullable|date', 'coop_id' => 'nullable|integer']);

        $start  = $request->date('start') ?? Carbon::today()->subDays(13);
        $end    = $request->date('end') ?? Carbon::today();
        $coopId = $request->integer('coop_id') ?: null;

        $query = DailyLog::with(['coop', 'feedStock', 'recorder'])
            ->whereBetween('log_date', [$start->toDateString(), $end->toDateString()])
            ->when($coopId, fn ($q) => $q->where('coop_id', $coopId));

        $totals = (clone $query)->selectRaw('COUNT(*) as n, SUM(eggs_total_count) as eggs, SUM(eggs_total_kg) as kg,
            SUM(feed_consumed_kg) as feed, SUM(mortality) + SUM(cull) as loss, AVG(hdp_percentage) as hdp')->first();

        $logs = $query->orderByDesc('log_date')->orderBy('coop_id')->paginate(20)->withQueryString();

        $coops = Coop::orderBy('name')->get(['id', 'name']);

        return view('daily_logs.index', compact('logs', 'coops', 'start', 'end', 'coopId', 'totals'));
    }

    public function create(Request $request)
    {
        $request->validate(['date' => 'nullable|date']);

        $date = $request->date('date') ?? Carbon::today();
        if ($date->isFuture()) {
            $date = Carbon::today();
        }

        $loggedCoopIds = DailyLog::where('log_date', $date->toDateString())->pluck('coop_id')->all();

        $coops      = Coop::where('status', 'active')->orderBy('name')->get();
        $feedStocks = FeedStock::orderBy('feed_name')->get();
        // Pekerja hanya mencatat telur campur; pemilahan per jenis dilakukan di menu Sortir Telur
        $eggGrades  = collect([EggGrade::mixed()]);
        $sackKg     = Setting::num('sack_kg');

        // Kandang pertama yang belum dicatat dipilih otomatis
        $suggestedCoop = old('coop_id') ?? $coops->first(fn ($c) => !in_array($c->id, $loggedCoopIds))?->id;

        // Pakan terakhir yang dipakai kandang ini (agar tidak perlu memilih ulang)
        $lastFeedByCoop = DailyLog::whereIn('id', DailyLog::selectRaw('MAX(id)')->groupBy('coop_id'))
            ->pluck('feed_stock_id', 'coop_id');

        $todayLogs = DailyLog::with('coop')->where('log_date', $date->toDateString())->get();

        return view('daily_logs.create', compact(
            'date', 'coops', 'feedStocks', 'eggGrades', 'sackKg', 'loggedCoopIds',
            'suggestedCoop', 'lastFeedByCoop', 'todayLogs'
        ));
    }

    public function store(Request $request)
    {
        $rules = DailyLogCalculator::rules();

        $rules['coop_id'] = ['required', FarmRule::exists('coops')->where('status', 'active')];
        $rules['log_date'] = [
            'required', 'date', 'before_or_equal:today',
            Rule::unique('daily_logs')->where(fn ($q) => $q->where('coop_id', $request->coop_id))->withoutTrashed(),
        ];

        // Pekerja hanya boleh mencatat hari ini atau kemarin
        if ($request->user()->isWorker()) {
            $rules['log_date'][] = 'after_or_equal:' . Carbon::yesterday()->toDateString();
        }

        $data = $request->validate($rules, [
            'log_date.after_or_equal' => 'Pekerja hanya bisa mencatat panen hari ini atau kemarin. Hubungi pemilik untuk tanggal lain.',
            'log_date.before_or_equal' => 'Tanggal panen tidak boleh di masa depan.',
        ]);

        $feed = FeedStock::findOrFail($data['feed_stock_id']);
        $calc = DailyLogCalculator::calculate($data, $feed, 0);

        if ($calc['eggs_total_count'] === 0 && $calc['feed_consumed_kg'] == 0 && empty($data['mortality']) && empty($data['cull'])) {
            return back()->withInput()->with('error', 'Formulir masih kosong. Isi minimal jumlah telur atau pakan.');
        }

        [$coop, $calc] = DB::transaction(function () use ($data, $feed, $request) {
            $coop = Coop::lockForUpdate()->findOrFail($data['coop_id']);
            $calc = DailyLogCalculator::calculate($data, $feed, $coop->current_population);

            if (($data['mortality'] ?? 0) + ($data['cull'] ?? 0) > $coop->current_population) {
                throw ValidationException::withMessages(['mortality' => 'Jumlah ayam mati + afkir melebihi jumlah ayam di ' . $coop->name . ' (' . Format::number($coop->current_population) . ' ekor).']);
            }

            if (DailyLog::where('coop_id', $coop->id)->where('log_date', $data['log_date'])->exists()) {
                throw ValidationException::withMessages(['log_date' => 'Panen ' . $coop->name . ' tanggal ini sudah dicatat.']);
            }

            DailyLog::onlyTrashed()->where('coop_id', $coop->id)->where('log_date', $data['log_date'])->get()->each->forceDelete();

            $log = DailyLog::create([
                'coop_id'          => $coop->id,
                'log_date'         => $data['log_date'],
                'mortality'        => $data['mortality'] ?? 0,
                'cull'             => $data['cull'] ?? 0,
                'feed_stock_id'    => $feed->id,
                'feed_consumed_kg' => $calc['feed_consumed_kg'],
                'feed_cost_total'  => $calc['feed_cost_total'],
                'eggs_total_count' => $calc['eggs_total_count'],
                'eggs_total_kg'    => $calc['eggs_total_kg'],
                'hdp_percentage'   => $calc['hdp_percentage'],
                'fcr'              => $calc['fcr'],
                'notes'            => $data['notes'] ?? null,
                'recorded_by'      => $request->user()->id,
            ]);

            $log->grades()->createMany($calc['grades']);

            $loss = ($data['mortality'] ?? 0) + ($data['cull'] ?? 0);
            if ($loss > 0) {
                Coop::whereKey($coop->id)->decrement('current_population', $loss);
            }

            if ($calc['feed_consumed_kg'] > 0) {
                FeedStock::whereKey($feed->id)->decrement('stock_kg', $calc['feed_consumed_kg']);
            }

            return [$coop, $calc];
        });

        $message = sprintf(
            'Tersimpan! %s: %s telur (%s), %s kg pakan.',
            $coop->name,
            Format::number($calc['eggs_total_count']),
            Format::trays($calc['eggs_total_count']),
            Format::number($calc['feed_consumed_kg'], 1)
        );

        return redirect()->route('daily-logs.create', ['date' => $data['log_date']])->with('success', $message);
    }

    public function edit(DailyLog $dailyLog)
    {
        $dailyLog->load(['grades', 'coop', 'recorder']);

        $coops      = Coop::where('status', 'active')->orWhere('id', $dailyLog->coop_id)->orderBy('name')->get();
        $feedStocks = FeedStock::orderBy('feed_name')->get();
        // Laporan baru berisi telur campur saja; laporan lama yang sudah berisi rincian jenis tetap bisa diubah per jenis
        $mixed     = EggGrade::mixed();
        $usedIds   = $dailyLog->grades->pluck('egg_grade_id');
        $eggGrades = $usedIds->isEmpty() || $usedIds->every(fn ($id) => $id == $mixed->id)
            ? collect([$mixed])
            : EggGrade::sorted()->where('is_active', true)->orWhereIn('id', $usedIds)->orderBy('id')->get();

        $sackKg      = Setting::num('sack_kg') ?: 50;
        $feedSacks   = (int) floor($dailyLog->feed_consumed_kg / $sackKg);
        $extraFeedKg = round($dailyLog->feed_consumed_kg - ($feedSacks * $sackKg), 2);

        return view('daily_logs.edit', compact('dailyLog', 'coops', 'feedStocks', 'eggGrades', 'feedSacks', 'extraFeedKg', 'sackKg'));
    }

    public function update(Request $request, DailyLog $dailyLog)
    {
        $rules = DailyLogCalculator::rules();
        $rules['log_date'] = [
            'required', 'date', 'before_or_equal:today',
            Rule::unique('daily_logs')
                ->where(fn ($q) => $q->where('coop_id', $request->coop_id))
                ->ignore($dailyLog->id)
                ->withoutTrashed(),
        ];

        $data = $request->validate($rules);

        $newCoop = Coop::findOrFail($data['coop_id']);
        $newFeed = FeedStock::findOrFail($data['feed_stock_id']);

        $oldLoss   = $dailyLog->mortality + $dailyLog->cull;
        $oldFeedKg = (float) $dailyLog->feed_consumed_kg;

        // Populasi sebelum penyusutan hari itu
        $population = $newCoop->current_population + ($dailyLog->coop_id == $newCoop->id ? $oldLoss : 0);
        $calc       = DailyLogCalculator::calculate($data, $newFeed, $population);

        if (($data['mortality'] ?? 0) + ($data['cull'] ?? 0) > $population) {
            return back()->withInput()->withErrors(['mortality' => 'Jumlah ayam mati + afkir melebihi jumlah ayam di kandang (' . Format::number($population) . ' ekor).']);
        }

        DB::transaction(function () use ($dailyLog, $data, $calc, $newCoop, $newFeed, $oldLoss, $oldFeedKg) {
            DailyLog::onlyTrashed()
                ->where('coop_id', $newCoop->id)
                ->where('log_date', $data['log_date'])
                ->get()->each->forceDelete();

            // Kembalikan dulu dampak catatan lama
            if ($oldLoss > 0) {
                Coop::where('id', $dailyLog->coop_id)->increment('current_population', $oldLoss);
            }
            if ($oldFeedKg > 0 && $dailyLog->feed_stock_id) {
                FeedStock::where('id', $dailyLog->feed_stock_id)->increment('stock_kg', $oldFeedKg);
            }

            $dailyLog->update([
                'coop_id'          => $newCoop->id,
                'log_date'         => $data['log_date'],
                'mortality'        => $data['mortality'] ?? 0,
                'cull'             => $data['cull'] ?? 0,
                'feed_stock_id'    => $newFeed->id,
                'feed_consumed_kg' => $calc['feed_consumed_kg'],
                'feed_cost_total'  => $calc['feed_cost_total'],
                'eggs_total_count' => $calc['eggs_total_count'],
                'eggs_total_kg'    => $calc['eggs_total_kg'],
                'hdp_percentage'   => $calc['hdp_percentage'],
                'fcr'              => $calc['fcr'],
                'notes'            => $data['notes'] ?? null,
            ]);

            $dailyLog->grades()->forceDelete();
            $dailyLog->grades()->createMany($calc['grades']);

            // Terapkan dampak catatan baru
            $newLoss = ($data['mortality'] ?? 0) + ($data['cull'] ?? 0);
            if ($newLoss > 0) {
                Coop::whereKey($newCoop->id)->decrement('current_population', $newLoss);
            }
            if ($calc['feed_consumed_kg'] > 0) {
                FeedStock::whereKey($newFeed->id)->decrement('stock_kg', $calc['feed_consumed_kg']);
            }
        });

        return redirect()->route('daily-logs.index')->with('success', 'Laporan panen ' . $newCoop->name . ' tanggal ' . Format::date($data['log_date']) . ' berhasil diperbarui.');
    }

    public function destroy(DailyLog $dailyLog)
    {
        DB::transaction(function () use ($dailyLog) {
            // Kembalikan populasi dan stok pakan seperti sebelum dicatat
            $loss = $dailyLog->mortality + $dailyLog->cull;
            if ($loss > 0) {
                Coop::where('id', $dailyLog->coop_id)->increment('current_population', $loss);
            }
            if ($dailyLog->feed_consumed_kg > 0 && $dailyLog->feed_stock_id) {
                FeedStock::where('id', $dailyLog->feed_stock_id)->increment('stock_kg', $dailyLog->feed_consumed_kg);
            }

            $dailyLog->grades()->delete();
            $dailyLog->delete();
        });

        return back()->with('success', 'Laporan panen dipindah ke Sampah. Jumlah ayam dan stok pakan sudah dikembalikan.');
    }
}
