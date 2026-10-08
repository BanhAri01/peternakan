<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ExpenseLedger;
use App\Services\FarmFinance;
use App\Services\FarmProvisioner;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    private string $period;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-20 08:00:00'));
        $this->setUpFarm();
        $this->period = '2026-10';
        $this->worker->update(['wage_type' => 'harian', 'wage_amount' => 80000]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function mark(string $date, string $status): void
    {
        $this->actingAs($this->owner)->post(route('attendance.store'), ['work_date' => $date, 'statuses' => [$this->worker->id => $status]]);
    }

    private function pay(array $overrides = [])
    {
        return $this->actingAs($this->owner)->post(route('payroll.pay'), array_merge([
            'worker_id' => $this->worker->id, 'period' => $this->period, 'paid_at' => '2026-10-20',
            'payment_method' => 'Tunai / Kas Kecil', 'bonus' => 20000,
        ], $overrides));
    }

    public function test_pekerja_yang_mencatat_panen_otomatis_hadir(): void
    {
        $this->actingAs($this->worker)->post(route('daily-logs.store'), $this->harvestPayload());
        $this->actAsFarm($this->farm);

        $attendance = Attendance::firstOrFail();
        $this->assertSame('hadir', $attendance->status);
        $this->assertSame('otomatis', $attendance->source);
        $this->assertSame('2026-10-20', $attendance->work_date->toDateString());
    }

    public function test_pemilik_mengisi_absensi_dan_bisa_mengubah(): void
    {
        $this->actingAs($this->owner)->get(route('attendance.index'))->assertOk()->assertSee('Wayan');

        $this->mark('2026-10-19', 'sakit');
        $this->mark('2026-10-19', 'hadir');
        $this->actAsFarm($this->farm);

        $this->assertSame(1, Attendance::count());
        $this->assertSame('hadir', Attendance::first()->status);
    }

    public function test_gaji_harian_dihitung_dari_kehadiran_lalu_masuk_buku_kas(): void
    {
        foreach (['2026-10-01', '2026-10-02', '2026-10-03'] as $d) {
            $this->mark($d, 'hadir');
        }
        $this->mark('2026-10-04', 'setengah');
        $this->mark('2026-10-05', 'alpa');

        $this->actingAs($this->owner)->get(route('payroll.index', ['bulan' => $this->period]))->assertOk()->assertSee('3,5 hari')->assertSee('280.000');

        $this->pay()->assertRedirect(route('payroll.index', ['bulan' => $this->period]))->assertSessionHas('success');
        $this->actAsFarm($this->farm);

        $expense = ExpenseLedger::firstOrFail();
        $this->assertEquals(300000, $expense->total_amount);
        $this->assertSame($this->worker->id, $expense->worker_id);
        $this->assertSame('Tenaga Kerja & Gaji', $expense->category);
        $this->assertStringContainsString('3,5 hari', $expense->notes);

        $summary = FarmFinance::summary('2026-10-01', '2026-10-31');
        $this->assertEquals(300000, $summary['expenses']);
    }

    public function test_gaji_tidak_bisa_dibayar_dua_kali_tetapi_bisa_dibatalkan_dan_dipulihkan(): void
    {
        $this->mark('2026-10-01', 'hadir');
        $this->pay();
        $this->pay()->assertSessionHasErrors('worker_id');
        $this->actAsFarm($this->farm);
        $expense = ExpenseLedger::firstOrFail();
        $this->assertSame(1, ExpenseLedger::count());

        $this->actingAs($this->owner)->delete(route('expenses.destroy', $expense));
        $this->actingAs($this->owner)->get(route('payroll.index', ['bulan' => $this->period]))->assertSee('Bayar Gaji');

        $this->actingAs($this->owner)->post(route('trash.restore', ['pengeluaran', $expense->id]));
        $this->actingAs($this->owner)->get(route('payroll.index', ['bulan' => $this->period]))->assertSee('Sudah dibayar');
    }

    public function test_gaji_bulanan_tetap_walau_absen(): void
    {
        $this->worker->update(['wage_type' => 'bulanan', 'wage_amount' => 2000000]);
        $this->mark('2026-10-01', 'alpa');

        $this->pay(['bonus' => 0, 'deduction' => 100000]);
        $this->actAsFarm($this->farm);

        $this->assertEquals(1900000, ExpenseLedger::firstOrFail()->total_amount);
    }

    public function test_gaji_nol_ditolak(): void
    {
        $this->worker->update(['wage_type' => null, 'wage_amount' => null]);

        $this->pay(['bonus' => 0])->assertSessionHasErrors('bonus');
    }

    public function test_besaran_gaji_diatur_di_menu_pengguna(): void
    {
        $this->actingAs($this->owner)->put(route('users.update', $this->worker), [
            'name' => 'Wayan', 'role' => 'worker', 'wage_type' => 'bulanan', 'wage_amount' => 2500000,
        ])->assertRedirect(route('users.index'));

        $this->assertSame('bulanan', $this->worker->fresh()->wage_type);
        $this->assertEquals(2500000, $this->worker->fresh()->wage_amount);
    }

    public function test_pekerja_peternakan_lain_tidak_bisa_diabsen_atau_digaji(): void
    {
        $otherOwner = app(FarmProvisioner::class)->create([
            'farm_name' => 'Telur Jaya', 'owner_name' => 'Ibu Putu', 'email' => 'jaya@farm.test', 'password' => 'rahasia123',
        ]);
        $this->actAsFarm($otherOwner->farm);
        $stranger = \App\Models\User::create(['farm_id' => $otherOwner->farm_id, 'name' => 'Ketut Jaya', 'role' => 'worker', 'pin' => '1111', 'wage_type' => 'harian', 'wage_amount' => 50000]);

        $this->actingAs($this->owner)->post(route('attendance.store'), ['work_date' => '2026-10-19', 'statuses' => [$stranger->id => 'hadir']]);
        $this->assertSame(0, Attendance::withoutGlobalScopes()->where('user_id', $stranger->id)->count());

        $this->pay(['worker_id' => $stranger->id])->assertNotFound();
    }
}
