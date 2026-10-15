<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroughtByTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
    }

    private function sell(string $customer, string $mode = 'lunas', array $extra = []): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), array_merge([
            'customer_name'  => $customer,
            'customer_phone' => '081234567890',
            'sale_date'      => now()->toDateString(),
            'payment_mode'   => $mode,
            'lines'          => [['egg_grade_id' => $this->grade->id, 'unit_type' => 'kg', 'quantity_unit' => 10, 'price_per_unit' => 26000]],
        ], $extra))->assertSessionHasNoErrors();
        $this->flushSession();
    }

    public function test_pemilik_mengisi_dibawa_oleh_dan_tidak_tampil_di_nota(): void
    {
        $this->sell('Pengepul Made');
        $customer = Customer::first();

        $this->actingAs($this->owner)->put(route('customers.update', $customer), [
            'name' => 'Pengepul Made', 'phone' => '081234567890', 'brought_by' => '  Ari  ',
        ])->assertRedirect(route('customers.show', $customer));

        $this->assertSame('Ari', $customer->fresh()->brought_by);
        $this->actingAs($this->owner)->get(route('customers.show', $customer))->assertSee('Dibawa oleh Ari');
        $this->actingAs($this->owner)->get(route('customers.edit', $customer))->assertSee('value="Ari"', false);

        $this->actingAs($this->owner)->put(route('customers.update', $customer), ['name' => 'Pengepul Made', 'brought_by' => ''])->assertSessionHasNoErrors();
        $this->assertNull($customer->fresh()->brought_by);
    }

    public function test_daftar_pelanggan_bisa_disaring_per_perantara(): void
    {
        $this->sell('Pengepul Made');
        $this->sell('Warung Lama');
        Customer::where('name', 'Pengepul Made')->update(['brought_by' => 'Ari']);

        $this->actingAs($this->owner)->get(route('customers.index', ['dibawa' => 'Ari']))
            ->assertOk()->assertSee('Pengepul Made')->assertDontSee('Warung Lama')->assertSee('Rekap Perantara');
    }

    public function test_rekap_perantara_menjumlah_penjualan_bulan_ini_saja(): void
    {
        $this->sell('Pengepul Made');
        $this->sell('Pengepul Made', 'tempo', ['due_date' => now()->addDays(7)->toDateString()]);
        $this->sell('Pengepul Made', 'lunas', ['sale_date' => now()->subMonthNoOverflow()->toDateString()]);
        $this->sell('Warung Lama');
        Customer::where('name', 'Pengepul Made')->update(['brought_by' => 'Ari']);

        $response = $this->actingAs($this->owner)->get(route('customers.brokers'))->assertOk();
        $response->assertSee('Dibawa oleh Ari')->assertDontSee('Warung Lama');
        $groups = $response->viewData('groups');
        $this->assertCount(1, $groups);
        $this->assertSame(2, $groups[0]['invoices']);
        $this->assertEquals(520000, $groups[0]['total']);
        $this->assertEquals(260000, $groups[0]['paid']);
        $this->assertEquals(260000, $groups[0]['debt']);

        $this->actingAs($this->owner)->get(route('customers.brokers', ['bulan' => '2026-13']))->assertOk();
    }

    public function test_rekap_perantara_tidak_memuat_data_peternakan_lain(): void
    {
        $this->sell('Pengepul Made');
        Customer::where('name', 'Pengepul Made')->update(['brought_by' => 'Ari']);

        $other = Farm::create(['name' => 'Peternakan Lain', 'status' => 'active']);
        $otherOwner = User::create(['farm_id' => $other->id, 'name' => 'Pemilik Lain', 'role' => 'owner', 'email' => 'lain@farm.test', 'password' => 'rahasia123']);

        $this->actingAs($otherOwner)->get(route('customers.brokers'))
            ->assertOk()->assertDontSee('Pengepul Made')->assertDontSee('Dibawa oleh Ari');
        $this->actingAs($otherOwner)->get(route('customers.index'))->assertDontSee('Dibawa Ari');
    }

    public function test_pekerja_tidak_bisa_membuka_rekap_perantara(): void
    {
        $this->useFarmDevice();
        $this->actingAs($this->worker)->get(route('customers.brokers'))->assertRedirect()->assertDontSee('Rekap Perantara');
    }
}
