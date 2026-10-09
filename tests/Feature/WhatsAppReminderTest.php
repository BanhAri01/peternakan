<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EggSale;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Models\Vaccination;
use App\Services\FarmProvisioner;
use App\Services\FarmReminders;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppReminderTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-20 06:00:00'));
        $this->setUpFarm();
        config(['hefam.whatsapp.driver' => 'fonnte', 'hefam.whatsapp.fonnte_token' => 'token-uji']);
        Setting::put(['farm_name' => 'Sinar Uji', 'wa_reminder_enabled' => '1', 'wa_reminder_phone' => '0812 3456 7890']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function seedMorningProblems(): void
    {
        $customer = Customer::create(['name' => 'Bu Sari']);
        EggSale::create([
            'customer_id' => $customer->id, 'egg_grade_id' => $this->grade->id, 'sale_date' => '2026-10-10',
            'unit_type' => 'kg', 'quantity_unit' => 10, 'price_per_unit' => 26000, 'paid_amount' => 0, 'due_date' => '2026-10-17',
        ]);
        Vaccination::create([
            'vaccination_date' => '2026-10-21', 'coop_id' => $this->coop->id, 'age_weeks' => 30, 'vaccine_name' => 'ND-IB',
            'method' => 'Tetes mata', 'dosage' => '1 dosis', 'officer' => 'Pemilik', 'cost' => 100000,
        ]);
    }

    public function test_pesan_pagi_berisi_tagihan_dan_vaksin_besok(): void
    {
        $this->seedMorningProblems();

        $message = app(FarmReminders::class)->build('pagi', Carbon::today());

        $this->assertStringContainsString('*Sinar Uji*', $message);
        $this->assertStringContainsString('Bu Sari Rp 260.000', $message);
        $this->assertStringContainsString('Vaksin ND-IB di Kandang A besok', $message);
    }

    public function test_isi_pengingat_bisa_dipilih_pemilik(): void
    {
        $this->seedMorningProblems();
        Setting::put(['wa_notify_debt' => '0']);

        $message = app(FarmReminders::class)->build('pagi', Carbon::today());
        $this->assertStringNotContainsString('Bu Sari', $message);
        $this->assertStringContainsString('Vaksin ND-IB', $message);

        Setting::put(['wa_notify_vaccine' => '0']);
        $this->assertNull(app(FarmReminders::class)->build('pagi', Carbon::today()));

        Setting::put(['wa_notify_harvest' => '0']);
        $this->assertNull(app(FarmReminders::class)->build('sore', Carbon::today()));
    }

    public function test_pilihan_pengingat_tersimpan_dari_halaman_pengaturan(): void
    {
        $this->actingAs($this->owner)->put(route('settings.update'), [
            'farm_name' => 'Sinar Uji', 'egg_price_per_kg' => 27000, 'sack_kg' => 50, 'hdp_warning' => 75, 'low_feed_days' => 4,
            'receipt_paper' => 'continuous', 'receipt_style' => 'ink', 'receipt_color' => '#3f5a26',
            'wa_reminder_enabled' => '1', 'wa_notify_feed' => '1', 'wa_notify_sort' => '1',
        ])->assertRedirect(route('settings.edit'));

        $this->assertSame('1', Setting::get('wa_notify_feed'));
        $this->assertSame('0', Setting::get('wa_notify_debt'));
        $this->assertSame('0', Setting::get('wa_notify_harvest'));
        $this->assertSame('1', Setting::get('wa_notify_sort'));

        $this->actingAs($this->owner)->get(route('settings.edit'))->assertOk()->assertSee('Telur campur yang belum disortir');
    }

    public function test_peternakan_lama_tetap_menerima_semua_isi_pengingat(): void
    {
        foreach (array_keys(FarmReminders::TOPICS) as $topic) {
            $this->assertSame('1', Setting::get($topic));
        }
    }

    public function test_pesan_sore_berisi_kandang_belum_dicatat(): void
    {
        $message = app(FarmReminders::class)->build('sore', Carbon::today());

        $this->assertStringContainsString('Panen hari ini belum dicatat: Kandang A', $message);
    }

    public function test_tidak_ada_pesan_jika_semua_beres(): void
    {
        $this->assertNull(app(FarmReminders::class)->build('pagi', Carbon::today()));
    }

    public function test_perintah_mengirim_lewat_fonnte_sekali_sehari(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true])]);
        $this->seedMorningProblems();

        $this->artisan('hefam:pengingat', ['waktu' => 'pagi'])->assertSuccessful()->expectsOutputToContain('terkirim');
        $this->artisan('hefam:pengingat', ['waktu' => 'pagi'])->assertSuccessful()->expectsOutputToContain('sudah terkirim');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'token-uji')
            && $request['target'] === '6281234567890'
            && str_contains($request['message'], 'Bu Sari'));

        $this->actAsFarm($this->farm);
        $this->assertSame('terkirim', NotificationLog::firstOrFail()->status);
    }

    public function test_gagal_kirim_dicatat_dan_dicoba_lagi(): void
    {
        Http::fakeSequence('api.fonnte.com/*')
            ->push(['status' => false, 'reason' => 'token invalid'])
            ->push(['status' => true]);
        $this->seedMorningProblems();

        $this->artisan('hefam:pengingat', ['waktu' => 'pagi'])->expectsOutputToContain('gagal');
        $this->actAsFarm($this->farm);
        $this->assertStringContainsString('token invalid', NotificationLog::firstOrFail()->error);

        $this->artisan('hefam:pengingat', ['waktu' => 'pagi'])->expectsOutputToContain('terkirim');
        $this->actAsFarm($this->farm);
        $this->assertSame('terkirim', NotificationLog::firstOrFail()->status);
    }

    public function test_peternakan_yang_tidak_mengaktifkan_tidak_dikirimi(): void
    {
        Http::fake();
        $other = app(FarmProvisioner::class)->create([
            'farm_name' => 'Telur Jaya', 'owner_name' => 'Ibu Putu', 'email' => 'jaya@farm.test', 'password' => 'rahasia123', 'phone' => '081399998888',
        ]);

        $this->artisan('hefam:pengingat', ['waktu' => 'sore'])->expectsOutputToContain('Peternakan #' . $other->farm_id . ': nonaktif');
        Http::assertSent(fn ($request) => $request['target'] === '6281234567890');
        Http::assertNotSent(fn ($request) => $request['target'] === '6281399998888');
    }

    public function test_tombol_uji_di_pengaturan(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true])]);

        $this->actingAs($this->owner)->get(route('settings.edit'))->assertOk()->assertSee('Pengingat WhatsApp');
        $this->actingAs($this->owner)->post(route('settings.whatsapp-test'))->assertSessionHas('success');

        Http::assertSentCount(1);
    }

    public function test_mode_uji_tanpa_token_tidak_menghubungi_fonnte(): void
    {
        Http::fake();
        config(['hefam.whatsapp.driver' => 'log']);

        $this->actingAs($this->owner)->post(route('settings.whatsapp-test'))->assertSessionHas('success', fn ($m) => str_contains($m, 'Mode uji'));
        Http::assertNothingSent();
    }
}
