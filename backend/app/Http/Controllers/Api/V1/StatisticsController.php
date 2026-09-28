<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Statistics\StatisticsFilterRequest;
use App\Models\CricketMatch;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Team;
use App\Services\Statistics\MatchStatisticsService;
use App\Services\Statistics\PhaseStatisticsService;
use App\Services\Statistics\PlayerStatisticsService;
use App\Services\Statistics\TeamStatisticsService;
use Illuminate\Http\JsonResponse;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly PlayerStatisticsService $players,
        private readonly TeamStatisticsService $teams,
        private readonly MatchStatisticsService $matches,
        private readonly PhaseStatisticsService $phases,
    ) {}

    public function player(
        StatisticsFilterRequest $request,
        Organization $organization,
        Player $player
    ): JsonResponse {
        $this->ensurePlayerBelongsToOrganization($organization, $player);

        return response()->json([
            'data' => $this->players->all($organization->id, $player->id, $request->filters()),
        ]);
    }

    public function batting(
        StatisticsFilterRequest $request,
        Organization $organization,
        Player $player
    ): JsonResponse {
        $this->ensurePlayerBelongsToOrganization($organization, $player);

        return response()->json([
            'data' => $this->players->batting($organization->id, $player->id, $request->filters()),
        ]);
    }

    public function bowling(
        StatisticsFilterRequest $request,
        Organization $organization,
        Player $player
    ): JsonResponse {
        $this->ensurePlayerBelongsToOrganization($organization, $player);

        return response()->json([
            'data' => $this->players->bowling($organization->id, $player->id, $request->filters()),
        ]);
    }

    public function fielding(
        StatisticsFilterRequest $request,
        Organization $organization,
        Player $player
    ): JsonResponse {
        $this->ensurePlayerBelongsToOrganization($organization, $player);

        return response()->json([
            'data' => $this->players->fielding($organization->id, $player->id, $request->filters()),
        ]);
    }

    public function team(
        StatisticsFilterRequest $request,
        Organization $organization,
        Team $team
    ): JsonResponse {
        abort_unless((int) $team->organization_id === (int) $organization->id, 404);

        return response()->json([
            'data' => $this->teams->forTeam($organization->id, $team->id, $request->filters()),
        ]);
    }

    public function match(
        Organization $organization,
        CricketMatch $match
    ): JsonResponse {
        abort_unless((int) $match->organization_id === (int) $organization->id, 404);

        return response()->json([
            'data' => $this->matches->forMatch($organization->id, $match->id),
        ]);
    }

    public function phases(
        Organization $organization,
        CricketMatch $match
    ): JsonResponse {
        abort_unless((int) $match->organization_id === (int) $organization->id, 404);

        return response()->json([
            'data' => $this->phases->forMatch($organization->id, $match->id),
        ]);
    }

    private function ensurePlayerBelongsToOrganization(
        Organization $organization,
        Player $player
    ): void {
        $belongs = $player->teams()
            ->where('teams.organization_id', $organization->id)
            ->exists();

        abort_unless($belongs, 404);
    }
}
