<?php

namespace Tests\Feature;

use App\Models\Coop;
use App\Models\DailyLog;
use App\Models\Device;
use App\Models\EggGrade;
use App\Models\Farm;
use App\Models\FeedStock;
use App\Models\Setting;
use App\Models\User;
use App\Services\DeviceService;
use App\Services\FarmProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    private Farm $otherFarm;
    private User $otherOwner;
    private Coop $otherCoop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();

        // Peternakan kedua dengan datanya sendiri
        $this->otherOwner = app(FarmProvisioner::class)->create([
            'farm_name' => 'Telur Jaya', 'owner_name' => 'Ibu Putu', 'email' => 'jaya@farm.test', 'password' => 'rahasia123',
        ]);
        $this->otherFarm = $this->otherOwner->farm;
        $this->actAsFarm($this->otherFarm);
        $this->otherCoop = Coop::create([
            'name' => 'Kandang Rahasia Jaya', 'capacity' => 500, 'initial_population' => 500, 'current_population' => 500,
            'chick_in_date' => now()->subWeeks(5)->toDateString(), 'initial_age_weeks' => 18, 'status' => 'active',
        ]);
        $this->actAsFarm($this->farm);
    }

    // ---------------- Pemisahan data ----------------

    public function test_pemilik_tidak_bisa_melihat_data_peternakan_lain(): void
    {
        $this->actingAs($this->owner)->get(route('coops.index'))->assertOk()->assertSee('Kandang A')->assertDontSee('Kandang Rahasia Jaya');
        $this->actingAs($this->owner)->get(route('coops.show', $this->otherCoop))->assertNotFound();
        $this->actingAs($this->owner)->get(route('users.edit', $this->otherOwner))->assertNotFound();
    }

    public function test_tidak_bisa_mencatat_panen_ke_kandang_peternakan_lain(): void
    {
        $this->actingAs($this->owner)
            ->post(route('daily-logs.store'), $this->harvestPayload(['coop_id' => $this->otherCoop->id]))
            ->assertSessionHasErrors('coop_id');

        $this->assertSame(0, DailyLog::allFarms()->count());
    }

    public function test_jenis_telur_dan_pengaturan_terpisah_per_peternakan(): void
    {
        $this->actingAs($this->owner)->get(route('grades.index'))->assertOk();

        $this->actAsFarm($this->otherFarm);
        $this->assertSame('Telur Jaya', Setting::get('farm_name'));
        $this->assertSame(5, EggGrade::count()); // campur + 4 jenis bawaan

        $this->actAsFarm($this->farm);
        $this->assertNotSame('Telur Jaya', Setting::get('farm_name'));
    }

    public function test_data_baru_otomatis_masuk_ke_peternakan_pengguna(): void
    {
        $this->actingAs($this->otherOwner)->post(route('feed-stocks.store'), ['feed_name' => 'Pakan Jaya', 'stock_kg' => 100, 'cost_per_kg' => 7000]);

        $feed = FeedStock::allFarms()->where('feed_name', 'Pakan Jaya')->first();
        $this->assertSame($this->otherFarm->id, $feed->farm_id);
    }

    // ---------------- Pendaftaran ----------------

    public function test_peternak_baru_bisa_mendaftar_dan_langsung_masa_coba(): void
    {
        $this->post(route('register.store'), [
            'farm_name' => 'Maju Makmur Farm', 'owner_name' => 'Pak Wayan', 'phone' => '08123', 'city' => 'Gianyar',
            'email' => 'maju@farm.test', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'agree' => '1',
        ])->assertRedirect(route('owner.dashboard'));

        $owner = User::where('email', 'maju@farm.test')->first();
        $this->assertAuthenticatedAs($owner);
        $this->assertSame('trial', $owner->farm->status);
        $this->assertSame(now()->addDays(Farm::TRIAL_DAYS)->toDateString(), $owner->farm->trial_ends_at->toDateString());

        $this->get(route('owner.dashboard'))->assertOk()->assertSee('Langkah awal');
    }

    public function test_email_yang_sudah_terdaftar_ditolak(): void
    {
        $this->post(route('register.store'), [
            'farm_name' => 'X', 'owner_name' => 'Y', 'phone' => '0812', 'email' => 'owner@farm.test',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'agree' => '1',
        ])->assertSessionHasErrors('email');
    }

    // ---------------- Langganan ----------------

    public function test_masa_coba_habis_pemilik_diarahkan_ke_halaman_langganan(): void
    {
        $this->farm->update(['status' => 'trial', 'trial_ends_at' => now()->subDay()->toDateString()]);

        $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertRedirect(route('subscription.show'));
        $this->actingAs($this->owner)->get(route('subscription.show'))->assertOk()->assertSee('sudah habis');
    }

    public function test_peternakan_dibekukan_pekerja_tidak_bisa_masuk(): void
    {
        $this->farm->update(['status' => 'suspended']);
        $this->useFarmDevice();

        $this->post(route('login.worker'), ['worker_id' => $this->worker->id, 'pin' => '2580'])->assertRedirect(route('login'));
        $this->assertGuest();

        // Pekerja yang sedang login ikut dikeluarkan
        $this->actingAs($this->worker)->get(route('daily-logs.create'))->assertRedirect(route('login'));
    }

    // ---------------- HP kandang ----------------

    public function test_pemilik_mendaftarkan_hp_kandang_lalu_menyerahkan_ke_pekerja(): void
    {
        $response = $this->actingAs($this->owner)->post(route('devices.store'), ['name' => 'HP Kandang A']);
        $response->assertRedirect(route('devices.index'))->assertCookie(DeviceService::COOKIE);

        $device = Device::first();
        $this->assertSame('HP Kandang A', $device->name);
        $this->assertSame($this->farm->id, $device->farm_id);

        $this->withCookie(DeviceService::COOKIE, $device->uuid)
            ->actingAs($this->owner)
            ->post(route('devices.hand-over'))
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->withCookie(DeviceService::COOKIE, $device->uuid)->get(route('login'))->assertSee('Wayan');
    }

    public function test_hp_kandang_yang_dicabut_tidak_bisa_dipakai(): void
    {
        $device = $this->useFarmDevice();
        $this->actingAs($this->owner)->delete(route('devices.destroy', $device));
        $this->assertFalse($device->fresh()->is_active);

        auth()->logout();
        $this->post(route('login.worker'), ['worker_id' => $this->worker->id, 'pin' => '2580'])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // ---------------- Admin HEFAM ----------------

    public function test_admin_hefam_melihat_semua_peternakan_dan_memperpanjang_langganan(): void
    {
        $admin = User::create(['name' => 'Admin', 'role' => 'superadmin', 'email' => 'admin@hefam.test', 'password' => 'rahasia123']);

        $this->actingAs($admin)->get('/')->assertRedirect(route('admin.farms.index'));
        $this->actingAs($admin)->get(route('admin.farms.index'))->assertOk()->assertSee('Peternakan Uji')->assertSee('Telur Jaya');
        $this->actingAs($admin)->get(route('admin.farms.edit', $this->otherFarm))->assertOk();

        $trialEnds = $this->otherFarm->fresh()->trial_ends_at;
        $this->actingAs($admin)->post(route('admin.farms.extend', $this->otherFarm), ['months' => 3])->assertSessionHas('success');
        $farm = $this->otherFarm->fresh();
        $this->assertSame('active', $farm->status);
        $this->assertSame($trialEnds->copy()->addMonthsNoOverflow(3)->toDateString(), $farm->active_until->toDateString());

        // Admin tidak bisa membuka halaman data peternakan
        $this->actingAs($admin)->get(route('owner.dashboard'))->assertRedirect(route('admin.farms.index'));
    }

    public function test_admin_membuat_peternakan_baru(): void
    {
        $admin = User::create(['name' => 'Admin', 'role' => 'superadmin', 'email' => 'admin@hefam.test', 'password' => 'rahasia123']);

        $this->actingAs($admin)->post(route('admin.farms.store'), [
            'farm_name' => 'Ayam Sehat', 'owner_name' => 'Pak Nyoman', 'email' => 'sehat@farm.test', 'password' => 'rahasia123', 'status' => 'trial',
        ])->assertRedirect();

        $this->assertSame('trial', Farm::where('name', 'Ayam Sehat')->value('status'));
    }

    public function test_pemilik_dan_pekerja_tidak_bisa_membuka_panel_admin(): void
    {
        $this->actingAs($this->owner)->get(route('admin.farms.index'))->assertForbidden();
        $this->actingAs($this->worker)->get(route('admin.farms.index'))->assertRedirect(route('daily-logs.create'));
    }
}
