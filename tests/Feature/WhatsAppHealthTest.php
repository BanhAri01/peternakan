<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WhatsApp;
use App\Services\WhatsAppHealth;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppHealthTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-14 17:00:00'));
        $this->setUpFarm();
        config([
            'hefam.whatsapp.driver' => 'fonnte',
            'hefam.whatsapp.fonnte_token' => 'token-utama',
            'hefam.assistant.fonnte_token' => 'token-asisten',
            'hefam.whatsapp.alert_email' => 'ari@contoh.test',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private string $mainStatus = 'connect';

    private function fakeDevices(string $main): void
    {
        $this->mainStatus = $main;

        Http::fake(function ($request) {
            $status = $request->header('Authorization')[0] === 'token-asisten' ? 'connect' : $this->mainStatus;

            return Http::response(['status' => true, 'device_status' => $status, 'device' => '6281200000000']);
        });
    }

    private function emails(): array
    {
        return array_map(fn ($sent) => $sent->getOriginalMessage(), app('mailer')->getSymfonyTransport()->messages()->all());
    }

    public function test_perangkat_terputus_terdeteksi_dan_admin_dapat_email_sekali(): void
    {
        $this->fakeDevices('disconnect');

        $result = app(WhatsAppHealth::class)->check();
        app(WhatsAppHealth::class)->check();

        $this->assertSame(['utama' => 'terputus', 'asisten' => 'tersambung'], $result);
        $this->assertArrayHasKey('utama', app(WhatsAppHealth::class)->down());
        $this->assertCount(1, $this->emails());
        $this->assertStringContainsString('WhatsApp terputus', $this->emails()[0]->getSubject());
        $this->assertSame('ari@contoh.test', $this->emails()[0]->getTo()[0]->getAddress());
    }

    public function test_email_diulang_tiap_6_jam_selama_masih_terputus_lalu_kabar_tersambung(): void
    {
        $this->fakeDevices('disconnect');
        app(WhatsAppHealth::class)->check();

        Carbon::setTestNow(now()->addHours(7));
        app(WhatsAppHealth::class)->check();
        $this->assertCount(2, $this->emails());
        $this->assertStringContainsString('masih terputus', $this->emails()[1]->getSubject());

        $this->mainStatus = 'connect';
        app(WhatsAppHealth::class)->check();

        $this->assertSame([], app(WhatsAppHealth::class)->down());
        $this->assertCount(3, $this->emails());
        $this->assertStringContainsString('tersambung lagi', $this->emails()[2]->getSubject());
    }

    public function test_pesan_gagal_karena_disconnected_langsung_menandai_terputus_dan_pesan_berhasil_memulihkan(): void
    {
        Http::fake(['api.fonnte.com/send' => Http::sequence()
            ->push(['status' => false, 'reason' => 'request invalid on disconnected device'])
            ->push(['status' => true, 'id' => ['1']])]);

        try {
            app(WhatsApp::class)->send('081234567890', 'Halo');
        } catch (\RuntimeException) {
        }
        $this->assertArrayHasKey('utama', app(WhatsAppHealth::class)->down());

        app(WhatsApp::class)->send('081234567890', 'Halo');
        $this->assertSame([], app(WhatsAppHealth::class)->down());
    }

    public function test_banner_muncul_untuk_pemilik_dan_admin_saat_terputus(): void
    {
        $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertDontSee('WhatsApp Kandala terputus');

        $this->fakeDevices('disconnect');
        app(WhatsAppHealth::class)->check();

        $this->actingAs($this->owner)->get(route('owner.dashboard'))
            ->assertSee('WhatsApp Kandala terputus')
            ->assertSee('Admin Kandala sudah diberi tahu');

        $admin = User::create(['name' => 'Admin', 'role' => 'superadmin', 'email' => 'admin@hefam.test', 'password' => 'rahasia123']);
        $this->actingAs($admin)->get(route('admin.farms.index'))
            ->assertSee('WhatsApp Kandala terputus')
            ->assertSee('Buka Fonnte');
    }

    public function test_mode_uji_tanpa_fonnte_tidak_memantau_apa_pun(): void
    {
        config(['hefam.whatsapp.driver' => 'log']);
        Http::fake();

        $this->assertSame([], app(WhatsAppHealth::class)->check());
        Http::assertNothingSent();
    }
}
