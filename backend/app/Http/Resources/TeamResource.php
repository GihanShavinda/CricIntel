<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'club' => $this->whenLoaded('club', fn () => [
                'id' => $this->club->id,
                'name' => $this->club->name,
                'organization_id' => $this->club->organization_id,
            ]),
            'name' => $this->name,
            'short_name' => $this->short_name,
            'gender' => $this->gender,
            'category' => $this->category,
            'age_group' => $this->age_group,
            'format_preferences' => $this->format_preferences ?? [],
            'home_ground' => $this->home_ground,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
