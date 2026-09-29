<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\EggPurchase;
use App\Models\EggSale;
use App\Models\ExpenseLedger;
use App\Models\FeedPurchase;
use App\Models\Vaccination;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class ExpenseLedgerController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        $expenseQuery = ExpenseLedger::whereBetween('transaction_date', [$startDate, $endDate]);

        if ($request->filled('category')) {
            $expenseQuery->where('category', $request->category);
        }

        $totalExpense = (clone $expenseQuery)->sum('total_amount');
        $expenses = (clone $expenseQuery)
            ->latest('transaction_date')
            ->paginate(10)
            ->withQueryString();

        $categories = ExpenseLedger::select('category')
            ->distinct()
            ->pluck('category');

        // ==========================================
        // LAPORAN PENJUALAN
        // ==========================================

        $salesQuery = EggSale::whereBetween('sale_date', [$startDate, $endDate]);

        $totalSalesRevenue = (clone $salesQuery)->sum('total_amount');
        $totalCashIn = (clone $salesQuery)->sum('paid_amount');
        $totalNewDebt = (clone $salesQuery)->sum('debt_amount');

        // ==========================================
        // BEBAN & BIAYA
        // ==========================================

        $feedConsumedCost = DailyLog::whereBetween(
            'log_date',
            [$startDate, $endDate]
        )->sum('feed_cost_total');

        $cashOutFeedPurchase = FeedPurchase::whereBetween(
            'purchase_date',
            [$startDate, $endDate]
        )->sum('total_cost');

        $cashOutEggPurchase = EggPurchase::whereBetween(
            'purchase_date',
            [$startDate, $endDate]
        )->sum('total_cost');

        $operationalCost = ExpenseLedger::whereBetween(
            'transaction_date',
            [$startDate, $endDate]
        )->sum('total_amount');

        // Biaya vaksinasi
        $vaccineCost = Vaccination::whereBetween(
            'vaccination_date',
            [$startDate, $endDate]
        )->sum('cost');

        // ==========================================
        // LABA BERSIH
        // ==========================================

        $totalCogsAndOpEx =
            $feedConsumedCost +
            $cashOutEggPurchase +
            $operationalCost +
            $vaccineCost;

        $netProfit = $totalSalesRevenue - $totalCogsAndOpEx;

        // ==========================================
        // ARUS KAS
        // ==========================================

        $totalCashOut =
            $cashOutFeedPurchase +
            $cashOutEggPurchase +
            $operationalCost +
            $vaccineCost;

        $netCashFlow = $totalCashIn - $totalCashOut;

        // ==========================================
        // PIUTANG
        // ==========================================

        $totalAllDebt = EggSale::where(
            'payment_status',
            '!=',
            'paid'
        )->sum('debt_amount');

        // ==========================================
        // DATA GRAFIK
        // ==========================================

        $period = CarbonPeriod::create($startDate, $endDate);

        $chartLabels = [];
        $chartSalesData = [];
        $chartExpenseData = [];
        $chartProfitData = [];

        $salesByDate = EggSale::whereBetween(
            'sale_date',
            [$startDate, $endDate]
        )
            ->selectRaw('sale_date, SUM(total_amount) as total')
            ->groupBy('sale_date')
            ->pluck('total', 'sale_date');

        $expenseByDate = ExpenseLedger::whereBetween(
            'transaction_date',
            [$startDate, $endDate]
        )
            ->selectRaw('transaction_date, SUM(total_amount) as total')
            ->groupBy('transaction_date')
            ->pluck('total', 'transaction_date');

        $feedCostByDate = DailyLog::whereBetween(
            'log_date',
            [$startDate, $endDate]
        )
            ->selectRaw('log_date, SUM(feed_cost_total) as total')
            ->groupBy('log_date')
            ->pluck('total', 'log_date');

        $vaccineCostByDate = Vaccination::whereBetween(
            'vaccination_date',
            [$startDate, $endDate]
        )
            ->selectRaw('vaccination_date, SUM(cost) as total')
            ->groupBy('vaccination_date')
            ->pluck('total', 'vaccination_date');

        foreach ($period as $date) {
            $d = $date->format('Y-m-d');

            $chartLabels[] = $date->format('d M');

            $s = (float) ($salesByDate[$d] ?? 0);
            $e = (float) ($expenseByDate[$d] ?? 0);
            $f = (float) ($feedCostByDate[$d] ?? 0);
            $v = (float) ($vaccineCostByDate[$d] ?? 0);

            $chartSalesData[] = $s;
            $chartExpenseData[] = $e + $v;
            $chartProfitData[] = $s - ($e + $f + $v);
        }

        // ==========================================
        // KOMPOSISI BIAYA
        // ==========================================

        $expenseCategoriesBreakdown = ExpenseLedger::whereBetween(
            'transaction_date',
            [$startDate, $endDate]
        )
            ->selectRaw('category, SUM(total_amount) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        // Tambahkan vaksinasi sebagai kategori biaya
        if ($vaccineCost > 0) {
            $expenseCategoriesBreakdown->put('Vaksinasi', $vaccineCost);
        }

        $categoryLabels = $expenseCategoriesBreakdown->keys()->toArray();
        $categoryData = $expenseCategoriesBreakdown->values()->toArray();

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
            'vaccineCost',
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
            'expense_type' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:100',
            'officer' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        ExpenseLedger::create($validated);

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Catatan pengeluaran kas berhasil dibukukan!');
    }

    public function edit(ExpenseLedger $expense)
    {
        return view('expense-ledgers.edit', compact('expense'));
    }

    public function update(Request $request, ExpenseLedger $expense)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'expense_type' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'supplier' => 'nullable|string|max:255',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:50',
            'unit_price' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:100',
            'officer' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $expense->update($validated);

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Data buku keuangan berhasil diperbarui!');
    }

    public function destroy(ExpenseLedger $expense)
    {
        $expense->delete();

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Data pengeluaran berhasil dihapus!');
    }
}