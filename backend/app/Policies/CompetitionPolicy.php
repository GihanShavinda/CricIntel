<?php
namespace App\Policies;
use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\User;

class CompetitionPolicy
{
    public function viewAny(User $user, Organization $organization): bool
    {
        return $user->hasRole(RoleName::Administrator->value)
            || $user->belongsToOrganization($organization->id);
    }

    public function manage(User $user, Organization $organization): bool
    {
        return $user->hasRole(RoleName::Administrator->value)
            || ($user->belongsToOrganization($organization->id)
                && $user->hasAnyRole([RoleName::Coach->value, RoleName::TeamManager->value]));
    }
}
