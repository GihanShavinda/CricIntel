<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class TournamentResource extends JsonResource {
 public function toArray(Request $request): array { return [
  'id'=>$this->id,'organization_id'=>$this->organization_id,'season_id'=>$this->season_id,'competition_format_id'=>$this->competition_format_id,
  'name'=>$this->name,'format'=>$this->format,'start_date'=>$this->start_date?->toDateString(),'end_date'=>$this->end_date?->toDateString(),
  'status'=>$this->status,'organizer'=>$this->organizer,'rules_json'=>$this->rules_json,
  'teams'=>$this->whenLoaded('teams', fn()=> $this->teams->map(fn($t)=>['id'=>$t->id,'name'=>$t->name,'seed'=>$t->pivot->seed,'status'=>$t->pivot->status])),
 ]; }
}
