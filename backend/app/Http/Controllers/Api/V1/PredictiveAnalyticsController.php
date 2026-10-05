<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Predictive\PredictionContextRequest;
use App\Http\Requests\Predictive\TrainPredictiveModelsRequest;
use App\Models\Organization;
use App\Models\Player;
use App\Models\Team;
use App\Models\Venue;
use App\Services\Predictive\PredictiveAnalyticsClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PredictiveAnalyticsController extends Controller
{
    public function __construct(
        private readonly PredictiveAnalyticsClient $client
    ) {}


    public function options(
        Request $request,
        Organization $organization
    ): JsonResponse {
        $this->assertCanView($request, $organization);

        $players = Player::query()
            ->where('organization_id', $organization->id)
            ->orderBy('display_name')
            ->get([
                'id',
                'display_name',
                'primary_role',
                'batting_style',
                'bowling_style',
                'status',
            ]);

        $teams = Team::query()
            ->whereHas('club', fn ($query) =>
                $query->where('organization_id', $organization->id)
            )
            ->orderBy('name')
            ->get(['id', 'name', 'short_name']);

        $venues = Venue::query()
            ->where('organization_id', $organization->id)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'country', 'pitch_type']);

        return response()->json([
            'data' => [
                'players' => $players,
                'teams' => $teams,
                'venues' => $venues,
            ],
        ]);
    }

    public function readiness(
        Request $request,
        Organization $organization
    ): JsonResponse {
        $this->assertCanView($request, $organization);

        return $this->proxy(
            fn () => $this->client->readiness($organization->id)
        );
    }

    public function train(
        TrainPredictiveModelsRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->assertCanTrain($request, $organization);

        $data = $request->validated();

        return $this->proxy(
            fn () => $this->client->train(
                $organization->id,
                $data['model_kinds'],
                (bool) ($data['force'] ?? false)
            )
        );
    }

    public function versions(
        Request $request,
        Organization $organization,
        string $modelKind
    ): JsonResponse {
        $this->assertCanView($request, $organization);

        abort_unless(
            in_array($modelKind, [
                'batter_score',
                'bowler_economy',
                'team_total',
            ], true),
            404
        );

        return $this->proxy(
            fn () => $this->client->modelVersions(
                $organization->id,
                $modelKind
            )
        );
    }

    public function batterScore(
        PredictionContextRequest $request,
        Organization $organization,
        Player $player
    ): JsonResponse {
        $this->assertCanView($request, $organization);
        $this->assertPlayerOrganization($organization, $player);
        $context = $this->validatedContext(
            $request,
            $organization
        );

        return $this->proxy(
            fn () => $this->client->predictBatterScore([
                'organization_id' => $organization->id,
                'player_id' => $player->id,
                ...$context,
            ])
        );
    }

    public function bowlerEconomy(
        PredictionContextRequest $request,
        Organization $organization,
        Player $player
    ): JsonResponse {
        $this->assertCanView($request, $organization);
        $this->assertPlayerOrganization($organization, $player);
        $context = $this->validatedContext(
            $request,
            $organization
        );

        return $this->proxy(
            fn () => $this->client->predictBowlerEconomy([
                'organization_id' => $organization->id,
                'player_id' => $player->id,
                ...$context,
            ])
        );
    }

    public function teamTotal(
        PredictionContextRequest $request,
        Organization $organization,
        Team $team
    ): JsonResponse {
        $this->assertCanView($request, $organization);
        $this->assertTeamOrganization($organization, $team);
        $context = $this->validatedContext(
            $request,
            $organization
        );

        return $this->proxy(
            fn () => $this->client->predictTeamTotal([
                'organization_id' => $organization->id,
                'team_id' => $team->id,
                ...$context,
            ])
        );
    }

    public function playerForm(
        Request $request,
        Organization $organization,
        Player $player
    ): JsonResponse {
        $this->assertCanView($request, $organization);
        $this->assertPlayerOrganization($organization, $player);

        return $this->proxy(
            fn () => $this->client->playerForm(
                $organization->id,
                $player->id
            )
        );
    }

    private function validatedContext(
        PredictionContextRequest $request,
        Organization $organization
    ): array {
        $data = $request->validated();

        if (! empty($data['opponent_team_id'])) {
            $team = Team::query()->findOrFail(
                $data['opponent_team_id']
            );
            $this->assertTeamOrganization($organization, $team);
        }

        if (! empty($data['venue_id'])) {
            abort_unless(
                Venue::query()
                    ->whereKey($data['venue_id'])
                    ->where('organization_id', $organization->id)
                    ->exists(),
                422,
                'The venue must belong to this organization.'
            );
        }

        return [
            'opponent_team_id' => $data['opponent_team_id'] ?? null,
            'venue_id' => $data['venue_id'] ?? null,
            'max_overs' => (int) ($data['max_overs'] ?? 20),
        ];
    }

    private function proxy(callable $callback): JsonResponse
    {
        try {
            return response()->json([
                'data' => $callback(),
            ]);
        } catch (ConnectionException $exception) {
            return response()->json([
                'message' => (
                    'Predictive analytics service is unavailable. '
                    . 'Start the local FastAPI service on port 8100.'
                ),
            ], 503);
        } catch (RequestException $exception) {
            $response = $exception->response;

            return response()->json([
                'message' => data_get(
                    $response->json(),
                    'detail',
                    'Predictive analytics request failed.'
                ),
                'service_status' => $response->status(),
            ], match (true) {
                $response->status() === 409 => 409,
                $response->status() === 422 => 422,
                default => 502,
            });
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 503);
        }
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

        abort_unless(
            $user->hasAnyRole([
                'Coach',
                'Analyst',
                'Selector',
                'Team Manager',
            ]),
            403,
            'You are not authorized to view predictive analytics.'
        );
    }

    private function assertCanTrain(
        Request $request,
        Organization $organization
    ): void {
        $this->assertCanView($request, $organization);

        abort_unless(
            $request->user()->can('access-administration') ||
            $request->user()->hasRole('Coach'),
            403,
            'Only a coach or administrator can train predictive models.'
        );
    }

    private function assertPlayerOrganization(
        Organization $organization,
        Player $player
    ): void {
        abort_unless(
            (int) $player->organization_id === (int) $organization->id,
            404
        );
    }

    private function assertTeamOrganization(
        Organization $organization,
        Team $team
    ): void {
        abort_unless(
            $team->club()
                ->where('organization_id', $organization->id)
                ->exists(),
            404
        );
    }
}
