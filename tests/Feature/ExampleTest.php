<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_halaman_login_di_hp_biasa_menjelaskan_cara_daftar_hp_kandang(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('HP ini belum didaftarkan sebagai HP kandang')
            ->assertSee('name="email"', false);
    }

    public function test_halaman_daftar_bisa_dibuka(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Daftarkan peternakan Anda');
    }
}
