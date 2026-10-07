<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function worker(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'Made',
            'role' => 'worker',
            'pin'  => '2580',
        ], $attrs));
    }

    private function owner(): User
    {
        return User::create([
            'name'     => 'Owner',
            'role'     => 'owner',
            'email'    => 'owner@farm.test',
            'password' => 'rahasia123',
        ]);
    }

    // ---------------------------------------------------------------
    // Login pekerja
    // ---------------------------------------------------------------

    public function test_pekerja_bisa_login_dengan_pin_yang_benar(): void
    {
        $worker = $this->worker();

        $this->post(route('login.worker'), ['worker_id' => $worker->id, 'pin' => '2580'])
            ->assertRedirect(route('daily-logs.create'));

        $this->assertAuthenticatedAs($worker);
    }

    public function test_pekerja_tidak_bisa_login_tanpa_pin(): void
    {
        $worker = $this->worker();

        $this->post(route('login.worker'), ['worker_id' => $worker->id])
            ->assertSessionHasErrors('pin');

        $this->assertGuest();
    }

    public function test_pekerja_tidak_bisa_login_dengan_pin_salah(): void
    {
        $worker = $this->worker();

        $this->post(route('login.worker'), ['worker_id' => $worker->id, 'pin' => '0000'])
            ->assertSessionHasErrors('pin');

        $this->assertGuest();
    }

    public function test_login_lama_dengan_nama_saja_tidak_berlaku_lagi(): void
    {
        $this->worker();

        $this->post(route('login.worker'), ['name' => 'Made'])
            ->assertSessionHasErrors(['worker_id', 'pin']);

        $this->assertGuest();
    }

    public function test_pekerja_yang_belum_punya_pin_tidak_bisa_login(): void
    {
        $worker = $this->worker(['pin' => null]);

        $this->post(route('login.worker'), ['worker_id' => $worker->id, 'pin' => '1234'])
            ->assertSessionHasErrors('pin');

        $this->assertGuest();
    }

    public function test_akun_owner_tidak_bisa_dipakai_lewat_login_pekerja(): void
    {
        $owner = $this->owner();
        $owner->forceFill(['pin' => '2580'])->save();

        $this->post(route('login.worker'), ['worker_id' => $owner->id, 'pin' => '2580'])
            ->assertSessionHasErrors('pin');

        $this->assertGuest();
    }

    public function test_login_pekerja_dikunci_setelah_5_kali_gagal(): void
    {
        $worker = $this->worker();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.worker'), ['worker_id' => $worker->id, 'pin' => '9999']);
        }

        // PIN benar pun ditolak selama masa kunci
        $this->post(route('login.worker'), ['worker_id' => $worker->id, 'pin' => '2580'])
            ->assertSessionHasErrors('pin');

        $this->assertGuest();
    }

    public function test_pin_disimpan_dalam_bentuk_hash(): void
    {
        $worker = $this->worker();

        $this->assertNotSame('2580', $worker->getRawOriginal('pin'));
        $this->assertTrue(Hash::check('2580', $worker->pin));
    }

    // ---------------------------------------------------------------
    // Login owner
    // ---------------------------------------------------------------

    public function test_owner_bisa_login_dengan_password_yang_benar(): void
    {
        $owner = $this->owner();

        $this->post(route('login.post'), ['email' => 'owner@farm.test', 'password' => 'rahasia123'])
            ->assertRedirect(route('owner.dashboard'));

        $this->assertAuthenticatedAs($owner);
    }

    public function test_login_owner_dikunci_setelah_5_kali_gagal(): void
    {
        $this->owner();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.post'), ['email' => 'owner@farm.test', 'password' => 'salah']);
        }

        $this->post(route('login.post'), ['email' => 'owner@farm.test', 'password' => 'rahasia123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    // ---------------------------------------------------------------
    // Owner mengelola PIN pekerja
    // ---------------------------------------------------------------

    public function test_owner_wajib_mengisi_pin_saat_menambah_pekerja(): void
    {
        $this->actingAs($this->owner());

        $this->post(route('users.store'), ['name' => 'Wayan', 'role' => 'worker'])
            ->assertSessionHasErrors('pin');

        $this->post(route('users.store'), ['name' => 'Wayan', 'role' => 'worker', 'pin' => '1357'])
            ->assertRedirect(route('users.index'));

        $wayan = User::where('name', 'Wayan')->first();
        $this->assertTrue(Hash::check('1357', $wayan->pin));
    }

    public function test_owner_bisa_mengganti_pin_pekerja_dan_pin_lama_tidak_berlaku(): void
    {
        $this->actingAs($this->owner());
        $worker = $this->worker();

        $this->put(route('users.update', $worker), ['name' => 'Made', 'role' => 'worker', 'pin' => '4321'])
            ->assertRedirect(route('users.index'));

        $worker->refresh();
        $this->assertTrue(Hash::check('4321', $worker->pin));
        $this->assertFalse(Hash::check('2580', $worker->pin));
    }

    public function test_pin_tidak_berubah_jika_dikosongkan_saat_edit(): void
    {
        $this->actingAs($this->owner());
        $worker = $this->worker();

        $this->put(route('users.update', $worker), ['name' => 'Made Baru', 'role' => 'worker', 'pin' => ''])
            ->assertRedirect(route('users.index'));

        $this->assertTrue(Hash::check('2580', $worker->refresh()->pin));
    }
}
