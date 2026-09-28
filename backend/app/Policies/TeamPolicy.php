<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    private function canManage(User $user, Organization $organization): bool
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return true;
        }

        return $user->belongsToOrganization($organization->id)
            && $user->hasAnyRole([
                RoleName::Coach->value,
                RoleName::TeamManager->value,
            ]);
    }

    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->hasRole(RoleName::Administrator->value)
            || $user->belongsToOrganization($organization->id);
    }

    public function view(User $user, Team $team): bool
    {
        return $this->viewAny($user, $team->club->organization);
    }

    public function create(User $user, Organization $organization): bool
    {
        return $this->canManage($user, $organization);
    }

    public function update(User $user, Team $team): bool
    {
        return $this->canManage($user, $team->club->organization);
    }

    public function delete(User $user, Team $team): bool
    {
        return $this->update($user, $team);
    }
}
