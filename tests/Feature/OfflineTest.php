<?php

namespace Tests\Feature;

use App\Models\DailyLog;
use App\Models\EggGrade;
use App\Models\EggSorting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflineTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    private const JSON = ['Accept' => 'application/json', 'X-Hefam-Queue' => '1'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
    }

    public function test_file_aplikasi_hp_bisa_diunduh_tanpa_login(): void
    {
        $this->get(route('pwa.manifest'))->assertOk()->assertHeader('Content-Type', 'application/manifest+json')->assertJsonPath('short_name', 'HEFAM');
        $this->get(route('pwa.sw'))->assertOk()->assertHeader('Service-Worker-Allowed', '/')->assertDontSee('__VERSION__');
        $this->get(route('pwa.icon', 'icon-192.png'))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get(route('pwa.icon', '..%2F..%2F.env'))->assertNotFound();
        $this->get(route('pwa.offline'))->assertOk()->assertSee('Tidak ada sinyal');
    }

    public function test_halaman_panen_memuat_skrip_offline_dan_form_bertanda(): void
    {
        $this->actingAs($this->worker)->get(route('daily-logs.create'))
            ->assertOk()
            ->assertSee('data-offline="panen"', false)
            ->assertSee('name="hefam-user"', false)
            ->assertSee(route('pwa.manifest'));
    }

    public function test_token_sesi_hanya_untuk_yang_sudah_masuk(): void
    {
        $this->getJson(route('session.token'))->assertUnauthorized();
        $this->actingAs($this->worker)->getJson(route('session.token'))->assertOk()->assertJsonPath('user', $this->worker->id);
    }

    public function test_panen_dari_antrian_hp_tersimpan_sekali_saja(): void
    {
        $uuid = (string) Str::uuid();
        $payload = $this->harvestPayload(['client_uuid' => $uuid]);

        $this->actingAs($this->worker)->post(route('daily-logs.store'), $payload, self::JSON)->assertCreated()->assertJsonStructure(['message', 'redirect']);
        $this->actingAs($this->worker)->post(route('daily-logs.store'), $payload, self::JSON)->assertCreated()->assertJsonPath('message', 'Catatan ini sudah diterima sebelumnya.');

        $this->actAsFarm($this->farm);
        $this->assertSame(1, DailyLog::count());
        $this->assertSame(995, $this->coop->fresh()->current_population);
        $this->assertSame($uuid, DailyLog::first()->client_uuid);
    }

    public function test_panen_dari_antrian_yang_salah_mendapat_pesan_jelas(): void
    {
        $payload = $this->harvestPayload(['client_uuid' => (string) Str::uuid(), 'log_date' => now()->subDays(3)->toDateString()]);

        $this->actingAs($this->worker)->post(route('daily-logs.store'), $payload, self::JSON)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('log_date');
    }

    public function test_form_panen_kosong_dari_antrian_ditolak_dengan_json(): void
    {
        $payload = $this->harvestPayload(['client_uuid' => (string) Str::uuid(), 'feed_sacks' => 0, 'extra_feed_kg' => 0, 'mortality' => 0, 'cull' => 0,
            'grades' => [['egg_grade_id' => $this->grade->id]]]);

        $this->actingAs($this->worker)->post(route('daily-logs.store'), $payload, self::JSON)->assertUnprocessable()->assertJsonValidationErrors('grades');
    }

    public function test_sortir_dari_antrian_hp_tersimpan_sekali_saja(): void
    {
        $this->actAsFarm($this->farm);
        EggGrade::mixed();
        $uuid = (string) Str::uuid();
        $payload = [
            'client_uuid' => $uuid,
            'sort_date'   => now()->toDateString(),
            'items'       => [['egg_grade_id' => $this->grade->id, 'trays_count' => 10, 'weight_kg' => 18]],
        ];

        $this->actingAs($this->worker)->post(route('sortings.store'), $payload, self::JSON)->assertCreated();
        $this->actingAs($this->worker)->post(route('sortings.store'), $payload, self::JSON)->assertCreated();

        $this->actAsFarm($this->farm);
        $this->assertSame(1, EggSorting::count());
    }

    public function test_simpan_langsung_tanpa_antrian_tetap_seperti_biasa(): void
    {
        $this->actingAs($this->worker)->post(route('daily-logs.store'), $this->harvestPayload())
            ->assertRedirect(route('daily-logs.create', ['date' => now()->toDateString()]))
            ->assertSessionHas('success');
    }

    public function test_simpan_langsung_lewat_json_membawa_pesan_ke_halaman_berikutnya(): void
    {
        $this->actingAs($this->worker)->post(route('daily-logs.store'), $this->harvestPayload(['client_uuid' => (string) Str::uuid()]), ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertSessionHas('success');
    }
}
