<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_halaman_login_menampilkan_kolom_pin_pekerja(): void
    {
        User::create(['name' => 'Made', 'role' => 'worker', 'pin' => '2580']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="pin"', false)
            ->assertSee('Made');
    }

    public function test_halaman_login_tanpa_pekerja_tetap_bisa_dibuka(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Belum ada akun pekerja');
    }
}
