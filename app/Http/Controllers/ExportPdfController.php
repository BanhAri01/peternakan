<?php

namespace App\Http\Controllers;

use App\Models\EggSale;
use App\Models\ExpenseLedger;
use App\Models\Setting;
use App\Services\FarmFinance;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExportPdfController extends Controller
{
    // Nota penjualan untuk pembeli (A5)
    public function printReceipt(EggSale $sale)
    {
        $sale->load(['customer', 'grade']);
        $farm = Setting::allValues();

        $pdf = Pdf::loadView('pdf.receipt', compact('sale', 'farm'))->setPaper('a5', 'portrait');

        return $pdf->stream('Nota-' . str_pad($sale->id, 5, '0', STR_PAD_LEFT) . '-' . Str::slug($sale->customer->name ?? 'pembeli') . '.pdf');
    }

    // Laporan bulanan: keuangan + performa kandang
    public function exportMonthlyReport(Request $request)
    {
        $request->validate(['month' => 'nullable|date_format:Y-m']);

        $month = Carbon::createFromFormat('Y-m-d', ($request->get('month') ?: now()->format('Y-m')) . '-01')->startOfDay();
        $start = $month->copy()->startOfMonth()->toDateString();
        $end   = $month->copy()->endOfMonth()->toDateString();

        $summary     = FarmFinance::summary($start, $end);
        $performance = FarmFinance::coopPerformance($start, $end);
        $farm        = Setting::allValues();

        $expenseByCategory = ExpenseLedger::whereBetween('transaction_date', [$start, $end])
            ->selectRaw('category, SUM(total_amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category');

        $pdf = Pdf::loadView('pdf.monthly_report', compact('month', 'summary', 'performance', 'farm', 'expenseByCategory'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('Laporan-Peternakan-' . $month->format('Y-m') . '.pdf');
    }
}
