<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Coop;
use App\Models\DailyLog;
use App\Services\FarmProvisioner;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-20 09:00:00'));
        $this->setUpFarm();

        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->harvestPayload());
        $this->actingAs($this->owner)->post(route('sales.store'), [
            'customer_name' => '=HYPERLINK("http://jahat.test","klik")',
            'sale_date'     => '2026-10-20',
            'payment_mode'  => 'tempo',
            'lines'         => [['egg_grade_id' => $this->grade->id, 'unit_type' => 'kg', 'quantity_unit' => 10, 'price_per_unit' => 26000]],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function download(array $query = []): Spreadsheet
    {
        $response = $this->actingAs($this->owner)->get(route('exports.download', array_merge([
            'start' => '2026-10-01', 'end' => '2026-10-20',
            'sheets' => ['ringkasan', 'panen', 'penjualan', 'piutang', 'pengeluaran', 'pendapatan', 'obat'],
        ], $query)));

        $response->assertOk()->assertDownload();
        $path = tempnam(sys_get_temp_dir(), 'hefam') . '.xlsx';
        file_put_contents($path, $response->streamedContent());

        return IOFactory::load($path);
    }

    public function test_file_excel_berisi_semua_sheet_dengan_data_yang_benar(): void
    {
        $book = $this->download();

        $this->assertSame(['Ringkasan', 'Panen', 'Penjualan', 'Piutang', 'Pengeluaran', 'Pendapatan lain', 'Obat'], $book->getSheetNames());

        $panen = $book->getSheetByName('Panen');
        $this->assertSame('Tanggal', $panen->getCell('A1')->getValue());
        $this->assertSame('2026-10-20', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($panen->getCell('A2')->getValue())->format('Y-m-d'));
        $this->assertSame('Kandang A', $panen->getCell('B2')->getValue());
        $this->assertEquals(900, $panen->getCell('C2')->getValue());

        $piutang = $book->getSheetByName('Piutang');
        $this->assertEquals(260000, $piutang->getCell('G2')->getValue());

        $ringkasan = $book->getSheetByName('Ringkasan');
        $this->assertSame('Hasil penjualan telur', $ringkasan->getCell('A6')->getValue());
        $this->assertEquals(260000, $ringkasan->getCell('B6')->getValue());
    }

    public function test_teks_berawalan_sama_dengan_tidak_menjadi_rumus(): void
    {
        $cell = $this->download(['sheets' => ['penjualan']])->getSheetByName('Penjualan')->getCell('C2');

        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertStringStartsWith('=HYPERLINK', $cell->getValue());
    }

    public function test_data_peternakan_lain_tidak_ikut(): void
    {
        $other = app(FarmProvisioner::class)->create([
            'farm_name' => 'Telur Jaya', 'owner_name' => 'Ibu Putu', 'email' => 'jaya@farm.test', 'password' => 'rahasia123',
        ]);
        $this->actAsFarm($other->farm);
        $coop = Coop::create(['name' => 'Kandang Rahasia', 'capacity' => 100, 'initial_population' => 100, 'current_population' => 100,
            'chick_in_date' => '2026-01-01', 'initial_age_weeks' => 18, 'status' => 'active']);
        DailyLog::create(['coop_id' => $coop->id, 'log_date' => '2026-10-19', 'eggs_total_count' => 77]);

        $panen = $this->download(['sheets' => ['panen']])->getSheetByName('Panen');

        $this->assertSame(2, $panen->getHighestDataRow());
        $this->assertSame('Kandang A', $panen->getCell('B2')->getValue());
    }

    public function test_ekspor_tercatat_di_riwayat_dan_halaman_bisa_dibuka(): void
    {
        $this->actingAs($this->owner)->get(route('exports.index'))->assertOk()->assertSee('Unduh File Excel');
        $this->download(['sheets' => ['ringkasan']]);

        $this->actAsFarm($this->farm);
        $this->assertSame(1, ActivityLog::where('event', 'export')->count());
    }

    public function test_input_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->owner)->get(route('exports.download', ['start' => '2026-10-10', 'end' => '2026-10-01', 'sheets' => ['panen']]))->assertSessionHasErrors('end');
        $this->actingAs($this->owner)->get(route('exports.download', ['start' => '2026-10-01', 'end' => '2026-10-10', 'sheets' => ['rahasia']]))->assertSessionHasErrors('sheets.0');
        $this->actingAs($this->owner)->get(route('exports.download', ['start' => '2020-01-01', 'end' => '2026-10-10', 'sheets' => ['panen']]))->assertSessionHasErrors('start');
    }

    public function test_pekerja_tidak_bisa_mengekspor(): void
    {
        $this->actingAs($this->worker)->get(route('exports.download', ['start' => '2026-10-01', 'end' => '2026-10-20', 'sheets' => ['panen']]))
            ->assertRedirect(route('daily-logs.create'));
    }
}
