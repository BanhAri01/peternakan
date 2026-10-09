<?php

namespace Tests\Feature;

use App\Models\FeedStock;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedPurchaseFilterTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-20 09:00:00'));
        $this->setUpFarm();
        $this->grower = FeedStock::create(['feed_name' => 'Pakan Grower', 'stock_kg' => 0, 'cost_per_kg' => 7000]);

        foreach ([['2026-09-05', $this->feed->id, 10], ['2026-10-02', $this->feed->id, 4], ['2026-10-15', $this->grower->id, 2]] as [$date, $feed, $sacks]) {
            $this->actingAs($this->owner)->post(route('procurement.feed-purchase.store'), [
                'supplier_name' => 'UD Makmur', 'feed_stock_id' => $feed, 'purchase_date' => $date,
                'sacks_count' => $sacks, 'extra_kg' => 0, 'cost_per_kg' => 8000,
            ])->assertSessionHas('success');
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_tanpa_filter_semua_riwayat_tampil(): void
    {
        $this->actingAs($this->owner)->get(route('procurement.index'))
            ->assertOk()
            ->assertViewHas('feedPurchases', fn ($p) => $p->total() === 3)
            ->assertViewHas('feedSummary', null);
    }

    public function test_filter_tanggal_menampilkan_riwayat_dan_total_periode(): void
    {
        $this->actingAs($this->owner)->get(route('procurement.index', ['dari' => '2026-10-01', 'sampai' => '2026-10-31']))
            ->assertOk()
            ->assertViewHas('feedPurchases', fn ($p) => $p->total() === 2)
            ->assertViewHas('feedSummary', fn ($s) => (int) $s->times === 2 && (float) $s->kg === 300.0 && (float) $s->cost === 2400000.0)
            ->assertSee('2 kali datang')
            ->assertDontSee('05 Sep 2026');
    }

    public function test_filter_jenis_pakan_dan_satu_tanggal(): void
    {
        $this->actingAs($this->owner)->get(route('procurement.index', ['pakan' => $this->grower->id]))
            ->assertViewHas('feedPurchases', fn ($p) => $p->total() === 1);

        $this->actingAs($this->owner)->get(route('procurement.index', ['dari' => '2026-09-05', 'sampai' => '2026-09-05']))
            ->assertViewHas('feedPurchases', fn ($p) => $p->total() === 1 && $p->first()->quantity_kg == 500);
    }

    public function test_tanggal_terbalik_dan_pakan_peternakan_lain_ditolak(): void
    {
        $this->actingAs($this->owner)->from(route('procurement.index'))
            ->get(route('procurement.index', ['dari' => '2026-10-10', 'sampai' => '2026-10-01']))
            ->assertSessionHasErrors('sampai');

        $this->actingAs($this->owner)->from(route('procurement.index'))
            ->get(route('procurement.index', ['pakan' => 999999]))
            ->assertSessionHasErrors('pakan');
    }
}
