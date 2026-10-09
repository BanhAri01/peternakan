<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AcceptsOfflineEntries;
use App\Tenancy\FarmRule;
use App\Models\Attendance;
use App\Models\Coop;
use App\Models\DailyLog;
use App\Models\EggGrade;
use App\Models\Feeding;
use App\Models\FeedStock;
use App\Models\Setting;
use App\Services\DailyLogCalculator;
use App\Services\FeedingService;
use App\Support\Format;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DailyLogController extends Controller
{
    use AcceptsOfflineEntries;

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
        $redirect = route('daily-logs.create', ['date' => $request->input('log_date')]);

        if ($duplicate = $this->alreadyReceived($request, DailyLog::class, $redirect)) {
            return $duplicate;
        }

        DailyLogCalculator::normalize($request);
        $rules = DailyLogCalculator::rules();
        $rules['client_uuid'] = 'nullable|uuid';

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
            'feeds.*.feed_stock_id.distinct' => 'Jenis pakan yang sama dipilih dua kali. Gabungkan jumlahnya di satu baris.',
        ]);

        $calc = DailyLogCalculator::calculate($data, 0);

        if ($calc['eggs_total_count'] === 0 && $calc['legacy_feeds'] === [] && empty($data['mortality']) && empty($data['cull'])) {
            $empty = 'Formulir masih kosong. Isi minimal jumlah telur atau ayam mati.';

            if ($request->expectsJson()) {
                throw ValidationException::withMessages(['grades' => $empty]);
            }

            return back()->withInput()->with('error', $empty);
        }

        $clientUuid = $this->clientUuid($request);

        $saved = DB::transaction(function () use ($data, $request, $clientUuid) {
            $coop = Coop::lockForUpdate()->findOrFail($data['coop_id']);

            if ($clientUuid && DailyLog::withTrashed()->where('client_uuid', $clientUuid)->exists()) {
                return null;
            }

            $calc = DailyLogCalculator::calculate($data, $coop->current_population);

            if (($data['mortality'] ?? 0) + ($data['cull'] ?? 0) > $coop->current_population) {
                throw ValidationException::withMessages(['mortality' => 'Jumlah ayam mati + afkir melebihi jumlah ayam di ' . $coop->name . ' (' . Format::number($coop->current_population) . ' ekor).']);
            }

            if (DailyLog::where('coop_id', $coop->id)->where('log_date', $data['log_date'])->exists()) {
                throw ValidationException::withMessages(['log_date' => 'Panen ' . $coop->name . ' tanggal ini sudah dicatat.']);
            }

            DailyLog::onlyTrashed()->where('coop_id', $coop->id)->where('log_date', $data['log_date'])->get()->each->forceDelete();

            $log = DailyLog::create([
                'client_uuid'      => $clientUuid,
                'coop_id'          => $coop->id,
                'log_date'         => $data['log_date'],
                'mortality'        => $data['mortality'] ?? 0,
                'cull'             => $data['cull'] ?? 0,
                'feed_consumed_kg' => 0,
                'feed_cost_total'  => 0,
                'eggs_total_count' => $calc['eggs_total_count'],
                'eggs_total_kg'    => $calc['eggs_total_kg'],
                'hdp_percentage'   => $calc['hdp_percentage'],
                'notes'            => $data['notes'] ?? null,
                'recorded_by'      => $request->user()->id,
            ]);

            $log->grades()->createMany($calc['grades']);

            if ($calc['legacy_feeds'] !== []) {
                app(FeedingService::class)->record(['coop_id' => $coop->id, 'feed_date' => $data['log_date'], 'session' => null, 'feeds' => $data['feeds']], $request->user()->id);
            }
            app(FeedingService::class)->syncDailyLog($coop->id, $data['log_date']);

            $loss = ($data['mortality'] ?? 0) + ($data['cull'] ?? 0);
            if ($loss > 0) {
                Coop::whereKey($coop->id)->decrement('current_population', $loss);
            }

            return [$coop, $log->fresh()];
        });

        if ($saved === null) {
            return $this->entrySaved($request, 'Catatan ini sudah diterima sebelumnya.', $redirect);
        }

        [$coop, $log] = $saved;

        Attendance::markPresent($request->user());

        $message = sprintf(
            'Tersimpan! %s: %s telur (%s). Pakan hari ini %s kg.',
            $coop->name,
            Format::number($log->eggs_total_count),
            Format::trays($log->eggs_total_count),
            Format::number($log->feed_consumed_kg, 1)
        );

        return $this->entrySaved($request, $message, $redirect);
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

        $feedings = Feeding::with('items.feedStock', 'recorder')->where('coop_id', $dailyLog->coop_id)
            ->where('feed_date', $dailyLog->log_date->toDateString())->orderByRaw('session IS NULL, session')->get();

        $sackKg = Setting::num('sack_kg') ?: 50;

        return view('daily_logs.edit', compact('dailyLog', 'coops', 'eggGrades', 'feedings', 'sackKg'));
    }

    public function update(Request $request, DailyLog $dailyLog)
    {
        DailyLogCalculator::normalize($request);
        $rules = DailyLogCalculator::rules();
        $rules['log_date'] = [
            'required', 'date', 'before_or_equal:today',
            Rule::unique('daily_logs')
                ->where(fn ($q) => $q->where('coop_id', $request->coop_id))
                ->ignore($dailyLog->id)
                ->withoutTrashed(),
        ];

        $data = $request->validate($rules, [
            'feeds.*.feed_stock_id.distinct' => 'Jenis pakan yang sama dipilih dua kali. Gabungkan jumlahnya di satu baris.',
        ]);

        $newCoop = Coop::findOrFail($data['coop_id']);

        $oldLoss = $dailyLog->mortality + $dailyLog->cull;

        // Populasi sebelum penyusutan hari itu
        $population = $newCoop->current_population + ($dailyLog->coop_id == $newCoop->id ? $oldLoss : 0);
        $calc       = DailyLogCalculator::calculate($data, $population);

        if (($data['mortality'] ?? 0) + ($data['cull'] ?? 0) > $population) {
            return back()->withInput()->withErrors(['mortality' => 'Jumlah ayam mati + afkir melebihi jumlah ayam di kandang (' . Format::number($population) . ' ekor).']);
        }

        DB::transaction(function () use ($dailyLog, $data, $calc, $newCoop, $oldLoss) {
            DailyLog::onlyTrashed()
                ->where('coop_id', $newCoop->id)
                ->where('log_date', $data['log_date'])
                ->get()->each->forceDelete();

            // Kembalikan dulu dampak catatan lama
            if ($oldLoss > 0) {
                Coop::where('id', $dailyLog->coop_id)->increment('current_population', $oldLoss);
            }
            $oldCoopId = $dailyLog->coop_id;
            $oldDate   = $dailyLog->log_date->toDateString();

            $dailyLog->update([
                'coop_id'          => $newCoop->id,
                'log_date'         => $data['log_date'],
                'mortality'        => $data['mortality'] ?? 0,
                'cull'             => $data['cull'] ?? 0,
                'eggs_total_count' => $calc['eggs_total_count'],
                'eggs_total_kg'    => $calc['eggs_total_kg'],
                'hdp_percentage'   => $calc['hdp_percentage'],
                'notes'            => $data['notes'] ?? null,
            ]);

            $dailyLog->grades()->forceDelete();
            $dailyLog->grades()->createMany($calc['grades']);

            $feedings = app(FeedingService::class);
            $feedings->syncDailyLog($oldCoopId, $oldDate);
            $feedings->syncDailyLog($newCoop->id, $data['log_date']);

            // Terapkan dampak catatan baru
            $newLoss = ($data['mortality'] ?? 0) + ($data['cull'] ?? 0);
            if ($newLoss > 0) {
                Coop::whereKey($newCoop->id)->decrement('current_population', $newLoss);
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
            $dailyLog->grades()->delete();
            $dailyLog->delete();
        });

        return back()->with('success', 'Laporan panen dipindah ke Sampah. Jumlah ayam sudah dikembalikan. Catatan pemberian pakan tetap tersimpan.');
    }
}
