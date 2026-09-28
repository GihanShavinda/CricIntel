<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\Realtime\DeliveryRecorded;
use App\Events\Realtime\InningsCompleted;
use App\Events\Realtime\MatchCompleted;
use App\Events\Realtime\WicketRecorded;
use App\Http\Controllers\Controller;
use App\Http\Requests\Match\CompleteMatchRequest;
use App\Http\Requests\Match\CreateMatchRequest;
use App\Http\Requests\Match\RecordDeliveryRequest;
use App\Http\Requests\Match\StartInningsRequest;
use App\Http\Requests\Match\StartMatchRequest;
use App\Models\CricketMatch;
use App\Models\Fixture;
use App\Models\Innings;
use App\Models\Organization;
use App\Services\MatchScoringService;
use App\Services\Realtime\LiveMatchStateService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class MatchController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly MatchScoringService $scoring,
        private readonly LiveMatchStateService $liveState,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | List Matches
    |--------------------------------------------------------------------------
    */

    public function index(
        Organization $organization
    ): JsonResponse {
        $this->authorize(
            'viewAny',
            [
                CricketMatch::class,
                $organization,
            ]
        );

        $matches = CricketMatch::query()
            ->where(
                'organization_id',
                $organization->id
            )
            ->with([
                'fixture',
            ])
            ->orderByDesc(
                'created_at'
            )
            ->paginate(20);

        return response()->json(
            $matches
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Create Match
    |--------------------------------------------------------------------------
    */

    public function store(
        CreateMatchRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->authorize(
            'create',
            [
                CricketMatch::class,
                $organization,
            ]
        );

        $data = $request->validated();

        $fixture = Fixture::query()
            ->where(
                'organization_id',
                $organization->id
            )
            ->findOrFail(
                $data['fixture_id']
            );

        $existing = CricketMatch::query()
            ->where(
                'organization_id',
                $organization->id
            )
            ->where(
                'fixture_id',
                $fixture->id
            )
            ->first();

        if ($existing) {
            return response()->json([
                'message' =>
                    'A match already exists for this fixture.',

                'data' =>
                    $existing,
            ], 409);
        }

        $match = CricketMatch::create([
            'organization_id' =>
                $organization->id,

            'fixture_id' =>
                $fixture->id,

            'toss_winner_id' =>
                $data['toss_winner_id'] ?? null,

            'toss_decision' =>
                $data['toss_decision'] ?? null,

            'status' =>
                $data['status'] ?? 'Scheduled',

            'max_overs' =>
                $data['max_overs'] ?? null,
        ]);

        return response()->json([
            'message' =>
                'Match created successfully.',

            'data' =>
                $match->fresh([
                    'fixture',
                ]),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Start Match
    |--------------------------------------------------------------------------
    */

    public function start(
        StartMatchRequest $request,
        Organization $organization,
        CricketMatch $match
    ): JsonResponse {
        $this->ensureMatchOrganization(
            $organization,
            $match
        );

        $this->authorize(
            'update',
            $match
        );

        $updatedMatch = $this->scoring
            ->startMatch(
                $match,
                $request->validated()
            );

        return response()->json([
            'message' =>
                'Match started successfully.',

            'data' =>
                $updatedMatch,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Start Innings
    |--------------------------------------------------------------------------
    */

    public function startInnings(
        StartInningsRequest $request,
        Organization $organization,
        CricketMatch $match
    ): JsonResponse {
        $this->ensureMatchOrganization(
            $organization,
            $match
        );

        $this->authorize(
            'update',
            $match
        );

        $innings = $this->scoring
            ->startInnings(
                $match,
                $request->validated()
            );

        /*
        |--------------------------------------------------------------------------
        | P8 - Broadcast Initial Live State
        |--------------------------------------------------------------------------
        |
        | The scoring service is authoritative. Broadcasting only tells connected
        | clients that they should consume the freshly calculated match snapshot.
        |
        */

        DeliveryRecorded::dispatch(
            $match->fresh()
        );

        return response()->json([
            'message' =>
                'Innings started successfully.',

            'data' =>
                $innings,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Record Delivery
    |--------------------------------------------------------------------------
    */

    public function recordDelivery(
        RecordDeliveryRequest $request,
        Organization $organization,
        CricketMatch $match,
        Innings $innings
    ): JsonResponse {
        $this->ensureMatchOrganization(
            $organization,
            $match
        );

        $this->ensureInningsMatch(
            $match,
            $innings
        );

        $this->authorize(
            'update',
            $match
        );

        $delivery = $this->scoring
            ->recordDelivery(
                $innings,
                $request->validated()
            );

        $freshMatch = $match->fresh();

        DeliveryRecorded::dispatch(
            $freshMatch
        );

        if (
            (bool) $delivery->wicket
        ) {
            WicketRecorded::dispatch(
                $freshMatch
            );
        }

        $freshInnings = $innings->fresh();

        if (
            $freshInnings &&
            $freshInnings->status === 'Completed'
        ) {
            InningsCompleted::dispatch(
                $freshMatch
            );
        }

        $freshMatch = $freshMatch->fresh();

        if (
            $freshMatch->status === 'Completed'
        ) {
            MatchCompleted::dispatch(
                $freshMatch
            );
        }

        return response()->json([
            'message' =>
                'Delivery recorded successfully.',

            'data' => [
                'delivery' =>
                    $delivery,

                'live' =>
                    $this->liveState
                        ->snapshot(
                            $freshMatch
                        ),
            ],
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Undo Latest Delivery
    |--------------------------------------------------------------------------
    */

    public function undoDelivery(
        Organization $organization,
        CricketMatch $match,
        Innings $innings
    ): JsonResponse {
        $this->ensureMatchOrganization(
            $organization,
            $match
        );

        $this->ensureInningsMatch(
            $match,
            $innings
        );

        $this->authorize(
            'update',
            $match
        );

        $result = $this->scoring
            ->undoLatestDelivery(
                $innings
            );

        $freshMatch = $match->fresh();

        DeliveryRecorded::dispatch(
            $freshMatch
        );

        return response()->json([
            'message' =>
                'Latest delivery undone successfully.',

            'data' => [
                'result' =>
                    $result,

                'live' =>
                    $this->liveState
                        ->snapshot(
                            $freshMatch
                        ),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Complete Innings
    |--------------------------------------------------------------------------
    */

    public function completeInnings(
        Organization $organization,
        CricketMatch $match,
        Innings $innings
    ): JsonResponse {
        $this->ensureMatchOrganization(
            $organization,
            $match
        );

        $this->ensureInningsMatch(
            $match,
            $innings
        );

        $this->authorize(
            'update',
            $match
        );

        $completedInnings = $this->scoring
            ->completeInnings(
                $innings
            );

        $freshMatch = $match->fresh();

        InningsCompleted::dispatch(
            $freshMatch
        );

        $freshMatch = $freshMatch->fresh();

        if (
            $freshMatch->status === 'Completed'
        ) {
            MatchCompleted::dispatch(
                $freshMatch
            );
        }

        return response()->json([
            'message' =>
                'Innings completed successfully.',

            'data' => [
                'innings' =>
                    $completedInnings,

                'live' =>
                    $this->liveState
                        ->snapshot(
                            $freshMatch
                        ),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Complete Match
    |--------------------------------------------------------------------------
    */

    public function completeMatch(
        CompleteMatchRequest $request,
        Organization $organization,
        CricketMatch $match
    ): JsonResponse {
        $this->ensureMatchOrganization(
            $organization,
            $match
        );

        $this->authorize(
            'update',
            $match
        );

        $completedMatch = $this->scoring
            ->completeMatch(
                $match,
                $request->validated()
            );

        $freshMatch = $match->fresh();

        MatchCompleted::dispatch(
            $freshMatch
        );

        return response()->json([
            'message' =>
                'Match completed successfully.',

            'data' => [
                'match' =>
                    $completedMatch,

                'live' =>
                    $this->liveState
                        ->snapshot(
                            $freshMatch
                        ),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Scorecard
    |--------------------------------------------------------------------------
    |
    | A scheduled future match is a valid resource even when no innings exist.
    | Therefore, no-innings must return HTTP 200 with innings: [] rather than
    | firstOrFail() -> 404.
    |
    */

    public function scorecard(
        Organization $organization,
        CricketMatch $match
    ): JsonResponse {
        $this->ensureMatchOrganization(
            $organization,
            $match
        );

        $this->authorize(
            'view',
            $match
        );

        $freshMatch = $match->fresh([
            'fixture',
        ]);

        $inningsList = Innings::query()
            ->where(
                'match_id',
                $match->id
            )
            ->orderBy(
                'innings_number'
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Scheduled / Future Match
        |--------------------------------------------------------------------------
        |
        | This is not an error state. The match exists, belongs to the requested
        | organization, and simply has not started yet.
        |
        */

        if (
            $inningsList->isEmpty()
        ) {
            return response()->json([
                'data' => [
                    'id' =>
                        $freshMatch->id,

                    'fixture_id' =>
                        $freshMatch->fixture_id,

                    'status' =>
                        $freshMatch->status,

                    'result_type' =>
                        $freshMatch->result_type,

                    'winner_team_id' =>
                        $freshMatch->winner_team_id,

                    'player_of_match_id' =>
                        $freshMatch->player_of_match_id,

                    'target_runs' =>
                        $freshMatch->target_runs,

                    'max_overs' =>
                        $freshMatch->max_overs,

                    'toss_winner_id' =>
                        $freshMatch->toss_winner_id,

                    'toss_decision' =>
                        $freshMatch->toss_decision,

                    'fixture' =>
                        $freshMatch->fixture,

                    'innings' => [],
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Match With Innings
        |--------------------------------------------------------------------------
        |
        | Score every innings independently using the existing deterministic P5
        | scoring service. This keeps the response stable as:
        |
        | match metadata + innings[]
        |
        */

        $scoredInnings = $inningsList
            ->map(
                fn (Innings $innings) =>
                    $this->scoring
                        ->score(
                            $innings
                        )
            )
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'id' =>
                    $freshMatch->id,

                'fixture_id' =>
                    $freshMatch->fixture_id,

                'status' =>
                    $freshMatch->status,

                'result_type' =>
                    $freshMatch->result_type,

                'winner_team_id' =>
                    $freshMatch->winner_team_id,

                'player_of_match_id' =>
                    $freshMatch->player_of_match_id,

                'target_runs' =>
                    $freshMatch->target_runs,

                'max_overs' =>
                    $freshMatch->max_overs,

                'toss_winner_id' =>
                    $freshMatch->toss_winner_id,

                'toss_decision' =>
                    $freshMatch->toss_decision,

                'fixture' =>
                    $freshMatch->fixture,

                'innings' =>
                    $scoredInnings,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Guard: Match belongs to organization
    |--------------------------------------------------------------------------
    */

    private function ensureMatchOrganization(
        Organization $organization,
        CricketMatch $match
    ): void {
        abort_unless(
            (int) $match->organization_id ===
                (int) $organization->id,
            404,
            'Match not found in this organization.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Guard: Innings belongs to Match
    |--------------------------------------------------------------------------
    */

    private function ensureInningsMatch(
        CricketMatch $match,
        Innings $innings
    ): void {
        abort_unless(
            (int) $innings->match_id ===
                (int) $match->id,
            404,
            'Innings does not belong to this match.'
        );
    }
}
