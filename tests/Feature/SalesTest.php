<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EggSale;
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

    private function sale(array $overrides = []): array
    {
        return array_merge([
            'customer_name'  => 'Bakul Bu Sari',
            'egg_grade_id'   => $this->grade->id,
            'sale_date'      => now()->toDateString(),
            'unit_type'      => 'kg',
            'quantity_unit'  => 10,
            'price_per_unit' => 26000,
            'payment_mode'   => 'lunas',
        ], $overrides);
    }

    public function test_penjualan_lunas_tercatat_tanpa_utang(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale())->assertRedirect(route('sales.index'));

        $sale = EggSale::first();
        $this->assertEquals(260000, $sale->total_amount);
        $this->assertEquals(260000, $sale->paid_amount);
        $this->assertEquals(0, $sale->debt_amount);
        $this->assertSame('paid', $sale->payment_status);
        $this->assertNull($sale->due_date);
    }

    public function test_penjualan_tempo_menjadi_piutang_dengan_jatuh_tempo_otomatis(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['payment_mode' => 'tempo']));

        $sale = EggSale::first();
        $this->assertEquals(260000, $sale->debt_amount);
        $this->assertSame('unpaid', $sale->payment_status);
        $this->assertSame(now()->addDays(7)->toDateString(), $sale->due_date->toDateString());
    }

    public function test_bayar_sebagian_wajib_isi_nominal(): void
    {
        $this->actingAs($this->owner)
            ->post(route('sales.store'), $this->sale(['payment_mode' => 'sebagian']))
            ->assertSessionHasErrors('paid_amount');

        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['payment_mode' => 'sebagian', 'paid_amount' => 100000]));

        $sale = EggSale::first();
        $this->assertEquals(160000, $sale->debt_amount);
        $this->assertSame('partial', $sale->payment_status);
    }

    public function test_penjualan_per_rak_menghitung_total_dan_berat(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['unit_type' => 'krat', 'quantity_unit' => 4, 'price_per_unit' => 52000]));

        $sale = EggSale::first();
        $this->assertEquals(208000, $sale->total_amount);
        $this->assertEquals(7.6, $sale->weight_kg); // perkiraan 1,9 kg per rak
    }

    public function test_pelanggan_dengan_nama_sama_tidak_dobel(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['customer_name' => 'Bakul Bu Sari']));
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['customer_name' => '  bakul bu   sari ']));

        $this->assertSame(1, Customer::count());
        $this->assertSame(2, Customer::first()->sales()->count());
    }

    public function test_pelunasan_piutang_dan_tidak_bisa_bayar_lebih(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['payment_mode' => 'tempo']));
        $sale = EggSale::first();

        $this->actingAs($this->owner)
            ->post(route('sales.pay-debt', $sale), ['payment_add' => 500000])
            ->assertSessionHasErrors('payment_add');

        $this->actingAs($this->owner)->post(route('sales.pay-debt', $sale), ['payment_add' => 60000]);
        $this->assertEquals(200000, $sale->fresh()->debt_amount);

        $this->actingAs($this->owner)->post(route('sales.pay-debt', $sale), ['payment_add' => 200000]);
        $this->assertSame('paid', $sale->fresh()->payment_status);
    }

    public function test_hapus_penjualan(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale());

        $this->actingAs($this->owner)->delete(route('sales.destroy', EggSale::first()))->assertSessionHas('success');

        $this->assertSame(0, EggSale::count());
    }

    public function test_nota_pdf_bisa_dicetak(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale());

        $response = $this->actingAs($this->owner)->get(route('sales.print-receipt', EggSale::first()));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
