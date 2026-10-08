<?php

namespace Tests\Feature;

use App\Models\Medicine;
use App\Models\MedicineMovement;
use App\Models\Setting;
use App\Services\FarmFinance;
use App\Services\FarmProvisioner;
use App\Services\FarmReminders;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicineTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    private Medicine $medicine;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-20 08:00:00'));
        $this->setUpFarm();

        $this->actingAs($this->owner)->post(route('medicines.store'), [
            'name' => 'Vita Stress', 'type' => 'vitamin', 'unit' => 'sachet', 'min_stock' => 5,
        ])->assertSessionHasNoErrors();
        $this->actAsFarm($this->farm);
        $this->medicine = Medicine::firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function move(string $direction, float $qty, array $extra = [])
    {
        return $this->actingAs($this->owner)->post(route('medicines.movement.store', $this->medicine), array_merge([
            'direction' => $direction, 'movement_date' => '2026-10-20', 'quantity' => $qty,
        ], $extra));
    }

    public function test_stok_dan_biaya_dihitung_dari_masuk_dan_pakai(): void
    {
        $this->move('masuk', 10, ['unit_cost' => 5000, 'expiry_date' => '2027-03-01'])->assertRedirect(route('medicines.index'));
        $this->move('masuk', 10, ['unit_cost' => 7000]);
        $this->move('pakai', 4, ['coop_id' => $this->coop->id])->assertSessionHas('success');
        $this->actAsFarm($this->farm);

        $medicine = Medicine::withStock()->firstOrFail();
        $this->assertEquals(16, $medicine->stock);
        $this->assertEquals(6000, $medicine->average_cost);
        $this->assertSame('2027-03-01', $medicine->expiry_date->toDateString());

        $use = MedicineMovement::where('direction', 'pakai')->firstOrFail();
        $this->assertEquals(24000, $use->total_cost);
        $this->assertSame($this->coop->id, $use->coop_id);

        $summary = FarmFinance::summary('2026-10-01', '2026-10-31');
        $this->assertEquals(24000, $summary['medicine_used']);
        $this->assertEquals(120000, $summary['medicine_bought']);
        $this->assertEquals(-24000, $summary['net_profit']);
        $this->assertEquals(-120000, $summary['net_cash']);
    }

    public function test_tidak_bisa_memakai_melebihi_stok(): void
    {
        $this->move('masuk', 3, ['unit_cost' => 5000]);
        $this->move('pakai', 4)->assertSessionHasErrors('quantity');
        $this->actAsFarm($this->farm);

        $this->assertSame(1, MedicineMovement::count());
    }

    public function test_hapus_dan_pulihkan_catatan_menghitung_ulang_stok(): void
    {
        $this->move('masuk', 10, ['unit_cost' => 5000]);
        $this->move('pakai', 4);
        $this->actAsFarm($this->farm);
        $use = MedicineMovement::where('direction', 'pakai')->firstOrFail();

        $this->actingAs($this->owner)->delete(route('medicines.movement.destroy', $use));
        $this->actAsFarm($this->farm);
        $this->assertEquals(10, Medicine::firstOrFail()->stock);

        $this->actingAs($this->owner)->get(route('trash.index', ['jenis' => 'obat']))->assertOk()->assertSee('Vita Stress');
        $this->actingAs($this->owner)->post(route('trash.restore', ['obat', $use->id]));
        $this->actAsFarm($this->farm);
        $this->assertEquals(6, Medicine::firstOrFail()->stock);
    }

    public function test_peringatan_stok_menipis_dan_kedaluwarsa(): void
    {
        $this->move('masuk', 4, ['unit_cost' => 5000, 'expiry_date' => '2026-10-10']);

        $this->actingAs($this->owner)->get(route('medicines.index'))->assertOk()->assertSee('Kedaluwarsa');
        $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertOk()->assertSee('Vita Stress sudah kedaluwarsa');

        $this->actAsFarm($this->farm);
        Setting::put(['farm_name' => 'Sinar Uji']);
        $this->assertStringContainsString('Vita Stress sudah kedaluwarsa', app(FarmReminders::class)->build('pagi', Carbon::today()));
    }

    public function test_obat_dengan_riwayat_tidak_bisa_dihapus(): void
    {
        $this->move('masuk', 4, ['unit_cost' => 5000]);

        $this->actingAs($this->owner)->delete(route('medicines.destroy', $this->medicine))->assertSessionHas('error');
        $this->actAsFarm($this->farm);
        $this->assertSame(1, Medicine::count());
    }

    public function test_halaman_bisa_dibuka_dan_terpisah_antar_peternakan(): void
    {
        $this->actingAs($this->owner)->get(route('medicines.movement', [$this->medicine, 'arah' => 'pakai']))->assertOk();
        $this->actingAs($this->owner)->get(route('medicines.edit', $this->medicine))->assertOk();

        $other = app(FarmProvisioner::class)->create([
            'farm_name' => 'Telur Jaya', 'owner_name' => 'Ibu Putu', 'email' => 'jaya@farm.test', 'password' => 'rahasia123',
        ]);
        $this->actingAs($other)->get(route('medicines.index'))->assertOk()->assertDontSee('Vita Stress');
        $this->actingAs($other)->post(route('medicines.movement.store', $this->medicine), ['direction' => 'masuk', 'movement_date' => '2026-10-20', 'quantity' => 1, 'unit_cost' => 1])->assertNotFound();
    }
}
