<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'organization_id'=>$this->organization_id,
            'user_id'=>$this->user_id,
            'first_name'=>$this->first_name,
            'last_name'=>$this->last_name,
            'display_name'=>$this->display_name,
            'date_of_birth'=>$this->date_of_birth?->toDateString(),
            'nationality'=>$this->nationality,
            'photo'=>$this->photo,
            'photo_url'=>$this->photo ? Storage::disk('public')->url($this->photo) : null,
            'primary_role'=>$this->primary_role,
            'batting_style'=>$this->batting_style,
            'bowling_style'=>$this->bowling_style,
            'fitness_status'=>$this->fitness_status,
            'status'=>$this->status,
            'notes'=>$this->notes,
            'positions'=>$this->whenLoaded('positions', fn()=> $this->positions->map(fn($p)=>[
                'id'=>$p->id,'position'=>$p->position,'priority'=>$p->priority,
            ])),
            'teams'=>$this->whenLoaded('teams', fn()=> $this->teams->map(fn($t)=>[
                'id'=>$t->id,'name'=>$t->name,'short_name'=>$t->short_name,
                'jersey_number'=>$t->pivot->jersey_number,
                'joined_at'=>$t->pivot->joined_at,
                'left_at'=>$t->pivot->left_at,
                'is_current'=>(bool)$t->pivot->is_current,
            ])),
            'availability'=>$this->whenLoaded('availability', fn()=> $this->availability->map(fn($a)=>[
                'id'=>$a->id,'available_from'=>$a->available_from?->toDateString(),
                'available_to'=>$a->available_to?->toDateString(),'reason'=>$a->reason,'status'=>$a->status,
            ])),
            'created_at'=>$this->created_at?->toISOString(),
            'updated_at'=>$this->updated_at?->toISOString(),
        ];
    }
}
