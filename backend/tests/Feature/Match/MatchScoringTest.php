<?php

namespace Tests\Feature\Match;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchScoringTest extends TestCase
{
    use RefreshDatabase;

    private MatchScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MatchScoringService::class);
    }

    private function setupMatch(int $maxOvers = 20): array
    {
        $owner = User::factory()->create();
        $org = Organization::create([
            'name' => 'Scoring Org',
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

        $clubA = Club::create(['organization_id'=>$org->id,'name'=>'A Club','code'=>'A']);
        $clubB = Club::create(['organization_id'=>$org->id,'name'=>'B Club','code'=>'B']);
        $teamA = Team::create(['club_id'=>$clubA->id,'name'=>'Team A','status'=>'active']);
        $teamB = Team::create(['club_id'=>$clubB->id,'name'=>'Team B','status'=>'active']);

        $tournament = Tournament::create([
            'organization_id'=>$org->id,
            'season_id'=>$season->id,
            'name'=>'Scoring Tournament',
            'format'=>'T20',
            'start_date'=>'2026-10-01',
            'end_date'=>'2026-11-30',
            'status'=>'Scheduled',
        ]);
        $tournament->teams()->attach([$teamA->id,$teamB->id]);

        $fixture = Fixture::create([
            'organization_id'=>$org->id,
            'tournament_id'=>$tournament->id,
            'home_team_id'=>$teamA->id,
            'away_team_id'=>$teamB->id,
            'scheduled_at'=>'2026-10-10 14:00:00+05:30',
            'status'=>'Scheduled',
        ]);

        $playersA = collect();
        $playersB = collect();
        for ($i=1; $i<=11; $i++) {
            $playersA->push(Player::create([
                'organization_id'=>$org->id,'first_name'=>'A'.$i,'last_name'=>'Player','display_name'=>'A'.$i,
                'primary_role'=>'Batter','fitness_status'=>'Fit','status'=>'Active',
            ]));
            $playersB->push(Player::create([
                'organization_id'=>$org->id,'first_name'=>'B'.$i,'last_name'=>'Player','display_name'=>'B'.$i,
                'primary_role'=>'Bowler','fitness_status'=>'Fit','status'=>'Active',
            ]));
        }

        $match = CricketMatch::create([
            'organization_id'=>$org->id,
            'fixture_id'=>$fixture->id,
            'status'=>'Scheduled',
            'max_overs'=>$maxOvers,
        ]);
        $this->service->startMatch($match);

        return compact('org','teamA','teamB','playersA','playersB','match');
    }

    private function startFirst(array $ctx)
    {
        return $this->service->startInnings($ctx['match'], [
            'batting_team_id'=>$ctx['teamA']->id,
            'bowling_team_id'=>$ctx['teamB']->id,
            'innings_number'=>1,
            'striker_id'=>$ctx['playersA'][0]->id,
            'non_striker_id'=>$ctx['playersA'][1]->id,
            'bowler_id'=>$ctx['playersB'][0]->id,
        ]);
    }

    private function ball($innings, array $ctx, array $override = [])
    {
        return $this->service->recordDelivery($innings, array_merge([
            'bowler_id'=>$ctx['playersB'][0]->id,
            'batter_id'=>$ctx['playersA'][0]->id,
            'non_striker_id'=>$ctx['playersA'][1]->id,
            'runs_off_bat'=>0,
            'extra_runs'=>0,
            'extra_type'=>'none',
            'wicket'=>false,
        ], $override));
    }

    public function test_wide_and_no_ball_do_not_count_as_legal_deliveries(): void
    {
        $ctx=$this->setupMatch();
        $innings=$this->startFirst($ctx);

        $this->ball($innings,$ctx,['extra_type'=>'wide','extra_runs'=>1]);
        $innings->refresh();
        $this->assertSame(0,$innings->legal_balls);
        $this->assertSame(1,$innings->runs);

        $this->ball($innings,$ctx,['extra_type'=>'no_ball','extra_runs'=>1]);
        $innings->refresh();
        $this->assertSame(0,$innings->legal_balls);
        $this->assertTrue($innings->free_hit_next);

        $this->ball($innings,$ctx);
        $innings->refresh();
        $this->assertSame(1,$innings->legal_balls);
        $this->assertFalse($innings->free_hit_next);
    }

    public function test_byes_and_leg_byes_are_legal_and_added_as_extras(): void
    {
        $ctx=$this->setupMatch();
        $innings=$this->startFirst($ctx);

        $this->ball($innings,$ctx,['extra_type'=>'bye','extra_runs'=>2]);
        $this->ball($innings,$ctx,['extra_type'=>'leg_bye','extra_runs'=>1]);

        $innings->refresh();
        $this->assertSame(2,$innings->legal_balls);
        $this->assertSame(3,$innings->runs);
    }

    public function test_six_legal_balls_complete_an_over(): void
    {
        $ctx=$this->setupMatch();
        $innings=$this->startFirst($ctx);

        for($i=0;$i<6;$i++) $this->ball($innings,$ctx);

        $innings->refresh();
        $this->assertSame(6,$innings->legal_balls);
        $this->assertSame('1.0',(string)$innings->overs_completed);
        $this->assertSame('Completed',$innings->overs()->first()->status);
    }

    public function test_bowler_wicket_is_not_counted_on_free_hit(): void
    {
        $ctx=$this->setupMatch();
        $innings=$this->startFirst($ctx);

        $this->ball($innings,$ctx,['extra_type'=>'no_ball','extra_runs'=>1]);
        $delivery=$this->ball($innings,$ctx,[
            'wicket'=>true,
            'wicket_type'=>'bowled',
            'dismissed_player_id'=>$ctx['playersA'][0]->id,
        ]);

        $innings->refresh();
        $this->assertFalse($delivery->wicket);
        $this->assertSame(0,$innings->wickets);
    }

    public function test_run_out_is_counted_on_free_hit(): void
    {
        $ctx=$this->setupMatch();
        $innings=$this->startFirst($ctx);

        $this->ball($innings,$ctx,['extra_type'=>'no_ball','extra_runs'=>1]);
        $delivery=$this->ball($innings,$ctx,[
            'wicket'=>true,
            'wicket_type'=>'run_out',
            'dismissed_player_id'=>$ctx['playersA'][0]->id,
            'fielder_id'=>$ctx['playersB'][1]->id,
        ]);

        $innings->refresh();
        $this->assertTrue($delivery->wicket);
        $this->assertSame(1,$innings->wickets);
        $this->assertDatabaseHas('wickets',['delivery_id'=>$delivery->id,'wicket_type'=>'run_out']);
    }

    public function test_maximum_overs_auto_complete_innings(): void
    {
        $ctx=$this->setupMatch(1);
        $innings=$this->startFirst($ctx);

        for($i=0;$i<6;$i++) $this->ball($innings,$ctx);

        $this->assertSame('Completed',$innings->fresh()->status);
    }

    public function test_target_chase_auto_completes_match(): void
    {
        $ctx=$this->setupMatch();
        $first=$this->startFirst($ctx);
        $this->ball($first,$ctx,['runs_off_bat'=>4]);
        $this->service->completeInnings($first);

        $second=$this->service->startInnings($ctx['match']->fresh(),[
            'batting_team_id'=>$ctx['teamB']->id,
            'bowling_team_id'=>$ctx['teamA']->id,
            'innings_number'=>2,
            'striker_id'=>$ctx['playersB'][0]->id,
            'non_striker_id'=>$ctx['playersB'][1]->id,
            'bowler_id'=>$ctx['playersA'][0]->id,
        ]);

        $this->service->recordDelivery($second,[
            'bowler_id'=>$ctx['playersA'][0]->id,
            'batter_id'=>$ctx['playersB'][0]->id,
            'non_striker_id'=>$ctx['playersB'][1]->id,
            'runs_off_bat'=>6,'extra_runs'=>0,'extra_type'=>'none','wicket'=>false,
        ]);

        $this->assertSame('Completed',$second->fresh()->status);
        $this->assertSame('Completed',$ctx['match']->fresh()->status);
        $this->assertSame($ctx['teamB']->id,$ctx['match']->fresh()->winner_team_id);
    }

    public function test_undo_latest_delivery_recalculates_score(): void
    {
        $ctx=$this->setupMatch();
        $innings=$this->startFirst($ctx);

        $this->ball($innings,$ctx,['runs_off_bat'=>4]);
        $this->ball($innings,$ctx,['runs_off_bat'=>2]);
        $this->assertSame(6,$innings->fresh()->runs);

        $this->service->undoLatestDelivery($innings);
        $innings->refresh();

        $this->assertSame(4,$innings->runs);
        $this->assertSame(1,$innings->legal_balls);
        $this->assertCount(1,$innings->deliveries);
    }
}
