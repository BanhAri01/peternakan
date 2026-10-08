<?php

namespace Tests\Feature;

use App\Models\Coop;
use App\Models\Device;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\FarmReminders;
use App\Services\Plans;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlanTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-20 10:00:00'));
        $this->setUpFarm();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function usePlan(string $plan): void
    {
        $this->farm->update(['plan' => $plan, 'status' => 'active', 'active_until' => '2027-10-20']);
        $this->owner->unsetRelation('farm');
    }

    private function coopPayload(string $name): array
    {
        return ['name' => $name, 'capacity' => 500, 'initial_population' => 500, 'strain' => 'Isa Brown',
            'chick_in_date' => '2026-06-01', 'initial_age_weeks' => 18, 'status' => 'active'];
    }

    public function test_harga_paket_dan_diskon_durasi(): void
    {
        $this->assertSame(99000, Plans::price('standar', 1));
        $this->assertSame(567000, Plans::price('pro', 3));
        $this->assertSame(3490000, Plans::price('entrepreneur', 12));
        $this->assertSame(398000, Plans::saving('pro', 12));
    }

    public function test_peternakan_baru_mendapat_paket_entrepreneur(): void
    {
        $this->assertSame('entrepreneur', $this->farm->fresh()->planKey());
        $this->assertTrue($this->farm->fresh()->allows('export'));
    }

    public function test_paket_standar_dibatasi_tiga_kandang_aktif(): void
    {
        $this->usePlan('standar');
        $this->actAsFarm($this->farm);
        Coop::create($this->coopPayload('Kandang B') + ['current_population' => 500]);
        Coop::create($this->coopPayload('Kandang C') + ['current_population' => 500]);

        $this->actingAs($this->owner)->post(route('coops.store'), $this->coopPayload('Kandang D'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'maksimal 3 kandang aktif'));
        $this->actingAs($this->owner)->post(route('coops.store'), ['status' => 'empty'] + $this->coopPayload('Kandang Cadangan'))
            ->assertRedirect(route('coops.index'));

        $this->actAsFarm($this->farm);
        $this->assertSame(3, Coop::where('status', 'active')->count());
    }

    public function test_paket_standar_dibatasi_tiga_pekerja(): void
    {
        $this->usePlan('standar');
        foreach (['Made', 'Komang'] as $name) {
            User::create(['farm_id' => $this->farm->id, 'name' => $name, 'role' => 'worker', 'pin' => '1234']);
        }

        $this->actingAs($this->owner)->post(route('users.store'), ['name' => 'Ketut', 'role' => 'worker', 'pin' => '4321'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'maksimal 3 pekerja'));

        $this->assertSame(3, User::where('farm_id', $this->farm->id)->where('role', 'worker')->count());
    }

    public function test_paket_standar_dibatasi_dua_hp_kandang(): void
    {
        $this->usePlan('standar');
        foreach (range(1, 2) as $i) {
            $device = new Device(['uuid' => (string) Str::uuid(), 'name' => 'HP ' . $i, 'is_active' => true]);
            $device->farm_id = $this->farm->id;
            $device->save();
        }

        $this->actingAs($this->owner)->post(route('devices.store'), ['name' => 'HP Ketiga'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'maksimal 2 HP kandang'));
    }

    public function test_fitur_terkunci_di_paket_standar_dan_terbuka_di_pro(): void
    {
        $this->usePlan('standar');

        foreach (['payroll.index', 'attendance.index', 'medicines.index', 'other-incomes.index', 'exports.index'] as $route) {
            $this->actingAs($this->owner)->get(route($route))
                ->assertRedirect(route('subscription.show'))
                ->assertSessionHas('warning', fn ($m) => str_contains($m, 'paket Pro'));
        }
        $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertOk()->assertSee('bi-lock-fill', false);

        $this->usePlan('pro');
        foreach (['payroll.index', 'attendance.index', 'medicines.index', 'other-incomes.index', 'exports.index'] as $route) {
            $this->actingAs($this->owner)->get(route($route))->assertOk();
        }
    }

    public function test_standar_strain_hanya_untuk_pro_ke_atas(): void
    {
        $this->actingAs($this->owner)->post(route('daily-logs.store'), $this->harvestPayload());

        $this->usePlan('standar');
        $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertOk()->assertDontSee('Standar umur');
        $this->actingAs($this->owner)->get(route('coops.show', $this->coop))->assertOk()->assertSee('tersedia mulai paket Pro');

        $this->usePlan('pro');
        $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertOk()->assertSee('Standar umur');
    }

    public function test_kuota_whatsapp_per_paket(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true])]);
        config(['hefam.whatsapp.driver' => 'fonnte', 'hefam.whatsapp.fonnte_token' => 'uji']);
        $this->actAsFarm($this->farm);
        Setting::put(['wa_reminder_enabled' => '1', 'wa_reminder_phone' => '081234567890']);

        $this->usePlan('standar');
        $this->assertSame('paket tanpa WhatsApp', app(FarmReminders::class)->sendAll('sore', $this->farm->id)[$this->farm->id]);
        $this->actingAs($this->owner)->get(route('settings.edit'))->assertOk()->assertSee('tersedia mulai paket');

        $this->usePlan('pro');
        foreach (range(1, 100) as $i) {
            NotificationLog::create(['farm_id' => $this->farm->id, 'kind' => 'uji' . $i, 'sent_on' => '2026-10-0' . (($i % 9) + 1), 'target' => 'x', 'status' => 'terkirim', 'message' => 'x']);
        }
        $this->assertSame('kuota WhatsApp bulan ini habis', app(FarmReminders::class)->sendAll('sore', $this->farm->id)[$this->farm->id]);
        $this->actingAs($this->owner)->post(route('settings.whatsapp-test'))->assertSessionHas('error', fn ($m) => str_contains($m, 'Kuota'));

        $this->usePlan('entrepreneur');
        $this->assertSame('terkirim', app(FarmReminders::class)->sendAll('sore', $this->farm->id)[$this->farm->id]);
        Http::assertSentCount(1);
    }

    public function test_ganti_paket_mengonversi_sisa_hari_sesuai_nilai(): void
    {
        $this->farm->update(['plan' => 'standar', 'status' => 'active', 'active_until' => '2027-10-20']);

        [$from, $until] = $this->farm->fresh()->extendSubscription(1, 'pro');

        $converted = (int) floor(365 * 99000 / 199000);
        $this->assertSame(Carbon::today()->addDays($converted)->toDateString(), $from->toDateString());
        $this->assertSame('pro', $this->farm->fresh()->plan);
        $this->assertTrue($until->lt(Carbon::parse('2027-10-20')->addMonth()));
    }

    public function test_perpanjang_paket_yang_sama_menyambung_dari_tanggal_akhir(): void
    {
        $this->farm->update(['plan' => 'pro', 'status' => 'active', 'active_until' => '2026-12-01']);

        [$from, $until] = $this->farm->fresh()->extendSubscription(3, 'pro');

        $this->assertSame('2026-12-01', $from->toDateString());
        $this->assertSame('2027-03-01', $until->toDateString());
    }

    public function test_halaman_langganan_menampilkan_tiga_paket_dan_fiturnya(): void
    {
        $this->actingAs($this->owner)->get(route('subscription.show'))
            ->assertOk()
            ->assertSee('Standar')->assertSee('Pro')->assertSee('Entrepreneur')
            ->assertSee('Maks 3 kandang aktif')
            ->assertSee('Pengingat WA 100 pesan/bulan')
            ->assertSee('Tanpa batas kandang aktif')
            ->assertSee('Gaji &amp; absensi pekerja', false);
    }

    public function test_admin_bisa_mengganti_paket_peternakan(): void
    {
        $admin = User::create(['name' => 'Ari', 'email' => 'admin@hefam.test', 'password' => 'admin12345', 'role' => 'superadmin']);

        $this->actingAs($admin)->put(route('admin.farms.update', $this->farm), [
            'name' => $this->farm->name, 'status' => 'active', 'plan' => 'standar',
        ])->assertSessionHas('success');

        $this->assertSame('standar', $this->farm->fresh()->plan);
        $this->actingAs($admin)->get(route('admin.farms.index'))->assertOk()->assertSee('Standar');
    }
}
