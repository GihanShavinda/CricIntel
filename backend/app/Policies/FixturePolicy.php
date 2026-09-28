<?php
namespace App\Policies;
use App\Enums\RoleName;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\User;

class FixturePolicy
{
    private function canViewOrg(User $user, Organization $organization): bool
    {
        return $user->hasRole(RoleName::Administrator->value)
            || $user->belongsToOrganization($organization->id);
    }

    private function canManageOrg(User $user, Organization $organization): bool
    {
        return $user->hasRole(RoleName::Administrator->value)
            || ($user->belongsToOrganization($organization->id)
                && $user->hasAnyRole([
                    RoleName::Coach->value,
                    RoleName::TeamManager->value,
                ]));
    }

    public function viewAny(User $user, Organization $organization): bool
    {
        return $this->canViewOrg($user, $organization);
    }

    public function view(User $user, Fixture $resource): bool
    {
        return $this->canViewOrg($user, $resource->organization);
    }

    public function create(User $user, Organization $organization): bool
    {
        return $this->canManageOrg($user, $organization);
    }

    public function update(User $user, Fixture $resource): bool
    {
        return $this->canManageOrg($user, $resource->organization);
    }

    public function delete(User $user, Fixture $resource): bool
    {
        return $this->update($user, $resource);
    }
}
