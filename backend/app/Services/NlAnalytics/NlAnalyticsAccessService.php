<?php

namespace App\Services\NlAnalytics;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NlAnalyticsAccessService
{
    public function assertView(
        Request $request,
        Organization $organization
    ): void {
        $user = $request->user();

        if ($user->can('access-administration')) {
            return;
        }

        if (method_exists($user, 'belongsToOrganization')) {
            abort_unless(
                $user->belongsToOrganization(
                    (int) $organization->id
                ),
                403,
                'You are not a member of this organization.'
            );
        } else {
            abort_unless(
                DB::table('organization_user')
                    ->where(
                        'organization_id',
                        $organization->id
                    )
                    ->where(
                        'user_id',
                        $user->id
                    )
                    ->where('status', 'active')
                    ->exists(),
                403,
                'You are not a member of this organization.'
            );
        }

        abort_unless(
            $user->hasAnyRole([
                'Coach',
                'Analyst',
                'Selector',
                'Team Manager',
            ]),
            403,
            'You are not authorized to use natural-language analytics.'
        );
    }
}
