<?php
namespace App\Http\Resources;
use App\Services\MatchScoringService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class MatchScorecardResource extends JsonResource {
 public function toArray(Request $request): array {
  $service=app(MatchScoringService::class);
  return [
   'id'=>$this->id,'fixture_id'=>$this->fixture_id,'status'=>$this->status,'result_type'=>$this->result_type,
   'winner_team_id'=>$this->winner_team_id,'player_of_match_id'=>$this->player_of_match_id,
   'target_runs'=>$this->target_runs,'max_overs'=>$this->max_overs,
   'fixture'=>$this->whenLoaded('fixture'),
   'innings'=>$this->whenLoaded('innings', fn()=> $this->innings->map(fn($inn)=>[
      'id'=>$inn->id,'innings_number'=>$inn->innings_number,'batting_team_id'=>$inn->batting_team_id,
      'bowling_team_id'=>$inn->bowling_team_id,'status'=>$inn->status,'score'=>$service->score($inn),
      'striker_id'=>$inn->striker_id,'non_striker_id'=>$inn->non_striker_id,'current_bowler_id'=>$inn->current_bowler_id,
      'free_hit_next'=>$inn->free_hit_next,
      'deliveries'=>$inn->relationLoaded('deliveries') ? $inn->deliveries->map(fn($d)=>[
        'id'=>$d->id,'sequence_number'=>$d->sequence_number,'ball_number'=>$d->ball_number,
        'batter_id'=>$d->batter_id,'bowler_id'=>$d->bowler_id,'runs_off_bat'=>$d->runs_off_bat,
        'extra_runs'=>$d->extra_runs,'total_runs'=>$d->total_runs,'extra_type'=>$d->extra_type,
        'is_legal'=>$d->is_legal,'is_free_hit'=>$d->is_free_hit,'wicket'=>$d->wicket,'wicket_type'=>$d->wicket_type,
        'dismissed_player_id'=>$d->dismissed_player_id,'fielder_id'=>$d->fielder_id,
      ])->values() : [],
   ])->values()),
  ];
 }
}
