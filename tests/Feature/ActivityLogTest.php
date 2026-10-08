<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Coop;
use App\Models\Invoice;
use App\Services\FarmProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
        ActivityLog::query()->delete();
    }

    public function test_perubahan_data_tercatat_dengan_nilai_lama_dan_baru(): void
    {
        $this->actingAs($this->owner)->put(route('coops.update', $this->coop), [
            'name' => 'Kandang Utara', 'capacity' => 1200, 'initial_population' => 1000, 'current_population' => 1000,
            'strain' => 'Isa Brown', 'chick_in_date' => $this->coop->chick_in_date->toDateString(), 'initial_age_weeks' => 18, 'status' => 'active',
        ])->assertSessionHasNoErrors();
        $this->actAsFarm($this->farm);

        $log = ActivityLog::where('event', 'updated')->where('subject_type', 'Coop')->firstOrFail();
        $this->assertSame('Pemilik', $log->user_name);
        $this->assertSame(['Kandang A', 'Kandang Utara'], $log->changes['name']);
        $this->assertArrayNotHasKey('updated_at', $log->changes);
    }

    public function test_panen_pekerja_tercatat_atas_nama_pekerja(): void
    {
        $this->actingAs($this->worker)->post(route('daily-logs.store'), $this->harvestPayload())->assertSessionHasNoErrors();
        $this->actAsFarm($this->farm);

        $log = ActivityLog::where('event', 'created')->where('subject_type', 'DailyLog')->firstOrFail();
        $this->assertSame('Wayan', $log->user_name);
        $this->assertSame('Kandang A', $log->changes['coop_id']);
        $this->assertStringContainsString('Kandang A', $log->subject_label);
    }

    public function test_nota_dan_pembayaran_tercatat_dengan_jumlah_uang(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), [
            'customer_name' => 'Bu Sari',
            'sale_date'     => now()->toDateString(),
            'payment_mode'  => 'tempo',
            'lines'         => [['egg_grade_id' => $this->grade->id, 'unit_type' => 'kg', 'quantity_unit' => 10, 'price_per_unit' => 26000]],
        ]);
        $this->actAsFarm($this->farm);
        $invoice = Invoice::firstOrFail();

        $this->actingAs($this->owner)->post(route('sales.pay-debt', $invoice), ['payment_add' => 100000])->assertSessionHas('success');
        $this->actAsFarm($this->farm);

        $created = ActivityLog::where('event', 'created')->where('subject_type', 'Invoice')->firstOrFail();
        $this->assertStringContainsString('260.000', $created->description);

        $paid = ActivityLog::where('event', 'paid')->firstOrFail();
        $this->assertStringContainsString('100.000', $paid->description);
        $this->assertStringContainsString('160.000', $paid->changes['remaining'][1]);
    }

    public function test_kata_sandi_dan_pin_tidak_pernah_tersimpan_di_riwayat(): void
    {
        $this->actingAs($this->owner)->put(route('users.update', $this->worker), [
            'name' => 'Wayan', 'role' => 'worker', 'pin' => '9911', 'pin_confirmation' => '9911',
        ]);
        $this->actAsFarm($this->farm);

        $raw = ActivityLog::all()->pluck('changes')->toJson();
        $this->assertStringNotContainsString('9911', $raw);
        $this->assertStringNotContainsString('$2y$', $raw);
    }

    public function test_login_pemilik_tercatat(): void
    {
        $this->post(route('login.post'), ['email' => 'owner@farm.test', 'password' => 'rahasia123'])->assertRedirect();
        $this->actAsFarm($this->farm);

        $this->assertSame(1, ActivityLog::where('event', 'login')->where('user_name', 'Pemilik')->count());
    }

    public function test_halaman_riwayat_hanya_menampilkan_peternakan_sendiri(): void
    {
        Coop::create([
            'name' => 'Kandang Baru', 'capacity' => 100, 'initial_population' => 100, 'current_population' => 100,
            'chick_in_date' => now()->toDateString(), 'initial_age_weeks' => 18, 'status' => 'active',
        ]);

        $other = app(FarmProvisioner::class)->create([
            'farm_name' => 'Telur Jaya', 'owner_name' => 'Ibu Putu', 'email' => 'jaya@farm.test', 'password' => 'rahasia123',
        ]);

        $this->actingAs($this->owner)->get(route('activity.index'))->assertOk()->assertSee('Kandang Baru');
        $this->actingAs($other)->get(route('activity.index'))->assertOk()->assertDontSee('Kandang Baru');
        $this->actingAs($this->worker)->get(route('activity.index'))->assertRedirect(route('daily-logs.create'));
    }
}
