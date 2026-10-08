<?php

namespace Tests\Feature;

use App\Models\EggGrade;
use App\Models\EggSorting;
use App\Services\EggStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SortingTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    private EggGrade $mixed;
    private EggGrade $small;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
        $this->mixed = EggGrade::mixed();
        $this->small = EggGrade::create(['name' => 'Telur Kecil', 'is_active' => true]);
    }

    private function harvestMixed(): void
    {
        // 30 rak telur campur, 55 kg
        $this->actingAs($this->worker)->post(route('daily-logs.store'), $this->harvestPayload([
            'grades' => [['egg_grade_id' => $this->mixed->id, 'trays_count' => 30, 'extra_eggs' => 0, 'weight_kg' => 55]],
        ]));
    }

    private function stock(EggGrade $g): float
    {
        EggStock::flush();

        return EggStock::kg()[$g->id] ?? 0;
    }

    public function test_form_panen_hanya_berisi_telur_campur(): void
    {
        $this->actingAs($this->worker)->get(route('daily-logs.create'))
            ->assertOk()
            ->assertSee('Telur Campur')
            ->assertDontSee('Telur Besar');
    }

    public function test_panen_masuk_ke_stok_telur_campur(): void
    {
        $this->harvestMixed();

        $this->assertEquals(55, $this->stock($this->mixed));
        $this->assertEquals(0, $this->stock($this->grade));
        $this->assertSame(900, EggStock::mixedEggsWaiting());
    }

    public function test_pekerja_menyortir_telur_campur_menjadi_per_jenis(): void
    {
        $this->harvestMixed();

        $this->actingAs($this->worker)->post(route('sortings.store'), [
            'sort_date' => now()->toDateString(),
            'items'     => [
                ['egg_grade_id' => $this->grade->id, 'trays_count' => 20, 'extra_eggs' => 0, 'weight_kg' => 39],
                ['egg_grade_id' => $this->small->id, 'trays_count' => 10, 'extra_eggs' => 0, 'weight_kg' => 16],
            ],
        ])->assertRedirect(route('sortings.create'))->assertSessionHas('success');

        $sorting = EggSorting::first();
        $this->assertSame(900, $sorting->input_count);
        $this->assertEquals(55, $sorting->input_kg);
        $this->assertSame($this->worker->id, $sorting->recorded_by);

        $this->assertEquals(0, $this->stock($this->mixed));
        $this->assertEquals(39, $this->stock($this->grade));
        $this->assertEquals(16, $this->stock($this->small));
        $this->assertSame(0, EggStock::mixedEggsWaiting());
    }

    public function test_sortir_kosong_ditolak(): void
    {
        $this->actingAs($this->worker)->post(route('sortings.store'), [
            'sort_date' => now()->toDateString(),
            'items'     => [['egg_grade_id' => $this->grade->id]],
        ])->assertSessionHas('error');

        $this->assertSame(0, EggSorting::count());
    }

    public function test_sortir_melebihi_stok_diberi_peringatan(): void
    {
        $this->actingAs($this->owner)->post(route('sortings.store'), [
            'sort_date' => now()->toDateString(),
            'items'     => [['egg_grade_id' => $this->grade->id, 'trays_count' => 5, 'weight_kg' => 10]],
        ])->assertSessionHas('warning');
    }

    public function test_owner_menghapus_sortir_dan_telur_kembali_ke_campur(): void
    {
        $this->harvestMixed();
        $this->actingAs($this->owner)->post(route('sortings.store'), [
            'sort_date' => now()->toDateString(),
            'items'     => [['egg_grade_id' => $this->grade->id, 'trays_count' => 30, 'weight_kg' => 55]],
        ]);

        $this->actingAs($this->worker)->delete(route('sortings.destroy', EggSorting::first()))->assertRedirect(route('daily-logs.create'));
        $this->actingAs($this->owner)->delete(route('sortings.destroy', EggSorting::first()))->assertSessionHas('success');

        $this->assertEquals(55, $this->stock($this->mixed));
        $this->assertEquals(0, $this->stock($this->grade));
    }

    public function test_telur_campur_tidak_bisa_disembunyikan(): void
    {
        $this->actingAs($this->owner)->post(route('grades.toggle', $this->mixed))->assertSessionHas('error');
        $this->assertTrue($this->mixed->fresh()->is_active);
    }
}
