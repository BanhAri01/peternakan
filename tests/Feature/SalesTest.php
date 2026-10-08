<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EggGrade;
use App\Models\EggSale;
use App\Models\Invoice;
use App\Support\Format;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
    }

    private function sale(array $overrides = [], ?array $lines = null): array
    {
        return array_merge([
            'customer_name' => 'Bakul Bu Sari',
            'sale_date'     => now()->toDateString(),
            'payment_mode'  => 'lunas',
            'lines'         => $lines ?? [
                ['egg_grade_id' => $this->grade->id, 'unit_type' => 'kg', 'quantity_unit' => 10, 'price_per_unit' => 26000],
            ],
        ], $overrides);
    }

    public function test_penjualan_lunas_tercatat_tanpa_utang(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale())->assertRedirect(route('sales.index'));

        $invoice = Invoice::first();
        $this->assertMatchesRegularExpression('/^NT-\d{6}-0001$/', $invoice->number);
        $this->assertEquals(260000, $invoice->total);
        $this->assertEquals(0, $invoice->debt);
        $this->assertNull($invoice->due_date);
        $this->assertSame('paid', EggSale::first()->payment_status);
    }

    public function test_satu_nota_berisi_beberapa_jenis_telur(): void
    {
        $small = EggGrade::create(['name' => 'Telur Kecil', 'is_active' => true]);

        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['payment_mode' => 'sebagian', 'paid_amount' => 300000], [
            ['egg_grade_id' => $this->grade->id, 'unit_type' => 'kg', 'quantity_unit' => 10, 'price_per_unit' => 26000],   // 260.000
            ['egg_grade_id' => $small->id, 'unit_type' => 'krat', 'quantity_unit' => 2, 'price_per_unit' => 45000],         // 90.000
        ]));

        $invoice = Invoice::with('lines')->first();
        $this->assertCount(2, $invoice->lines);
        $this->assertEquals(350000, $invoice->total);
        $this->assertEquals(300000, $invoice->paid);
        $this->assertEquals(50000, $invoice->debt);
        // Uang dibagikan berurutan: baris pertama lunas, baris kedua sisa 50.000
        $this->assertSame('paid', $invoice->lines[0]->payment_status);
        $this->assertEquals(50000, $invoice->lines[1]->debt_amount);
        $this->assertEquals(3.8, $invoice->lines[1]->weight_kg);
    }

    public function test_penjualan_tempo_menjadi_piutang_dengan_jatuh_tempo_otomatis(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['payment_mode' => 'tempo']));

        $invoice = Invoice::first();
        $this->assertEquals(260000, $invoice->debt);
        $this->assertSame(now()->addDays(7)->toDateString(), $invoice->due_date->toDateString());
    }

    public function test_bayar_sebagian_wajib_isi_nominal(): void
    {
        $this->actingAs($this->owner)
            ->post(route('sales.store'), $this->sale(['payment_mode' => 'sebagian']))
            ->assertSessionHasErrors('paid_amount');

        $this->assertSame(0, Invoice::count());
    }

    public function test_nota_tanpa_baris_ditolak(): void
    {
        $this->actingAs($this->owner)
            ->post(route('sales.store'), $this->sale([], []))
            ->assertSessionHasErrors('lines');
    }

    public function test_nomor_nota_berurutan(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale());
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale());

        $this->assertStringEndsWith('-0002', Invoice::orderByDesc('id')->value('number'));
    }

    public function test_pelanggan_dengan_nama_sama_tidak_dobel(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['customer_name' => 'Bakul Bu Sari']));
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['customer_name' => '  bakul bu   sari ']));

        $this->assertSame(1, Customer::count());
        $this->assertSame(2, Customer::first()->invoices()->count());
    }

    public function test_pelunasan_nota_dan_tidak_bisa_bayar_lebih(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['payment_mode' => 'tempo']));
        $invoice = Invoice::first();

        $this->actingAs($this->owner)
            ->post(route('sales.pay-debt', $invoice), ['payment_add' => 500000])
            ->assertSessionHasErrors('payment_add');

        $this->actingAs($this->owner)->post(route('sales.pay-debt', $invoice), ['payment_add' => 60000]);
        $this->assertEquals(200000, $invoice->fresh()->debt);

        $this->actingAs($this->owner)->post(route('sales.pay-debt', $invoice), ['payment_add' => 200000]);
        $this->assertEquals(0, $invoice->fresh()->debt);
    }

    public function test_hapus_nota_menghapus_semua_barisnya(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale());

        $this->actingAs($this->owner)->delete(route('sales.destroy', Invoice::first()))->assertSessionHas('success');

        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, EggSale::count());
    }

    public function test_nota_pdf_bisa_dicetak_di_semua_ukuran_kertas(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale());
        $invoice = Invoice::first();

        foreach (['continuous', 'a4', 'a5'] as $paper) {
            $response = $this->actingAs($this->owner)->get(route('sales.print-receipt', [$invoice, 'kertas' => $paper]));
            $response->assertOk();
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }
    }

    public function test_terbilang(): void
    {
        $this->assertSame('Satu juta dua ratus lima puluh ribu rupiah', Format::terbilang(1250000));
        $this->assertSame('Sebelas ribu lima ratus rupiah', Format::terbilang(11500));
        $this->assertSame('Seratus sebelas rupiah', Format::terbilang(111));
    }
}
