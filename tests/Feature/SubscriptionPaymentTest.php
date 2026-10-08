<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\SubscriptionPayment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubscriptionPaymentTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    private const SERVER_KEY = 'SB-Mid-server-UJI';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-20 10:00:00'));
        $this->setUpFarm();
        $this->farm->update(['status' => 'trial', 'trial_ends_at' => '2026-10-25', 'phone' => '081234567890']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function useMidtrans(): void
    {
        config(['services.payment.driver' => 'midtrans', 'services.midtrans.server_key' => self::SERVER_KEY, 'services.midtrans.is_production' => false]);
    }

    private function midtransPayload(SubscriptionPayment $payment, string $status = 'settlement', ?string $amount = null): array
    {
        $amount ??= $payment->amount . '.00';

        return [
            'order_id'           => $payment->reference,
            'status_code'        => '200',
            'gross_amount'       => $amount,
            'transaction_status' => $status,
            'payment_type'       => 'qris',
            'transaction_id'     => 'TRX-1',
            'signature_key'      => hash('sha512', $payment->reference . '200' . $amount . self::SERVER_KEY),
        ];
    }

    private function startPayment(int $months = 3): SubscriptionPayment
    {
        $this->actingAs($this->owner)->post(route('subscription.pay'), ['months' => $months])->assertRedirect();
        $this->actAsFarm($this->farm);

        return SubscriptionPayment::latest('id')->firstOrFail();
    }

    public function test_halaman_langganan_menampilkan_paket(): void
    {
        $this->actingAs($this->owner)->get(route('subscription.show'))
            ->assertOk()
            ->assertSee('Bayar langganan')
            ->assertSee('Rp 1.500.000')
            ->assertSee('Paling hemat');
    }

    public function test_simulasi_lokal_bayar_berhasil_memperpanjang_langganan(): void
    {
        $response = $this->actingAs($this->owner)->post(route('subscription.pay'), ['months' => 3]);
        $this->actAsFarm($this->farm);
        $payment = SubscriptionPayment::firstOrFail();
        $this->assertSame(3, (int) $payment->months);
        $this->assertSame(420000, (int) $payment->amount);
        $this->assertStringStartsWith('HEFAM-' . $this->farm->id . '-', $payment->reference);

        $this->actingAs($this->owner)->get($response->headers->get('Location'))->assertOk()->assertSee('Bayar berhasil');

        $this->actingAs($this->owner)->post(route('subscription.simulate.confirm', $payment->reference), ['status' => 'paid'])
            ->assertRedirect(route('subscription.finish', ['ref' => $payment->reference]));
        $this->actingAs($this->owner)->get(route('subscription.finish', ['ref' => $payment->reference]))
            ->assertRedirect(route('subscription.show'))
            ->assertSessionHas('success');

        $farm = $this->farm->fresh();
        $this->assertSame('active', $farm->status);
        $this->assertSame('2027-01-20', $farm->active_until->toDateString());
        $this->actAsFarm($this->farm);
        $this->assertSame(1, ActivityLog::where('subject_type', 'SubscriptionPayment')->where('event', 'paid')->count());
    }

    public function test_midtrans_membuat_tagihan_snap_dan_webhook_memperpanjang_sekali_saja(): void
    {
        $this->useMidtrans();
        Http::fake(['app.sandbox.midtrans.com/snap/v1/transactions' => Http::response(['token' => 'tok', 'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/tok'], 201)]);

        $this->actingAs($this->owner)->post(route('subscription.pay'), ['months' => 1])
            ->assertRedirect('https://app.sandbox.midtrans.com/snap/v4/redirection/tok');
        Http::assertSent(fn ($r) => $r['transaction_details']['gross_amount'] === 150000 && $r->hasHeader('Authorization'));

        $this->actAsFarm($this->farm);
        $payment = SubscriptionPayment::firstOrFail();

        $this->postJson(route('webhooks.payment'), $this->midtransPayload($payment))->assertOk()->assertJsonPath('status', 'paid');
        $this->postJson(route('webhooks.payment'), $this->midtransPayload($payment))->assertOk();

        $this->assertSame('2026-11-20', $this->farm->fresh()->active_until->toDateString());
        $this->assertSame('qris', $payment->fresh()->method);
    }

    public function test_webhook_dengan_tanda_tangan_palsu_ditolak(): void
    {
        $this->useMidtrans();
        Http::fake(['*' => Http::response(['token' => 't', 'redirect_url' => 'https://x.test'], 201)]);
        $payment = $this->startPayment();

        $payload = $this->midtransPayload($payment);
        $payload['signature_key'] = str_repeat('a', 128);

        $this->postJson(route('webhooks.payment'), $payload)->assertForbidden();
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('trial', $this->farm->fresh()->status);
    }

    public function test_nominal_berbeda_tidak_mengaktifkan_langganan(): void
    {
        $this->useMidtrans();
        Http::fake(['*' => Http::response(['token' => 't', 'redirect_url' => 'https://x.test'], 201)]);
        $payment = $this->startPayment();

        $this->postJson(route('webhooks.payment'), $this->midtransPayload($payment, 'settlement', '1000.00'))->assertOk();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('trial', $this->farm->fresh()->status);
    }

    public function test_halaman_selesai_mengecek_status_ke_midtrans_jika_webhook_belum_datang(): void
    {
        $this->useMidtrans();
        Http::fake(['app.sandbox.midtrans.com/*' => Http::response(['token' => 't', 'redirect_url' => 'https://x.test'], 201)]);
        $payment = $this->startPayment(6);

        Http::fake(['api.sandbox.midtrans.com/v2/*' => Http::response($this->midtransPayload($payment))]);

        $this->actingAs($this->owner)->get(route('subscription.finish', ['ref' => $payment->reference]))->assertSessionHas('success');
        $this->assertSame('2027-04-20', $this->farm->fresh()->active_until->toDateString());
    }

    public function test_pembayaran_kedaluwarsa_dicatat(): void
    {
        $this->useMidtrans();
        Http::fake(['*' => Http::response(['token' => 't', 'redirect_url' => 'https://x.test'], 201)]);
        $payment = $this->startPayment();

        $this->postJson(route('webhooks.payment'), $this->midtransPayload($payment, 'expire'))->assertOk();

        $this->assertSame('expired', $payment->fresh()->status);
    }

    public function test_pemilik_yang_masa_cobanya_habis_tetap_bisa_membayar(): void
    {
        $this->farm->update(['trial_ends_at' => '2026-10-01']);

        $this->actingAs($this->owner)->get(route('owner.dashboard'))->assertRedirect(route('subscription.show'));
        $this->actingAs($this->owner)->post(route('subscription.pay'), ['months' => 1])->assertRedirect();
    }

    public function test_peternakan_dibekukan_atau_tanpa_batas_tidak_bisa_membayar(): void
    {
        $this->farm->update(['status' => 'suspended']);
        $this->actingAs($this->owner)->post(route('subscription.pay'), ['months' => 1])->assertSessionHasErrors('months');

        $this->farm->update(['status' => 'active', 'active_until' => null]);
        $this->actingAs($this->owner)->post(route('subscription.pay'), ['months' => 1])->assertSessionHasErrors('months');

        $this->assertSame(0, SubscriptionPayment::withoutGlobalScopes()->count());
    }

    public function test_simulasi_tidak_tersedia_di_production_atau_tanpa_tanda_tangan(): void
    {
        $payment = $this->startPayment();

        $this->actingAs($this->owner)->get(route('subscription.simulate', $payment->reference))->assertForbidden();

        $this->app['env'] = 'production';
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->actingAs($this->owner)->post(route('subscription.simulate.confirm', $payment->reference), ['status' => 'paid'])->assertNotFound();
        $this->assertSame('pending', $payment->fresh()->status);
    }

    public function test_pekerja_tidak_bisa_membayar(): void
    {
        $this->actingAs($this->worker)->post(route('subscription.pay'), ['months' => 1])->assertRedirect(route('daily-logs.create'));
    }
}
