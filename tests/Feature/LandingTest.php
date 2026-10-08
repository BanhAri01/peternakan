<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
    }

    public function test_tamu_melihat_halaman_promosi_dengan_harga_dan_seo(): void
    {
        config(['hefam.admin_whatsapp' => '6281234567890']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Catatan peternakan rapi')
            ->assertSee('Rp 1.500.000')
            ->assertSee('Coba gratis 14 hari')
            ->assertSee('<meta name="description"', false)
            ->assertSee('wa.me/6281234567890', false)
            ->assertSee(route('register'));
    }

    public function test_pengguna_yang_sudah_masuk_diarahkan_sesuai_peran(): void
    {
        $admin = User::create(['name' => 'Ari', 'email' => 'admin@hefam.test', 'password' => 'admin12345', 'role' => 'superadmin']);

        $this->actingAs($this->owner)->get('/')->assertRedirect(route('owner.dashboard'));
        $this->actingAs($this->worker)->get('/')->assertRedirect(route('daily-logs.create'));
        $this->actingAs($admin)->get('/')->assertRedirect(route('admin.farms.index'));
    }
}
