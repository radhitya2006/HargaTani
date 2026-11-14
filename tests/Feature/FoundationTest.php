<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_can_only_access_its_own_dashboard(): void
    {
        foreach (Role::cases() as $role) {
            $user = User::factory()->create(['role' => $role]);
            foreach (Role::cases() as $destination) {
                $response = $this->actingAs($user)->get(route($destination->value.'.dashboard'));
                $response->assertStatus($role === $destination ? 200 : 403);
            }
        }
    }

    public function test_guests_must_login_for_all_role_dashboards(): void
    {
        foreach (Role::cases() as $role) {
            $this->get(route($role->value.'.dashboard'))->assertRedirect(route('login'));
        }
    }

    public function test_public_registration_rejects_admin_unknown_or_missing_roles(): void
    {
        foreach (['admin', 'superadmin', null] as $role) {
            $this->post(route('register.store'), [
                'name' => 'Test', 'email' => 'attacker@example.com',
                'password' => 'password', 'password_confirmation' => 'password', 'role' => $role,
            ])->assertSessionHasErrors('role');
            $this->assertGuest();
        }
        $this->assertDatabaseCount('users', 0);
    }

    public function test_petani_registration_ignores_privilege_fields_and_hashes_password(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Petani', 'email' => 'petani@example.com', 'role' => 'petani',
            'password' => 'password', 'password_confirmation' => 'password', 'is_active' => false,
        ])->assertSessionHasNoErrors();
        $user = User::firstOrFail();
        $this->assertSame(Role::Petani, $user->role);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_inactive_user_cannot_login_and_existing_session_is_logged_out(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_user_policy_protects_other_profiles_and_role_is_not_mass_assignable(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->assertTrue(Gate::forUser($user)->allows('update', $user));
        $this->assertFalse(Gate::forUser($user)->allows('update', $other));
        $this->assertFalse(Gate::forUser($user)->allows('view', $other));
        $this->assertFalse($user->isFillable('role'));
        $this->assertFalse($user->isFillable('is_active'));
    }

    public function test_admin_seeder_is_idempotent_and_does_not_reset_existing_account(): void
    {
        config(['seeding.admin_email' => 'admin@example.com', 'seeding.admin_password' => 'testing-password-long']);
        $this->seed(AdminSeeder::class);
        $user = User::firstOrFail();
        $this->assertSame(Role::Admin, $user->role);
        $this->assertTrue(Hash::check('testing-password-long', $user->password));
        $user->is_active = false;
        $user->save();
        config(['seeding.admin_password' => 'different-password-long']);
        $this->seed(AdminSeeder::class);
        $this->assertDatabaseCount('users', 1);
        $this->assertFalse($user->fresh()->is_active);
        $this->assertTrue(Hash::check('testing-password-long', $user->fresh()->password));
    }

    public function test_admin_seeder_refuses_to_promote_existing_public_account(): void
    {
        $user = User::factory()->create();
        config(['seeding.admin_email' => $user->email, 'seeding.admin_password' => 'testing-password-long']);
        $this->expectException(\RuntimeException::class);
        $this->seed(AdminSeeder::class);
    }
}
