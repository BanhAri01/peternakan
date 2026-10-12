<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EggGrade;
use App\Models\EggSale;
use App\Models\Farm;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalSecurityTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    private Customer $customerA;
    private Customer $customerB;
    private Farm $farmB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();

        // Pelanggan di Farm A
        $this->customerA = Customer::create([
            'name'            => 'Toko Berkah Gianyar',
            'phone'           => '081234567890',
            'deposit_balance' => 150000,
        ]);

        // Farm B dan Pelanggan B untuk uji isolasi multi-tenant
        $this->farmB = Farm::create(['name' => 'Farm Sukses', 'status' => 'active']);
        $this->actAsFarm($this->farmB);

        $this->customerB = Customer::create([
            'name'            => 'Toko Rejeki Tabanan',
            'phone'           => '089876543210',
            'deposit_balance' => 50000,
        ]);

        $this->actAsFarm($this->farm);
    }

    public function test_akses_portal_dengan_token_valid_berhasil(): void
    {
        $token = $this->customerA->portalToken();

        $response = $this->get(route('portal.customer', [
            'customer' => $this->customerA->id,
            'token'    => $token,
        ]));

        $response->assertOk()
            ->assertSee('Toko Berkah Gianyar')
            ->assertSee('Titip Uang (Deposit)')
            ->assertSee('150.000');
    }

    public function test_akses_portal_dengan_token_palsu_ditolak_403(): void
    {
        $response = $this->get(route('portal.customer', [
            'customer' => $this->customerA->id,
            'token'    => 'token-palsu-123456789abcdef',
        ]));

        $response->assertForbidden();
    }

    public function test_tidak_bisa_mengakses_nota_milik_pelanggan_lain_lewat_portal(): void
    {
        $grade = EggGrade::create(['name' => 'Telur Normal', 'code' => 'N', 'is_active' => true]);

        // Invoice milik Customer A
        $invA = Invoice::create([
            'number'      => 'NT-TEST-0001',
            'customer_id' => $this->customerA->id,
            'sale_date'   => today()->toDateString(),
            'due_date'    => today()->addDays(7)->toDateString(),
        ]);
        EggSale::create([
            'invoice_id'     => $invA->id,
            'customer_id'    => $this->customerA->id,
            'egg_grade_id'   => $grade->id,
            'sale_date'      => today()->toDateString(),
            'unit_type'      => 'krat',
            'quantity_unit'  => 10,
            'price_per_unit' => 50000,
            'total_amount'   => 500000,
            'paid_amount'    => 200000,
            'debt_amount'    => 300000,
            'payment_status' => 'partial',
        ]);

        // Invoice milik Customer B di Farm B
        $this->actAsFarm($this->farmB);
        $gradeB = EggGrade::create(['name' => 'Telur B', 'code' => 'B2', 'is_active' => true]);
        $invB = Invoice::create([
            'number'      => 'NT-TEST-0002',
            'customer_id' => $this->customerB->id,
            'sale_date'   => today()->toDateString(),
        ]);
        EggSale::create([
            'invoice_id'     => $invB->id,
            'customer_id'    => $this->customerB->id,
            'egg_grade_id'   => $gradeB->id,
            'sale_date'      => today()->toDateString(),
            'unit_type'      => 'krat',
            'quantity_unit'  => 5,
            'price_per_unit' => 50000,
            'total_amount'   => 250000,
            'paid_amount'    => 0,
            'debt_amount'    => 250000,
            'payment_status' => 'unpaid',
        ]);
        $this->actAsFarm($this->farm);

        // Percobaan 1: Pelanggan A mencoba unduh nota milik Pelanggan B menggunakan token Pelanggan A
        $response = $this->get(route('portal.receipt', [
            'customer' => $this->customerA->id,
            'token'    => $this->customerA->portalToken(),
            'invoice'  => $invB->id,
        ]));

        $response->assertNotFound();

        // Percobaan 2: Mengakses dengan ID Pelanggan B tetapi token Pelanggan A
        $response2 = $this->get(route('portal.customer', [
            'customer' => $this->customerB->id,
            'token'    => $this->customerA->portalToken(),
        ]));

        $response2->assertForbidden();
    }

    public function test_portal_menampilkan_perhitungan_utang_bersih_yang_akurat(): void
    {
        $grade = EggGrade::create(['name' => 'Telur Normal', 'code' => 'N', 'is_active' => true]);

        // Buat 1 invoice jatuh tempo dan 1 invoice belum jatuh tempo
        $invOverdue = Invoice::create([
            'number'         => 'NT-OVERDUE-01',
            'customer_id'    => $this->customerA->id,
            'sale_date'      => today()->subDays(10)->toDateString(),
            'due_date'       => today()->subDays(3)->toDateString(),
        ]);
        EggSale::create([
            'invoice_id'     => $invOverdue->id,
            'customer_id'    => $this->customerA->id,
            'egg_grade_id'   => $grade->id,
            'sale_date'      => today()->subDays(10)->toDateString(),
            'due_date'       => today()->subDays(3)->toDateString(),
            'unit_type'      => 'krat',
            'quantity_unit'  => 10,
            'price_per_unit' => 50000,
            'total_amount'   => 500000,
            'paid_amount'    => 200000,
            'debt_amount'    => 300000, // Utang jatuh tempo 300.000
            'payment_status' => 'partial',
        ]);

        $invUpcoming = Invoice::create([
            'number'         => 'NT-UPCOMING-01',
            'customer_id'    => $this->customerA->id,
            'sale_date'      => today()->toDateString(),
            'due_date'       => today()->addDays(5)->toDateString(),
        ]);
        EggSale::create([
            'invoice_id'     => $invUpcoming->id,
            'customer_id'    => $this->customerA->id,
            'egg_grade_id'   => $grade->id,
            'sale_date'      => today()->toDateString(),
            'due_date'       => today()->addDays(5)->toDateString(),
            'unit_type'      => 'krat',
            'quantity_unit'  => 5,
            'price_per_unit' => 50000,
            'total_amount'   => 250000,
            'paid_amount'    => 50000,
            'debt_amount'    => 200000, // Utang belum jatuh tempo 200.000
            'payment_status' => 'partial',
        ]);

        // Customer deposit adalah 150.000
        // Total utang kotor = 500.000
        // Sisa kewajiban bersih = 500.000 - 150.000 = 350.000

        $response = $this->get(route('portal.customer', [
            'customer' => $this->customerA->id,
            'token'    => $this->customerA->portalToken(),
        ]));

        $response->assertOk()
            ->assertSee('Sudah Jatuh Tempo')
            ->assertSee('Belum Jatuh Tempo')
            ->assertSee('350.000') // Net remaining debt
            ->assertSee('150.000'); // Deposit balance
    }

    public function test_portal_ditolak_jika_peternakan_dibekukan(): void
    {
        $this->farm->update(['status' => 'suspended']);

        $response = $this->get(route('portal.customer', [
            'customer' => $this->customerA->id,
            'token'    => $this->customerA->portalToken(),
        ]));

        $response->assertForbidden();
    }
}
