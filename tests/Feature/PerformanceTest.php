<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EggSale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
    }

    private function countQueries(callable $callback): int
    {
        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });
        $callback();

        return $count;
    }

    private function makeDebtors(int $total): void
    {
        foreach (range(1, $total) as $i) {
            $customer = Customer::create(['name' => 'Bakul ' . $i, 'phone' => '08123456' . str_pad($i, 4, '0', STR_PAD_LEFT)]);
            EggSale::create([
                'customer_id' => $customer->id, 'egg_grade_id' => $this->grade->id, 'sale_date' => now()->toDateString(),
                'unit_type' => 'kg', 'quantity_unit' => 10, 'price_per_unit' => 26000, 'paid_amount' => 0,
            ]);
        }
    }

    public function test_jumlah_query_halaman_pelanggan_tidak_bertambah_mengikuti_jumlah_pelanggan(): void
    {
        $this->makeDebtors(3);
        $this->actingAs($this->owner)->get(route('customers.index'));
        $few = $this->countQueries(fn () => $this->actingAs($this->owner)->get(route('customers.index'))->assertOk());

        $this->actAsFarm($this->farm);
        $this->makeDebtors(15);
        $many = $this->countQueries(fn () => $this->actingAs($this->owner)->get(route('customers.index'))->assertOk()->assertSee('Tagih'));

        $this->assertSame($few, $many);
    }

    public function test_total_utang_pelanggan_tetap_benar_tanpa_withsum(): void
    {
        $this->makeDebtors(1);
        $this->actAsFarm($this->farm);

        $customer = Customer::firstOrFail();
        $this->assertEquals(260000, $customer->total_debt);

        $customer->update(['phone' => '0899']);
        $this->assertSame('0899', $customer->fresh()->phone);
    }

    public function test_index_performa_terpasang(): void
    {
        $this->assertTrue(Schema::hasIndex('daily_logs', 'dl_farm_date'));
        $this->assertTrue(Schema::hasIndex('egg_sales', 'sale_farm_stock'));
        $this->assertTrue(Schema::hasIndex('daily_log_grades', 'dlg_farm_stock'));
    }
}
