<?php

namespace App\Http\Controllers;

use App\Models\EggSale;
use App\Models\ExpenseLedger;
use App\Services\FarmFinance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['month' => 'nullable|date_format:Y-m']);

        $month = Carbon::createFromFormat('Y-m-d', ($request->get('month') ?: now()->format('Y-m')) . '-01')->startOfDay();
        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();
        if ($end->isFuture()) {
            $end = Carbon::today();
        }

        $summary     = FarmFinance::summary($start->toDateString(), $end->toDateString());
        $performance = FarmFinance::coopPerformance($start->toDateString(), $end->toDateString());

        // Bandingkan dengan bulan sebelumnya
        $prevStart = $start->copy()->subMonthNoOverflow()->startOfMonth();
        $previous  = FarmFinance::summary($prevStart->toDateString(), $prevStart->copy()->endOfMonth()->toDateString());

        $expenseByCategory = ExpenseLedger::whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('category, SUM(total_amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category');

        $salesByGrade = EggSale::query()
            ->join('egg_grades', 'egg_grades.id', '=', 'egg_sales.egg_grade_id')
            ->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('egg_grades.name, SUM(egg_sales.weight_kg) as kg, SUM(egg_sales.total_amount) as total')
            ->groupBy('egg_grades.name')
            ->orderByDesc('total')
            ->get();

        $months = collect(range(0, 11))->map(fn ($i) => now()->startOfMonth()->subMonthsNoOverflow($i));

        return view('reports.index', compact(
            'month', 'start', 'end', 'summary', 'previous', 'performance', 'expenseByCategory', 'salesByGrade', 'months'
        ));
    }
}
