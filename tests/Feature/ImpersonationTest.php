<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();
        $this->admin = User::create(['name' => 'Ari', 'email' => 'admin@hefam.test', 'password' => 'admin12345', 'role' => 'superadmin']);
    }

    public function test_admin_masuk_sebagai_pemilik_lalu_kembali(): void
    {
        $this->actingAs($this->admin)->post(route('admin.farms.impersonate', $this->farm))->assertRedirect(route('owner.dashboard'));
        $this->assertAuthenticatedAs($this->owner);

        $this->get(route('owner.dashboard'))->assertOk()->assertSee('Mode bantuan admin')->assertSee('Kembali ke Admin');

        $this->post(route('impersonate.stop'))->assertRedirect(route('admin.farms.edit', $this->farm->id));
        $this->assertAuthenticatedAs($this->admin);
        $this->get(route('admin.farms.index'))->assertOk()->assertDontSee('Mode bantuan admin');
    }

    public function test_perubahan_selama_bantuan_tercatat_atas_nama_admin(): void
    {
        $this->actingAs($this->admin)->post(route('admin.farms.impersonate', $this->farm));
        $this->put(route('coops.update', $this->coop), [
            'name' => 'Kandang Diperbaiki', 'capacity' => 1200, 'initial_population' => 1000, 'current_population' => 1000,
            'strain' => 'Isa Brown', 'chick_in_date' => $this->coop->chick_in_date->toDateString(), 'initial_age_weeks' => 18, 'status' => 'active',
        ]);
        $this->post(route('impersonate.stop'));

        $this->actAsFarm($this->farm);
        $this->assertSame('Pemilik (dibantu admin HEFAM)', ActivityLog::where('event', 'updated')->where('subject_type', 'Coop')->value('user_name'));
        $this->assertSame(1, ActivityLog::where('event', 'impersonate')->count());
        $this->assertSame(1, ActivityLog::where('event', 'impersonate_end')->count());
    }

    public function test_pemilik_tidak_bisa_memakai_tombol_kembali_untuk_naik_jadi_admin(): void
    {
        $this->actingAs($this->owner)->post(route('impersonate.stop'))->assertForbidden();
        $this->assertAuthenticatedAs($this->owner);
    }

    public function test_pemilik_tidak_bisa_masuk_sebagai_pemilik_lain(): void
    {
        $this->actingAs($this->owner)->post(route('admin.farms.impersonate', $this->farm))->assertForbidden();
    }

    public function test_tombol_masuk_tampil_di_panel_admin(): void
    {
        $this->actingAs($this->admin)->get(route('admin.farms.edit', $this->farm))->assertOk()->assertSee('Masuk sebagai pemilik');
    }
}
