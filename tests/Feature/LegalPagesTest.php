<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
        config([
            'hefam.admin_whatsapp' => '6285133642784',
            'hefam.business.name' => 'HEFAM (Hermes Works)',
            'hefam.business.owner' => 'Pemilik Uji',
            'hefam.business.address' => 'Jl. Uji No. 1, Bangli, Bali',
            'hefam.business.email' => 'halo@hermesworkss.com',
        ]);
    }

    public function test_semua_halaman_kebijakan_bisa_dibuka_tamu(): void
    {
        foreach (['legal.about' => 'Tentang Kami', 'legal.terms' => 'Syarat & Ketentuan', 'legal.privacy' => 'Kebijakan Privasi', 'legal.refund' => 'Kebijakan Pengembalian Dana'] as $route => $title) {
            $this->get(route($route))->assertOk()->assertSee($title)->assertSee('Midtrans');
        }
    }

    public function test_tentang_kami_menampilkan_kontak_dan_harga_rupiah(): void
    {
        $this->get(route('legal.about'))
            ->assertOk()
            ->assertSee('Jl. Uji No. 1, Bangli, Bali')
            ->assertSee('halo@hermesworkss.com')
            ->assertSee('085133642784')
            ->assertSee('Pemilik Uji')
            ->assertSee('Rp 99.000')
            ->assertSee('Rp 349.000');
    }

    public function test_halaman_depan_menautkan_semua_kebijakan_dan_kontak(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('legal.terms'))
            ->assertSee(route('legal.privacy'))
            ->assertSee(route('legal.refund'))
            ->assertSee(route('legal.about'))
            ->assertSee('halo@hermesworkss.com');
    }

    public function test_pendaftaran_menautkan_syarat_dan_privasi(): void
    {
        $this->get(route('register'))->assertOk()->assertSee(route('legal.terms'))->assertSee(route('legal.privacy'));
    }

    public function test_kontak_kosong_tidak_menampilkan_baris_kosong(): void
    {
        config(['hefam.business.address' => '', 'hefam.business.email' => '', 'hefam.business.owner' => '']);

        $this->get(route('legal.about'))->assertOk()->assertDontSee('Alamat</dt>', false)->assertDontSee('mailto:', false);
    }
}
