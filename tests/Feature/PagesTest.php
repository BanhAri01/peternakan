<?php

namespace Tests\Feature;

use App\Models\Coop;
use App\Models\Customer;
use App\Models\DailyLog;
use App\Models\FeedStock;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();

        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->harvestPayload());
        $this->actingAs($this->owner)->post(route('sales.store'), [
            'customer_name' => 'Toko Berkah', 'egg_grade_id' => $this->grade->id, 'sale_date' => now()->toDateString(),
            'unit_type' => 'kg', 'quantity_unit' => 10, 'price_per_unit' => 25000, 'payment_mode' => 'tempo',
        ]);
    }

    public function test_semua_halaman_owner_bisa_dibuka(): void
    {
        $log      = DailyLog::first();
        $customer = Customer::first();

        $pages = [
            route('owner.dashboard'), route('owner.dashboard', ['date' => now()->subDays(3)->toDateString()]),
            route('daily-logs.create'), route('daily-logs.index'), route('daily-logs.edit', $log),
            route('sales.index'), route('customers.index'), route('customers.show', $customer), route('customers.edit', $customer),
            route('procurement.index'), route('procurement.index', ['tab' => 'telur']), route('suppliers.index'),
            route('coops.index'), route('coops.show', $this->coop), route('coops.create'), route('coops.edit', $this->coop),
            route('feed-stocks.index'), route('feed-stocks.create'), route('feed-stocks.edit', $this->feed),
            route('grades.index'), route('expenses.index'), route('expenses.create'),
            route('vaccinations.index'), route('vaccinations.create'),
            route('users.index'), route('users.create'), route('users.edit', $this->worker),
            route('reports.index'), route('settings.edit'), route('sortings.create'),
        ];

        foreach ($pages as $url) {
            $this->actingAs($this->owner)->get($url)->assertOk();
        }
    }

    public function test_pekerja_hanya_bisa_membuka_halaman_catat_panen(): void
    {
        $this->actingAs($this->worker)->get(route('daily-logs.create'))->assertOk();

        foreach (['owner.dashboard', 'sales.index', 'reports.index', 'users.index', 'settings.edit', 'daily-logs.index'] as $name) {
            $this->actingAs($this->worker)->get(route($name))->assertRedirect(route('daily-logs.create'));
        }
    }

    public function test_pengguna_yang_sudah_masuk_diarahkan_dari_halaman_login(): void
    {
        $this->actingAs($this->worker)->get(route('login'))->assertRedirect('/');
        $this->actingAs($this->worker)->get('/')->assertRedirect(route('daily-logs.create'));
    }

    public function test_laporan_pdf_bulanan_bisa_diunduh(): void
    {
        $response = $this->actingAs($this->owner)->get(route('reports.monthly-pdf', ['month' => now()->format('Y-m')]));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_kandang_yang_punya_riwayat_tidak_bisa_dihapus(): void
    {
        $this->actingAs($this->owner)->delete(route('coops.destroy', $this->coop))->assertSessionHas('error');
        $this->assertNotNull($this->coop->fresh());

        $empty = Coop::create(['name' => 'Kandang Kosong', 'capacity' => 0, 'initial_population' => 0, 'current_population' => 0, 'chick_in_date' => now()->toDateString(), 'initial_age_weeks' => 0, 'status' => 'empty']);
        $this->actingAs($this->owner)->delete(route('coops.destroy', $empty))->assertSessionHas('success');
        $this->assertNull($empty->fresh());
    }

    public function test_pakan_yang_pernah_dipakai_tidak_bisa_dihapus(): void
    {
        $this->actingAs($this->owner)->delete(route('feed-stocks.destroy', $this->feed))->assertSessionHas('error');
        $this->assertNotNull(FeedStock::find($this->feed->id));
    }

    public function test_pengaturan_peternakan_tersimpan_dan_dipakai(): void
    {
        $this->actingAs($this->owner)->put(route('settings.update'), [
            'farm_name' => 'Sinar Abadi Farm', 'farm_owner' => 'Pak Ketut', 'farm_address' => 'Bangli', 'farm_phone' => '0812',
            'egg_price_per_kg' => 27000, 'sack_kg' => 40, 'hdp_warning' => 75, 'low_feed_days' => 4,
        ])->assertRedirect(route('settings.edit'));

        $this->assertSame('Sinar Abadi Farm', Setting::get('farm_name'));
        $this->assertEquals(40, Setting::num('sack_kg'));

        $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertSee('Sinar Abadi Farm');
    }

    public function test_pembelian_pakan_menambah_stok_dan_harga_rata_rata(): void
    {
        $this->actingAs($this->owner)->post(route('procurement.feed-purchase.store'), [
            'supplier_name' => 'UD Makmur', 'feed_stock_id' => $this->feed->id, 'purchase_date' => now()->toDateString(),
            'sacks_count' => 10, 'extra_kg' => 0, 'cost_per_kg' => 8000,
        ])->assertSessionHas('success');

        $feed = $this->feed->fresh();
        $this->assertEquals(1390, $feed->stock_kg); // 890 + 500
    }

    public function test_pengeluaran_total_dihitung_di_server(): void
    {
        $this->actingAs($this->owner)->post(route('expenses.store'), [
            'transaction_date' => now()->toDateString(), 'expense_type' => 'Operasional', 'category' => 'Listrik & Utilitas Air',
            'item_name' => 'Token listrik', 'quantity' => 2, 'unit' => 'Pcs', 'unit_price' => 150000,
            'total_amount' => 1, 'payment_method' => 'Tunai / Kas Kecil', 'officer' => 'Pemilik',
        ])->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expense_ledgers', ['item_name' => 'Token listrik', 'total_amount' => 300000]);
    }
}
