<?php

namespace App\Services\Reports;

use App\Enums\RoleName;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ReportAccessService
{
    public function authorize(User $user, Organization $organization): void
    {
        if ($user->hasRole(RoleName::Administrator->value)) {
            return;
        }

        if (! method_exists($user, 'belongsToOrganization') ||
            ! $user->belongsToOrganization((int) $organization->id)) {
            throw new AuthorizationException('You do not belong to this organization.');
        }

        if (! $user->hasAnyRole([
            RoleName::Coach->value,
            RoleName::Analyst->value,
            RoleName::Selector->value,
            RoleName::TeamManager->value,
        ])) {
            throw new AuthorizationException('You are not allowed to generate professional reports.');
        }
    }
}
