<?php

namespace App\Services\Organization;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrganizationService
{
    public function create(array $data, User $creator, ?UploadedFile $logo = null): Organization
    {
        return DB::transaction(function () use ($data, $creator, $logo) {
            if ($logo) {
                $data['logo'] = $logo->store('organizations', 'public');
            }

            $data['created_by'] = $creator->id;
            $organization = Organization::create($data);

            $organization->members()->syncWithoutDetaching([
                $creator->id => ['title' => 'Creator', 'status' => 'active'],
            ]);

            return $organization;
        });
    }

    public function update(Organization $organization, array $data, ?UploadedFile $logo = null): Organization
    {
        return DB::transaction(function () use ($organization, $data, $logo) {
            if ($logo) {
                if ($organization->logo) {
                    Storage::disk('public')->delete($organization->logo);
                }
                $data['logo'] = $logo->store('organizations', 'public');
            }

            $organization->update($data);
            return $organization->refresh();
        });
    }

    public function delete(Organization $organization): void
    {
        DB::transaction(function () use ($organization) {
            if ($organization->logo) {
                Storage::disk('public')->delete($organization->logo);
            }

            $organization->clubs()->each(function ($club) {
                if ($club->logo) {
                    Storage::disk('public')->delete($club->logo);
                }
            });

            $organization->delete();
        });
    }
}
