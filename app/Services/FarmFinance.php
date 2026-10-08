<?php

namespace App\Services;

use App\Models\Coop;
use App\Models\DailyLog;
use App\Models\EggPurchase;
use App\Models\EggSale;
use App\Models\ExpenseLedger;
use App\Models\FeedPurchase;
use App\Models\Vaccination;
use Carbon\Carbon;

// Satu sumber perhitungan keuangan agar Buku Kas, Laporan, dan PDF selalu sama angkanya
class FarmFinance
{
    public static function summary(string $start, string $end): array
    {
        $sales = EggSale::whereBetween('sale_date', [$start, $end]);

        $revenue  = (float) (clone $sales)->sum('total_amount');
        $cashIn   = (float) (clone $sales)->sum('paid_amount');
        $newDebt  = (float) (clone $sales)->sum('debt_amount');

        $feedUsed     = (float) DailyLog::whereBetween('log_date', [$start, $end])->sum('feed_cost_total');
        $feedBought   = (float) FeedPurchase::whereBetween('purchase_date', [$start, $end])->sum('total_cost');
        $eggBought    = (float) EggPurchase::whereBetween('purchase_date', [$start, $end])->sum('total_cost');
        $expenses     = (float) ExpenseLedger::whereBetween('transaction_date', [$start, $end])->sum('total_amount');
        $vaccines     = (float) Vaccination::whereBetween('vaccination_date', [$start, $end])->sum('cost');

        // Laba: pakan dihitung dari yang benar-benar dimakan ayam
        $totalCost = $feedUsed + $eggBought + $expenses + $vaccines;
        // Arus kas: pakan dihitung dari uang yang keluar untuk membeli pakan
        $cashOut   = $feedBought + $eggBought + $expenses + $vaccines;

        return [
            'revenue'     => $revenue,
            'cash_in'     => $cashIn,
            'new_debt'    => $newDebt,
            'feed_used'   => $feedUsed,
            'feed_bought' => $feedBought,
            'egg_bought'  => $eggBought,
            'expenses'    => $expenses,
            'vaccines'    => $vaccines,
            'total_cost'  => $totalCost,
            'net_profit'  => $revenue - $totalCost,
            'cash_out'    => $cashOut,
            'net_cash'    => $cashIn - $cashOut,
        ];
    }

    // Rata-rata biaya non-pakan per hari (30 hari terakhir), dipakai untuk HPP
    public static function dailyOverhead(string $date): float
    {
        $end   = Carbon::parse($date);
        $start = $end->copy()->subDays(29)->toDateString();
        $end   = $end->toDateString();

        $expenses = ExpenseLedger::whereBetween('transaction_date', [$start, $end])->sum('total_amount');
        $vaccines = Vaccination::whereBetween('vaccination_date', [$start, $end])->sum('cost');

        return ($expenses + $vaccines) / 30;
    }

    // Ringkasan performa tiap kandang dalam satu periode
    public static function coopPerformance(string $start, string $end)
    {
        $stats = DailyLog::whereBetween('log_date', [$start, $end])
            ->selectRaw('coop_id, COUNT(*) as days, SUM(eggs_total_kg) as egg_kg, SUM(eggs_total_count) as egg_count,
                SUM(feed_consumed_kg) as feed_kg, AVG(hdp_percentage) as avg_hdp, SUM(mortality) as mortality, SUM(cull) as cull')
            ->groupBy('coop_id')
            ->get()
            ->keyBy('coop_id');

        return Coop::orderBy('name')->get()
            ->filter(fn ($coop) => $coop->status === 'active' || $stats->has($coop->id))
            ->map(function ($coop) use ($stats) {
                $s      = $stats->get($coop->id);
                $eggKg  = (float) ($s->egg_kg ?? 0);
                $feedKg = (float) ($s->feed_kg ?? 0);

                return [
                    'coop'      => $coop,
                    'days'      => (int) ($s->days ?? 0),
                    'egg_kg'    => $eggKg,
                    'egg_count' => (int) ($s->egg_count ?? 0),
                    'feed_kg'   => $feedKg,
                    'avg_hdp'   => round((float) ($s->avg_hdp ?? 0), 1),
                    'fcr'       => $eggKg > 0 ? round($feedKg / $eggKg, 2) : null,
                    'loss'      => (int) ($s->mortality ?? 0) + (int) ($s->cull ?? 0),
                ];
            })
            ->values();
    }
}
