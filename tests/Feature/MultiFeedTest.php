<?php

namespace Tests\Feature;

use App\Models\DailyLog;
use App\Models\DailyLogFeed;
use App\Models\FeedStock;
use App\Services\FarmReminders;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MultiFeedTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected FeedStock $grower;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
        $this->grower = FeedStock::create(['feed_name' => 'Pakan Grower', 'stock_kg' => 500, 'cost_per_kg' => 6000]);
    }

    private function twoFeeds(array $overrides = []): array
    {
        $payload = $this->harvestPayload($overrides);
        unset($payload['feed_stock_id'], $payload['feed_sacks'], $payload['extra_feed_kg']);

        return $payload + ['feeds' => [
            ['feed_stock_id' => $this->feed->id, 'sacks' => 2, 'extra_kg' => 0],
            ['feed_stock_id' => $this->grower->id, 'sacks' => 0, 'extra_kg' => 25],
        ]];
    }

    public function test_dua_jenis_pakan_mengurangi_stok_masing_masing_dan_total_dijumlah(): void
    {
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->twoFeeds())->assertSessionHas('success');

        $log = DailyLog::firstOrFail();
        $this->assertEquals(125, $log->feed_consumed_kg);
        $this->assertEquals(100 * 7000 + 25 * 6000, $log->feed_cost_total);
        $this->assertSame($this->feed->id, $log->feed_stock_id);
        $this->assertEquals(round(125 / 55, 2), $log->fcr);
        $this->assertSame(2, $log->feeds()->count());

        $this->assertEquals(900, $this->feed->fresh()->stock_kg);
        $this->assertEquals(475, $this->grower->fresh()->stock_kg);
    }

    public function test_format_lama_satu_pakan_tetap_diterima(): void
    {
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->harvestPayload())->assertSessionHas('success');

        $this->assertEquals(890, $this->feed->fresh()->stock_kg);
        $this->assertSame(1, DailyLogFeed::count());
    }

    public function test_jenis_pakan_yang_sama_dua_kali_ditolak(): void
    {
        $payload = $this->twoFeeds();
        $payload['feeds'][1]['feed_stock_id'] = $this->feed->id;

        $this->actingAs($this->owner)->post(route('daily-logs.store'), $payload)->assertSessionHasErrors('feeds.1.feed_stock_id');
        $this->assertSame(0, DailyLog::count());
        $this->assertEquals(1000, $this->feed->fresh()->stock_kg);
    }

    public function test_ubah_catatan_mengembalikan_stok_lama_lalu_memotong_yang_baru(): void
    {
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->twoFeeds());
        $log = DailyLog::firstOrFail();

        $payload = $this->twoFeeds();
        $payload['feeds'] = [['feed_stock_id' => $this->grower->id, 'sacks' => 1, 'extra_kg' => 0]];
        $this->actingAs($this->owner)->put(route('daily-logs.update', $log), $payload)->assertSessionHasNoErrors();

        $this->assertEquals(1000, $this->feed->fresh()->stock_kg);
        $this->assertEquals(450, $this->grower->fresh()->stock_kg);
        $this->assertEquals(50, $log->fresh()->feed_consumed_kg);
        $this->assertSame($this->grower->id, $log->fresh()->feed_stock_id);
        $this->assertSame(1, $log->feeds()->count());

        $this->actingAs($this->owner)->get(route('daily-logs.edit', $log))->assertOk()->assertSee('Tambah jenis pakan lain');
    }

    public function test_hapus_dan_pulihkan_mengembalikan_kedua_stok(): void
    {
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->twoFeeds());
        $log = DailyLog::firstOrFail();

        $this->actingAs($this->owner)->delete(route('daily-logs.destroy', $log))->assertSessionHas('success');
        $this->actAsFarm($this->farm);
        $this->assertEquals(1000, $this->feed->fresh()->stock_kg);
        $this->assertEquals(500, $this->grower->fresh()->stock_kg);

        $this->actingAs($this->owner)->post(route('trash.restore', ['panen', $log->id]))->assertSessionHas('success');
        $this->actAsFarm($this->farm);
        $this->assertEquals(900, $this->feed->fresh()->stock_kg);
        $this->assertEquals(475, $this->grower->fresh()->stock_kg);
    }

    public function test_pemakaian_harian_per_jenis_pakan_ikut_menghitung_pakan_kedua(): void
    {
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->twoFeeds(['log_date' => now()->subDay()->toDateString()]));

        $usage = DailyLogFeed::dailyUsageByFeed(now()->subDays(7)->toDateString(), now()->toDateString());
        $this->assertEquals(round(25 / 7, 4), round((float) $usage[$this->grower->id], 4));

        $this->grower->update(['stock_kg' => 10]);
        $message = app(FarmReminders::class)->build('pagi', Carbon::today());
        $this->assertStringContainsString('Pakan Grower tinggal', $message);

        $this->actingAs($this->owner)->delete(route('feed-stocks.destroy', $this->grower))->assertSessionHas('error');
    }

    public function test_migrasi_memindahkan_catatan_lama(): void
    {
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->harvestPayload());
        DB::table('daily_log_feeds')->delete();

        $migration = require database_path('migrations/2026_10_21_000000_create_daily_log_feeds_table.php');
        \Illuminate\Support\Facades\Schema::drop('daily_log_feeds');
        $migration->up();

        $line = DailyLogFeed::firstOrFail();
        $this->assertSame($this->feed->id, $line->feed_stock_id);
        $this->assertEquals(110, $line->feed_kg);
    }
}
