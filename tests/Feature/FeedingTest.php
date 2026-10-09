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

    public function test_sesi_bawaan_pagi_sore_dan_sesi_aktif_mengikuti_jam(): void
    {
        $this->assertSame(['Pagi', 'Sore'], array_column(FeedingService::sessions(), 'name'));
        $this->assertSame(0, FeedingService::currentSession(Carbon::parse('2026-10-20 09:00')));
        $this->assertSame(1, FeedingService::currentSession(Carbon::parse('2026-10-20 16:00')));
    }

    public function test_pekerja_mencatat_pagi_dan_sore_stok_berkurang_dan_panen_menjumlahkan(): void
    {
        $this->useFarmDevice();
        $this->actingAs($this->worker)->get(route('feedings.create'))->assertOk()->assertSee('Sesi ke berapa?')->assertSee('Kandang A');

        $this->actingAs($this->worker)->feed(0, [$this->layer(1)])->assertSessionHas('success');
        $this->actingAs($this->worker)->feed(1, [$this->layer(1, 5), ['feed_stock_id' => $this->grower->id, 'sacks' => 0, 'extra_kg' => 10]])->assertSessionHas('success');

        $this->assertEquals(1000 - 105, $this->feed->fresh()->stock_kg);
        $this->assertEquals(490, $this->grower->fresh()->stock_kg);

        $payload = $this->harvestPayload();
        unset($payload['feed_stock_id'], $payload['feed_sacks'], $payload['extra_feed_kg']);
        $this->actingAs($this->worker)->post(route('daily-logs.store'), $payload)->assertSessionHas('success');

        $log = DailyLog::firstOrFail();
        $this->assertEquals(115, $log->feed_consumed_kg);
        $this->assertEquals(105 * 7000 + 10 * 6000, $log->feed_cost_total);
        $this->assertEquals(round(115 / 55, 2), $log->fcr);
        $this->assertSame($this->feed->id, $log->feed_stock_id);
        $this->assertEquals(1000 - 105, $this->feed->fresh()->stock_kg);
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

    public function test_pemilik_mengubah_dan_menghapus_lalu_memulihkan(): void
    {
        $this->actingAs($this->worker)->feed(0, [$this->layer(2)]);
        $feeding = Feeding::firstOrFail();

        $this->actingAs($this->worker)->get(route('feedings.edit', $feeding))->assertRedirect();

        $this->actingAs($this->owner)->get(route('feedings.edit', $feeding))->assertOk()->assertSee('Simpan Perubahan');
        $this->actingAs($this->owner)->put(route('feedings.update', $feeding), [
            'coop_id' => $this->coop->id, 'feed_date' => today()->toDateString(), 'session' => 0,
            'feeds' => [['feed_stock_id' => $this->grower->id, 'sacks' => 1, 'extra_kg' => 0]],
        ])->assertSessionHas('success');

        $this->assertEquals(1000, $this->feed->fresh()->stock_kg);
        $this->assertEquals(450, $this->grower->fresh()->stock_kg);

        $this->actingAs($this->owner)->delete(route('feedings.destroy', $feeding))->assertSessionHas('success');
        $this->actAsFarm($this->farm);
        $this->assertEquals(500, $this->grower->fresh()->stock_kg);

        $this->actingAs($this->owner)->post(route('trash.restore', ['beri-pakan', $feeding->id]))->assertSessionHas('success');
        $this->actAsFarm($this->farm);
        $this->assertEquals(450, $this->grower->fresh()->stock_kg);
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

    public function test_pengaturan_sesi_bisa_diubah_jadi_tiga_kali(): void
    {
        $this->actingAs($this->owner)->put(route('settings.update'), [
            'farm_name' => 'Uji', 'egg_price_per_kg' => 27000, 'sack_kg' => 50, 'hdp_warning' => 75, 'low_feed_days' => 4,
            'receipt_paper' => 'continuous', 'receipt_style' => 'ink', 'receipt_color' => '#3f5a26',
            'feed_sessions' => [['name' => 'Sore', 'time' => '15:00'], ['name' => 'Pagi', 'time' => '06:00'], ['name' => 'Siang', 'time' => '11:00']],
            'feed_count_tolerance_kg' => 5,
        ])->assertSessionHasNoErrors();

        $this->assertSame(['Pagi', 'Siang', 'Sore'], array_column(FeedingService::sessions(), 'name'));
        $this->assertEquals(5, Setting::num('feed_count_tolerance_kg'));
        $this->actingAs($this->owner)->get(route('feedings.create'))->assertSee('Siang');
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
        $this->actingAs($this->worker)->feed(0, [$this->layer(1)]);
        $feeding = Feeding::firstOrFail();

        $other = app(\App\Services\FarmProvisioner::class)->create([
            'farm_name' => 'Telur Jaya', 'owner_name' => 'Ibu Putu', 'email' => 'jaya@farm.test', 'password' => 'rahasia123',
        ]);

        $this->actingAs($other)->get(route('feedings.edit', $feeding))->assertNotFound();
        $this->actingAs($other)->delete(route('feedings.destroy', $feeding))->assertNotFound();
    }
}
