<?php
namespace App\Policies;
use App\Enums\RoleName;
use App\Models\CricketMatch;
use App\Models\Organization;
use App\Models\User;
class CricketMatchPolicy {
    private function member(User $user, Organization $organization): bool {
        return $user->hasRole(RoleName::Administrator->value) || $user->belongsToOrganization($organization->id);
    }
    private function manager(User $user, Organization $organization): bool {
        return $user->hasRole(RoleName::Administrator->value) || ($user->belongsToOrganization($organization->id) && $user->hasAnyRole([RoleName::Coach->value,RoleName::TeamManager->value]));
    }
    public function viewAny(User $user, Organization $organization): bool { return $this->member($user,$organization); }
    public function view(User $user, CricketMatch $match): bool { return $this->member($user,$match->organization); }
    public function create(User $user, Organization $organization): bool { return $this->manager($user,$organization); }
    public function update(User $user, CricketMatch $match): bool { return $this->manager($user,$match->organization); }
}
