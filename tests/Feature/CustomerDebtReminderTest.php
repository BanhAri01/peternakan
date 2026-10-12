<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EggSale;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerDebtReminderTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-13 09:00:00'));
        $this->setUpFarm();
        config([
            'hefam.whatsapp.driver' => 'fonnte',
            'hefam.whatsapp.fonnte_token' => 'token-fonnte-hefam-123',
        ]);
        Setting::put([
            'farm_name' => 'Sinar Telur Uji',
            'wa_reminder_enabled' => '1',
            'wa_auto_customer_debt' => '1',
        ]);

        $this->customer = Customer::create([
            'name' => 'Bakul Bu Sari',
            'phone' => '081234567890',
            'address' => 'Pasar Badung Bali',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createDueSale(string $dueDate = '2026-10-13', float $debt = 260000): Invoice
    {
        $invoice = Invoice::create([
            'number' => 'NT-202610-0099',
            'customer_id' => $this->customer->id,
            'sale_date' => '2026-10-06',
            'due_date' => $dueDate,
            'created_by' => $this->owner->id,
        ]);

        $paid = $debt <= 0 ? 260000 : 0;
        EggSale::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'egg_grade_id' => $this->grade->id,
            'sale_date' => '2026-10-06',
            'unit_type' => 'kg',
            'quantity_unit' => 10,
            'price_per_unit' => 26000,
            'weight_kg' => 10,
            'price_per_kg' => 26000,
            'total_amount' => 260000,
            'paid_amount' => $paid,
            'debt_amount' => $debt,
            'payment_status' => $debt <= 0 ? 'paid' : 'unpaid',
            'due_date' => $dueDate,
        ]);

        return $invoice;
    }

    public function test_mengirim_tagihan_otomatis_ke_whatsapp_pelanggan_saat_jatuh_tempo_antara_07_sampai_19(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true, 'id' => ['fonnte-123']])]);
        $this->createDueSale();

        // Eksekusi scheduler perintah tagih pelanggan
        $this->artisan('hefam:tagih-pelanggan')
            ->assertSuccessful()
            ->expectsOutputToContain('1 pesan ke pembeli');

        // Pastikan Fonnte dipanggil dengan token dan target nomor WA pembeli
        Http::assertSent(function ($request) {
            $isFonnteUrl = str_contains($request->url(), 'api.fonnte.com');
            $hasAuth = $request->hasHeader('Authorization', 'token-fonnte-hefam-123');
            $targetWa = $request['target'] === '6281234567890';
            $msg = $request['message'];

            $containsTitle = str_contains($msg, 'PEMBERITAHUAN TAGIHAN TELUR');
            $containsCustomer = str_contains($msg, 'Bakul Bu Sari');
            $containsOverdue = str_contains($msg, 'SUDAH JATUH TEMPO');
            $containsPortal = str_contains($msg, '/tagihan/' . $this->customer->id);

            return $isFonnteUrl && $hasAuth && $targetWa && $containsTitle && $containsCustomer && $containsOverdue && $containsPortal;
        });

        // Pastikan invoice & baris penjualan ditandai last_reminded_at
        $this->actAsFarm($this->farm);
        $invoice = Invoice::first();
        $this->assertNotNull($invoice->last_reminded_at);
        $this->assertNotNull($invoice->lines->first()->last_reminded_at);

        // Pastikan log tersimpan
        $log = NotificationLog::where('kind', 'tagih_' . $this->customer->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('terkirim', $log->status);
    }

    public function test_pesan_tagihan_memperhitungkan_saldo_titip_uang_deposit(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true])]);
        $this->customer->update(['deposit_balance' => 60000]);
        $this->createDueSale('2026-10-13', 260000); // 260.000 - deposit 60.000 = bersih 200.000

        $this->artisan('hefam:tagih-pelanggan')->assertSuccessful();

        Http::assertSent(function ($request) {
            $msg = $request['message'];
            return str_contains($msg, 'TITIP UANG / DEPOSIT: - Rp 60.000')
                && str_contains($msg, 'TOTAL BERSIH YANG HARUS DIBAYAR : Rp 200.000');
        });
    }

    public function test_tidak_mengirim_di_luar_jam_operasional_07_sampai_19_tanpa_opsi_paksa(): void
    {
        Http::fake();
        $this->createDueSale();

        // Set waktu ke jam 20:30 malam (di luar jam 07:00 - 19:00)
        Carbon::setTestNow(Carbon::parse('2026-10-13 20:30:00'));

        $this->artisan('hefam:tagih-pelanggan')
            ->assertSuccessful()
            ->expectsOutputToContain('di luar jam kirim pembeli (07:00 - 19:00)');

        Http::assertNothingSent();
    }

    public function test_bisa_mengirim_di_luar_jam_operasional_bila_memakai_opsi_paksa(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true])]);
        $this->createDueSale();

        // Set waktu jam 22:00 malam tapi gunakan --paksa
        Carbon::setTestNow(Carbon::parse('2026-10-13 22:00:00'));

        $this->artisan('hefam:tagih-pelanggan', ['--paksa' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('1 pesan ke pembeli');

        Http::assertSentCount(1);
    }

    public function test_tidak_mengirim_bila_jatuh_tempo_belum_tiba(): void
    {
        Http::fake();
        // Jatuh tempo lusa (2026-10-15)
        $this->createDueSale('2026-10-15');

        $this->artisan('hefam:tagih-pelanggan')
            ->assertSuccessful()
            ->expectsOutputToContain('0 pesan ke pembeli');

        Http::assertNothingSent();
    }

    public function test_tidak_mengirim_bila_tagihan_sudah_lunas(): void
    {
        Http::fake();
        $this->createDueSale('2026-10-13', 0);

        $this->artisan('hefam:tagih-pelanggan')
            ->assertSuccessful()
            ->expectsOutputToContain('0 pesan ke pembeli');

        Http::assertNothingSent();
    }

    public function test_tidak_mengulang_kirim_di_hari_yang_sama_kecuali_paksa_atau_setelah_7_hari(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true])]);
        $this->createDueSale('2026-10-13');

        // Panggilan pertama terkirim
        $this->artisan('hefam:tagih-pelanggan')->expectsOutputToContain('1 pesan');
        Http::assertSentCount(1);

        // Panggilan kedua di hari yang sama diabaikan agar tidak spam
        $this->artisan('hefam:tagih-pelanggan')->expectsOutputToContain('0 pesan');
        Http::assertSentCount(1);

        // Maju 7 hari kemudian jika masih belum lunas
        Carbon::setTestNow(Carbon::parse('2026-10-20 09:00:00'));
        $this->artisan('hefam:tagih-pelanggan')->expectsOutputToContain('1 pesan');
        Http::assertSentCount(2);
    }

    public function test_bisa_dinonaktifkan_lewat_pengaturan(): void
    {
        Http::fake();
        Setting::put(['wa_auto_customer_debt' => '0']);
        $this->createDueSale('2026-10-13');

        $this->artisan('hefam:tagih-pelanggan')
            ->assertSuccessful()
            ->expectsOutputToContain('tagih pembeli dinonaktifkan');

        Http::assertNothingSent();
    }
}
