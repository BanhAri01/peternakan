<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\EggSale;
use App\Models\ExpenseLedger;
use App\Models\Vaccination;
use App\Services\FarmFinance;
use App\Support\Format;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class ExpenseLedgerController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date',
            'category'   => 'nullable|string|max:255',
        ]);

        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->get('end_date', Carbon::now()->toDateString());
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $expenseQuery = ExpenseLedger::whereBetween('transaction_date', [$startDate, $endDate])
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->category));

        $filteredTotal = (float) (clone $expenseQuery)->sum('total_amount');
        $expenses = (clone $expenseQuery)->latest('transaction_date')->latest('id')->paginate(15)->withQueryString();

        $categories = ExpenseLedger::select('category')->distinct()->orderBy('category')->pluck('category');
        $summary    = FarmFinance::summary($startDate, $endDate);

        // Grafik harian: uang masuk vs uang keluar
        $salesByDate   = EggSale::whereBetween('sale_date', [$startDate, $endDate])->selectRaw('sale_date as d, SUM(total_amount) as t')->groupBy('sale_date')->pluck('t', 'd');
        $expenseByDate = ExpenseLedger::whereBetween('transaction_date', [$startDate, $endDate])->selectRaw('transaction_date as d, SUM(total_amount) as t')->groupBy('transaction_date')->pluck('t', 'd');
        $feedByDate    = DailyLog::whereBetween('log_date', [$startDate, $endDate])->selectRaw('log_date as d, SUM(feed_cost_total) as t')->groupBy('log_date')->pluck('t', 'd');
        $vaccineByDate = Vaccination::whereBetween('vaccination_date', [$startDate, $endDate])->selectRaw('vaccination_date as d, SUM(cost) as t')->groupBy('vaccination_date')->pluck('t', 'd');

        $norm = fn ($c) => $c->mapWithKeys(fn ($v, $k) => [Carbon::parse($k)->toDateString() => (float) $v]);
        [$salesByDate, $expenseByDate, $feedByDate, $vaccineByDate] = array_map($norm, [$salesByDate, $expenseByDate, $feedByDate, $vaccineByDate]);

        $chart = ['labels' => [], 'income' => [], 'cost' => []];
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            $d = $date->toDateString();
            $chart['labels'][] = $date->translatedFormat('d M');
            $chart['income'][] = $salesByDate[$d] ?? 0;
            $chart['cost'][]   = ($expenseByDate[$d] ?? 0) + ($feedByDate[$d] ?? 0) + ($vaccineByDate[$d] ?? 0);
        }

        $byCategory = ExpenseLedger::whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw('category, SUM(total_amount) as total')
            ->groupBy('category')->orderByDesc('total')
            ->pluck('total', 'category');

        return view('expense-ledgers.index', compact(
            'startDate', 'endDate', 'expenses', 'filteredTotal', 'categories', 'summary', 'chart', 'byCategory'
        ));
    }

    public function create()
    {
        return view('expense-ledgers.create', ['expense' => new ExpenseLedger([
            'transaction_date' => today(),
            'expense_type'     => 'Operasional',
            'quantity'         => 1,
            'unit'             => 'Pcs',
            'payment_method'   => 'Tunai / Kas Kecil',
            'officer'          => auth()->user()->name,
        ])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $expense = ExpenseLedger::create($data);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran "' . $expense->item_name . '" sebesar ' . Format::rupiah($expense->total_amount) . ' tercatat.');
    }

    public function edit(ExpenseLedger $expense)
    {
        return view('expense-ledgers.edit', compact('expense'));
    }

    public function update(Request $request, ExpenseLedger $expense)
    {
        $expense->update($this->validated($request));

        return redirect()->route('expenses.index')->with('success', 'Catatan pengeluaran berhasil diperbarui.');
    }

    public function destroy(ExpenseLedger $expense)
    {
        $expense->delete();

        return back()->with('success', 'Catatan pengeluaran dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'transaction_date' => 'required|date|before_or_equal:today',
            'expense_type'     => 'required|string|max:255',
            'category'         => 'required|string|max:255',
            'item_name'        => 'required|string|max:255',
            'supplier'         => 'nullable|string|max:255',
            'quantity'         => 'required|numeric|min:0.01',
            'unit'             => 'required|string|max:50',
            'unit_price'       => 'required|numeric|min:0',
            'payment_method'   => 'required|string|max:100',
            'officer'          => 'required|string|max:255',
            'notes'            => 'nullable|string|max:1000',
        ]);

        // Total selalu dihitung ulang di server (jumlah x harga satuan)
        $data['total_amount'] = round($data['quantity'] * $data['unit_price'], 2);

        return $data;
    }
}
