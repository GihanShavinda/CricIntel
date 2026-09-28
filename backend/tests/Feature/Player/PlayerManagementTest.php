<?php

namespace Tests\Feature\Player;

use App\Enums\RoleName;
use App\Models\Club;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerManagementTest extends TestCase
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

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $roleModel = Role::where('name', $role)->firstOrFail();
        $user->roles()->attach($roleModel);

        return $user;
    }

    private function organizationFor(User $user): Organization
    {
        $organization = Organization::create([
            'name' => 'Player Test Organization',
            'timezone' => 'Asia/Colombo',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $organization->members()->attach($user->id, [
            'title' => 'Member',
            'status' => 'active',
        ]);

        return $organization;
    }

    private function teamFor(Organization $organization): Team
    {
        $club = Club::create([
            'organization_id' => $organization->id,
            'name' => 'Test Cricket Club',
            'code' => 'TCC',
        ]);

        return Team::create([
            'club_id' => $club->id,
            'name' => 'First XI',
            'short_name' => '1XI',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_player(): void
    {
        $admin = $this->userWithRole(RoleName::Administrator->value);
        $organization = $this->organizationFor($admin);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/organizations/{$organization->id}/players", [
                'first_name' => 'Nimal',
                'last_name' => 'Perera',
                'display_name' => 'Nimal Perera',
                'date_of_birth' => '1998-03-14',
                'nationality' => 'Sri Lankan',
                'primary_role' => 'Batter',
                'batting_style' => 'Right-handed',
                'fitness_status' => 'Fit',
                'status' => 'Active',
                'positions' => ['Opening Batter', 'Top Order'],
            ])
            ->assertCreated()
            ->assertJsonPath('data.display_name', 'Nimal Perera')
            ->assertJsonPath('data.primary_role', 'Batter');

        $this->assertDatabaseHas('players', [
            'display_name' => 'Nimal Perera',
        ]);

        $this->assertDatabaseHas('player_positions', [
            'position' => 'Opening Batter',
        ]);
    }

    public function test_coach_member_can_update_player(): void
    {
        $coach = $this->userWithRole(RoleName::Coach->value);
        $organization = $this->organizationFor($coach);

        $player = Player::create([
            'organization_id' => $organization->id,
            'first_name' => 'Kasun',
            'last_name' => 'Silva',
            'display_name' => 'Kasun Silva',
            'primary_role' => 'All-rounder',
            'fitness_status' => 'Fit',
            'status' => 'Active',
        ]);

        $this->actingAs($coach, 'sanctum')
            ->putJson("/api/v1/organizations/{$organization->id}/players/{$player->id}", [
                'fitness_status' => 'Under Observation',
                'status' => 'Active',
            ])
            ->assertOk()
            ->assertJsonPath('data.fitness_status', 'Under Observation');
    }

    public function test_player_role_cannot_create_player(): void
    {
        $user = $this->userWithRole(RoleName::Player->value);
        $organization = $this->organizationFor($user);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/organizations/{$organization->id}/players", [
                'first_name' => 'Denied',
                'last_name' => 'Player',
                'display_name' => 'Denied Player',
                'primary_role' => 'Batter',
                'fitness_status' => 'Fit',
                'status' => 'Active',
            ])
            ->assertForbidden();
    }

    public function test_analyst_member_can_view_player(): void
    {
        $analyst = $this->userWithRole(RoleName::Analyst->value);
        $organization = $this->organizationFor($analyst);

        $player = Player::create([
            'organization_id' => $organization->id,
            'first_name' => 'View',
            'last_name' => 'Player',
            'display_name' => 'View Player',
            'primary_role' => 'Bowler',
            'fitness_status' => 'Fit',
            'status' => 'Active',
        ]);

        $this->actingAs($analyst, 'sanctum')
            ->getJson("/api/v1/organizations/{$organization->id}/players/{$player->id}")
            ->assertOk()
            ->assertJsonPath('data.display_name', 'View Player');
    }

    public function test_non_member_cannot_view_player(): void
    {
        $owner = $this->userWithRole(RoleName::Coach->value);
        $outsider = $this->userWithRole(RoleName::Analyst->value);

        $organization = $this->organizationFor($owner);

        $player = Player::create([
            'organization_id' => $organization->id,
            'first_name' => 'Private',
            'last_name' => 'Player',
            'display_name' => 'Private Player',
            'primary_role' => 'Batter',
            'fitness_status' => 'Fit',
            'status' => 'Active',
        ]);

        $this->actingAs($outsider, 'sanctum')
            ->getJson("/api/v1/organizations/{$organization->id}/players/{$player->id}")
            ->assertForbidden();
    }

    public function test_team_history_and_jersey_number_can_be_synced(): void
    {
        $coach = $this->userWithRole(RoleName::Coach->value);
        $organization = $this->organizationFor($coach);
        $team = $this->teamFor($organization);

        $player = Player::create([
            'organization_id' => $organization->id,
            'first_name' => 'Team',
            'last_name' => 'Player',
            'display_name' => 'Team Player',
            'primary_role' => 'Batter',
            'fitness_status' => 'Fit',
            'status' => 'Active',
        ]);

        $this->actingAs($coach, 'sanctum')
            ->postJson(
                "/api/v1/organizations/{$organization->id}/players/{$player->id}/teams/sync",
                [
                    'memberships' => [[
                        'team_id' => $team->id,
                        'jersey_number' => 23,
                        'joined_at' => '2026-01-01',
                        'left_at' => null,
                        'is_current' => true,
                    ]],
                ]
            )
            ->assertOk()
            ->assertJsonPath('data.teams.0.jersey_number', 23);

        $this->assertDatabaseHas('player_team', [
            'player_id' => $player->id,
            'team_id' => $team->id,
            'jersey_number' => 23,
            'is_current' => true,
        ]);
    }

    public function test_availability_can_be_added(): void
    {
        $manager = $this->userWithRole(RoleName::TeamManager->value);
        $organization = $this->organizationFor($manager);

        $player = Player::create([
            'organization_id' => $organization->id,
            'first_name' => 'Available',
            'last_name' => 'Player',
            'display_name' => 'Available Player',
            'primary_role' => 'Bowler',
            'fitness_status' => 'Fit',
            'status' => 'Active',
        ]);

        $this->actingAs($manager, 'sanctum')
            ->postJson(
                "/api/v1/organizations/{$organization->id}/players/{$player->id}/availability",
                [
                    'available_from' => '2026-09-01',
                    'available_to' => '2026-12-31',
                    'reason' => 'Available for season',
                    'status' => 'Available',
                ]
            )
            ->assertCreated()
            ->assertJsonPath('data.availability.0.status', 'Available');
    }

    public function test_filters_by_role_team_and_status(): void
    {
        $coach = $this->userWithRole(RoleName::Coach->value);
        $organization = $this->organizationFor($coach);
        $team = $this->teamFor($organization);

        $batter = Player::create([
            'organization_id' => $organization->id,
            'first_name' => 'Senior',
            'last_name' => 'Batter',
            'display_name' => 'Senior Batter',
            'primary_role' => 'Batter',
            'batting_style' => 'Right-handed',
            'fitness_status' => 'Fit',
            'status' => 'Active',
        ]);

        $bowler = Player::create([
            'organization_id' => $organization->id,
            'first_name' => 'Fast',
            'last_name' => 'Bowler',
            'display_name' => 'Fast Bowler',
            'primary_role' => 'Bowler',
            'batting_style' => 'Left-handed',
            'bowling_style' => 'Left-arm fast',
            'fitness_status' => 'Fit',
            'status' => 'Active',
        ]);

        $batter->teams()->attach($team->id, [
            'jersey_number' => 10,
            'is_current' => true,
        ]);

        $response = $this->actingAs($coach, 'sanctum')
            ->getJson(
                "/api/v1/organizations/{$organization->id}/players?primary_role=Batter&team_id={$team->id}&status=Active"
            )
            ->assertOk();

        $this->assertCount(1, $response->json('data.data'));
        $this->assertSame('Senior Batter', $response->json('data.data.0.display_name'));
    }
}
