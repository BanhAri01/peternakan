<?php

namespace Tests\Feature;

use App\Models\DailyLog;
use App\Models\FeedCount;
use App\Models\Feeding;
use App\Models\FeedingItem;
use App\Models\FeedStock;
use App\Models\Setting;
use App\Services\FarmReminders;
use App\Services\FeedingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FeedingTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected FeedStock $grower;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-20 16:00:00'));
        $this->setUpFarm();
        $this->grower = FeedStock::create(['feed_name' => 'Pakan Grower', 'stock_kg' => 500, 'cost_per_kg' => 6000]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function feed(int $session, array $feeds, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('feedings.store'), array_merge([
            'coop_id' => $this->coop->id, 'feed_date' => today()->toDateString(), 'session' => $session, 'feeds' => $feeds,
        ], $overrides));
    }

    private function layer(int $sacks, float $kg = 0): array
    {
        return ['feed_stock_id' => $this->feed->id, 'sacks' => $sacks, 'extra_kg' => $kg];
    }

    public function test_menu_beri_pakan_dan_sesi_sudah_tidak_ada(): void
    {
        $this->useFarmDevice();
        $this->actingAs($this->worker)->get(route('daily-logs.create'))->assertOk()
            ->assertSee('Pakan yang diberikan')->assertDontSee('Beri Pakan');
        $this->actingAs($this->owner)->get('/pakan/beri')->assertStatus(405);
        $this->actingAs($this->owner)->get(route('settings.edit'))->assertOk()->assertDontSee('Nama sesi');
    }

    public function test_pekerja_mencatat_pakan_di_form_panen_dengan_beberapa_jenis(): void
    {
        $this->useFarmDevice();
        $payload = $this->harvestPayload(['feeds' => [$this->layer(2, 5), ['feed_stock_id' => $this->grower->id, 'sacks' => 0, 'extra_kg' => 10]]]);
        unset($payload['feed_stock_id'], $payload['feed_sacks'], $payload['extra_feed_kg']);

        $this->actingAs($this->worker)->post(route('daily-logs.store'), $payload)->assertSessionHas('success');

        $log = DailyLog::firstOrFail();
        $this->assertEquals(115, $log->feed_consumed_kg);
        $this->assertEquals(105 * 7000 + 10 * 6000, $log->feed_cost_total);
        $this->assertEquals(round(115 / 55, 2), $log->fcr);
        $this->assertSame($this->feed->id, $log->feed_stock_id);
        $this->assertEquals(1000 - 105, $this->feed->fresh()->stock_kg);
        $this->assertEquals(490, $this->grower->fresh()->stock_kg);
        $this->assertSame(1, Feeding::count());
        $this->assertNull(Feeding::first()->session);
    }

    public function test_sesi_yang_sama_tidak_bisa_dicatat_dua_kali(): void
    {
        $this->actingAs($this->worker)->feed(0, [$this->layer(1)]);
        $this->actingAs($this->worker)->feed(0, [$this->layer(2)])->assertSessionHasErrors('session');

        $this->assertSame(1, Feeding::count());
        $this->assertEquals(950, $this->feed->fresh()->stock_kg);
    }

    public function test_pakan_yang_dicatat_setelah_panen_ikut_masuk_ke_laporan_panen(): void
    {
        $payload = $this->harvestPayload();
        unset($payload['feed_stock_id'], $payload['feed_sacks'], $payload['extra_feed_kg']);
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $payload);
        $this->assertEquals(0, DailyLog::first()->feed_consumed_kg);

        $this->actingAs($this->owner)->feed(1, [$this->layer(2)]);
        $this->assertEquals(100, DailyLog::first()->feed_consumed_kg);
    }

    public function test_pekerja_hanya_hari_ini_atau_kemarin_dan_kiriman_offline_tidak_dobel(): void
    {
        $this->actingAs($this->worker)->feed(0, [$this->layer(1)], ['feed_date' => today()->subDays(3)->toDateString()])->assertSessionHasErrors('feed_date');

        $uuid = (string) Str::uuid();
        $this->actingAs($this->worker)->feed(0, [$this->layer(1)], ['client_uuid' => $uuid])->assertSessionHas('success');
        $this->actingAs($this->worker)->feed(0, [$this->layer(1)], ['client_uuid' => $uuid])->assertSessionHas('success');

        $this->assertSame(1, Feeding::count());
        $this->assertEquals(950, $this->feed->fresh()->stock_kg);
    }

    public function test_ubah_panen_menggabungkan_pakan_sesi_lama_lalu_hapus_dan_pulihkan(): void
    {
        $this->actingAs($this->worker)->feed(0, [$this->layer(1)]);
        $this->actingAs($this->worker)->feed(1, [$this->layer(1, 5)]);
        $payload = $this->harvestPayload();
        unset($payload['feed_stock_id'], $payload['feed_sacks'], $payload['extra_feed_kg']);
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $payload)->assertSessionHas('success');
        $log = DailyLog::firstOrFail();
        $this->assertEquals(105, $log->feed_consumed_kg);

        $this->actingAs($this->owner)->get(route('daily-logs.edit', $log))->assertOk()->assertSee('Pakan yang diberikan')
            ->assertViewHas('feedLines', [['id' => (string) $this->feed->id, 'sacks' => 2, 'extra' => 5]]);

        $this->feed->update(['cost_per_kg' => 9000]);
        $this->actingAs($this->owner)->put(route('daily-logs.update', $log), $payload + ['feeds' => [$this->layer(2, 5)]])->assertSessionHasNoErrors();
        $this->assertSame(2, Feeding::count());
        $this->assertEquals(105 * 7000, $log->fresh()->feed_cost_total);

        $this->actingAs($this->owner)->put(route('daily-logs.update', $log), $payload + ['feeds' => [['feed_stock_id' => $this->grower->id, 'sacks' => 1, 'extra_kg' => 0]]])->assertSessionHasNoErrors();
        $this->assertSame(1, Feeding::count());
        $this->assertEquals(1000, $this->feed->fresh()->stock_kg);
        $this->assertEquals(450, $this->grower->fresh()->stock_kg);
        $this->assertEquals(50, $log->fresh()->feed_consumed_kg);

        $this->actingAs($this->owner)->delete(route('daily-logs.destroy', $log))->assertSessionHas('success');
        $this->actAsFarm($this->farm);
        $this->assertEquals(500, $this->grower->fresh()->stock_kg);
        $this->assertSame(0, Feeding::count());

        $this->actingAs($this->owner)->post(route('trash.restore', ['panen', $log->id]))->assertSessionHas('success');
        $this->actAsFarm($this->farm);
        $this->assertEquals(450, $this->grower->fresh()->stock_kg);
        $this->assertSame(1, Feeding::count());
        $this->assertEquals(50, $log->fresh()->feed_consumed_kg);
    }

    public function test_ubah_panen_tanpa_pakan_mengembalikan_stok(): void
    {
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->harvestPayload())->assertSessionHas('success');
        $log = DailyLog::firstOrFail();

        $payload = $this->harvestPayload(['feeds' => [['feed_stock_id' => $this->feed->id, 'sacks' => '', 'extra_kg' => '']]]);
        unset($payload['feed_stock_id'], $payload['feed_sacks'], $payload['extra_feed_kg']);
        $this->actingAs($this->owner)->put(route('daily-logs.update', $log), $payload)->assertSessionHasNoErrors();

        $this->assertEquals(1000, $this->feed->fresh()->stock_kg);
        $this->assertEquals(0, $log->fresh()->feed_consumed_kg);
        $this->assertSame(0, Feeding::count());
    }

    public function test_format_panen_lama_dengan_pakan_tetap_diterima_sebagai_pemberian_pakan(): void
    {
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->harvestPayload())->assertSessionHas('success');

        $this->assertEquals(890, $this->feed->fresh()->stock_kg);
        $this->assertNull(Feeding::firstOrFail()->session);
        $this->assertEquals(110, DailyLog::firstOrFail()->feed_consumed_kg);
    }

    public function test_hitung_stok_gudang_cocok_dan_tidak_cocok_tampil_merah(): void
    {
        $this->actingAs($this->worker)->feed(0, [$this->layer(2)]);
        $this->assertEquals(900, $this->feed->fresh()->stock_kg);

        $this->actingAs($this->worker)->post(route('feed-counts.store'), ['counts' => [
            ['feed_stock_id' => $this->feed->id, 'sacks' => 18, 'extra_kg' => 0],
            ['feed_stock_id' => $this->grower->id, 'sacks' => null, 'extra_kg' => null],
        ]])->assertSessionHas('success');

        $count = FeedCount::firstOrFail();
        $this->assertEquals(900, $count->expected_kg);
        $this->assertTrue($count->isBalanced());
        $this->assertSame(1, FeedCount::count());

        Carbon::setTestNow(Carbon::parse('2026-10-21 16:00:00'));
        $this->actingAs($this->worker)->feed(0, [$this->layer(2)]);
        $this->actingAs($this->owner)->post(route('feed-counts.store'), ['counts' => [
            ['feed_stock_id' => $this->feed->id, 'sacks' => 15, 'extra_kg' => 0],
        ]])->assertSessionHas('warning');

        $off = FeedCount::where('count_date', '2026-10-21')->firstOrFail();
        $this->assertEquals(800, $off->expected_kg);
        $this->assertEquals(-50, $off->difference_kg);
        $this->assertFalse($off->isBalanced());
        $this->assertEquals(750, $this->feed->fresh()->stock_kg);

        $this->actingAs($this->owner)->get(route('feed-counts.index'))
            ->assertOk()
            ->assertSee('1 tanggal tidak cocok')
            ->assertSee('row-danger', false)
            ->assertSee('kurang 50 kg');

        $this->actingAs($this->worker)->get(route('feed-counts.index'))->assertRedirect();
        $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertSee('Stok pakan gudang tidak cocok di 1 tanggal');
    }

    public function test_hitung_ulang_di_hari_yang_sama_tidak_menggandakan_selisih(): void
    {
        $this->actingAs($this->owner)->post(route('feed-counts.store'), ['counts' => [['feed_stock_id' => $this->feed->id, 'sacks' => 19, 'extra_kg' => 0]]]);
        $this->actingAs($this->owner)->post(route('feed-counts.store'), ['counts' => [['feed_stock_id' => $this->feed->id, 'sacks' => 20, 'extra_kg' => 0]]]);

        $count = FeedCount::firstOrFail();
        $this->assertEquals(1000, $count->expected_kg);
        $this->assertEquals(0, $count->difference_kg);
        $this->assertEquals(1000, $this->feed->fresh()->stock_kg);
    }

    public function test_perkiraan_stok_di_tanggal_lampau(): void
    {
        $this->actingAs($this->owner)->feed(0, [$this->layer(2)], ['feed_date' => '2026-10-18']);
        $this->actingAs($this->owner)->feed(0, [$this->layer(1)], ['feed_date' => '2026-10-20']);

        $this->assertEquals(850, $this->feed->fresh()->stock_kg);
        $this->assertEquals(900, FeedingService::expectedStock($this->feed->id, '2026-10-19'));
        $this->assertEquals(1000, FeedingService::expectedStock($this->feed->id, '2026-10-17'));
    }

    public function test_pengaturan_toleransi_hitung_stok_tetap_bisa_diubah(): void
    {
        $this->actingAs($this->owner)->put(route('settings.update'), [
            'farm_name' => 'Uji', 'egg_price_per_kg' => 27000, 'sack_kg' => 50, 'hdp_warning' => 75, 'low_feed_days' => 4,
            'receipt_paper' => 'continuous', 'receipt_style' => 'ink', 'receipt_color' => '#3f5a26',
            'feed_count_tolerance_kg' => 5,
        ])->assertSessionHasNoErrors();

        $this->assertEquals(5, Setting::num('feed_count_tolerance_kg'));
    }

    public function test_pemakaian_harian_dan_pengingat_memakai_pemberian_pakan(): void
    {
        $this->actingAs($this->owner)->feed(0, [['feed_stock_id' => $this->grower->id, 'sacks' => 0, 'extra_kg' => 70]], ['feed_date' => '2026-10-19']);

        $usage = FeedingItem::dailyUsageByFeed('2026-10-13', '2026-10-19');
        $this->assertEquals(10, round((float) $usage[$this->grower->id], 2));

        $this->grower->update(['stock_kg' => 20]);
        $this->assertStringContainsString('Pakan Grower tinggal', app(FarmReminders::class)->build('pagi', Carbon::today()));

        $this->actingAs($this->owner)->delete(route('feed-stocks.destroy', $this->grower))->assertSessionHas('error');
    }

    public function test_peternakan_lain_tidak_bisa_melihat_atau_mengubah(): void
    {
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->harvestPayload())->assertSessionHas('success');
        $log = DailyLog::firstOrFail();

        $other = app(\App\Services\FarmProvisioner::class)->create([
            'farm_name' => 'Telur Jaya', 'owner_name' => 'Ibu Putu', 'email' => 'jaya@farm.test', 'password' => 'rahasia123',
        ]);
        $payload = $this->harvestPayload(['feeds' => [$this->layer(5)]]);
        unset($payload['feed_stock_id'], $payload['feed_sacks'], $payload['extra_feed_kg']);

        $this->actingAs($other)->get(route('daily-logs.edit', $log))->assertNotFound();
        $this->actingAs($other)->put(route('daily-logs.update', $log), $payload)->assertNotFound();
        $this->actingAs($other)->delete(route('daily-logs.destroy', $log))->assertNotFound();

        $this->actAsFarm($this->farm);
        $this->assertEquals(890, $this->feed->fresh()->stock_kg);
    }
}
