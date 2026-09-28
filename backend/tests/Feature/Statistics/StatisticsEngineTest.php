<?php

namespace Tests\Feature\Statistics;

use App\Models\Club;
use App\Models\CricketMatch;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\MatchScoringService;
use App\Services\Statistics\BattingStatisticsService;
use App\Services\Statistics\BowlingStatisticsService;
use App\Services\Statistics\FieldingStatisticsService;
use App\Services\Statistics\MatchStatisticsService;
use App\Services\Statistics\TeamStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsEngineTest extends TestCase
{
    use RefreshDatabase;

    private MatchScoringService $scoring;
    private BattingStatisticsService $batting;
    private BowlingStatisticsService $bowling;
    private FieldingStatisticsService $fielding;
    private TeamStatisticsService $teams;
    private MatchStatisticsService $matches;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scoring = app(MatchScoringService::class);
        $this->batting = app(BattingStatisticsService::class);
        $this->bowling = app(BowlingStatisticsService::class);
        $this->fielding = app(FieldingStatisticsService::class);
        $this->teams = app(TeamStatisticsService::class);
        $this->matches = app(MatchStatisticsService::class);
    }

    private function setupContext(): array
    {
        $owner = User::factory()->create();

        $org = Organization::create([
            'name' => 'Statistics Org',
            'timezone' => 'Asia/Colombo',
            'status' => 'active',
            'created_by' => $owner->id,
        ]);

        $season = Season::create([
            'organization_id' => $org->id,
            'name' => '2026/27',
            'start_date' => '2026-10-01',
            'end_date' => '2027-04-30',
            'status' => 'active',
        ]);

        $clubA = Club::create([
            'organization_id' => $org->id,
            'name' => 'A Club',
            'code' => 'A',
        ]);

        $clubB = Club::create([
            'organization_id' => $org->id,
            'name' => 'B Club',
            'code' => 'B',
        ]);

        $teamA = Team::create([
            'club_id' => $clubA->id,
            'name' => 'Team A',
            'status' => 'active',
        ]);

        $teamB = Team::create([
            'club_id' => $clubB->id,
            'name' => 'Team B',
            'status' => 'active',
        ]);

        $tournament = Tournament::create([
            'organization_id' => $org->id,
            'season_id' => $season->id,
            'name' => 'Statistics T20',
            'format' => 'T20',
            'start_date' => '2026-10-01',
            'end_date' => '2026-11-30',
            'status' => 'Scheduled',
        ]);

        $tournament->teams()->attach([$teamA->id, $teamB->id]);

        $fixture = Fixture::create([
            'organization_id' => $org->id,
            'tournament_id' => $tournament->id,
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'scheduled_at' => '2026-10-10 14:00:00+05:30',
            'status' => 'Scheduled',
        ]);

        $a1 = Player::create([
            'organization_id' => $org->id,
            'first_name' => 'Alpha',
            'last_name' => 'Batter',
            'display_name' => 'Alpha Batter',
            'primary_role' => 'Batter',
            'batting_style' => 'Right-handed',
            'fitness_status' => 'Fit',
            'status' => 'Active',
        ]);

        $a2 = Player::create([
            'organization_id' => $org->id,
            'first_name' => 'Bravo',
            'last_name' => 'Batter',
            'display_name' => 'Bravo Batter',
            'primary_role' => 'Batter',
            'fitness_status' => 'Fit',
            'status' => 'Active',
        ]);

        $b1 = Player::create([
            'organization_id' => $org->id,
            'first_name' => 'Pace',
            'last_name' => 'Bowler',
            'display_name' => 'Pace Bowler',
            'primary_role' => 'Bowler',
            'bowling_style' => 'Right-arm fast',
            'fitness_status' => 'Fit',
            'status' => 'Active',
        ]);

        $b2 = Player::create([
            'organization_id' => $org->id,
            'first_name' => 'Safe',
            'last_name' => 'Fielder',
            'display_name' => 'Safe Fielder',
            'primary_role' => 'All-rounder',
            'fitness_status' => 'Fit',
            'status' => 'Active',
        ]);

        $a1->teams()->attach($teamA->id, [
            'jersey_number' => 1,
            'is_current' => true,
        ]);

        $a2->teams()->attach($teamA->id, [
            'jersey_number' => 2,
            'is_current' => true,
        ]);

        $b1->teams()->attach($teamB->id, [
            'jersey_number' => 10,
            'is_current' => true,
        ]);

        $b2->teams()->attach($teamB->id, [
            'jersey_number' => 11,
            'is_current' => true,
        ]);

        $match = CricketMatch::create([
            'organization_id' => $org->id,
            'fixture_id' => $fixture->id,
            'status' => 'Scheduled',
            'max_overs' => 20,
        ]);

        $this->scoring->startMatch($match);

        $innings = $this->scoring->startInnings($match->fresh(), [
            'batting_team_id' => $teamA->id,
            'bowling_team_id' => $teamB->id,
            'innings_number' => 1,
            'striker_id' => $a1->id,
            'non_striker_id' => $a2->id,
            'bowler_id' => $b1->id,
        ]);

        return compact(
            'org',
            'teamA',
            'teamB',
            'a1',
            'a2',
            'b1',
            'b2',
            'match',
            'innings'
        );
    }

    private function delivery(array $ctx, array $changes): void
    {
        $this->scoring->recordDelivery(
            $ctx['innings']->fresh(),
            array_merge([
                'bowler_id' => $ctx['b1']->id,
                'batter_id' => $ctx['a1']->id,
                'non_striker_id' => $ctx['a2']->id,
                'runs_off_bat' => 0,
                'extra_runs' => 0,
                'extra_type' => 'none',
                'wicket' => false,
            ], $changes)
        );
    }

    private function scorecard(array $ctx): void
    {
        // Alpha Batter: 13 runs from 7 legal balls.
        // Sequence: 4, dot, 1, 6, 2, dot, caught.
        $this->delivery($ctx, ['runs_off_bat' => 4]);
        $this->delivery($ctx, []);
        $this->delivery($ctx, ['runs_off_bat' => 1]);
        $this->delivery($ctx, ['runs_off_bat' => 6]);
        $this->delivery($ctx, ['runs_off_bat' => 2]);
        $this->delivery($ctx, []);
        $this->delivery($ctx, [
            'wicket' => true,
            'wicket_type' => 'caught',
            'dismissed_player_id' => $ctx['a1']->id,
            'fielder_id' => $ctx['b2']->id,
        ]);
    }

    public function test_known_batting_scorecard_is_calculated_exactly(): void
    {
        $ctx = $this->setupContext();
        $this->scorecard($ctx);

        $stats = $this->batting->forPlayer($ctx['org']->id, $ctx['a1']->id);

        $this->assertSame(1, $stats['matches']);
        $this->assertSame(1, $stats['innings']);
        $this->assertSame(13, $stats['runs']);
        $this->assertSame(7, $stats['balls_faced']);
        $this->assertSame(13, $stats['highest_score']);
        $this->assertSame(13.0, $stats['average']);
        $this->assertSame(185.71, $stats['strike_rate']);
        $this->assertSame(0, $stats['fifties']);
        $this->assertSame(0, $stats['hundreds']);
        $this->assertSame(1, $stats['fours']);
        $this->assertSame(1, $stats['sixes']);
        $this->assertSame(76.92, $stats['boundary_percentage']);
        $this->assertSame(42.86, $stats['dot_ball_percentage']);
        $this->assertSame(185.71, $stats['powerplay_strike_rate']);
        $this->assertSame(185.71, $stats['pace_strike_rate']);
    }

    public function test_known_bowling_scorecard_is_calculated_exactly(): void
    {
        $ctx = $this->setupContext();
        $this->scorecard($ctx);

        $stats = $this->bowling->forPlayer($ctx['org']->id, $ctx['b1']->id);

        $this->assertSame('1.1', $stats['overs']);
        $this->assertSame(7, $stats['balls']);
        $this->assertSame(13, $stats['runs_conceded']);
        $this->assertSame(1, $stats['wickets']);
        $this->assertSame(13.0, $stats['average']);
        $this->assertSame(11.14, $stats['economy']);
        $this->assertSame(7.0, $stats['strike_rate']);
        $this->assertSame(42.86, $stats['dot_percentage']);
        $this->assertSame(28.57, $stats['boundary_conceded_percentage']);
        $this->assertSame(0, $stats['maidens']);
        $this->assertSame(11.14, $stats['powerplay_economy']);
    }

    public function test_fielding_catch_and_efficiency_are_derived_from_wickets(): void
    {
        $ctx = $this->setupContext();
        $this->scorecard($ctx);

        $stats = $this->fielding->forPlayer($ctx['org']->id, $ctx['b2']->id);

        $this->assertSame(1, $stats['catches']);
        $this->assertSame(0, $stats['run_outs']);
        $this->assertSame(0, $stats['stumpings']);
        $this->assertSame(0, $stats['drops']);
        $this->assertSame(1, $stats['fielding_opportunities']);
        $this->assertSame(100.0, $stats['fielding_efficiency']);
    }

    public function test_team_and_match_statistics_are_delivery_derived(): void
    {
        $ctx = $this->setupContext();
        $this->scorecard($ctx);

        $team = $this->teams->forTeam($ctx['org']->id, $ctx['teamA']->id);
        $match = $this->matches->forMatch($ctx['org']->id, $ctx['match']->id);

        $this->assertSame(13.0, $team['average_score']);
        $this->assertSame(11.14, $team['run_rate']);
        $this->assertSame(1, $team['wickets_lost']);

        $this->assertSame(1, $match['summary']['fours']);
        $this->assertSame(1, $match['summary']['sixes']);
        $this->assertSame(3, $match['summary']['dot_balls']);
        $this->assertSame(13, $match['innings'][0]['runs']);
        $this->assertSame('1.1', $match['innings'][0]['overs']);
    }
}
