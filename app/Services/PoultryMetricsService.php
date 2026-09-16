<?php
namespace App\Services;

use App\Models\DailyLog;
use App\Models\OperationalCost;
use Carbon\Carbon;

class PoultryMetricsService
{
    /**
     * Menghitung HPP per Kilogram Telur pada tanggal tertentu
     */
    public function calculateDailyCostPerKg(string $date): array
    {
        // 1. Total biaya pakan hari ini
        $dailyFeedCost = DailyLog::where('log_date', $date)->sum('feed_cost_total');

        // 2. Total kg telur yang dihasilkan hari ini
        $dailyEggKg = DailyLog::where('log_date', $date)->sum('eggs_total_kg');

        // 3. Alokasi biaya operasional harian (rata-rata 30 hari terakhir)
        $monthlyOps = OperationalCost::whereBetween('expense_date', [
            Carbon::parse($date)->subDays(30)->toDateString(),
            $date
        ])->sum('amount');
        
        $dailyOpsCostAllocation = $monthlyOps > 0 ? ($monthlyOps / 30) : 0;

        $totalDailyCost = $dailyFeedCost + $dailyOpsCostAllocation;

        $hppPerKg = ($dailyEggKg > 0) ? round($totalDailyCost / $dailyEggKg, 2) : 0;

        return [
            'total_egg_kg'      => $dailyEggKg,
            'feed_cost'         => $dailyFeedCost,
            'ops_cost_estimate' => $dailyOpsCostAllocation,
            'total_production_cost' => $totalDailyCost,
            'hpp_per_kg'        => $hppPerKg,
        ];
    }
}