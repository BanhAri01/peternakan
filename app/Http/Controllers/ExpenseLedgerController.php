<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\EggPurchase;
use App\Models\EggSale;
use App\Models\ExpenseLedger;
use App\Models\FeedPurchase;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class ExpenseLedgerController extends Controller
{
    public function index(Request $request)
    {
        // 1. Rentang Tanggal Filter (Default: Awal bulan ini s/d hari ini)
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->get('end_date', Carbon::now()->toDateString());

        // 2. Query Transaksi Buku Kas / Pengeluaran
        $expenseQuery = ExpenseLedger::whereBetween('transaction_date', [$startDate, $endDate]);

        if ($request->filled('category')) {
            $expenseQuery->where('category', $request->category);
        }

        $totalExpense = (clone $expenseQuery)->sum('total_amount');
        $expenses = (clone $expenseQuery)->latest('transaction_date')->paginate(10)->withQueryString();
        $categories = ExpenseLedger::select('category')->distinct()->pluck('category');

        // ==========================================
        // 3. KALKULASI LAPORAN KEUANGAN
        // ==========================================
        // Penjualan Telur
        $salesQuery = EggSale::whereBetween('sale_date', [$startDate, $endDate]);
        $totalSalesRevenue = (clone $salesQuery)->sum('total_amount'); // Total Omzet Penjualan
        $totalCashIn       = (clone $salesQuery)->sum('paid_amount');  // Uang Masuk Kas Nyata
        $totalNewDebt      = (clone $salesQuery)->sum('debt_amount');  // Piutang Baru

        // Beban & Biaya
        $feedConsumedCost    = DailyLog::whereBetween('log_date', [$startDate, $endDate])->sum('feed_cost_total');
        $cashOutFeedPurchase = FeedPurchase::whereBetween('purchase_date', [$startDate, $endDate])->sum('total_cost');
        $cashOutEggPurchase  = EggPurchase::whereBetween('purchase_date', [$startDate, $endDate])->sum('total_cost');
        $operationalCost     = ExpenseLedger::whereBetween('transaction_date', [$startDate, $endDate])->sum('total_amount');

        // Laba Bersih & Arus Kas Nyata
        $totalCogsAndOpEx = $feedConsumedCost + $cashOutEggPurchase + $operationalCost;
        $netProfit        = $totalSalesRevenue - $totalCogsAndOpEx;

        $totalCashOut = $cashOutFeedPurchase + $cashOutEggPurchase + $operationalCost;
        $netCashFlow  = $totalCashIn - $totalCashOut;

        // Piutang Bakul Berjalan
        $totalAllDebt = EggSale::where('payment_status', '!=', 'paid')->sum('debt_amount');

        // ==========================================
        // 4. GENERATE DATA GRAFIK (CHART.JS)
        // ==========================================
        $period = CarbonPeriod::create($startDate, $endDate);
        $chartLabels = [];
        $chartSalesData = [];
        $chartExpenseData = [];
        $chartProfitData = [];

        // Kelompokkan data per tanggal
        $salesByDate = EggSale::whereBetween('sale_date', [$startDate, $endDate])
            ->selectRaw('sale_date, SUM(total_amount) as total')
            ->groupBy('sale_date')
            ->pluck('total', 'sale_date');

        $expenseByDate = ExpenseLedger::whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('transaction_date, SUM(total_amount) as total')
            ->groupBy('transaction_date')
            ->pluck('total', 'transaction_date');

        $feedCostByDate = DailyLog::whereBetween('log_date', [$startDate, $endDate])
            ->selectRaw('log_date, SUM(feed_cost_total) as total')
            ->groupBy('log_date')
            ->pluck('total', 'log_date');

        foreach ($period as $date) {
            $d = $date->format('Y-m-d');
            $chartLabels[] = $date->format('d M');

            $s = (float) ($salesByDate[$d] ?? 0);
            $e = (float) ($expenseByDate[$d] ?? 0);
            $f = (float) ($feedCostByDate[$d] ?? 0);

            $chartSalesData[] = $s;
            $chartExpenseData[] = $e;
            $chartProfitData[] = $s - ($e + $f);
        }

        // Komposisi Biaya per Kategori (Donut Chart)
        $expenseCategoriesBreakdown = ExpenseLedger::whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('category, SUM(total_amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $categoryLabels = $expenseCategoriesBreakdown->keys()->toArray();
        $categoryData   = $expenseCategoriesBreakdown->values()->toArray();

        return view('expense-ledgers.index', compact(
            'startDate',
            'endDate',
            'expenses',
            'totalExpense',
            'categories',
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
            'totalAllDebt',
            'chartLabels',
            'chartSalesData',
            'chartExpenseData',
            'chartProfitData',
            'categoryLabels',
            'categoryData'
        ));
    }

    public function create()
    {
        return view('expense-ledgers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'expense_type'     => 'required|string|max:255',
            'category'         => 'required|string|max:255',
            'item_name'        => 'required|string|max:255',
            'supplier'         => 'nullable|string|max:255',
            'quantity'         => 'required|numeric|min:0.01',
            'unit'             => 'required|string|max:50',
            'unit_price'       => 'required|numeric|min:0',
            'total_amount'     => 'required|numeric|min:0',
            'payment_method'   => 'required|string|max:100',
            'officer'          => 'required|string|max:255',
            'notes'            => 'nullable|string',
        ]);

        ExpenseLedger::create($validated);

        return redirect()->route('expenses.index')->with('success', 'Catatan pengeluaran kas berhasil dibukukan!');
    }

    public function edit(ExpenseLedger $expense)
    {
        return view('expense-ledgers.edit', compact('expense'));
    }

    public function update(Request $request, ExpenseLedger $expense)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'expense_type'     => 'required|string|max:255',
            'category'         => 'required|string|max:255',
            'item_name'        => 'required|string|max:255',
            'supplier'         => 'nullable|string|max:255',
            'quantity'         => 'required|numeric|min:0.01',
            'unit'             => 'required|string|max:50',
            'unit_price'       => 'required|numeric|min:0',
            'total_amount'     => 'required|numeric|min:0',
            'payment_method'   => 'required|string|max:100',
            'officer'          => 'required|string|max:255',
            'notes'            => 'nullable|string',
        ]);

        $expense->update($validated);

        return redirect()->route('expenses.index')->with('success', 'Data buku keuangan berhasil diperbarui!');
    }

    public function destroy(ExpenseLedger $expense)
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Data pengeluaran berhasil dihapus!');
    }
}