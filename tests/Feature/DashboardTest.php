<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_visit_the_summary_dashboard(): void
    {
        $user = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk()->assertSeeText('Ringkasan');
    }

    public function test_petani_go_straight_to_their_dashboard_from_the_public_site(): void
    {
        $petani = User::factory()->create(['role' => Role::Petani]);
        $this->actingAs($petani);

        $this->get(route('home'))->assertOk()->assertSee('href="'.route('dashboard').'"', false);
        $this->get(route('dashboard'))->assertRedirect(route('petani.dashboard'));
        $this->get(route('petani.dashboard'))->assertOk()
            ->assertSee('href="'.route('home').'"', false)
            ->assertSeeText('Kembali ke Beranda')
            ->assertSeeText('Kalkulator Harga Jual')
            ->assertDontSeeText('Ringkasan');
    }

    public function test_pembeli_go_straight_to_their_dashboard_with_catalog_navigation(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Pembeli]));

        $this->get(route('home'))->assertOk()->assertSee('href="'.route('dashboard').'"', false);
        $this->get(route('dashboard'))->assertRedirect(route('pembeli.dashboard'));
        $this->get(route('pembeli.dashboard'))->assertOk()
            ->assertSeeText('Dashboard Pembeli')
            ->assertSeeText('Kembali ke Beranda')
            ->assertSee('href="'.route('catalog.index').'"', false)
            ->assertSee('href="'.route('profile.edit').'"', false)
            ->assertDontSeeText('Ringkasan')
            ->assertDontSeeText('Kalkulator Harga Jual')
            ->assertDontSee('href="'.route('calculator.show').'"', false);
    }
}
