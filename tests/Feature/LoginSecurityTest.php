<?php

namespace Tests\Feature;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->farm = Farm::create(['name' => 'Peternakan Uji', 'status' => 'active']);
        $this->actAsFarm($this->farm);
    }

    private function worker(array $attrs = []): User
    {
        return User::create(array_merge([
            'farm_id' => $this->farm->id,
            'name'    => 'Made',
            'role'    => 'worker',
            'pin'     => '2580',
        ], $attrs));
    }

    private function owner(): User
    {
        return User::create([
            'farm_id'  => $this->farm->id,
            'name'     => 'Owner',
            'role'     => 'owner',
            'email'    => 'owner@farm.test',
            'password' => 'rahasia123',
        ]);
    }

    // ---------------------------------------------------------------
    // Login pekerja (di HP kandang)
    // ---------------------------------------------------------------

    public function test_pekerja_bisa_login_dengan_pin_yang_benar_di_hp_kandang(): void
    {
        $worker = $this->worker();
        $this->useFarmDevice();

        $this->post(route('login.worker'), ['worker_id' => $worker->id, 'pin' => '2580'])
            ->assertRedirect(route('daily-logs.create'));

        $this->assertAuthenticatedAs($worker);
    }

    public function test_pekerja_tidak_bisa_login_di_hp_yang_belum_didaftarkan(): void
    {
        $worker = $this->worker();

        $this->post(route('login.worker'), ['worker_id' => $worker->id, 'pin' => '2580'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_daftar_nama_pekerja_hanya_muncul_di_hp_kandang(): void
    {
        $this->worker(['name' => 'Ketut Sukarja']);

        $this->get(route('login'))->assertOk()->assertDontSee('Ketut Sukarja');

        $this->useFarmDevice();
        $this->get(route('login'))->assertOk()->assertSee('Ketut Sukarja');
    }

    public function test_hp_kandang_tidak_menampilkan_atau_menerima_pekerja_peternakan_lain(): void
    {
        $other       = Farm::create(['name' => 'Peternakan Lain', 'status' => 'active']);
        $otherWorker = User::create(['farm_id' => $other->id, 'name' => 'Pekerja Lain', 'role' => 'worker', 'pin' => '1111']);
        $this->useFarmDevice();

        $this->get(route('login'))->assertDontSee('Pekerja Lain');

        $this->post(route('login.worker'), ['worker_id' => $otherWorker->id, 'pin' => '1111'])
            ->assertSessionHasErrors('pin');
        $this->assertGuest();
    }

    public function test_pekerja_tidak_bisa_login_tanpa_pin(): void
    {
        $worker = $this->worker();
        $this->useFarmDevice();

        $this->post(route('login.worker'), ['worker_id' => $worker->id])->assertSessionHasErrors('pin');

        $this->assertGuest();
    }

    public function test_pekerja_tidak_bisa_login_dengan_pin_salah(): void
    {
        $worker = $this->worker();
        $this->useFarmDevice();

        $this->post(route('login.worker'), ['worker_id' => $worker->id, 'pin' => '0000'])->assertSessionHasErrors('pin');

        $this->assertGuest();
    }

    public function test_pekerja_yang_belum_punya_pin_tidak_bisa_login(): void
    {
        $worker = $this->worker(['pin' => null]);
        $this->useFarmDevice();

        $this->post(route('login.worker'), ['worker_id' => $worker->id, 'pin' => '1234'])->assertSessionHasErrors('pin');

        $this->assertGuest();
    }

    public function test_akun_owner_tidak_bisa_dipakai_lewat_login_pekerja(): void
    {
        $owner = $this->owner();
        $owner->forceFill(['pin' => '2580'])->save();
        $this->useFarmDevice();

        $this->post(route('login.worker'), ['worker_id' => $owner->id, 'pin' => '2580'])->assertSessionHasErrors('pin');

        $this->assertGuest();
    }

    public function test_login_pekerja_dikunci_setelah_5_kali_gagal(): void
    {
        $worker = $this->worker();
        $this->useFarmDevice();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.worker'), ['worker_id' => $worker->id, 'pin' => '9999']);
        }

        // PIN benar pun ditolak selama masa kunci
        $this->post(route('login.worker'), ['worker_id' => $worker->id, 'pin' => '2580'])->assertSessionHasErrors('pin');

        $this->assertGuest();
    }

    public function test_pin_disimpan_dalam_bentuk_hash(): void
    {
        $worker = $this->worker();

        $this->assertNotSame('2580', $worker->getRawOriginal('pin'));
        $this->assertTrue(Hash::check('2580', $worker->pin));
    }

    // ---------------------------------------------------------------
    // Login owner & admin
    // ---------------------------------------------------------------

    public function test_owner_bisa_login_dengan_password_yang_benar(): void
    {
        $owner = $this->owner();

        $this->post(route('login.post'), ['email' => 'Owner@Farm.test', 'password' => 'rahasia123'])->assertRedirect('/');

        $this->assertAuthenticatedAs($owner);
        $this->get('/')->assertRedirect(route('owner.dashboard'));
    }

    public function test_pekerja_tidak_bisa_login_lewat_form_email(): void
    {
        $this->worker(['email' => 'pekerja@farm.test', 'password' => 'rahasia123']);

        $this->post(route('login.post'), ['email' => 'pekerja@farm.test', 'password' => 'rahasia123'])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_owner_dikunci_setelah_5_kali_gagal(): void
    {
        $this->owner();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.post'), ['email' => 'owner@farm.test', 'password' => 'salah']);
        }

        $this->post(route('login.post'), ['email' => 'owner@farm.test', 'password' => 'rahasia123'])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    // ---------------------------------------------------------------
    // Owner mengelola PIN pekerja
    // ---------------------------------------------------------------

    public function test_owner_wajib_mengisi_pin_saat_menambah_pekerja(): void
    {
        $this->actingAs($this->owner());

        $this->post(route('users.store'), ['name' => 'Wayan', 'role' => 'worker'])->assertSessionHasErrors('pin');

        $this->post(route('users.store'), ['name' => 'Wayan', 'role' => 'worker', 'pin' => '1357'])->assertRedirect(route('users.index'));

        $wayan = User::where('name', 'Wayan')->first();
        $this->assertTrue(Hash::check('1357', $wayan->pin));
        $this->assertSame($this->farm->id, $wayan->farm_id);
    }

    public function test_owner_bisa_mengganti_pin_pekerja_dan_pin_lama_tidak_berlaku(): void
    {
        $this->actingAs($this->owner());
        $worker = $this->worker();

        $this->put(route('users.update', $worker), ['name' => 'Made', 'role' => 'worker', 'pin' => '4321'])->assertRedirect(route('users.index'));

        $worker->refresh();
        $this->assertTrue(Hash::check('4321', $worker->pin));
        $this->assertFalse(Hash::check('2580', $worker->pin));
    }

    public function test_pin_tidak_berubah_jika_dikosongkan_saat_edit(): void
    {
        $this->actingAs($this->owner());
        $worker = $this->worker();

        $this->put(route('users.update', $worker), ['name' => 'Made Baru', 'role' => 'worker', 'pin' => ''])->assertRedirect(route('users.index'));

        $this->assertTrue(Hash::check('2580', $worker->refresh()->pin));
    }
}
