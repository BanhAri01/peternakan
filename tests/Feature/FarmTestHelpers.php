<?php

namespace Tests\Feature;

use App\Models\Coop;
use App\Models\Device;
use App\Models\EggGrade;
use App\Models\Farm;
use App\Models\FeedStock;
use App\Models\User;
use App\Services\DeviceService;
use App\Tenancy\FarmContext;
use Illuminate\Support\Str;

// Data dasar peternakan untuk pengujian
trait FarmTestHelpers
{
    protected Farm $farm;
    protected User $owner;
    protected User $worker;
    protected Coop $coop;
    protected FeedStock $feed;
    protected EggGrade $grade;

    protected function setUpFarm(): void
    {
        $this->farm = Farm::create(['name' => 'Peternakan Uji', 'status' => 'active']);
        $this->actAsFarm($this->farm);

        $this->owner  = User::create(['farm_id' => $this->farm->id, 'name' => 'Pemilik', 'role' => 'owner', 'email' => 'owner@farm.test', 'password' => 'rahasia123']);
        $this->worker = User::create(['farm_id' => $this->farm->id, 'name' => 'Wayan', 'role' => 'worker', 'pin' => '2580']);

        $this->coop = Coop::create([
            'name' => 'Kandang A', 'capacity' => 1200, 'initial_population' => 1000, 'current_population' => 1000,
            'strain' => 'Isa Brown', 'chick_in_date' => now()->subWeeks(10)->toDateString(), 'initial_age_weeks' => 18, 'status' => 'active',
        ]);
        $this->feed  = FeedStock::create(['feed_name' => 'Pakan Layer', 'stock_kg' => 1000, 'cost_per_kg' => 7000]);
        $this->grade = EggGrade::create(['name' => 'Telur Besar', 'code' => 'B', 'is_active' => true]);
    }

    // Jadikan peternakan ini aktif untuk kode di dalam test (di luar request)
    protected function actAsFarm(Farm $farm): void
    {
        app(FarmContext::class)->set($farm);
    }

    // Daftarkan "HP kandang" untuk peternakan, lalu kirim cookie-nya di request berikutnya
    protected function useFarmDevice(?Farm $farm = null): Device
    {
        $farm ??= $this->farm;
        $device = new Device(['uuid' => (string) Str::uuid(), 'name' => 'HP Uji', 'is_active' => true]);
        $device->farm_id = $farm->id;
        $device->save();

        $this->withCookie(DeviceService::COOKIE, $device->uuid);

        return $device;
    }

    protected function harvestPayload(array $overrides = []): array
    {
        return array_merge([
            'coop_id'       => $this->coop->id,
            'log_date'      => now()->toDateString(),
            'feed_stock_id' => $this->feed->id,
            'feed_sacks'    => 2,
            'extra_feed_kg' => 10,
            'mortality'     => 3,
            'cull'          => 2,
            'grades'        => [
                ['egg_grade_id' => $this->grade->id, 'trays_count' => 30, 'extra_eggs' => 0, 'weight_kg' => 55],
            ],
        ], $overrides);
    }
}
