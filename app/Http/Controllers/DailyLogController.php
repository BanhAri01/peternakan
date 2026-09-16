<?php
namespace App\Http\Controllers;

use App\Models\Coop;
use App\Models\FeedStock;
use App\Models\DailyLog;
use App\Models\DailyLogGrade;
use App\Models\EggGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyLogController extends Controller
{
    public function create()
    {
        $coops = Coop::where('status', 'active')->get();
        $feedStocks = FeedStock::all();
        // Ambil semua grade aktif yang dibuat oleh owner
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
            
            // Validasi Array Dinamis untuk Setiap Grade
            'grades' => 'required|array',
            'grades.*.egg_grade_id' => 'required|exists:egg_grades,id',
            'grades.*.trays_count'  => 'nullable|integer|min:0',
            'grades.*.extra_eggs'   => 'nullable|integer|min:0',
            'grades.*.weight_kg'    => 'nullable|numeric|min:0',
        ]);

        $coop = Coop::findOrFail($validated['coop_id']);
        $feed = FeedStock::findOrFail($validated['feed_stock_id']);

        $totalFeedKg = (($validated['feed_sacks'] ?? 0) * 50) + ($validated['extra_feed_kg'] ?? 0);
        $totalFeedCost = round($totalFeedKg * $feed->cost_per_kg, 2);

        // Hitung total telur dan kg dari seluruh grade yang diinput
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
                    'trays_count'  => $trays,
                    'extra_eggs'   => $extra,
                    'total_eggs'   => $count,
                    'weight_kg'    => $kg,
                ];
            }
        }

        // Kalkulasi HDP & FCR
        $activePop = $coop->current_population;
        $hdp = $activePop > 0 ? round(($totalEggsCount / $activePop) * 100, 2) : 0;
        $fcr = $totalEggsKg > 0 ? min(999.99, round($totalFeedKg / $totalEggsKg, 2)) : null;

        // Simpan dalam Transaksi Atomik
        DB::transaction(function () use ($validated, $totalFeedKg, $totalFeedCost, $totalEggsCount, $totalEggsKg, $hdp, $fcr, $gradeDetails, $coop, $feed) {
            $log = DailyLog::create([
                'coop_id'          => $validated['coop_id'],
                'log_date'         => $validated['log_date'],
                'mortality'        => $validated['mortality'] ?? 0,
                'cull'             => $validated['cull'] ?? 0,
                'feed_stock_id'    => $validated['feed_stock_id'],
                'feed_consumed_kg' => $totalFeedKg,
                'feed_cost_total'  => $totalFeedCost,
                'eggs_total_count' => $totalEggsCount,
                'eggs_total_kg'    => $totalEggsKg,
                'hdp_percentage'   => $hdp,
                'fcr'              => $fcr,
                'notes'            => $validated['notes'] ?? null,
            ]);

            foreach ($gradeDetails as $detail) {
                $log->grades()->create($detail);
            }

            // Kurangi ayam & potong pakan
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
}
