<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ClubResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'code' => $this->code,
            'logo' => $this->logo,
            'logo_url' => $this->logo ? Storage::disk('public')->url($this->logo) : null,
            'location' => $this->location,
            'founded_year' => $this->founded_year,
            'description' => $this->description,
            'teams_count' => $this->whenCounted('teams'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
