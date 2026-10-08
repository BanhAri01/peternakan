<?php

namespace App\Http\Controllers;

use App\Audit\ActivityRecorder;
use App\Models\Setting;
use App\Services\ExcelExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController extends Controller
{
    private const MAX_DAYS = 366;

    public function index()
    {
        return view('exports.index', [
            'sheets' => ExcelExport::SHEETS,
            'start'  => Carbon::today()->startOfMonth(),
            'end'    => Carbon::today(),
        ]);
    }

    public function download(Request $request, ExcelExport $export)
    {
        $data = $request->validate([
            'start'    => 'required|date',
            'end'      => 'required|date|after_or_equal:start|before_or_equal:today',
            'sheets'   => 'required|array|min:1',
            'sheets.*' => [Rule::in(array_keys(ExcelExport::SHEETS))],
        ], [
            'sheets.required' => 'Pilih minimal satu data yang ingin diekspor.',
            'end.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal awal.',
        ]);

        $start = Carbon::parse($data['start']);
        $end   = Carbon::parse($data['end']);

        if ($start->diffInDays($end) > self::MAX_DAYS) {
            return back()->withInput()->withErrors(['start' => 'Rentang maksimal 1 tahun per file. Untuk data lebih panjang, ekspor per tahun.']);
        }

        @set_time_limit(300);
        $book = $export->build($start, $end, $data['sheets']);

        $filename = 'HEFAM-' . Str::slug(Setting::get('farm_name')) . '-' . $start->format('Ymd') . '-' . $end->format('Ymd') . '.xlsx';

        ActivityRecorder::custom($request->user(), 'export', 'Mengunduh ekspor Excel ' . $start->format('d/m/Y') . '–' . $end->format('d/m/Y') . ' (' . implode(', ', $data['sheets']) . ')');

        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
            $book->disconnectWorksheets();
        }, $filename, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store',
        ]);
    }
}
