<?php

namespace Database\Seeders;

use App\Models\CompetitionFormat;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompetitionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name'=>'Twenty20','code'=>'T20','default_overs'=>20,'is_custom'=>false],
            ['name'=>'One Day International','code'=>'ODI','default_overs'=>50,'is_custom'=>false],
            ['name'=>'Test Cricket','code'=>'TEST','default_overs'=>null,'is_custom'=>false],
            ['name'=>'T10 Cricket','code'=>'T10','default_overs'=>10,'is_custom'=>false],
            ['name'=>'Custom','code'=>'CUSTOM','default_overs'=>null,'is_custom'=>true],
        ] as $format) {
            CompetitionFormat::updateOrCreate(
                ['code'=>$format['code']],
                $format + ['status'=>'active']
            );
        }

        $organization=Organization::with(['seasons','clubs.teams'])->first();
        if(! $organization) return;

        $season=$organization->seasons->first();
        $teams=$organization->clubs->flatMap(fn($club)=>$club->teams)->take(2);

        if(! $season || $teams->count()<2) return;

        DB::transaction(function() use($organization,$season,$teams){
            $venue=$organization->venues()->updateOrCreate(
                ['name'=>'CricIntel National Ground'],
                [
                    'city'=>'Colombo',
                    'country'=>'Sri Lanka',
                    'capacity'=>18000,
                    'pitch_type'=>'Balanced',
                    'boundary_dimensions'=>['straight_m'=>70,'square_m'=>65],
                    'notes'=>'Sample venue for P4.',
                    'status'=>'active',
                ]
            );

            $format=CompetitionFormat::where('code','T20')->first();

            $start=max($season->start_date->toDateString(),'2026-10-10');
            $end=min($season->end_date->toDateString(),'2026-11-30');

            $tournament=$organization->tournaments()->updateOrCreate(
                ['season_id'=>$season->id,'name'=>'CricIntel Premier T20'],
                [
                    'competition_format_id'=>$format?->id,
                    'format'=>'T20',
                    'start_date'=>$start,
                    'end_date'=>$end,
                    'status'=>'Scheduled',
                    'organizer'=>'CricIntel Demo Organization',
                    'rules_json'=>['overs'=>20,'points_win'=>2,'points_tie'=>1],
                ]
            );

            $payload=[];
            foreach($teams->values() as $index=>$team) {
                $payload[$team->id]=['seed'=>$index+1,'status'=>'registered'];
            }
            $tournament->teams()->sync($payload);

            $home=$teams->values()->get(0);
            $away=$teams->values()->get(1);

            $organization->fixtures()->updateOrCreate(
                [
                    'tournament_id'=>$tournament->id,
                    'home_team_id'=>$home->id,
                    'away_team_id'=>$away->id,
                    'match_number'=>1,
                ],
                [
                    'venue_id'=>$venue->id,
                    'scheduled_at'=>$start.' 14:00:00+05:30',
                    'round'=>'League',
                    'status'=>'Scheduled',
                    'notes'=>'Opening fixture',
                ]
            );
        });
    }
}
