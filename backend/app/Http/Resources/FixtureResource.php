<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class FixtureResource extends JsonResource {
 public function toArray(Request $request): array { return [
  'id'=>$this->id,'organization_id'=>$this->organization_id,'tournament_id'=>$this->tournament_id,
  'home_team'=>$this->whenLoaded('homeTeam',fn()=>['id'=>$this->homeTeam->id,'name'=>$this->homeTeam->name]),
  'away_team'=>$this->whenLoaded('awayTeam',fn()=>['id'=>$this->awayTeam->id,'name'=>$this->awayTeam->name]),
  'venue'=>$this->whenLoaded('venue',fn()=> $this->venue ? ['id'=>$this->venue->id,'name'=>$this->venue->name] : null),
  'scheduled_at'=>$this->scheduled_at?->toISOString(),'match_number'=>$this->match_number,'round'=>$this->round,'status'=>$this->status,'notes'=>$this->notes,
 ]; }
}
