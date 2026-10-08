<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ExpenseLedger;
use App\Models\Setting;
use App\Services\FarmFinance;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExportPdfController extends Controller
{
    // Ukuran kertas nota (dalam point; 1 inci = 72 pt)
    public const RECEIPT_PAPERS = [
        'continuous' => ['label' => 'Kertas kontinyu 9,5 x 11 inci (printer dot-matrix)', 'size' => [0, 0, 684, 792]],
        'a4'         => ['label' => 'A4 (HVS biasa)', 'size' => 'a4'],
        'a5'         => ['label' => 'A5 mendatar (setengah A4)', 'size' => 'a5-landscape'],
    ];

    // Nota penjualan untuk pembeli
    public function printReceipt(Request $request, Invoice $invoice)
    {
        $invoice->load(['customer', 'lines.grade', 'creator']);
        $farm  = Setting::allValues();
        $paper = $request->get('kertas', $farm['receipt_paper'] ?? 'continuous');
        $paper = array_key_exists($paper, self::RECEIPT_PAPERS) ? $paper : 'continuous';

        $size = self::RECEIPT_PAPERS[$paper]['size'];
        $pdf  = Pdf::loadView('pdf.receipt', compact('invoice', 'farm', 'paper'));
        $size === 'a5-landscape' ? $pdf->setPaper('a5', 'landscape') : $pdf->setPaper($size, 'portrait');

        return $pdf->stream($invoice->number . '-' . Str::slug($invoice->customer->name ?? 'pembeli') . '.pdf');
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
