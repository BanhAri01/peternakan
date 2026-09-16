<?php

namespace App\Http\Controllers;

use App\Models\Coop;
use App\Models\DailyLog;
use App\Models\EggPurchase;
use App\Models\EggSale;
use App\Models\FeedPurchase;
use App\Models\OperationalCost;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExportPdfController extends Controller
{
    /**
     * Cetak Nota Faktur Penjualan Telur (Untuk Bakul/Pelanggan)
     * Dibuat ramah cetak thermal kertas struk / A5 / A4 landscape
     */
    public function printReceipt(EggSale $sale)
    {
        $sale->load(['customer', 'grade']);

        $pdf = Pdf::loadView('pdf.receipt', compact('sale'))
                  ->setPaper('a5', 'landscape');

        return $pdf->stream("Nota-Penjualan-{$sale->id}-{$sale->customer->name}.pdf");
    }

    /**
     * Ekspor Laporan Bulanan Performa Kandang & Laba Rugi Eksekutif
     */
    public function exportMonthlyReport(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::parse($month)->startOfMonth()->toDateString();
        $endDate   = Carbon::parse($month)->endOfMonth()->toDateString();

        // 1. Data Penjualan & Kas
        $sales = EggSale::whereBetween('sale_date', [$startDate, $endDate])->get();
        $totalRevenue = $sales->sum('total_amount');
        $totalCashIn  = $sales->sum('paid_amount');
        $totalDebt    = $sales->sum('debt_amount');

        // 2. Data Biaya & Pengeluaran
        $feedConsumedCost = DailyLog::whereBetween('log_date', [$startDate, $endDate])->sum('feed_cost_total');
        $cashOutFeed      = FeedPurchase::whereBetween('purchase_date', [$startDate, $endDate])->sum('total_cost');
        $cashOutEggBuy    = EggPurchase::whereBetween('purchase_date', [$startDate, $endDate])->sum('total_cost');
        $opsCost          = OperationalCost::whereBetween('expense_date', [$startDate, $endDate])->sum('amount');

        $netProfit = $totalRevenue - ($feedConsumedCost + $cashOutEggBuy + $opsCost);
        $netCashFlow = $totalCashIn - ($cashOutFeed + $cashOutEggBuy + $opsCost);

        // 3. Performa Kandang Periode Tersebut
        $coopPerformance = Coop::all()->map(function ($coop) use ($startDate, $endDate) {
            $logs = DailyLog::where('coop_id', $coop->id)
                ->whereBetween('log_date', [$startDate, $endDate])
                ->get();

            $totalEggsKg = $logs->sum('eggs_total_kg');
            $totalFeedKg = $logs->sum('feed_consumed_kg');
            $avgHdp      = $logs->avg('hdp_percentage') ?? 0;
            $avgFcr      = $totalEggsKg > 0 ? round($totalFeedKg / $totalEggsKg, 2) : 0;
            $mortality   = $logs->sum('mortality') + $logs->sum('cull');

            return [
                'coop'        => $coop,
                'totalEggsKg' => $totalEggsKg,
                'totalFeedKg' => $totalFeedKg,
                'avgHdp'      => round($avgHdp, 1),
                'avgFcr'      => $avgFcr,
                'mortality'   => $mortality,
            ];
        });

        $pdf = Pdf::loadView('pdf.monthly_report', compact(
            'month',
            'startDate',
            'endDate',
            'totalRevenue',
            'totalCashIn',
            'totalDebt',
            'feedConsumedCost',
            'cashOutFeed',
            'cashOutEggBuy',
            'opsCost',
            'netProfit',
            'netCashFlow',
            'coopPerformance'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("Laporan-Peternakan-{$month}.pdf");
    }
}