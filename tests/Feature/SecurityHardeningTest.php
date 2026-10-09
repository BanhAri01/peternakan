<?php

namespace Tests\Feature;

use App\Models\Farm;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->farm = Farm::create(['name' => 'Peternakan Uji', 'status' => 'active']);
        $this->actAsFarm($this->farm);
    }

    public function test_pin_pekerja_dikunci_setelah_20_kali_salah_sehari_dan_pemilik_dikabari(): void
    {
        config(['hefam.whatsapp.driver' => 'fonnte', 'hefam.whatsapp.fonnte_token' => 'token-uji']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true])]);
        Setting::put(['wa_reminder_phone' => '0812 3456 7890']);

        $worker = User::create(['farm_id' => $this->farm->id, 'name' => 'Made', 'role' => 'worker', 'pin' => '2580']);
        $device = $this->useFarmDevice();

        for ($i = 0; $i < 20; $i++) {
            RateLimiter::clear('login-worker|' . $device->id . '|' . $worker->id);
            $this->post(route('login.worker'), ['worker_id' => $worker->id, 'pin' => '9999']);
        }

        $this->post(route('login.worker'), ['worker_id' => $worker->id, 'pin' => '2580'])
            ->assertSessionHasErrors(['pin' => 'PIN Anda dikunci karena terlalu sering salah. Minta pemilik mengganti PIN Anda di menu Pengguna.']);
        $this->assertGuest();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains($request['message'], 'Peringatan keamanan') && str_contains($request['message'], 'Made'));

        $worker->update(['pin' => '1357']);

        $this->post(route('login.worker'), ['worker_id' => $worker->id, 'pin' => '1357'])->assertRedirect(route('daily-logs.create'));
        $this->assertAuthenticatedAs($worker);
    }

    public function test_header_keamanan_dikirim(): void
    {
        $this->get(route('login'))
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_http_dialihkan_ke_https_di_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->get('http://localhost/login')->assertStatus(301)->assertRedirect('https://localhost/login');
        $this->get('https://localhost/login')->assertOk();
    }

    public function test_webhook_mode_uji_ditolak_di_production(): void
    {
        config(['services.payment.driver' => 'fake']);
        $this->app->detectEnvironment(fn () => 'production');

        $this->postJson(route('webhooks.payment'), ['order_id' => 'x'])->assertNotFound();
    }
}
