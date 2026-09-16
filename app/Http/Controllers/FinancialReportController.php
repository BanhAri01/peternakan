<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\EggPurchase;
use App\Models\EggSale;
use App\Models\FeedPurchase;
use App\Models\OperationalCost;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
    public function index(Request $request)
    {
        // Default rentang tanggal: Bulan berjalan (awal bulan sampai hari ini)
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->get('end_date', Carbon::now()->toDateString());

        // ==========================================
        // 1. PENDAPATAN & PENJUALAN TELUR
        // ==========================================
        $salesQuery = EggSale::whereBetween('sale_date', [$startDate, $endDate]);
        $totalSalesRevenue = $salesQuery->sum('total_amount'); // Total nilai tagihan penjualan
        $totalCashIn       = $salesQuery->sum('paid_amount');  // Kas nyata yang sudah diterima tunai/transfer
        $totalNewDebt      = $salesQuery->sum('debt_amount');  // Piutang baru yang belum lunas pada periode ini

        // ==========================================
        // 2. BEBAN & PENGELUARAN KAS RIIL
        // ==========================================
        // A. Biaya Pakan yang Terpakai di Kandang (Cost of Goods Sold / COGS Pakan)
        $feedConsumedCost = DailyLog::whereBetween('log_date', [$startDate, $endDate])->sum('feed_cost_total');

        // B. Kas Keluar Pembelian Pakan Trukan (Gudang Restock)
        $cashOutFeedPurchase = FeedPurchase::whereBetween('purchase_date', [$startDate, $endDate])->sum('total_cost');

        // C. Kas Keluar Kulakan Telur dari Luar (Egg Arbitrage Buy)
        $cashOutEggPurchase = EggPurchase::whereBetween('purchase_date', [$startDate, $endDate])->sum('total_cost');

        // D. Biaya Operasional Kandang (Gaji ABK, Listrik, Vaksin, Sekam)
        $operationalCost = OperationalCost::whereBetween('expense_date', [$startDate, $endDate])->sum('amount');

        // ==========================================
        // 3. KALKULASI LABA BERSIH & NET CASH FLOW
        // ==========================================
        // Laba Bersih Operasional = Penjualan Telur - (Beban Pakan Terpakai + Biaya Kulakan Telur + Beban Operasional)
        $netProfit = $totalSalesRevenue - ($feedConsumedCost + $cashOutEggPurchase + $operationalCost);

        // Arus Kas Bersih (Uang Masuk Nyata - Uang Keluar Nyata)
        $totalCashOut = $cashOutFeedPurchase + $cashOutEggPurchase + $operationalCost;
        $netCashFlow  = $totalCashIn - $totalCashOut;

        // ==========================================
        // 4. PEMANTAU UMUR PIUTANG BAKUL (AGING RECEIVABLES)
        // ==========================================
        // Ambil semua transaksi yang belum lunas (tanpa batasan tanggal agar hutang lama tetap terpantau)
        $unpaidSales = EggSale::with('customer')
            ->where('payment_status', '!=', 'paid')
            ->where('debt_amount', '>', 0)
            ->get();

        $today = Carbon::today();
        $agingBuckets = [
            'current'  => ['label' => '< 7 Hari (Lancar)', 'total' => 0, 'count' => 0],
            'warning'  => ['label' => '8 - 14 Hari (Waspada)', 'total' => 0, 'count' => 0],
            'critical' => ['label' => '> 14 Hari (Kritis/Macet)', 'total' => 0, 'count' => 0],
        ];

        $agedSalesList = $unpaidSales->map(function ($sale) use ($today, &$agingBuckets) {
            $daysPast = Carbon::parse($sale->sale_date)->diffInDays($today);
            $sale->days_past = $daysPast;

            if ($daysPast <= 7) {
                $sale->age_status = 'current';
                $agingBuckets['current']['total'] += $sale->debt_amount;
                $agingBuckets['current']['count']++;
            } elseif ($daysPast <= 14) {
                $sale->age_status = 'warning';
                $agingBuckets['warning']['total'] += $sale->debt_amount;
                $agingBuckets['warning']['count']++;
            } else {
                $sale->age_status = 'critical';
                $agingBuckets['critical']['total'] += $sale->debt_amount;
                $agingBuckets['critical']['count']++;
            }

            return $sale;
        })->sortByDesc('days_past');

        $totalAllDebt = $unpaidSales->sum('debt_amount');

        return view('financial.index', compact(
            'startDate',
            'endDate',
            'totalSalesRevenue',
            'totalCashIn',
            'totalNewDebt',
            'feedConsumedCost',
            'cashOutFeedPurchase',
            'cashOutEggPurchase',
            'operationalCost',
            'netProfit',
            'totalCashOut',
            'netCashFlow',
            'agingBuckets',
            'agedSalesList',
            'totalAllDebt'
        ));
    }
}