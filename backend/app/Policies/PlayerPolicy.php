<?php
namespace App\Policies;
use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\Player;
use App\Models\User;

class PlayerPolicy
{
    private function member(User $user, Organization $organization): bool
    {
        return $user->hasRole(RoleName::Administrator->value)
            || $user->belongsToOrganization($organization->id);
    }

    public function viewAny(User $user, Organization $organization): bool
    {
        return $this->member($user, $organization);
    }

    public function view(User $user, Player $player): bool
    {
        return $this->member($user, $player->organization);
    }

    public function create(User $user, Organization $organization): bool
    {
        return $user->hasRole(RoleName::Administrator->value)
            || ($user->belongsToOrganization($organization->id)
                && $user->hasAnyRole([RoleName::Coach->value, RoleName::TeamManager->value]));
    }

    public function update(User $user, Player $player): bool
    {
        if ($user->hasRole(RoleName::Administrator->value)) return true;
        if ($player->user_id === $user->id && $user->belongsToOrganization($player->organization_id)) return true;

        return $user->belongsToOrganization($player->organization_id)
            && $user->hasAnyRole([RoleName::Coach->value, RoleName::TeamManager->value]);
    }

    public function delete(User $user, Player $player): bool
    {
        return $user->hasRole(RoleName::Administrator->value)
            || ($user->belongsToOrganization($player->organization_id)
                && $user->hasAnyRole([RoleName::Coach->value, RoleName::TeamManager->value]));
    }
}
