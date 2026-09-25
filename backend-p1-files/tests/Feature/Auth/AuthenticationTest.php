<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test Player',
            'email' => 'player@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'player@example.com')
            ->assertJsonPath('data.roles.0', RoleName::Player->value);

        $this->assertDatabaseHas('users', [
            'email' => 'player@example.com',
        ]);
    }

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'Password123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'Password123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_invalid_login_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'Password123',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPassword',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_protected_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_user_can_access_me(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_non_admin_cannot_access_admin_endpoint(): void
    {
        $user = User::factory()->create();

        $playerRole = Role::where(
            'name',
            RoleName::Player->value
        )->firstOrFail();

        $user->roles()->attach($playerRole);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/ping')
            ->assertForbidden();
    }

    public function test_administrator_can_access_admin_endpoint(): void
    {
        $admin = User::factory()->create();

        $adminRole = Role::where(
            'name',
            RoleName::Administrator->value
        )->firstOrFail();

        $admin->roles()->attach($adminRole);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/ping')
            ->assertOk()
            ->assertJsonPath('data.authorized', true);
    }
}
