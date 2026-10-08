<?php

namespace Tests\Feature;

use App\Models\DailyLog;
use App\Models\DailyLogGrade;
use App\Models\EggSale;
use App\Models\ExpenseLedger;
use App\Models\Invoice;
use App\Services\FarmProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrashTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
    }

    private function harvest(): DailyLog
    {
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->harvestPayload())->assertSessionHasNoErrors();
        $this->actAsFarm($this->farm);

        return DailyLog::firstOrFail();
    }

    private function sell(): Invoice
    {
        $this->actingAs($this->owner)->post(route('sales.store'), [
            'customer_name' => 'Bu Sari',
            'sale_date'     => now()->toDateString(),
            'payment_mode'  => 'tempo',
            'lines'         => [['egg_grade_id' => $this->grade->id, 'unit_type' => 'kg', 'quantity_unit' => 10, 'price_per_unit' => 26000]],
        ])->assertSessionHasNoErrors();
        $this->actAsFarm($this->farm);

        return Invoice::firstOrFail();
    }

    public function test_panen_yang_dihapus_masuk_sampah_dan_bisa_dipulihkan(): void
    {
        $log = $this->harvest();
        $this->assertSame(995, $this->coop->fresh()->current_population);

        $this->actingAs($this->owner)->delete(route('daily-logs.destroy', $log))->assertSessionHas('success');
        $this->actAsFarm($this->farm);

        $this->assertSoftDeleted($log);
        $this->assertSame(0, DailyLogGrade::count());
        $this->assertSame(1000, $this->coop->fresh()->current_population);
        $this->assertEquals(1000, $this->feed->fresh()->stock_kg);

        $this->actingAs($this->owner)->get(route('trash.index', ['jenis' => 'panen']))->assertOk()->assertSee('Kandang A')->assertSee('Pulihkan');

        $this->actingAs($this->owner)->post(route('trash.restore', ['panen', $log->id]))->assertSessionHas('success');
        $this->actAsFarm($this->farm);

        $this->assertNotSoftDeleted($log);
        $this->assertSame(1, DailyLogGrade::count());
        $this->assertSame(995, $this->coop->fresh()->current_population);
        $this->assertEquals(890, $this->feed->fresh()->stock_kg);
    }

    public function test_panen_tanggal_sama_bisa_dicatat_ulang_setelah_dihapus(): void
    {
        $log = $this->harvest();
        $this->actingAs($this->owner)->delete(route('daily-logs.destroy', $log));

        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->harvestPayload(['mortality' => 0, 'cull' => 0]))->assertSessionHasNoErrors();
        $this->actAsFarm($this->farm);

        $this->assertSame(1, DailyLog::count());
        $this->assertSame(0, DailyLog::onlyTrashed()->count());
    }

    public function test_panen_tidak_dipulihkan_jika_ayam_di_kandang_sudah_kurang(): void
    {
        $log = $this->harvest();
        $this->actingAs($this->owner)->delete(route('daily-logs.destroy', $log));
        $this->actAsFarm($this->farm);

        $this->coop->newQuery()->whereKey($this->coop->id)->update(['current_population' => 3]);

        $this->actingAs($this->owner)->post(route('trash.restore', ['panen', $log->id]))->assertSessionHasErrors('restore');
        $this->actAsFarm($this->farm);

        $this->assertSoftDeleted($log);
        $this->assertSame(3, $this->coop->fresh()->current_population);
        $this->assertEquals(1000, $this->feed->fresh()->stock_kg);
    }

    public function test_nota_dihapus_lalu_dipulihkan_beserta_piutangnya(): void
    {
        $invoice = $this->sell();

        $this->actingAs($this->owner)->delete(route('sales.destroy', $invoice))->assertSessionHas('success');
        $this->actAsFarm($this->farm);

        $this->assertSoftDeleted($invoice);
        $this->assertEquals(0, EggSale::sum('debt_amount'));

        $this->actingAs($this->owner)->get(route('trash.index', ['jenis' => 'nota']))->assertOk()->assertSee($invoice->number)->assertSee('260.000');

        $this->actingAs($this->owner)->post(route('trash.restore', ['nota', $invoice->id]))->assertSessionHas('success');
        $this->actAsFarm($this->farm);

        $this->assertNotSoftDeleted($invoice);
        $this->assertEquals(260000, EggSale::sum('debt_amount'));
    }

    public function test_nomor_nota_yang_dihapus_tidak_dipakai_ulang(): void
    {
        $first = $this->sell();
        $this->actingAs($this->owner)->delete(route('sales.destroy', $first));

        $this->actingAs($this->owner)->post(route('sales.store'), [
            'customer_name' => 'Pak Made',
            'sale_date'     => now()->toDateString(),
            'payment_mode'  => 'lunas',
            'lines'         => [['egg_grade_id' => $this->grade->id, 'unit_type' => 'kg', 'quantity_unit' => 5, 'price_per_unit' => 26000]],
        ])->assertSessionHasNoErrors();
        $this->actAsFarm($this->farm);

        $this->assertStringEndsWith('-0002', Invoice::firstOrFail()->number);
    }

    public function test_pengeluaran_dihapus_lalu_dipulihkan(): void
    {
        $expense = ExpenseLedger::create([
            'transaction_date' => now()->toDateString(), 'expense_type' => 'Operasional', 'category' => 'Listrik',
            'item_name' => 'Token listrik', 'quantity' => 1, 'unit' => 'kali', 'unit_price' => 200000, 'total_amount' => 200000,
            'payment_method' => 'Tunai', 'officer' => 'Pemilik',
        ]);

        $this->actingAs($this->owner)->delete(route('expenses.destroy', $expense));
        $this->actAsFarm($this->farm);
        $this->assertSoftDeleted($expense);

        $this->actingAs($this->owner)->post(route('trash.restore', ['pengeluaran', $expense->id]))->assertSessionHas('success');
        $this->actAsFarm($this->farm);
        $this->assertNotSoftDeleted($expense);
    }

    public function test_peternakan_lain_tidak_bisa_melihat_atau_memulihkan_sampah(): void
    {
        $log = $this->harvest();
        $this->actingAs($this->owner)->delete(route('daily-logs.destroy', $log));

        $other = app(FarmProvisioner::class)->create([
            'farm_name' => 'Telur Jaya', 'owner_name' => 'Ibu Putu', 'email' => 'jaya@farm.test', 'password' => 'rahasia123',
        ]);

        $this->actingAs($other)->get(route('trash.index', ['jenis' => 'panen']))->assertOk()->assertDontSee('Kandang A');
        $this->actingAs($other)->post(route('trash.restore', ['panen', $log->id]))->assertNotFound();
        $this->actAsFarm($this->farm);
        $this->assertSoftDeleted($log);
    }

    public function test_pekerja_tidak_bisa_membuka_sampah(): void
    {
        $this->actingAs($this->worker)->get(route('trash.index'))->assertRedirect(route('daily-logs.create'));
    }
}
