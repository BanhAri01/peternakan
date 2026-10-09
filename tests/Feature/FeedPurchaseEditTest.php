<?php

namespace Tests\Feature;

use App\Models\FeedPurchase;
use App\Models\FeedStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedPurchaseEditTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
    }

    private function buy(array $overrides = []): FeedPurchase
    {
        $this->actingAs($this->owner)->post(route('procurement.feed-purchase.store'), array_merge([
            'supplier_name' => 'UD Makmur', 'feed_stock_id' => $this->feed->id, 'purchase_date' => now()->toDateString(),
            'sacks_count' => 10, 'extra_kg' => 0, 'cost_per_kg' => 9000,
        ], $overrides))->assertSessionHas('success');

        return FeedPurchase::latest('id')->firstOrFail();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'supplier_name' => 'UD Makmur', 'feed_stock_id' => $this->feed->id, 'purchase_date' => now()->toDateString(),
            'sacks_count' => 1, 'extra_kg' => 0, 'cost_per_kg' => 9000,
        ], $overrides);
    }

    public function test_hapus_mengembalikan_stok_dan_harga_modal_lalu_bisa_dipulihkan(): void
    {
        $purchase = $this->buy();
        $this->assertEquals(1500, $this->feed->fresh()->stock_kg);
        $this->assertEqualsWithDelta(round((1000 * 7000 + 500 * 9000) / 1500, 2), (float) $this->feed->fresh()->cost_per_kg, 0.02);

        $this->actingAs($this->owner)->get(route('procurement.index'))->assertSee('Ubah')->assertSee(route('procurement.feed-purchase.destroy', $purchase));

        $this->actingAs($this->owner)->delete(route('procurement.feed-purchase.destroy', $purchase))->assertSessionHas('success');
        $this->actAsFarm($this->farm);

        $this->assertSoftDeleted($purchase);
        $this->assertEquals(1000, $this->feed->fresh()->stock_kg);
        $this->assertEqualsWithDelta(7000, (float) $this->feed->fresh()->cost_per_kg, 0.02);
        $this->actingAs($this->owner)->get(route('procurement.index'))->assertViewHas('monthFeed', 0.0);

        $this->actingAs($this->owner)->get(route('trash.index', ['jenis' => 'pakan-masuk']))->assertOk()->assertSee('Pakan Layer')->assertSee('Pulihkan');
        $this->actingAs($this->owner)->post(route('trash.restore', ['pakan-masuk', $purchase->id]))->assertSessionHas('success');
        $this->actAsFarm($this->farm);

        $this->assertNotSoftDeleted($purchase);
        $this->assertEquals(1500, $this->feed->fresh()->stock_kg);
        $this->assertEqualsWithDelta(round((1000 * 7000 + 500 * 9000) / 1500, 2), (float) $this->feed->fresh()->cost_per_kg, 0.02);
    }

    public function test_ubah_jumlah_dan_harga_menghitung_ulang_stok_dan_modal(): void
    {
        $purchase = $this->buy();

        $this->actingAs($this->owner)->get(route('procurement.feed-purchase.edit', $purchase))->assertOk()->assertSee('Simpan Perubahan');

        $this->actingAs($this->owner)->put(route('procurement.feed-purchase.update', $purchase), $this->payload(['sacks_count' => 2, 'cost_per_kg' => 8000]))
            ->assertRedirect(route('procurement.index'));

        $purchase->refresh();
        $this->assertEquals(100, $purchase->quantity_kg);
        $this->assertEquals(800000, $purchase->total_cost);
        $this->assertEquals(1100, $this->feed->fresh()->stock_kg);
        $this->assertEqualsWithDelta(round((1000 * 7000 + 100 * 8000) / 1100, 2), (float) $this->feed->fresh()->cost_per_kg, 0.02);
    }

    public function test_ubah_jenis_pakan_memindahkan_stok(): void
    {
        $grower   = FeedStock::create(['feed_name' => 'Pakan Grower', 'stock_kg' => 0, 'cost_per_kg' => 0]);
        $purchase = $this->buy();

        $this->actingAs($this->owner)->put(route('procurement.feed-purchase.update', $purchase), $this->payload(['feed_stock_id' => $grower->id, 'sacks_count' => 10]))
            ->assertSessionHasNoErrors();

        $this->assertEquals(1000, $this->feed->fresh()->stock_kg);
        $this->assertEqualsWithDelta(7000, (float) $this->feed->fresh()->cost_per_kg, 0.02);
        $this->assertEquals(500, $grower->fresh()->stock_kg);
        $this->assertEqualsWithDelta(9000, (float) $grower->fresh()->cost_per_kg, 0.02);
    }

    public function test_hanya_mengubah_tanggal_tidak_mengubah_stok(): void
    {
        $purchase = $this->buy();
        $before   = $this->feed->fresh()->only(['stock_kg', 'cost_per_kg']);

        $this->actingAs($this->owner)->put(route('procurement.feed-purchase.update', $purchase), $this->payload(['sacks_count' => 10, 'purchase_date' => now()->subDays(3)->toDateString(), 'supplier_name' => 'Toko Baru']))
            ->assertSessionHasNoErrors();

        $this->assertEquals($before, $this->feed->fresh()->only(['stock_kg', 'cost_per_kg']));
        $this->assertSame('Toko Baru', $purchase->fresh()->supplier->name);
    }

    public function test_pekerja_dan_peternakan_lain_tidak_bisa_mengubah_atau_menghapus(): void
    {
        $purchase = $this->buy();

        $this->actingAs($this->worker)->delete(route('procurement.feed-purchase.destroy', $purchase))->assertRedirect();

        $other = app(\App\Services\FarmProvisioner::class)->create([
            'farm_name' => 'Telur Jaya', 'owner_name' => 'Ibu Putu', 'email' => 'jaya@farm.test', 'password' => 'rahasia123',
        ]);
        $this->actingAs($other)->delete(route('procurement.feed-purchase.destroy', $purchase))->assertNotFound();
        $this->actingAs($other)->put(route('procurement.feed-purchase.update', $purchase), $this->payload())->assertNotFound();

        $this->actAsFarm($this->farm);
        $this->assertNotSoftDeleted($purchase);
        $this->assertEquals(1500, $this->feed->fresh()->stock_kg);
    }
}
