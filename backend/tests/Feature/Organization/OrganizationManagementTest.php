<?php

namespace Tests\Feature\Organization;

use App\Enums\RoleName;
use App\Models\Club;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);
    }

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $role = Role::where('name', $roleName)->firstOrFail();
        $user->roles()->attach($role);

        return $user;
    }

    private function createOrganization(User $creator, string $name = 'Test Organization'): Organization
    {
        $organization = Organization::create([
            'name' => $name,
            'short_name' => 'TO',
            'country' => 'Sri Lanka',
            'timezone' => 'Asia/Colombo',
            'status' => 'active',
            'created_by' => $creator->id,
        ]);

        $organization->members()->attach($creator->id, [
            'title' => 'Member',
            'status' => 'active',
        ]);

        return $organization;
    }

    public function test_administrator_can_create_organization(): void
    {
        $admin = $this->userWithRole(RoleName::Administrator->value);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/organizations', [
                'name' => 'National Cricket Academy',
                'short_name' => 'NCA',
                'country' => 'Sri Lanka',
                'timezone' => 'Asia/Colombo',
                'status' => 'active',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'National Cricket Academy');

        $this->assertDatabaseHas('organization_user', [
            'user_id' => $admin->id,
        ]);
    }

    public function test_member_can_view_organization(): void
    {
        $player = $this->userWithRole(RoleName::Player->value);
        $organization = $this->createOrganization($player);

        $this->actingAs($player, 'sanctum')
            ->getJson("/api/v1/organizations/{$organization->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $organization->id);
    }

    public function test_non_member_cannot_view_organization(): void
    {
        $player = $this->userWithRole(RoleName::Player->value);
        $coach = $this->userWithRole(RoleName::Coach->value);
        $organization = $this->createOrganization($coach);

        $this->actingAs($player, 'sanctum')
            ->getJson("/api/v1/organizations/{$organization->id}")
            ->assertForbidden();
    }

    public function test_coach_member_can_create_club_team_season_and_sync_team(): void
    {
        $coach = $this->userWithRole(RoleName::Coach->value);
        $organization = $this->createOrganization($coach);

        $clubResponse = $this->actingAs($coach, 'sanctum')
            ->postJson("/api/v1/organizations/{$organization->id}/clubs", [
                'name' => 'Colombo CC',
                'code' => 'CCC',
                'location' => 'Colombo',
            ])
            ->assertCreated();

        $clubId = $clubResponse->json('data.id');

        $teamResponse = $this->actingAs($coach, 'sanctum')
            ->postJson("/api/v1/organizations/{$organization->id}/teams", [
                'club_id' => $clubId,
                'name' => 'First XI',
                'short_name' => '1XI',
                'gender' => 'male',
                'category' => 'senior',
                'age_group' => 'open',
                'format_preferences' => ['T20'],
                'status' => 'active',
            ])
            ->assertCreated();

        $teamId = $teamResponse->json('data.id');

        $seasonResponse = $this->actingAs($coach, 'sanctum')
            ->postJson("/api/v1/organizations/{$organization->id}/seasons", [
                'name' => '2026/27',
                'start_date' => '2026-10-01',
                'end_date' => '2027-04-30',
                'status' => 'active',
            ])
            ->assertCreated();

        $seasonId = $seasonResponse->json('data.id');

        $this->actingAs($coach, 'sanctum')
            ->postJson(
                "/api/v1/organizations/{$organization->id}/seasons/{$seasonId}/teams/sync",
                ['team_ids' => [$teamId]]
            )
            ->assertOk();

        $this->assertDatabaseHas('season_team', [
            'season_id' => $seasonId,
            'team_id' => $teamId,
        ]);
    }

    public function test_player_member_cannot_create_club(): void
    {
        $player = $this->userWithRole(RoleName::Player->value);
        $organization = $this->createOrganization($player);

        $this->actingAs($player, 'sanctum')
            ->postJson("/api/v1/organizations/{$organization->id}/clubs", [
                'name' => 'Denied Club',
                'code' => 'DEN',
            ])
            ->assertForbidden();
    }

    public function test_non_admin_organization_list_is_membership_scoped(): void
    {
        $player = $this->userWithRole(RoleName::Player->value);
        $coach = $this->userWithRole(RoleName::Coach->value);

        $visible = $this->createOrganization($player, 'Visible Organization');
        $hidden = $this->createOrganization($coach, 'Hidden Organization');

        $response = $this->actingAs($player, 'sanctum')
            ->getJson('/api/v1/organizations')
            ->assertOk();

        $ids = collect($response->json('data.data'))->pluck('id');

        $this->assertTrue($ids->contains($visible->id));
        $this->assertFalse($ids->contains($hidden->id));
    }

    public function test_team_category_filter_works(): void
    {
        $coach = $this->userWithRole(RoleName::Coach->value);
        $organization = $this->createOrganization($coach);

        $club = Club::create([
            'organization_id' => $organization->id,
            'name' => 'Test Club',
            'code' => 'TC',
        ]);

        Team::create([
            'club_id' => $club->id,
            'name' => 'Senior Team',
            'category' => 'senior',
            'status' => 'active',
        ]);

        Team::create([
            'club_id' => $club->id,
            'name' => 'Junior Team',
            'category' => 'junior',
            'status' => 'active',
        ]);

        $response = $this->actingAs($coach, 'sanctum')
            ->getJson("/api/v1/organizations/{$organization->id}/teams?category=senior")
            ->assertOk();

        $this->assertCount(1, $response->json('data.data'));
        $this->assertSame('Senior Team', $response->json('data.data.0.name'));
    }
}
