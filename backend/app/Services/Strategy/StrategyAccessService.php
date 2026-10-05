<?php

namespace App\Services\Strategy;

use App\Models\Organization;
use App\Models\StrategyPlan;
use App\Models\User;
use Illuminate\Http\Request;

class StrategyAccessService
{
    public function assertView(Request $request, Organization $organization): void
    {
        $this->assertMembership($request->user(), $organization);

        abort_unless(
            $request->user()->can('access-administration') ||
            $request->user()->hasAnyRole([
                'Coach',
                'Analyst',
                'Selector',
                'Team Manager',
            ]),
            403,
            'You are not authorized to view tactical strategy.'
        );
    }

    public function assertManage(Request $request, Organization $organization): void
    {
        $this->assertMembership($request->user(), $organization);

        abort_unless(
            $request->user()->can('access-administration') ||
            $request->user()->hasAnyRole(['Coach']),
            403,
            'Only a coach or administrator can manage strategy plans.'
        );
    }

    public function assertCollaborate(Request $request, Organization $organization): void
    {
        $this->assertMembership($request->user(), $organization);

        abort_unless(
            $request->user()->can('access-administration') ||
            $request->user()->hasAnyRole([
                'Coach',
                'Analyst',
                'Selector',
                'Team Manager',
            ]),
            403,
            'You are not authorized to collaborate on strategy.'
        );
    }

    public function assertPlanOrganization(
        Organization $organization,
        StrategyPlan $plan
    ): void {
        abort_unless(
            (int) $plan->organization_id === (int) $organization->id,
            404,
            'Strategy plan not found in this organization.'
        );
    }

    public function assertMembership(User $user, Organization $organization): void
    {
        if ($user->can('access-administration')) {
            return;
        }

        if (method_exists($user, 'belongsToOrganization')) {
            abort_unless(
                $user->belongsToOrganization((int) $organization->id),
                403,
                'You are not a member of this organization.'
            );
            return;
        }

        abort_unless(
            $user->organizations()
                ->where('organizations.id', $organization->id)
                ->where('organization_user.status', 'active')
                ->exists(),
            403,
            'You are not a member of this organization.'
        );
    }
}
