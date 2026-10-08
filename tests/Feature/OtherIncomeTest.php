<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\OtherIncome;
use App\Services\FarmFinance;
use App\Services\FarmProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtherIncomeTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'income_date' => now()->toDateString(),
            'category'    => 'ayam_afkir',
            'item_name'   => 'Ayam afkir kandang A',
            'quantity'    => 50,
            'unit'        => 'ekor',
            'unit_price'  => 35000,
            'buyer'       => 'Pak Nyoman',
            'coop_id'     => $this->coop->id,
        ], $overrides);
    }

    public function test_pendapatan_lain_tersimpan_dengan_total_dihitung_server(): void
    {
        $this->actingAs($this->owner)->post(route('other-incomes.store'), $this->payload(['total_amount' => 1]))
            ->assertRedirect(route('other-incomes.index'))->assertSessionHas('success');
        $this->actAsFarm($this->farm);

        $income = OtherIncome::firstOrFail();
        $this->assertEquals(1750000, $income->total_amount);
        $this->assertSame($this->owner->id, $income->recorded_by);
        $this->assertSame(1000, $this->coop->fresh()->current_population);
    }

    public function test_pendapatan_lain_ikut_dihitung_di_laba_dan_kas(): void
    {
        $this->actingAs($this->owner)->post(route('other-incomes.store'), $this->payload());
        $this->actAsFarm($this->farm);

        $summary = FarmFinance::summary(now()->startOfMonth()->toDateString(), now()->toDateString());
        $this->assertEquals(1750000, $summary['other_income']);
        $this->assertEquals(1750000, $summary['net_profit']);
        $this->assertEquals(1750000, $summary['net_cash']);

        $this->actingAs($this->owner)->get(route('reports.index'))->assertOk()->assertSee('Pendapatan lain')->assertSee('1.750.000');
        $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertOk()->assertSee('Pendapatan lain');
        $this->actingAs($this->owner)->get(route('reports.monthly-pdf'))->assertOk();
    }

    public function test_daftar_ubah_hapus_dan_pulihkan(): void
    {
        $this->actingAs($this->owner)->post(route('other-incomes.store'), $this->payload());
        $this->actAsFarm($this->farm);
        $income = OtherIncome::firstOrFail();

        $this->actingAs($this->owner)->get(route('other-incomes.index'))->assertOk()->assertSee('Ayam afkir kandang A')->assertSee('1.750.000');
        $this->actingAs($this->owner)->get(route('other-incomes.edit', $income))->assertOk();

        $this->actingAs($this->owner)->put(route('other-incomes.update', $income), $this->payload(['category' => 'kotoran', 'item_name' => 'Kotoran ayam', 'quantity' => 20, 'unit' => 'karung', 'unit_price' => 15000]))
            ->assertRedirect(route('other-incomes.index'));
        $this->actAsFarm($this->farm);
        $this->assertEquals(300000, $income->fresh()->total_amount);

        $this->actingAs($this->owner)->delete(route('other-incomes.destroy', $income));
        $this->actAsFarm($this->farm);
        $this->assertSoftDeleted($income);

        $this->actingAs($this->owner)->get(route('trash.index', ['jenis' => 'pendapatan']))->assertOk()->assertSee('Kotoran ayam');
        $this->actingAs($this->owner)->post(route('trash.restore', ['pendapatan', $income->id]))->assertSessionHas('success');
        $this->actAsFarm($this->farm);
        $this->assertNotSoftDeleted($income);

        $this->assertSame(1, ActivityLog::where('subject_type', 'OtherIncome')->where('event', 'updated')->count());
    }

    public function test_data_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->owner)->post(route('other-incomes.store'), $this->payload(['category' => 'judi', 'quantity' => 0, 'income_date' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors(['category', 'quantity', 'income_date']);
    }

    public function test_pekerja_dan_peternakan_lain_tidak_bisa_mengakses(): void
    {
        $this->actingAs($this->owner)->post(route('other-incomes.store'), $this->payload());
        $this->actAsFarm($this->farm);
        $income = OtherIncome::firstOrFail();

        $other = app(FarmProvisioner::class)->create([
            'farm_name' => 'Telur Jaya', 'owner_name' => 'Ibu Putu', 'email' => 'jaya@farm.test', 'password' => 'rahasia123',
        ]);

        $this->actingAs($other)->get(route('other-incomes.index'))->assertOk()->assertDontSee('Ayam afkir kandang A');
        $this->actingAs($other)->get(route('other-incomes.edit', $income))->assertNotFound();
        $this->actingAs($this->worker)->get(route('other-incomes.index'))->assertRedirect(route('daily-logs.create'));
    }
}
