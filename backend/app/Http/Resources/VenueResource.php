<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class VenueResource extends JsonResource {
 public function toArray(Request $request): array { return [
  'id'=>$this->id,'organization_id'=>$this->organization_id,'name'=>$this->name,'city'=>$this->city,'country'=>$this->country,
  'capacity'=>$this->capacity,'pitch_type'=>$this->pitch_type,'boundary_dimensions'=>$this->boundary_dimensions,
  'notes'=>$this->notes,'status'=>$this->status,
 ]; }
}
