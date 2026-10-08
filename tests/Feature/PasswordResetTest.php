<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
    }

    public function test_lupa_sandi_mengarahkan_ke_whatsapp_admin_tanpa_email(): void
    {
        config(['hefam.admin_whatsapp' => '6285133642784']);

        $this->get(route('login'))->assertOk()->assertSee(route('password.request'));
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('wa.me/6285133642784', false)
            ->assertSee('Chat Admin HEFAM')
            ->assertDontSee('name="email"', false);
    }

    public function test_tanpa_nomor_admin_tetap_ada_petunjuk(): void
    {
        config(['hefam.admin_whatsapp' => '']);

        $this->get(route('password.request'))->assertOk()->assertSee('Hubungi admin HEFAM')->assertDontSee('wa.me', false);
    }

    public function test_alur_reset_lewat_email_sudah_tidak_ada(): void
    {
        $this->post('/lupa-sandi', ['email' => 'owner@farm.test'])->assertStatus(405);
        $this->get('/atur-ulang-sandi/token-apa-saja')->assertNotFound();
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
