<?php

namespace App\Services;

use App\Models\DailyLog;
use App\Models\EggSale;
use App\Models\ExpenseLedger;
use App\Models\MedicineMovement;
use App\Models\OtherIncome;
use App\Models\Setting;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelExport
{
    public const SHEETS = [
        'ringkasan'   => 'Ringkasan laba & kas',
        'panen'       => 'Panen harian',
        'penjualan'   => 'Penjualan telur',
        'piutang'     => 'Piutang belum lunas',
        'pengeluaran' => 'Pengeluaran (Buku Kas)',
        'pendapatan'  => 'Pendapatan lain',
        'obat'        => 'Obat & vitamin',
    ];

    private const RUPIAH = '"Rp"#,##0';
    private const NUMBER = '#,##0.##';
    private const DATE   = 'dd/mm/yyyy';

    private const WIDTHS = [
        self::RUPIAH => 16,
        self::NUMBER => 12,
        self::DATE   => 12,
        '#,##0'      => 12,
        '0.0%'       => 9,
        '0.00'       => 8,
        '@'          => 16,
    ];

    private Carbon $start;
    private Carbon $end;

    public function build(Carbon $start, Carbon $end, array $sheets): Spreadsheet
    {
        $this->start = $start->copy()->startOfDay();
        $this->end   = $end->copy()->startOfDay();

        $book = new Spreadsheet();
        $book->getProperties()->setCreator('HEFAM')->setTitle('Data ' . Setting::get('farm_name'));
        $book->removeSheetByIndex(0);

        foreach (array_keys(self::SHEETS) as $key) {
            if (in_array($key, $sheets, true)) {
                $this->{'sheet' . ucfirst($key)}($book->createSheet());
            }
        }

        $book->setActiveSheetIndex(0);

        return $book;
    }

    private function range(): array
    {
        return [$this->start->toDateString(), $this->end->toDateString()];
    }

    private function date($value): ?float
    {
        return $value ? ExcelDate::PHPToExcel(Carbon::parse($value)->startOfDay()) : null;
    }

    private function table(Worksheet $sheet, string $title, array $columns, iterable $rows): void
    {
        $sheet->setTitle(mb_substr($title, 0, 31));

        $headers = array_keys($columns);
        $this->writeRow($sheet, 1, $headers);

        $line = 2;
        foreach ($rows as $row) {
            $this->writeRow($sheet, $line, array_values($row));
            $line++;
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $lastRow    = max(2, $line - 1);

        $header = $sheet->getStyle('A1:' . $lastColumn . '1');
        $header->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF3F5A26');

        foreach (array_values($columns) as $i => $format) {
            $column = Coordinate::stringFromColumnIndex($i + 1);
            if ($format) {
                $sheet->getStyle($column . '2:' . $column . $lastRow)->getNumberFormat()->setFormatCode($format);
            }
            $sheet->getColumnDimension($column)->setWidth(max(mb_strlen($headers[$i]) + 4, self::WIDTHS[$format] ?? 22));
        }

        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:' . $lastColumn . $lastRow);
    }

    private function writeRow(Worksheet $sheet, int $row, array $values): void
    {
        foreach ($values as $i => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $cell = [$i + 1, $row];

            if (is_string($value)) {
                $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
            } else {
                $sheet->setCellValue($cell, $value);
            }
        }
    }

    private function sheetRingkasan(Worksheet $sheet): void
    {
        $s = FarmFinance::summary(...$this->range());

        $rows = [
            ['Peternakan', Setting::get('farm_name')],
            ['Periode', $this->start->translatedFormat('d F Y') . ' – ' . $this->end->translatedFormat('d F Y')],
            ['Dibuat', now()->translatedFormat('d F Y H:i')],
            ['', ''],
            ['UNTUNG & RUGI', ''],
            ['Hasil penjualan telur', $s['revenue']],
            ['Pendapatan lain', $s['other_income']],
            ['Pakan yang dimakan', -$s['feed_used']],
            ['Beli telur dari luar', -$s['egg_bought']],
            ['Biaya lain (Buku Kas, termasuk gaji)', -$s['expenses']],
            ['Vaksinasi', -$s['vaccines']],
            ['Obat & vitamin dipakai', -$s['medicine_used']],
            [$s['net_profit'] >= 0 ? 'UNTUNG BERSIH' : 'RUGI', $s['net_profit']],
            ['', ''],
            ['UANG MASUK & KELUAR', ''],
            ['Uang diterima dari pembeli', $s['cash_in']],
            ['Pendapatan lain', $s['other_income']],
            ['Beli pakan', -$s['feed_bought']],
            ['Beli telur', -$s['egg_bought']],
            ['Biaya lain + vaksin', -($s['expenses'] + $s['vaccines'])],
            ['Beli obat & vitamin', -$s['medicine_bought']],
            ['SISA UANG KAS', $s['net_cash']],
            ['Piutang baru (belum dibayar)', $s['new_debt']],
        ];

        $sheet->setTitle('Ringkasan');
        foreach ($rows as $i => $row) {
            $this->writeRow($sheet, $i + 1, $row);
        }
        $sheet->getStyle('B6:B23')->getNumberFormat()->setFormatCode(self::RUPIAH);
        foreach ([5, 13, 15, 22] as $bold) {
            $sheet->getStyle('A' . $bold . ':B' . $bold)->getFont()->setBold(true);
        }
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    private function sheetPanen(Worksheet $sheet): void
    {
        $rows = DailyLog::with(['coop:id,name', 'recorder:id,name'])
            ->whereBetween('log_date', $this->range())
            ->orderBy('log_date')->orderBy('coop_id')
            ->lazy(500)
            ->map(fn (DailyLog $l) => [
                $this->date($l->log_date), $l->coop->name ?? '-', (int) $l->eggs_total_count, round($l->eggs_total_count / 30, 1),
                (float) $l->eggs_total_kg, (float) $l->hdp_percentage / 100, (float) $l->feed_consumed_kg, (float) $l->feed_cost_total,
                $l->fcr !== null ? (float) $l->fcr : null, (int) $l->mortality, (int) $l->cull, $l->recorder->name ?? '-', $l->notes,
            ]);

        $this->table($sheet, 'Panen', [
            'Tanggal' => self::DATE, 'Kandang' => null, 'Telur (butir)' => '#,##0', 'Rak' => self::NUMBER, 'Berat (kg)' => self::NUMBER,
            'HDP' => '0.0%', 'Pakan (kg)' => self::NUMBER, 'Biaya pakan' => self::RUPIAH, 'FCR' => '0.00', 'Mati' => '#,##0',
            'Afkir' => '#,##0', 'Dicatat oleh' => null, 'Catatan' => null,
        ], $rows);
    }

    private function sheetPenjualan(Worksheet $sheet): void
    {
        $rows = EggSale::with(['invoice:id,number', 'customer:id,name', 'grade:id,name'])
            ->whereBetween('sale_date', $this->range())
            ->orderBy('sale_date')->orderBy('id')
            ->lazy(500)
            ->map(fn (EggSale $s) => [
                $this->date($s->sale_date), $s->invoice->number ?? '-', $s->customer->name ?? '-', $s->grade->name ?? '-',
                $s->unit_label, (float) $s->quantity_unit, (float) $s->price_per_unit, (float) $s->weight_kg,
                (float) $s->total_amount, (float) $s->paid_amount, (float) $s->debt_amount, $s->status_label, $this->date($s->due_date),
            ]);

        $this->table($sheet, 'Penjualan', [
            'Tanggal' => self::DATE, 'Nota' => null, 'Pelanggan' => null, 'Jenis telur' => null, 'Satuan' => null, 'Jumlah' => self::NUMBER,
            'Harga satuan' => self::RUPIAH, 'Berat (kg)' => self::NUMBER, 'Total' => self::RUPIAH, 'Dibayar' => self::RUPIAH,
            'Sisa' => self::RUPIAH, 'Status' => null, 'Jatuh tempo' => self::DATE,
        ], $rows);
    }

    private function sheetPiutang(Worksheet $sheet): void
    {
        $today = Carbon::today();

        $rows = EggSale::with(['invoice:id,number', 'customer:id,name,phone'])
            ->where('debt_amount', '>', 0)
            ->orderBy('due_date')->orderBy('sale_date')
            ->lazy(500)
            ->map(fn (EggSale $s) => [
                $s->customer->name ?? '-', $s->customer->phone ?? '', $s->invoice->number ?? '-', $this->date($s->sale_date),
                $this->date($s->due_date), (float) $s->total_amount, (float) $s->debt_amount,
                $s->due_date && $s->due_date->lt($today) ? (int) $s->due_date->diffInDays($today) : 0,
            ]);

        $this->table($sheet, 'Piutang', [
            'Pelanggan' => null, 'No. HP' => '@', 'Nota' => null, 'Tanggal jual' => self::DATE, 'Jatuh tempo' => self::DATE,
            'Total nota' => self::RUPIAH, 'Sisa utang' => self::RUPIAH, 'Telat (hari)' => '#,##0',
        ], $rows);
    }

    private function sheetPengeluaran(Worksheet $sheet): void
    {
        $rows = ExpenseLedger::whereBetween('transaction_date', $this->range())
            ->orderBy('transaction_date')->orderBy('id')
            ->lazy(500)
            ->map(fn (ExpenseLedger $e) => [
                $this->date($e->transaction_date), $e->expense_type, $e->category, $e->item_name, $e->supplier,
                (float) $e->quantity, $e->unit, (float) $e->unit_price, (float) $e->total_amount, $e->payment_method, $e->officer, $e->notes,
            ]);

        $this->table($sheet, 'Pengeluaran', [
            'Tanggal' => self::DATE, 'Jenis' => null, 'Kategori' => null, 'Uraian' => null, 'Toko/penerima' => null, 'Jumlah' => self::NUMBER,
            'Satuan' => null, 'Harga satuan' => self::RUPIAH, 'Total' => self::RUPIAH, 'Cara bayar' => null, 'Petugas' => null, 'Catatan' => null,
        ], $rows);
    }

    private function sheetPendapatan(Worksheet $sheet): void
    {
        $rows = OtherIncome::with('coop:id,name')
            ->whereBetween('income_date', $this->range())
            ->orderBy('income_date')->orderBy('id')
            ->lazy(500)
            ->map(fn (OtherIncome $i) => [
                $this->date($i->income_date), $i->category_label, $i->item_name, (float) $i->quantity, $i->unit,
                (float) $i->unit_price, (float) $i->total_amount, $i->buyer, $i->coop->name ?? '', $i->notes,
            ]);

        $this->table($sheet, 'Pendapatan lain', [
            'Tanggal' => self::DATE, 'Jenis' => null, 'Uraian' => null, 'Jumlah' => self::NUMBER, 'Satuan' => null,
            'Harga satuan' => self::RUPIAH, 'Total' => self::RUPIAH, 'Pembeli' => null, 'Kandang' => null, 'Catatan' => null,
        ], $rows);
    }

    private function sheetObat(Worksheet $sheet): void
    {
        $rows = MedicineMovement::with(['medicine:id,name,unit', 'coop:id,name'])
            ->whereBetween('movement_date', $this->range())
            ->orderBy('movement_date')->orderBy('id')
            ->lazy(500)
            ->map(fn (MedicineMovement $m) => [
                $this->date($m->movement_date), $m->medicine->name ?? '-', $m->direction_label, (float) $m->quantity,
                $m->medicine->unit ?? '', (float) $m->unit_cost, (float) $m->total_cost, $m->coop->name ?? '', $m->supplier, $m->notes,
            ]);

        $this->table($sheet, 'Obat', [
            'Tanggal' => self::DATE, 'Obat' => null, 'Kegiatan' => null, 'Jumlah' => self::NUMBER, 'Satuan' => null,
            'Harga satuan' => self::RUPIAH, 'Total' => self::RUPIAH, 'Kandang' => null, 'Beli dari' => null, 'Catatan' => null,
        ], $rows);
    }
}
