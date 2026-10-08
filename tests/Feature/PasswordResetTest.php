<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
    }

    public function test_halaman_lupa_sandi_bisa_dibuka_dari_login(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(route('password.request'));
        $this->get(route('password.request'))->assertOk()->assertSee('Lupa kata sandi');
    }

    public function test_pemilik_menerima_email_tautan_atur_ulang(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'OWNER@farm.test'])->assertSessionHas('success');

        Notification::assertSentTo($this->owner, ResetPasswordNotification::class);
    }

    public function test_email_tidak_terdaftar_mendapat_pesan_yang_sama(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'tidakada@farm.test'])->assertSessionHas('success');

        Notification::assertNothingSent();
    }

    public function test_pemilik_bisa_mengganti_sandi_dengan_tautan(): void
    {
        $token = Password::createToken($this->owner);

        $this->post(route('password.update'), [
            'token' => $token, 'email' => 'owner@farm.test',
            'password' => 'sandiBaru123', 'password_confirmation' => 'sandiBaru123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('sandiBaru123', $this->owner->fresh()->password));
        $this->actAsFarm($this->farm);
        $this->assertSame(1, ActivityLog::where('event', 'password')->count());

        $this->post(route('login.post'), ['email' => 'owner@farm.test', 'password' => 'sandiBaru123'])->assertRedirect('/');
    }

    public function test_tautan_palsu_ditolak(): void
    {
        $this->post(route('password.update'), [
            'token' => 'palsu', 'email' => 'owner@farm.test',
            'password' => 'sandiBaru123', 'password_confirmation' => 'sandiBaru123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('rahasia123', $this->owner->fresh()->password));
    }

    public function test_admin_membuat_sandi_baru_untuk_pemilik(): void
    {
        $admin = User::create(['name' => 'Ari', 'email' => 'admin@hefam.test', 'password' => 'admin12345', 'role' => 'superadmin']);

        $response = $this->actingAs($admin)->post(route('admin.farms.reset-password', $this->farm))->assertSessionHas('new_password');
        $password = $response->getSession()->get('new_password')['password'];

        $this->assertTrue(Hash::check($password, $this->owner->fresh()->password));
        $this->actingAs($admin)->get(route('admin.farms.edit', $this->farm))->assertOk();
    }

    public function test_pemilik_tidak_bisa_memakai_fitur_admin(): void
    {
        $this->actingAs($this->owner)->post(route('admin.farms.reset-password', $this->farm))->assertForbidden();
    }
}
