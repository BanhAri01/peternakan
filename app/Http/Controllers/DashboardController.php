<?php

namespace App\Http\Controllers;

use App\Models\Coop;
use App\Models\DailyLog;
use App\Models\DailyLogGrade;
use App\Models\EggGrade;
use App\Models\EggSale;
use App\Models\FeedStock;
use App\Models\OperationalCost;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->get('date', Carbon::today()->toDateString());

        // 1. Data Log Harian dengan Relasi
        $dailyLogs = DailyLog::with(['coop', 'feedStock', 'grades.grade'])
            ->where('log_date', $selectedDate)
            ->get();

        // 2. Ringkasan Global Farm Hari Ini
        $totalEggKg          = $dailyLogs->sum('eggs_total_kg');
        $totalEggCount       = $dailyLogs->sum('eggs_total_count');
        $totalEggTrays       = floor($totalEggCount / 30);
        $totalEggExtra       = $totalEggCount % 30;
        $totalMortality      = $dailyLogs->sum('mortality');
        $totalCull           = $dailyLogs->sum('cull');
        $totalFeedConsumedKg = $dailyLogs->sum('feed_consumed_kg');
        $totalFeedCost       = $dailyLogs->sum('feed_cost_total');

        // 3. Populasi & HDP Farm Global
        $totalActivePop = Coop::where('status', 'active')->sum('current_population');
        $overallHdp     = $totalActivePop > 0 ? round(($totalEggCount / $totalActivePop) * 100, 1) : 0;
        $overallFcr     = $totalEggKg > 0 ? round($totalFeedConsumedKg / $totalEggKg, 2) : 0;

        // 4. Kalkulasi HPP Real-time
        $monthlyOps = OperationalCost::whereBetween('expense_date', [
            Carbon::parse($selectedDate)->subDays(30)->toDateString(),
            $selectedDate
        ])->sum('amount');
        $dailyOpsCost        = $monthlyOps > 0 ? ($monthlyOps / 30) : 0;
        $totalProductionCost = $totalFeedCost + $dailyOpsCost;
        $hppPerKg            = $totalEggKg > 0 ? round($totalProductionCost / $totalEggKg) : 0;

        // 5. Rata-rata Harga Jual Telur Terkini
        $avgSalePricePerKg = EggSale::whereBetween('sale_date', [
            Carbon::parse($selectedDate)->subDays(7)->toDateString(),
            $selectedDate
        ])->avg('price_per_kg') ?: 25000;

        // 6. Data Trend 7 Hari untuk Chart Garis
        $sevenDaysDates = collect(range(6, 0))->map(function ($days) use ($selectedDate) {
            return Carbon::parse($selectedDate)->subDays($days)->toDateString();
        });

        $chartDates = [];
        $chartEggKg = [];
        $chartFeedKg = [];

        foreach ($sevenDaysDates as $d) {
            $chartDates[]  = Carbon::parse($d)->format('d M');
            $chartEggKg[]  = (float) DailyLog::where('log_date', $d)->sum('eggs_total_kg');
            $chartFeedKg[] = (float) DailyLog::where('log_date', $d)->sum('feed_consumed_kg');
        }

        // 7. Komposisi Grade Telur Hari Ini untuk Donut Chart
        $gradeBreakdown = DailyLogGrade::with('grade')
            ->whereHas('dailyLog', function ($q) use ($selectedDate) {
                $q->where('log_date', $selectedDate);
            })
            ->get()
            ->groupBy('egg_grade_id')
            ->map(function ($items) {
                return [
                    'name'      => $items->first()->grade->name ?? 'Grade',
                    'weight_kg' => (float) $items->sum('weight_kg'),
                ];
            })
            ->values();

        // 8. Rincian Eksekutif Sangat Detail Per Kandang
        $coopDetails = Coop::where('status', 'active')->get()->map(function ($coop) use ($selectedDate, $avgSalePricePerKg) {
            $weeksPassed     = Carbon::parse($coop->chick_in_date)->diffInWeeks(Carbon::parse($selectedDate));
            $currentAgeWeeks = $coop->initial_age_weeks + $weeksPassed;

            $log = DailyLog::with(['grades.grade', 'feedStock'])
                ->where('coop_id', $coop->id)
                ->where('log_date', $selectedDate)
                ->first();

            $yesterdayLog = DailyLog::where('coop_id', $coop->id)
                ->where('log_date', Carbon::parse($selectedDate)->subDay()->toDateString())
                ->first();

            $statusColor = 'emerald';
            $statusNote  = 'Performa Normal';

            if (!$log) {
                $statusColor = 'slate';
                $statusNote  = 'Belum Ada Input';
            } else {
                if ($log->hdp_percentage < 68) {
                    $statusColor = 'rose';
                    $statusNote  = 'Kritis! HDP < 68% (Potensi Tekor / Evaluasi Afkir)';
                } elseif ($yesterdayLog && ($yesterdayLog->hdp_percentage - $log->hdp_percentage) >= 4.0) {
                    $statusColor = 'amber';
                    $statusNote  = 'Drop Produksi Anomali (> 4%)';
                }
            }

            $feedGramPerHen = 0;
            $eggRevenueEst  = 0;
            $feedCostDaily  = 0;
            $marginDaily    = 0;
            $marginPerHen   = 0;

            if ($log) {
                $pop = $coop->current_population;
                if ($pop > 0) {
                    $feedGramPerHen = round(($log->feed_consumed_kg * 1000) / $pop, 1);
                }

                $eggRevenueEst = round($log->eggs_total_kg * $avgSalePricePerKg);
                $feedCostDaily = $log->feed_cost_total;
                $marginDaily   = $eggRevenueEst - $feedCostDaily;
                $marginPerHen  = $pop > 0 ? round($marginDaily / $pop) : 0;
            }

            return [
                'coop'           => $coop,
                'age_weeks'      => $currentAgeWeeks,
                'log'            => $log,
                'statusColor'    => $statusColor,
                'statusNote'     => $statusNote,
                'feedGramPerHen' => $feedGramPerHen,
                'eggRevenueEst'  => $eggRevenueEst,
                'feedCostDaily'  => $feedCostDaily,
                'marginDaily'    => $marginDaily,
                'marginPerHen'   => $marginPerHen,
            ];
        });

        // 9. Ketahanan Pakan Gudang
        $avgDailyFeed = DailyLog::whereBetween('log_date', [
            Carbon::parse($selectedDate)->subDays(7)->toDateString(),
            $selectedDate
        ])->avg('feed_consumed_kg') ?: ($totalFeedConsumedKg ?: 100);

        $feedStocks = FeedStock::all()->map(function ($feed) use ($avgDailyFeed) {
            $daysLeft = $avgDailyFeed > 0 ? floor($feed->stock_kg / $avgDailyFeed) : 0;
            return [
                'name'      => $feed->feed_name,
                'stock_kg'  => $feed->stock_kg,
                'days_left' => $daysLeft,
            ];
        });

        return view('dashboard.index', compact(
            'selectedDate',
            'totalEggKg',
            'totalEggCount',
            'totalEggTrays',
            'totalEggExtra',
            'totalMortality',
            'totalCull',
            'totalFeedConsumedKg',
            'overallHdp',
            'overallFcr',
            'hppPerKg',
            'totalProductionCost',
            'chartDates',
            'chartEggKg',
            'chartFeedKg',
            'gradeBreakdown',
            'coopDetails',
            'feedStocks'
        ));
    }
}