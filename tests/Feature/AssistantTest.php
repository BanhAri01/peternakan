<?php

namespace Tests\Feature;

use App\Assistant\Agent;
use App\Assistant\DemoLlm;
use App\Assistant\Llm;
use App\Assistant\Quota;
use App\Models\AssistantMessage;
use App\Models\AssistantPhone;
use App\Models\Coop;
use App\Models\Customer;
use App\Models\DailyLog;
use App\Models\EggSale;
use App\Models\Farm;
use App\Models\FeedStock;
use App\Models\Setting;
use App\Models\User;
use App\Services\Plans;
use App\Services\WhatsApp;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AssistantTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-20 09:00:00'));

        // Siapkan peternakan dengan paket Entrepreneur
        $this->setUpFarm();
        $this->farm->update(['plan' => 'entrepreneur']);
        Setting::put(['farm_name' => 'Sinar Uji']);

        // Gunakan DemoLlm agar pengujian berjalan offline, cepat, dan tanpa biaya
        $this->app->singleton(Llm::class, fn () => new DemoLlm());
        config([
            'hefam.assistant.driver' => 'demo',
            'hefam.assistant.webhook_secret' => 'rahasia-asisten-123',
            'hefam.whatsapp.driver' => 'fonnte',
            'hefam.whatsapp.fonnte_token' => 'token-test',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_paket_standar_dan_pro_tidak_bisa_mengakses_asisten(): void
    {
        $this->farm->update(['plan' => 'pro']);

        $response = $this->actingAs($this->owner)->get(route('assistant.index'));
        $response->assertRedirect(route('subscription.show'));
        $response->assertSessionHas('warning');

        $this->farm->update(['plan' => 'standar']);
        $response2 = $this->actingAs($this->owner)->get(route('assistant.index'));
        $response2->assertRedirect(route('subscription.show'));
    }

    public function test_paket_entrepreneur_bisa_membuka_halaman_asisten(): void
    {
        $response = $this->actingAs($this->owner)->get(route('assistant.index'));
        $response->assertOk();
        $response->assertSee('Asisten Kandang');
        $response->assertSee('Pulsa Asisten');
        $response->assertSee('Contoh pertanyaan cepat');
    }

    public function test_pemilik_bisa_bertanya_via_web_dan_mendapat_jawaban_akurat(): void
    {
        // Masukkan data panen hari ini
        DailyLog::create([
            'coop_id' => $this->coop->id,
            'log_date' => Carbon::today()->toDateString(),
            'eggs_total_count' => 850,
            'eggs_total_kg' => 52.5,
            'hdp_percentage' => 85.0,
            'feed_consumed_kg' => 110,
            'recorded_by' => $this->worker->id,
        ]);

        $response = $this->actingAs($this->owner)->postJson(route('assistant.ask'), [
            'question' => 'Telur hari ini berapa?',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['text', 'status', 'mode', 'cost', 'tools', 'quota']);
        $this->assertEquals('ok', $response->json('status'));
        $this->assertStringContainsString('850 butir', $response->json('text'));
        $this->assertContains('produksi_harian', $response->json('tools'));

        // Cek bahwa pesan tersimpan di database dan memotong pulsa
        $this->assertDatabaseHas('assistant_messages', [
            'farm_id' => $this->farm->id,
            'channel' => Agent::WEB,
            'role' => 'user',
            'content' => 'Telur hari ini berapa?',
        ]);
        $this->assertDatabaseHas('assistant_messages', [
            'farm_id' => $this->farm->id,
            'channel' => Agent::WEB,
            'role' => 'assistant',
            'status' => 'ok',
        ]);
    }

    public function test_deteksi_mode_otomatis_untuk_analisis_mendalam(): void
    {
        $response = $this->actingAs($this->owner)->postJson(route('assistant.ask'), [
            'question' => 'Kenapa telur kandang A turun?',
        ]);

        $response->assertOk();
        $this->assertEquals('deep', $response->json('mode'));
    }

    public function test_perintah_gratis_seperti_menu_dan_pulsa_tidak_memanggil_alat_berat(): void
    {
        $response = $this->actingAs($this->owner)->postJson(route('assistant.ask'), [
            'question' => 'pulsa',
        ]);

        $response->assertOk();
        $this->assertEquals('command', $response->json('status'));
        $this->assertStringContainsString('Pulsa Asisten', $response->json('text'));
        $this->assertEmpty($response->json('tools'));
    }

    private function registerPhone(User $owner, string $phone, bool $ok = true): ?string
    {
        Http::fake(['https://api.fonnte.com/send' => Http::response(['status' => $ok, 'reason' => $ok ? null : 'nomor tidak aktif'], 200)]);

        $this->actingAs($owner)->post(route('assistant.phones.store'), ['phone' => $phone, 'label' => 'HP Bapak']);

        $sent = Http::recorded()->last()[0] ?? null;

        return $sent && preg_match('/\*(\d{6})\*/', (string) $sent['message'], $m) ? $m[1] : null;
    }

    public function test_pemilik_bisa_mendaftarkan_dan_menghapus_nomor_whatsapp(): void
    {
        $code = $this->registerPhone($this->owner, '0812 3456 7890');

        $this->assertNotNull($code);
        $this->assertDatabaseHas('assistant_phones', [
            'farm_id' => $this->farm->id,
            'phone' => '6281234567890',
            'label' => 'HP Bapak',
            'verified_at' => null,
        ]);

        $phone = AssistantPhone::where('phone', '6281234567890')->first();
        $this->actingAs($this->owner)->post(route('assistant.phones.verify', $phone), ['code' => $code])->assertSessionHasNoErrors();
        $this->assertNotNull($phone->fresh()->verified_at);

        $delResponse = $this->actingAs($this->owner)->delete(route('assistant.phones.destroy', $phone));
        $delResponse->assertRedirect();
        $this->assertDatabaseMissing('assistant_phones', ['id' => $phone->id]);
    }

    public function test_nomor_belum_diverifikasi_tidak_dilayani_dan_kode_salah_ditolak(): void
    {
        $code  = $this->registerPhone($this->owner, '0812 3456 7890');
        $phone = AssistantPhone::where('phone', '6281234567890')->first();

        $this->postJson(route('webhooks.assistant', ['secret' => 'rahasia-asisten-123']), ['sender' => '081234567890', 'message' => 'Telur?'])
            ->assertJson(['status' => 'ignored', 'reason' => 'unknown']);

        $this->actingAs($this->owner)->post(route('assistant.phones.verify', $phone), ['code' => '000000'])->assertSessionHasErrors("code_{$phone->id}");
        $this->actingAs($this->owner)->post(route('assistant.phones.verify', $phone), ['code' => $code])->assertSessionHasNoErrors();

        $this->postJson(route('webhooks.assistant', ['secret' => 'rahasia-asisten-123']), ['sender' => '081234567890', 'message' => 'Telur?'])
            ->assertJson(['status' => 'accepted']);
    }

    public function test_kode_salah_lima_kali_membatalkan_kode(): void
    {
        $code  = $this->registerPhone($this->owner, '0812 3456 7890');
        $phone = AssistantPhone::where('phone', '6281234567890')->first();

        foreach (range(1, 5) as $i) {
            $this->actingAs($this->owner)->post(route('assistant.phones.verify', $phone), ['code' => '111111']);
        }

        $this->actingAs($this->owner)->post(route('assistant.phones.verify', $phone), ['code' => $code])->assertSessionHasErrors("code_{$phone->id}");
        $this->assertNull($phone->fresh()->verified_at);
    }

    public function test_peternakan_lain_tidak_bisa_merebut_nomor_yang_sedang_didaftarkan(): void
    {
        $this->registerPhone($this->owner, '0812 3456 7890');

        $farm2  = Farm::create(['name' => 'Farm Kedua', 'plan' => 'entrepreneur', 'status' => 'active']);
        $owner2 = User::create(['farm_id' => $farm2->id, 'name' => 'Owner 2', 'role' => 'owner', 'email' => 'o2@test.com', 'password' => 'pass']);

        $this->actingAs($owner2)->post(route('assistant.phones.store'), ['phone' => '081234567890'])->assertSessionHasErrors('phone');

        $this->travel(11)->minutes();
        $this->assertNotNull($this->registerPhone($owner2, '081234567890'));
        $this->assertSame($farm2->id, AssistantPhone::allFarms()->sole()->farm_id);
    }

    public function test_pendaftaran_dibatalkan_bila_kode_gagal_dikirim(): void
    {
        $this->registerPhone($this->owner, '0812 3456 7890', ok: false);

        $this->assertSame(0, AssistantPhone::allFarms()->count());
    }

    public function test_kirim_ulang_kode_ditahan_satu_menit(): void
    {
        $this->registerPhone($this->owner, '0812 3456 7890');
        $this->actingAs($this->owner)->post(route('assistant.phones.store'), ['phone' => '0812 3456 7890'])->assertSessionHasErrors('phone');

        $this->travel(2)->minutes();
        $this->assertNotNull($this->registerPhone($this->owner, '0812 3456 7890'));
        $this->assertSame(1, AssistantPhone::allFarms()->count());
    }

    public function test_nomor_berhenti_dilayani_bila_akun_pendaftarnya_dihapus(): void
    {
        $owner2 = User::create(['farm_id' => $this->farm->id, 'name' => 'Owner Kedua', 'role' => 'owner', 'email' => 'kedua@test.com', 'password' => 'pass']);
        $phone  = AssistantPhone::create(['farm_id' => $this->farm->id, 'phone' => '6281234567890', 'user_id' => $owner2->id, 'verified_at' => now()]);
        $owner2->delete();

        $this->postJson(route('webhooks.assistant', ['secret' => 'rahasia-asisten-123']), ['sender' => '081234567890', 'message' => 'Telur?'])
            ->assertJson(['status' => 'ignored', 'reason' => 'inactive']);

        Http::fake();
        (new \App\Jobs\AnswerAssistantWhatsApp($phone->id, 'Telur?'))->handle(app(Agent::class), app(WhatsApp::class));
        Http::assertNothingSent();
    }

    public function test_nomor_whatsapp_tidak_bisa_didaftarkan_ganda_antar_peternakan(): void
    {
        AssistantPhone::create([
            'farm_id' => $this->farm->id,
            'phone' => '6281234567890',
            'label' => 'HP Utama',
            'user_id' => $this->owner->id,
            'verified_at' => now(),
        ]);

        // Buat peternakan kedua
        $farm2 = Farm::create(['name' => 'Farm Kedua', 'plan' => 'entrepreneur', 'status' => 'active']);
        $owner2 = User::create(['farm_id' => $farm2->id, 'name' => 'Owner 2', 'role' => 'owner', 'email' => 'o2@test.com', 'password' => 'pass']);

        $response = $this->actingAs($owner2)->post(route('assistant.phones.store'), [
            'phone' => '081234567890',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_webhook_whatsapp_menolak_rahasia_url_palsu(): void
    {
        $response = $this->postJson(route('webhooks.assistant', ['secret' => 'salah']), [
            'sender' => '081234567890',
            'message' => 'Telur hari ini berapa?',
        ]);

        $response->assertNotFound();
    }

    public function test_webhook_whatsapp_mengabaikan_grup_dan_nomor_tak_dikenal(): void
    {
        // 1. Pesan dari grup
        $response1 = $this->postJson(route('webhooks.assistant', ['secret' => 'rahasia-asisten-123']), [
            'sender' => '6281234567890',
            'member' => '628111111111',
            'message' => 'Halo',
        ]);
        $response1->assertJson(['status' => 'ignored', 'reason' => 'group']);

        // 2. Pesan dari nomor yang belum didaftarkan
        $response2 = $this->postJson(route('webhooks.assistant', ['secret' => 'rahasia-asisten-123']), [
            'sender' => '089999999999',
            'message' => 'Telur hari ini berapa?',
        ]);
        $response2->assertJson(['status' => 'ignored', 'reason' => 'unknown']);
    }

    public function test_webhook_whatsapp_menerima_pesan_dari_nomor_terdaftar(): void
    {
        AssistantPhone::create([
            'farm_id' => $this->farm->id,
            'phone' => '6281234567890',
            'label' => 'HP Mandor',
            'user_id' => $this->owner->id,
            'verified_at' => now(),
        ]);

        $response = $this->postJson(route('webhooks.assistant', ['secret' => 'rahasia-asisten-123']), [
            'sender' => '081234567890',
            'message' => 'Telur hari ini berapa?',
            'inboxid' => 'pesan-123',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'accepted']);

        // Uji deduplikasi: pesan dengan inboxid yang sama persis diabaikan (tidak diproses ulang)
        $responseDup = $this->postJson(route('webhooks.assistant', ['secret' => 'rahasia-asisten-123']), [
            'sender' => '081234567890',
            'message' => 'Telur hari ini berapa?',
            'inboxid' => 'pesan-123',
        ]);
        $responseDup->assertOk();
        $responseDup->assertJson(['status' => 'ignored', 'reason' => 'duplicate']);
    }

    public function test_job_penjawab_whatsapp_mengirim_balasan_ke_fonnte(): void
    {
        $phone = AssistantPhone::create([
            'farm_id' => $this->farm->id,
            'phone' => '6281234567890',
            'label' => 'HP Mandor',
            'user_id' => $this->owner->id,
            'verified_at' => now(),
        ]);

        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        $job = new \App\Jobs\AnswerAssistantWhatsApp($phone->id, 'pakan cukup berapa hari?');
        $job->handle(app(Agent::class), app(WhatsApp::class));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fonnte.com/send'
                && $request['target'] === '6281234567890'
                && str_contains($request['message'], 'Pakan');
        });
    }

    public function test_asisten_tetap_merespon_ramah_jika_pulsa_habis(): void
    {
        // Habiskan pulsa dengan mencatat biaya besar
        AssistantMessage::create([
            'farm_id' => $this->farm->id,
            'channel' => Agent::WEB,
            'role' => 'assistant',
            'cost' => 50000, // melebih pagu Rp25.000
            'content' => 'Jawaban sebelumnya',
        ]);

        $quota = Quota::for($this->farm);
        $this->assertEquals(0, $quota->remaining());

        $response = $this->actingAs($this->owner)->postJson(route('assistant.ask'), [
            'question' => 'Telur hari ini berapa?',
        ]);

        $response->assertOk();
        $this->assertEquals('quota', $response->json('status'));
        $this->assertStringContainsString('Pulsa Asisten bulan ini sudah habis', $response->json('text'));
    }
}
