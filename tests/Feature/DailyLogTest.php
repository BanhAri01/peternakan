<?php

namespace Tests\Feature;

use App\Models\DailyLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyLogTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
    }

    public function test_pekerja_mencatat_panen_dan_stok_ikut_berubah(): void
    {
        $this->actingAs($this->worker)
            ->post(route('daily-logs.store'), $this->harvestPayload())
            ->assertRedirect()
            ->assertSessionHas('success');

        $log = DailyLog::first();
        $this->assertSame(900, $log->eggs_total_count);       // 30 rak x 30
        $this->assertEquals(110, $log->feed_consumed_kg);      // 2 karung x 50 + 10
        $this->assertEquals(90, $log->hdp_percentage);         // 900 / 1000 ayam
        $this->assertEquals(2, $log->fcr);                     // 110 / 55
        $this->assertSame($this->worker->id, $log->recorded_by);

        $this->assertSame(995, $this->coop->fresh()->current_population);
        $this->assertEquals(890, $this->feed->fresh()->stock_kg);
    }

    public function test_kandang_yang_sama_tidak_bisa_dicatat_dua_kali_di_tanggal_sama(): void
    {
        $this->actingAs($this->worker)->post(route('daily-logs.store'), $this->harvestPayload());

        $this->actingAs($this->worker)
            ->post(route('daily-logs.store'), $this->harvestPayload())
            ->assertSessionHasErrors('log_date');

        $this->assertSame(1, DailyLog::count());
        $this->assertSame(995, $this->coop->fresh()->current_population);
    }

    public function test_pekerja_tidak_bisa_mencatat_tanggal_lama(): void
    {
        $this->actingAs($this->worker)
            ->post(route('daily-logs.store'), $this->harvestPayload(['log_date' => now()->subDays(3)->toDateString()]))
            ->assertSessionHasErrors('log_date');

        $this->assertSame(0, DailyLog::count());
    }

    public function test_tanggal_masa_depan_ditolak(): void
    {
        $this->actingAs($this->owner)
            ->post(route('daily-logs.store'), $this->harvestPayload(['log_date' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors('log_date');
    }

    public function test_ayam_mati_melebihi_populasi_ditolak(): void
    {
        $this->actingAs($this->owner)
            ->post(route('daily-logs.store'), $this->harvestPayload(['mortality' => 1500]))
            ->assertSessionHasErrors('mortality');

        $this->assertSame(1000, $this->coop->fresh()->current_population);
    }

    public function test_formulir_kosong_ditolak(): void
    {
        $payload = $this->harvestPayload(['feed_sacks' => 0, 'extra_feed_kg' => 0, 'mortality' => 0, 'cull' => 0]);
        $payload['grades'][0] = ['egg_grade_id' => $this->grade->id];

        $this->actingAs($this->worker)
            ->post(route('daily-logs.store'), $payload)
            ->assertSessionHas('error');

        $this->assertSame(0, DailyLog::count());
    }

    public function test_owner_mengubah_panen_dan_stok_dihitung_ulang(): void
    {
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->harvestPayload());
        $log = DailyLog::first();

        $this->actingAs($this->owner)
            ->put(route('daily-logs.update', $log), $this->harvestPayload(['feed_sacks' => 1, 'extra_feed_kg' => 0, 'mortality' => 1, 'cull' => 0]))
            ->assertRedirect(route('daily-logs.index'));

        $this->assertSame(999, $this->coop->fresh()->current_population);
        $this->assertEquals(890, $this->feed->fresh()->stock_kg);
        $this->assertEquals(110, $log->fresh()->feed_consumed_kg);
        $this->assertEquals(90, $log->fresh()->hdp_percentage);
    }

    public function test_owner_menghapus_panen_dan_stok_dikembalikan(): void
    {
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->harvestPayload());
        $log = DailyLog::first();

        $this->actingAs($this->owner)->delete(route('daily-logs.destroy', $log))->assertSessionHas('success');

        $this->assertSame(0, DailyLog::count());
        $this->assertSame(1000, $this->coop->fresh()->current_population);
        $this->assertEquals(890, $this->feed->fresh()->stock_kg);
        $this->assertSame(1, \App\Models\Feeding::count());
    }

    public function test_pekerja_tidak_bisa_mengubah_atau_menghapus_panen(): void
    {
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->harvestPayload());
        $log = DailyLog::first();

        $this->actingAs($this->worker)->get(route('daily-logs.edit', $log))->assertRedirect(route('daily-logs.create'));
        $this->actingAs($this->worker)->delete(route('daily-logs.destroy', $log))->assertRedirect(route('daily-logs.create'));

        $this->assertSame(1, DailyLog::count());
    }
}
