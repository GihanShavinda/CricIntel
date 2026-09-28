<?php

namespace Tests\Feature\Competition;

use App\Enums\RoleName;
use App\Models\Club;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionManagementTest extends TestCase
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

    private function orgFor(User $user): Organization
    {
        $org = Organization::create([
            'name' => 'Competition Test Org',
            'timezone' => 'Asia/Colombo',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $org->members()->attach($user->id, [
            'title' => 'Member',
            'status' => 'active',
        ]);

        return $org;
    }

    private function seasonFor(Organization $org): Season
    {
        return Season::create([
            'organization_id' => $org->id,
            'name' => '2026/27',
            'start_date' => '2026-10-01',
            'end_date' => '2027-04-30',
            'status' => 'active',
        ]);
    }

    private function teamFor(Organization $org, string $code, string $name): Team
    {
        $club = Club::create([
            'organization_id' => $org->id,
            'name' => $name.' Club',
            'code' => $code,
        ]);

        return Team::create([
            'club_id' => $club->id,
            'name' => $name,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_create_venue_and_tournament(): void
    {
        $admin = $this->userWithRole(RoleName::Administrator->value);
        $org = $this->orgFor($admin);
        $season = $this->seasonFor($org);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/organizations/{$org->id}/venues", [
                'name' => 'National Ground',
                'city' => 'Colombo',
                'country' => 'Sri Lanka',
                'capacity' => 20000,
                'status' => 'active',
            ])
            ->assertCreated();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/organizations/{$org->id}/tournaments", [
                'season_id' => $season->id,
                'name' => 'Premier T20',
                'format' => 'T20',
                'start_date' => '2026-10-10',
                'end_date' => '2026-11-20',
                'status' => 'Scheduled',
            ])
            ->assertCreated()
            ->assertJsonPath('data.format', 'T20');
    }

    public function test_tournament_dates_must_be_inside_season(): void
    {
        $admin = $this->userWithRole(RoleName::Administrator->value);
        $org = $this->orgFor($admin);
        $season = $this->seasonFor($org);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/organizations/{$org->id}/tournaments", [
                'season_id' => $season->id,
                'name' => 'Invalid Tournament',
                'format' => 'ODI',
                'start_date' => '2026-09-01',
                'end_date' => '2026-10-05',
                'status' => 'Scheduled',
            ])
            ->assertUnprocessable();
    }

    public function test_coach_can_register_teams_and_schedule_fixture(): void
    {
        $coach = $this->userWithRole(RoleName::Coach->value);
        $org = $this->orgFor($coach);
        $season = $this->seasonFor($org);
        $home = $this->teamFor($org, 'HOM', 'Home XI');
        $away = $this->teamFor($org, 'AWY', 'Away XI');

        $venueResponse = $this->actingAs($coach, 'sanctum')
            ->postJson("/api/v1/organizations/{$org->id}/venues", [
                'name' => 'City Ground',
                'status' => 'active',
            ])
            ->assertCreated();

        $venueId = $venueResponse->json('data.id');

        $tournamentResponse = $this->actingAs($coach, 'sanctum')
            ->postJson("/api/v1/organizations/{$org->id}/tournaments", [
                'season_id' => $season->id,
                'name' => 'League',
                'format' => 'T20',
                'start_date' => '2026-10-10',
                'end_date' => '2026-11-10',
                'status' => 'Scheduled',
            ])
            ->assertCreated();

        $tournamentId = $tournamentResponse->json('data.id');

        $this->actingAs($coach, 'sanctum')
            ->postJson(
                "/api/v1/organizations/{$org->id}/tournaments/{$tournamentId}/teams/sync",
                [
                    'teams' => [
                        ['team_id' => $home->id, 'seed' => 1, 'status' => 'registered'],
                        ['team_id' => $away->id, 'seed' => 2, 'status' => 'registered'],
                    ],
                ]
            )
            ->assertOk();

        $this->actingAs($coach, 'sanctum')
            ->postJson("/api/v1/organizations/{$org->id}/fixtures", [
                'tournament_id' => $tournamentId,
                'home_team_id' => $home->id,
                'away_team_id' => $away->id,
                'venue_id' => $venueId,
                'scheduled_at' => '2026-10-15T14:00:00+05:30',
                'match_number' => 1,
                'round' => 'League',
                'status' => 'Scheduled',
            ])
            ->assertCreated();
    }

    public function test_duplicate_fixture_is_rejected_even_if_home_away_reversed(): void
    {
        $admin = $this->userWithRole(RoleName::Administrator->value);
        $org = $this->orgFor($admin);
        $season = $this->seasonFor($org);
        $home = $this->teamFor($org, 'AAA', 'Team A');
        $away = $this->teamFor($org, 'BBB', 'Team B');

        $tournament = Tournament::create([
            'organization_id' => $org->id,
            'season_id' => $season->id,
            'name' => 'Duplicate Test',
            'format' => 'T20',
            'start_date' => '2026-10-01',
            'end_date' => '2026-11-30',
            'status' => 'Scheduled',
        ]);

        $tournament->teams()->attach([
            $home->id => ['status' => 'registered'],
            $away->id => ['status' => 'registered'],
        ]);

        $payload = [
            'tournament_id' => $tournament->id,
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'scheduled_at' => '2026-10-20T14:00:00+05:30',
            'status' => 'Scheduled',
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/organizations/{$org->id}/fixtures", $payload)
            ->assertCreated();

        $payload['home_team_id'] = $away->id;
        $payload['away_team_id'] = $home->id;

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/organizations/{$org->id}/fixtures", $payload)
            ->assertUnprocessable();
    }

    public function test_player_member_cannot_manage_competitions(): void
    {
        $player = $this->userWithRole(RoleName::Player->value);
        $org = $this->orgFor($player);

        $this->actingAs($player, 'sanctum')
            ->postJson("/api/v1/organizations/{$org->id}/venues", [
                'name' => 'Denied Ground',
                'status' => 'active',
            ])
            ->assertForbidden();
    }
}
