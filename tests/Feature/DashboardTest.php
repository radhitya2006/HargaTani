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

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
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
            ->assertDontSeeText('Ringkasan');
    }
}
