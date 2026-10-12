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
            ->assertSee('Rp 349.000')->assertSee('Entrepreneur')
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

    public function test_tamu_melihat_harga_pasar_komoditas_dengan_fokus_bali_dan_pinsar(): void
    {
        $response = $this->get('/');
        $response->assertOk()
            ->assertSee('Harga Pasar Telur, Ayam', false)
            ->assertSee('Rujukan resmi Pinsar Indonesia', false)
            ->assertSee('Bali (Tabanan, Badung', false)
            ->assertSee('Kalkulator Kebutuhan Pakan', false);
    }

    public function test_api_market_prices_mengembalikan_json_dengan_prioritas_bali(): void
    {
        $response = $this->get(route('api.market.prices'));
        $response->assertOk()
            ->assertJsonStructure([
                'last_updated',
                'primary_source',
                'regions' => [
                    '*' => [
                        'region_code',
                        'region_name',
                        'is_primary',
                        'items' => [
                            '*' => [
                                'commodity_code',
                                'commodity_name',
                                'price',
                                'price_formatted',
                                'unit',
                            ],
                        ],
                    ],
                ],
            ]);

        $data = $response->json();
        $this->assertSame('bali', $data['regions'][0]['region_code']);
        $this->assertTrue($data['regions'][0]['is_primary']);
    }
}
