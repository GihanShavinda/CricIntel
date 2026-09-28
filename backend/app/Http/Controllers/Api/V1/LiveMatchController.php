<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CricketMatch;
use App\Models\Organization;
use App\Services\Realtime\LiveMatchStateService;
use Illuminate\Http\JsonResponse;

class LiveMatchController extends Controller
{
    public function __construct(
        private readonly LiveMatchStateService $liveState
    ) {}

    public function show(
        Organization $organization,
        CricketMatch $match
    ): JsonResponse {
        abort_unless(
            (int) $match->organization_id ===
            (int) $organization->id,
            404
        );

        $user = request()->user();

        if (! $user->can('access-administration')) {
            abort_unless(
                $user->belongsToOrganization(
                    $organization->id
                ),
                403
            );
        }

        return response()->json([
            'data' => $this->liveState
                ->snapshot($match),
        ]);
    }
}
