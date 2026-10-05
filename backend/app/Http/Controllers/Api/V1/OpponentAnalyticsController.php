<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Team;
use App\Services\OpponentAnalytics\OpponentAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpponentAnalyticsController extends Controller
{
    public function __construct(
        private readonly OpponentAnalyticsService $analytics
    ) {}

    public function options(
        Request $request,
        Organization $organization
    ): JsonResponse {
        $this->assertCanView($request, $organization);

        return response()->json([
            'data' => $this->analytics->options($organization->id),
        ]);
    }

    public function team(
        Request $request,
        Organization $organization,
        int $team
    ): JsonResponse {
        $this->assertCanView($request, $organization);
        $this->assertTeamOrganization($organization, $team);

        return response()->json([
            'data' => $this->analytics->teamProfile(
                $organization->id,
                $team,
                $this->filters($request)
            ),
        ]);
    }

    public function batter(
        Request $request,
        Organization $organization,
        int $player
    ): JsonResponse {
        $this->assertCanView($request, $organization);

        return response()->json([
            'data' => $this->analytics->batter(
                $organization->id,
                $player,
                $this->filters($request)
            ),
        ]);
    }

    public function bowler(
        Request $request,
        Organization $organization,
        int $player
    ): JsonResponse {
        $this->assertCanView($request, $organization);

        return response()->json([
            'data' => $this->analytics->bowler(
                $organization->id,
                $player,
                $this->filters($request)
            ),
        ]);
    }

    public function matchup(
        Request $request,
        Organization $organization
    ): JsonResponse {
        $this->assertCanView($request, $organization);

        $validated = $request->validate([
            'batter_id' => ['required', 'integer', 'exists:players,id'],
            'bowler_id' => ['required', 'integer', 'exists:players,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'tournament_id' => ['nullable', 'integer', 'exists:tournaments,id'],
            'opponent_team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);

        return response()->json([
            'data' => $this->analytics->matchup(
                $organization->id,
                (int) $validated['batter_id'],
                (int) $validated['bowler_id'],
                $validated
            ),
        ]);
    }

    public function partnerships(
        Request $request,
        Organization $organization,
        int $team
    ): JsonResponse {
        $this->assertCanView($request, $organization);
        $this->assertTeamOrganization($organization, $team);

        return response()->json([
            'data' => $this->analytics->partnerships(
                $organization->id,
                $team,
                $this->filters($request)
            ),
        ]);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'tournament_id' => ['nullable', 'integer', 'exists:tournaments,id'],
            'opponent_team_id' => ['nullable', 'integer', 'exists:teams,id'],
        ]);
    }

    private function assertTeamOrganization(Organization $organization, int $teamId): void
    {
        $exists = Team::query()
            ->whereKey($teamId)
            ->whereHas('club', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->exists();

        abort_unless($exists, 404, 'Team not found in this organization.');
    }

    private function assertCanView(
        Request $request,
        Organization $organization
    ): void {
        $user = $request->user();

        if ($user->can('access-administration')) {
            return;
        }

        if (method_exists($user, 'belongsToOrganization')) {
            abort_unless(
                $user->belongsToOrganization((int) $organization->id),
                403,
                'You are not a member of this organization.'
            );
        }

        $allowed = false;

        if (method_exists($user, 'hasAnyRole')) {
            $allowed = $user->hasAnyRole([
                'Coach',
                'Analyst',
                'Selector',
                'Team Manager',
            ]);
        } elseif (method_exists($user, 'roles')) {
            $allowed = $user->roles()
                ->whereIn('name', [
                    'Coach',
                    'Analyst',
                    'Selector',
                    'Team Manager',
                ])
                ->exists();
        }

        abort_unless(
            $allowed,
            403,
            'You are not authorized to view opponent analytics.'
        );
    }
}
