<?php

namespace App\Services\Organization;

use App\Models\Club;
use App\Models\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ClubService
{
    public function create(Organization $organization, array $data, ?UploadedFile $logo = null): Club
    {
        if ($logo) {
            $data['logo'] = $logo->store('clubs', 'public');
        }

        return $organization->clubs()->create($data);
    }

    public function update(Club $club, array $data, ?UploadedFile $logo = null): Club
    {
        if ($logo) {
            if ($club->logo) {
                Storage::disk('public')->delete($club->logo);
            }
            $data['logo'] = $logo->store('clubs', 'public');
        }

        $club->update($data);
        return $club->refresh();
    }

    public function delete(Club $club): void
    {
        if ($club->logo) {
            Storage::disk('public')->delete($club->logo);
        }
        $club->delete();
    }
}
