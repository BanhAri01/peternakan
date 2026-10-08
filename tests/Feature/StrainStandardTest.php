<?php

namespace Tests\Feature;

use App\Services\StrainStandard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrainStandardTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
    }

    public function test_nama_strain_dikenali_dari_teks_bebas(): void
    {
        $this->assertSame('isa_brown', StrainStandard::keyFor('Isa Brown'));
        $this->assertSame('lohmann_brown', StrainStandard::keyFor('LOHMAN brown'));
        $this->assertSame('hyline_brown', StrainStandard::keyFor('Hy-Line'));
        $this->assertSame('umum', StrainStandard::keyFor('Ayam kampung super'));
        $this->assertSame('umum', StrainStandard::keyFor(null));
    }

    public function test_standar_dihitung_menurut_umur_dengan_interpolasi(): void
    {
        $this->assertSame(0.0, StrainStandard::hdp('isa_brown', 16));
        $this->assertSame(90.0, StrainStandard::hdp('isa_brown', 22));
        $this->assertSame(91.5, StrainStandard::hdp('isa_brown', 22.5));
        $this->assertSame(77.0, StrainStandard::hdp('isa_brown', 100));
    }

    public function test_beranda_memberi_peringatan_jika_di_bawah_standar(): void
    {
        $this->assertSame(28, $this->coop->ageInWeeks());

        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->harvestPayload([
            'mortality' => 0, 'cull' => 0,
            'grades' => [['egg_grade_id' => $this->grade->id, 'trays_count' => 30, 'extra_eggs' => 0, 'weight_kg' => 55]],
        ]));

        $this->actingAs($this->owner)->get(route('owner.dashboard'))
            ->assertOk()
            ->assertSee('Di bawah standar')
            ->assertSee('Standar ISA Brown umur 28 minggu: 95,5%');
    }

    public function test_beranda_tidak_memberi_peringatan_jika_sesuai_standar(): void
    {
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->harvestPayload([
            'mortality' => 0, 'cull' => 0,
            'grades' => [['egg_grade_id' => $this->grade->id, 'trays_count' => 31, 'extra_eggs' => 20, 'weight_kg' => 58]],
        ]));

        $this->actingAs($this->owner)->get(route('owner.dashboard'))
            ->assertOk()
            ->assertDontSee('Di bawah standar')
            ->assertSee('Standar umur 28 minggu');
    }

    public function test_grafik_kandang_memuat_garis_standar(): void
    {
        $this->actingAs($this->owner)->get(route('coops.show', $this->coop))
            ->assertOk()
            ->assertSee('Standar strain')
            ->assertSee('standar ISA Brown sesuai umur ayam');
    }
}
