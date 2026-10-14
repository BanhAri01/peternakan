<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EggGrade;
use App\Models\EggSale;
use App\Models\Invoice;
use App\Support\Format;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
    }

    private function sale(array $overrides = [], ?array $lines = null): array
    {
        return array_merge([
            'customer_name'  => 'Bakul Bu Sari',
            'customer_phone' => '081234567890',
            'sale_date'      => now()->toDateString(),
            'payment_mode'   => 'lunas',
            'lines'          => $lines ?? [
                ['egg_grade_id' => $this->grade->id, 'unit_type' => 'kg', 'quantity_unit' => 10, 'price_per_unit' => 26000],
            ],
        ], $overrides);
    }

    public function test_penjualan_lunas_tercatat_tanpa_utang(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale())->assertRedirect(route('sales.index'));

        $invoice = Invoice::first();
        $this->assertMatchesRegularExpression('/^NT-\d{6}-0001$/', $invoice->number);
        $this->assertEquals(260000, $invoice->total);
        $this->assertEquals(0, $invoice->debt);
        $this->assertNull($invoice->due_date);
        $this->assertSame('paid', EggSale::first()->payment_status);
    }

    public function test_satu_nota_berisi_beberapa_jenis_telur(): void
    {
        $small = EggGrade::create(['name' => 'Telur Kecil', 'is_active' => true]);

        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['payment_mode' => 'sebagian', 'paid_amount' => 300000], [
            ['egg_grade_id' => $this->grade->id, 'unit_type' => 'kg', 'quantity_unit' => 10, 'price_per_unit' => 26000],   // 260.000
            ['egg_grade_id' => $small->id, 'unit_type' => 'krat', 'quantity_unit' => 2, 'price_per_unit' => 45000],         // 90.000
        ]));

        $invoice = Invoice::with('lines')->first();
        $this->assertCount(2, $invoice->lines);
        $this->assertEquals(350000, $invoice->total);
        $this->assertEquals(300000, $invoice->paid);
        $this->assertEquals(50000, $invoice->debt);
        // Uang dibagikan berurutan: baris pertama lunas, baris kedua sisa 50.000
        $this->assertSame('paid', $invoice->lines[0]->payment_status);
        $this->assertEquals(50000, $invoice->lines[1]->debt_amount);
        $this->assertEquals(3.8, $invoice->lines[1]->weight_kg);
    }

    public function test_penjualan_tempo_menjadi_piutang_dengan_jatuh_tempo_otomatis(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['payment_mode' => 'tempo']));

        $invoice = Invoice::first();
        $this->assertEquals(260000, $invoice->debt);
        $this->assertSame(now()->addDays(7)->toDateString(), $invoice->due_date->toDateString());
    }

    public function test_bayar_sebagian_wajib_isi_nominal(): void
    {
        $this->actingAs($this->owner)
            ->post(route('sales.store'), $this->sale(['payment_mode' => 'sebagian']))
            ->assertSessionHasErrors('paid_amount');

        $this->assertSame(0, Invoice::count());
    }

    public function test_nota_tanpa_baris_ditolak(): void
    {
        $this->actingAs($this->owner)
            ->post(route('sales.store'), $this->sale([], []))
            ->assertSessionHasErrors('lines');
    }

    public function test_nomor_nota_berurutan(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale());
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale());

        $this->assertStringEndsWith('-0002', Invoice::orderByDesc('id')->value('number'));
    }

    public function test_pelanggan_dengan_nama_sama_tidak_dobel(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['customer_name' => 'Bakul Bu Sari']));
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['customer_name' => '  bakul bu   sari ']));

        $this->assertSame(1, Customer::count());
        $this->assertSame(2, Customer::first()->invoices()->count());
    }

    public function test_pelunasan_nota_dan_kelebihan_bayar_masuk_ke_saldo_deposit(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['payment_mode' => 'tempo']));
        $invoice  = Invoice::first();
        $customer = Customer::first();
        $this->assertEquals(260000, $invoice->debt);

        // Pelanggan utang 260.000, tapi membayar 500.000 (kelebihan 240.000)
        $this->actingAs($this->owner)
            ->post(route('sales.pay-debt', $invoice), ['payment_add' => 500000])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertEquals(0, $invoice->fresh()->debt);
        $this->assertEquals(240000, $customer->fresh()->deposit_balance);
        $this->assertDatabaseHas('customer_deposits', [
            'customer_id'   => $customer->id,
            'invoice_id'    => $invoice->id,
            'amount'        => 240000,
            'balance_after' => 240000,
        ]);
    }

    public function test_hapus_nota_menghapus_semua_barisnya(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale());

        $this->actingAs($this->owner)->delete(route('sales.destroy', Invoice::first()))->assertSessionHas('success');

        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, EggSale::count());
    }

    public function test_nota_pdf_bisa_dicetak_di_semua_ukuran_kertas(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale());
        $invoice = Invoice::first();

        foreach (['continuous', 'a4', 'a5'] as $paper) {
            $response = $this->actingAs($this->owner)->get(route('sales.print-receipt', [$invoice, 'kertas' => $paper]));
            $response->assertOk();
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }
    }

    public function test_nomor_hp_wajib_diisi_jika_belum_lunas(): void
    {
        $payload = $this->sale(['payment_mode' => 'tempo', 'customer_phone' => null]);
        $this->actingAs($this->owner)->post(route('sales.store'), $payload)
            ->assertSessionHasErrors(['customer_phone']);

        $payloadPartial = $this->sale(['payment_mode' => 'sebagian', 'paid_amount' => 100000, 'customer_phone' => '']);
        $this->actingAs($this->owner)->post(route('sales.store'), $payloadPartial)
            ->assertSessionHasErrors(['customer_phone']);

        // Jika lunas, nomor HP boleh kosong
        $payloadLunas = $this->sale(['payment_mode' => 'lunas', 'customer_phone' => null]);
        $this->actingAs($this->owner)->post(route('sales.store'), $payloadLunas)
            ->assertSessionHasNoErrors();
    }

    public function test_kirim_nota_lewat_wa(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale());
        $invoice = Invoice::first();

        $response = $this->actingAs($this->owner)->post(route('sales.send-wa', $invoice));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_pesan_wa_menyertakan_link_portal_pelanggan(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['payment_mode' => 'tempo']));
        $invoice  = Invoice::first();
        $customer = Customer::first();

        $message = $customer->buildDebtSummaryWaMessage($invoice);

        $this->assertStringContainsString('/tagihan/' . $customer->id . '/' . $customer->portalToken(), $message);
    }

    public function test_pelanggan_bisa_membuka_halaman_portal_tagihan_tanpa_login(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['payment_mode' => 'tempo']));
        $invoice  = Invoice::first();
        $customer = Customer::first();

        // Tamu (tanpa login) membuka link portal
        auth()->logout();

        $response = $this->get(route('portal.customer', [
            'customer' => $customer->id,
            'token'    => $customer->portalToken(),
            'nota'     => $invoice->id,
        ]));

        $response->assertOk();
        $response->assertSee('Bakul Bu Sari');
        $response->assertSee('Portal Resmi Pelanggan');
        $response->assertSee($invoice->number);
    }

    public function test_portal_token_tidak_valid_ditolak_403(): void
    {
        $customer = Customer::create(['name' => 'Pak Budi', 'phone' => '0811111111']);

        $response = $this->get(route('portal.customer', [
            'customer' => $customer->id,
            'token'    => 'token-palsu-12345678',
        ]));

        $response->assertForbidden();
    }

    public function test_pelanggan_bisa_unduh_nota_pdf_dari_portal(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale());
        $invoice  = Invoice::first();
        $customer = Customer::first();

        auth()->logout();

        $response = $this->get(route('portal.receipt', [
            'customer' => $customer->id,
            'token'    => $customer->portalToken(),
            'invoice'  => $invoice->id,
        ]));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_bisa_memotong_saldo_deposit_untuk_pelunasan_nota(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['payment_mode' => 'tempo']));
        $invoice  = Invoice::first();
        $customer = Customer::first();

        // Berikan saldo deposit awal 100.000 ke pelanggan
        $customer->forceFill(['deposit_balance' => 100000])->save();

        // Bayar nota menggunakan saldo deposit
        $this->actingAs($this->owner)
            ->post(route('sales.pay-debt', $invoice), ['use_deposit' => true])
            ->assertSessionHas('success');

        $invoice->refresh();
        $customer->refresh();

        // Tagihan nota 260.000 terpotong 100.000 -> sisa 160.000
        $this->assertSame(160000.0, (float) $invoice->debt);
        $this->assertSame(0.0, (float) $customer->deposit_balance);
        $this->assertDatabaseHas('customer_deposits', [
            'customer_id' => $customer->id,
            'type'        => 'out',
            'amount'      => 100000,
        ]);
    }
    public function test_uang_tidak_hilang_saat_tombol_bayar_ditekan_dua_kali(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['payment_mode' => 'tempo']));
        $invoice  = Invoice::first();
        $customer = Customer::first();

        $this->actingAs($this->owner)->post(route('sales.pay-debt', $invoice), ['payment_add' => 300000]);
        $this->actingAs($this->owner)->post(route('sales.pay-debt', $invoice), ['payment_add' => 300000]);

        $this->assertEquals(0, $invoice->fresh()->debt);
        $this->assertEquals(340000, $customer->fresh()->deposit_balance);
    }

    public function test_titip_uang_bisa_dikembalikan_tapi_tidak_bisa_minus(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale());
        $customer = Customer::first();
        $customer->addDeposit(150000, null, 'Titip');

        $this->actingAs($this->owner)->post(route('customers.deposit-return', $customer), ['amount' => 200000, 'note' => 'salah ketik'])->assertSessionHas('danger');
        $this->actingAs($this->owner)->post(route('customers.deposit-return', $customer), ['amount' => 50000, 'note' => 'dikembalikan tunai'])->assertSessionHas('success');

        $this->assertEquals(100000, $customer->fresh()->deposit_balance);
        $this->actingAs($this->worker)->post(route('customers.deposit-return', $customer), ['amount' => 1, 'note' => 'x'])->assertRedirect();
        $this->assertEquals(100000, $customer->fresh()->deposit_balance);
    }

    public function test_pembeli_bernama_sama_tidak_menimpa_nomor_wa_pelanggan_lama(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale());
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale(['customer_phone' => '089999999999']));

        $this->assertSame('081234567890', Customer::sole()->phone);
    }

    public function test_kirim_wa_dari_tombol_ikut_aturan_paket(): void
    {
        $this->actingAs($this->owner)->post(route('sales.store'), $this->sale());
        $invoice = Invoice::first();
        $this->farm->forceFill(['plan' => 'standar'])->save();
        $this->actAsFarm($this->farm->fresh());

        $this->actingAs($this->owner->fresh())->post(route('sales.send-wa', $invoice))->assertSessionHas('danger');
        $this->assertDatabaseCount('notification_logs', 0);
    }
}
