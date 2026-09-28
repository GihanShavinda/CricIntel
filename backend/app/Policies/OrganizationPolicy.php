<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    private function isAdmin(User $user): bool
    {
        return $user->hasRole(RoleName::Administrator->value);
    }

    private function isManager(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::Coach->value,
            RoleName::TeamManager->value,
        ]);
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->isAdmin($user)
            || $user->belongsToOrganization($organization->id);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $this->isManager($user);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $this->isAdmin($user)
            || ($this->isManager($user) && $user->belongsToOrganization($organization->id));
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $this->update($user, $organization);
    }
}
