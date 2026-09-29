<?php

namespace App\Http\Controllers;

use App\Models\Coop;
use App\Models\FeedStock;
use App\Models\DailyLog;
use App\Models\EggGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DailyLogController extends Controller
{
    public function create()
    {
        $coops = Coop::where('status', 'active')->get();
        $feedStocks = FeedStock::all();
        $eggGrades = EggGrade::where('is_active', true)->get();

        return view('daily_logs.create', compact('coops', 'feedStocks', 'eggGrades'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'coop_id' => 'required|exists:coops,id',
            'log_date' => 'required|date',
            'feed_stock_id' => 'required|exists:feed_stocks,id',
            'feed_sacks' => 'nullable|integer|min:0',
            'extra_feed_kg' => 'nullable|numeric|min:0',
            'mortality' => 'nullable|integer|min:0',
            'cull' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
            'grades' => 'required|array',
            'grades.*.egg_grade_id' => 'required|exists:egg_grades,id',
            'grades.*.trays_count' => 'nullable|integer|min:0',
            'grades.*.extra_eggs' => 'nullable|integer|min:0',
            'grades.*.weight_kg' => 'nullable|numeric|min:0',
        ]);

        $coop = Coop::findOrFail($validated['coop_id']);
        $feed = FeedStock::findOrFail($validated['feed_stock_id']);

        $totalFeedKg = (($validated['feed_sacks'] ?? 0) * 50) + ($validated['extra_feed_kg'] ?? 0);
        $totalFeedCost = round($totalFeedKg * $feed->cost_per_kg, 2);

        $totalEggsCount = 0;
        $totalEggsKg = 0;
        $gradeDetails = [];

        foreach ($validated['grades'] as $item) {
            $trays = $item['trays_count'] ?? 0;
            $extra = $item['extra_eggs'] ?? 0;
            $kg = $item['weight_kg'] ?? 0;
            $count = ($trays * 30) + $extra;

            if ($count > 0 || $kg > 0) {
                $totalEggsCount += $count;
                $totalEggsKg += $kg;
                $gradeDetails[] = [
                    'egg_grade_id' => $item['egg_grade_id'],
                    'trays_count' => $trays,
                    'extra_eggs' => $extra,
                    'total_eggs' => $count,
                    'weight_kg' => $kg,
                ];
            }
        }

        $activePop = $coop->current_population;
        $hdp = $activePop > 0 ? round(($totalEggsCount / $activePop) * 100, 2) : 0;
        $fcr = $totalEggsKg > 0 ? min(999.99, round($totalFeedKg / $totalEggsKg, 2)) : null;

        DB::transaction(function () use ($validated, $totalFeedKg, $totalFeedCost, $totalEggsCount, $totalEggsKg, $hdp, $fcr, $gradeDetails, $coop, $feed) {
            $log = DailyLog::create([
                'coop_id' => $validated['coop_id'],
                'log_date' => $validated['log_date'],
                'mortality' => $validated['mortality'] ?? 0,
                'cull' => $validated['cull'] ?? 0,
                'feed_stock_id' => $validated['feed_stock_id'],
                'feed_consumed_kg' => $totalFeedKg,
                'feed_cost_total' => $totalFeedCost,
                'eggs_total_count' => $totalEggsCount,
                'eggs_total_kg' => $totalEggsKg,
                'hdp_percentage' => $hdp,
                'fcr' => $fcr,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($gradeDetails as $detail) {
                $log->grades()->create($detail);
            }

            $loss = ($validated['mortality'] ?? 0) + ($validated['cull'] ?? 0);
            if ($loss > 0) {
                $coop->decrement('current_population', $loss);
            }

            if ($totalFeedKg > 0) {
                $feed->decrement('stock_kg', $totalFeedKg);
            }
        });

        return redirect()->route('daily-logs.create')->with('success', 'Panen telur per grade berhasil disimpan!');
    }

    public function edit(DailyLog $dailyLog)
    {
        $coops = Coop::where('status', 'active')->get();
        $feedStocks = FeedStock::all();
        $eggGrades = EggGrade::all();

        $dailyLog->load('grades');

        $feedSacks = floor($dailyLog->feed_consumed_kg / 50);
        $extraFeedKg = $dailyLog->feed_consumed_kg - ($feedSacks * 50);

        return view('daily_logs.edit', compact('dailyLog', 'coops', 'feedStocks', 'eggGrades', 'feedSacks', 'extraFeedKg'));
    }

    public function update(Request $request, DailyLog $dailyLog)
    {
        $validated = $request->validate([
            'coop_id' => 'required|exists:coops,id',
            'log_date' => [
                'required',
                'date',
                Rule::unique('daily_logs')
                    ->where(fn($query) => $query->where('coop_id', $request->coop_id))
                    ->ignore($dailyLog->id)
            ],
            'feed_stock_id' => 'required|exists:feed_stocks,id',
            'feed_sacks' => 'nullable|integer|min:0',
            'extra_feed_kg' => 'nullable|numeric|min:0',
            'mortality' => 'nullable|integer|min:0',
            'cull' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
            'grades' => 'required|array',
            'grades.*.egg_grade_id' => 'required|exists:egg_grades,id',
            'grades.*.trays_count' => 'nullable|integer|min:0',
            'grades.*.extra_eggs' => 'nullable|integer|min:0',
            'grades.*.weight_kg' => 'nullable|numeric|min:0',
        ]);

        $newCoop = Coop::findOrFail($validated['coop_id']);
        $newFeed = FeedStock::findOrFail($validated['feed_stock_id']);

        $newTotalFeedKg = (($validated['feed_sacks'] ?? 0) * 50) + ($validated['extra_feed_kg'] ?? 0);
        $newTotalFeedCost = round($newTotalFeedKg * $newFeed->cost_per_kg, 2);

        $newTotalEggsCount = 0;
        $newTotalEggsKg = 0;
        $gradeDetails = [];

        foreach ($validated['grades'] as $item) {
            $trays = $item['trays_count'] ?? 0;
            $extra = $item['extra_eggs'] ?? 0;
            $kg = $item['weight_kg'] ?? 0;
            $count = ($trays * 30) + $extra;

            if ($count > 0 || $kg > 0) {
                $newTotalEggsCount += $count;
                $newTotalEggsKg += $kg;
                $gradeDetails[] = [
                    'egg_grade_id' => $item['egg_grade_id'],
                    'trays_count' => $trays,
                    'extra_eggs' => $extra,
                    'total_eggs' => $count,
                    'weight_kg' => $kg,
                ];
            }
        }

        $oldLoss = ($dailyLog->mortality ?? 0) + ($dailyLog->cull ?? 0);
        $newLoss = ($validated['mortality'] ?? 0) + ($validated['cull'] ?? 0);
        $oldFeedKg = $dailyLog->feed_consumed_kg ?? 0;

        if ($dailyLog->coop_id == $newCoop->id) {
            $populationBeforeLog = $newCoop->current_population + $oldLoss;
        } else {
            $populationBeforeLog = $newCoop->current_population;
        }

        $hdp = $populationBeforeLog > 0
            ? round(($newTotalEggsCount / $populationBeforeLog) * 100, 2)
            : 0;

        $fcr = $newTotalEggsKg > 0
            ? min(999.99, round($newTotalFeedKg / $newTotalEggsKg, 2))
            : null;

        DB::transaction(function () use (
            $dailyLog,
            $validated,
            $newCoop,
            $newFeed,
            $newTotalFeedKg,
            $newTotalFeedCost,
            $newTotalEggsCount,
            $newTotalEggsKg,
            $hdp,
            $fcr,
            $gradeDetails,
            $oldLoss,
            $oldFeedKg,
            $newLoss
        ) {
            if ($oldLoss > 0) {
                Coop::where('id', $dailyLog->coop_id)->increment('current_population', $oldLoss);
            }

            if ($oldFeedKg > 0) {
                FeedStock::where('id', $dailyLog->feed_stock_id)->increment('stock_kg', $oldFeedKg);
            }

            $dailyLog->update([
                'coop_id' => $validated['coop_id'],
                'log_date' => $validated['log_date'],
                'mortality' => $validated['mortality'] ?? 0,
                'cull' => $validated['cull'] ?? 0,
                'feed_stock_id' => $validated['feed_stock_id'],
                'feed_consumed_kg' => $newTotalFeedKg,
                'feed_cost_total' => $newTotalFeedCost,
                'eggs_total_count' => $newTotalEggsCount,
                'eggs_total_kg' => $newTotalEggsKg,
                'hdp_percentage' => $hdp,
                'fcr' => $fcr,
                'notes' => $validated['notes'] ?? null,
            ]);

            $dailyLog->grades()->delete();

            foreach ($gradeDetails as $detail) {
                $dailyLog->grades()->create($detail);
            }

            if ($newLoss > 0) {
                $newCoop->decrement('current_population', $newLoss);
            }

            if ($newTotalFeedKg > 0) {
                $newFeed->decrement('stock_kg', $newTotalFeedKg);
            }
        });

        return redirect()->route('daily-logs.create')->with('success', 'Data panen berhasil diperbarui!');
    }
}