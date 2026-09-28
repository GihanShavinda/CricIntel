<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'logo' => $this->logo,
            'logo_url' => $this->logo ? Storage::disk('public')->url($this->logo) : null,
            'country' => $this->country,
            'timezone' => $this->timezone,
            'description' => $this->description,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'members_count' => $this->whenCounted('members'),
            'clubs_count' => $this->whenCounted('clubs'),
            'seasons_count' => $this->whenCounted('seasons'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
