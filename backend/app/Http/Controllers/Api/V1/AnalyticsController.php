<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\AnalyticsFilterRequest;
use App\Http\Requests\Analytics\PlayerComparisonRequest;
use App\Models\Organization;
use App\Services\Analytics\AnalyticsOptionsService;
use App\Services\Analytics\DashboardAnalyticsService;
use App\Services\Analytics\PlayerComparisonService;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly DashboardAnalyticsService $dashboard,
        private readonly AnalyticsOptionsService $options,
        private readonly PlayerComparisonService $comparison,
    ) {}

    public function options(Organization $organization): JsonResponse
    {
        $this->authorizeOrganization($organization);

        return response()->json([
            'data' => $this->options->options($organization->id),
        ]);
    }

    public function dashboard(
        AnalyticsFilterRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->authorizeOrganization($organization);

        $filters = $request->filters();

        if (empty($filters['team_id'])) {
            return response()->json([
                'message' => 'team_id is required for the team analytics dashboard.',
                'errors' => [
                    'team_id' => ['Select a team before loading analytics.'],
                ],
            ], 422);
        }

        $teamId = (int) $filters['team_id'];
        unset($filters['team_id']);

        return response()->json([
            'data' => $this->dashboard->dashboard(
                $organization->id,
                $teamId,
                $filters
            ),
        ]);
    }

    public function compare(
        PlayerComparisonRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->authorizeOrganization($organization);

        return response()->json([
            'data' => $this->comparison->compare(
                $organization->id,
                (int) $request->validated('player_a'),
                (int) $request->validated('player_b'),
                $request->filters()
            ),
        ]);
    }

    private function authorizeOrganization(Organization $organization): void
    {
        $user = request()->user();

        if ($user->can('access-administration')) {
            return;
        }

        abort_unless(
            $user->belongsToOrganization($organization->id),
            403,
            'You do not have access to this organization.'
        );
    }
}
