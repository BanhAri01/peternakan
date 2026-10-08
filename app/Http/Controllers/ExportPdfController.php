<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\EggGrade;
use App\Models\EggSale;
use App\Models\Invoice;
use App\Models\ExpenseLedger;
use App\Models\Setting;
use App\Services\FarmFinance;
use App\Support\Format;
use App\Support\Qr;
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

        return $this->renderReceipt($request, $invoice, Setting::allValues());
    }

    // Contoh nota dari pengaturan saat ini (tidak disimpan ke database)
    public function previewReceipt(Request $request)
    {
        $farm = Setting::allValues();

        $invoice = new Invoice(['number' => 'NT-' . now()->format('Ym') . '-0001', 'sale_date' => today(), 'due_date' => today()->addDays(7)]);
        $invoice->setRelation('customer', new Customer(['name' => 'Bakul Bu Sari (contoh)', 'phone' => '081234567890', 'address' => 'Pasar Kidul Kios 4']));
        $invoice->setRelation('lines', collect([
            ['Telur Besar', 'kg', 25, 27000, 675000, 675000],
            ['Telur Sedang', 'kg', 15, 25500, 382500, 225000],
            ['Telur Kecil', 'krat', 4, 45000, 180000, 0],
        ])->map(function ($l) {
            $line = new EggSale(['unit_type' => $l[1], 'quantity_unit' => $l[2], 'price_per_unit' => $l[3]]);
            $line->forceFill(['total_amount' => $l[4], 'paid_amount' => $l[5], 'debt_amount' => $l[4] - $l[5], 'weight_kg' => $l[1] === 'krat' ? $l[2] * 1.9 : $l[2]]);
            $line->setRelation('grade', new EggGrade(['name' => $l[0]]));

            return $line;
        }));

        return $this->renderReceipt($request, $invoice, $farm);
    }

    private function renderReceipt(Request $request, Invoice $invoice, array $farm)
    {
        $paper = $request->get('kertas', $farm['receipt_paper'] ?? 'continuous');
        $paper = array_key_exists($paper, self::RECEIPT_PAPERS) ? $paper : 'continuous';
        $style = $request->get('gaya', $farm['receipt_style'] ?? 'color') === 'ink' ? 'ink' : 'color';

        // Warna: gaya hemat tinta selalu hitam
        $primary = $style === 'ink' ? '#000000' : (preg_match('/^#[0-9a-f]{6}$/i', $farm['receipt_color'] ?? '') ? $farm['receipt_color'] : '#3f5a26');
        $brand = [
            'style'   => $style,
            'primary' => $primary,
            'soft'    => $style === 'ink' ? '#ffffff' : self::tint($primary, 0.88),
            'mid'     => $style === 'ink' ? '#000000' : self::tint($primary, 0.55),
            'qr'      => null,
            'wa'      => Format::waNumber($farm['farm_phone'] ?? ''),
        ];
        if (($farm['receipt_show_qr'] ?? '1') === '1' && $brand['wa']) {
            $brand['qr'] = Qr::dataUri('https://wa.me/' . $brand['wa'] . '?text=' . rawurlencode('Halo ' . $farm['farm_name'] . ', saya mau pesan telur.'));
        }

        $size = self::RECEIPT_PAPERS[$paper]['size'];
        $pdf  = Pdf::loadView('pdf.receipt', compact('invoice', 'farm', 'paper', 'brand'));
        $size === 'a5-landscape' ? $pdf->setPaper('a5', 'landscape') : $pdf->setPaper($size, 'portrait');

        return $pdf->stream($invoice->number . '-' . Str::slug($invoice->customer->name ?? 'pembeli') . '.pdf');
    }

    // Campur warna dengan putih (0 = warna asli, 1 = putih)
    private static function tint(string $hex, float $amount): string
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
        $mix = fn ($c) => (int) round($c + (255 - $c) * $amount);

        return sprintf('#%02x%02x%02x', $mix($r), $mix($g), $mix($b));
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
