<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\NlAnalytics\RunNaturalLanguageAnalyticsRequest;
use App\Models\NlAnalyticsQuery;
use App\Models\Organization;
use App\Services\NlAnalytics\AnalyticsEntityResolver;
use App\Services\NlAnalytics\AnalyticsExplanationService;
use App\Services\NlAnalytics\ControlledAnalyticsSchema;
use App\Services\NlAnalytics\ControlledAnalyticsService;
use App\Services\NlAnalytics\ControlledAnalyticsValidator;
use App\Services\NlAnalytics\NaturalLanguageIntentParser;
use App\Services\NlAnalytics\NlAnalyticsAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NaturalLanguageAnalyticsController extends Controller
{
    public function __construct(
        private readonly NlAnalyticsAccessService $access,
        private readonly ControlledAnalyticsSchema $schema,
        private readonly NaturalLanguageIntentParser $parser,
        private readonly AnalyticsEntityResolver $resolver,
        private readonly ControlledAnalyticsValidator $validator,
        private readonly ControlledAnalyticsService $analytics,
        private readonly AnalyticsExplanationService $explanations
    ) {}

    public function options(
        Request $request,
        Organization $organization
    ): JsonResponse {
        $this->access->assertView(
            $request,
            $organization
        );

        return response()->json([
            'data' => [
                'supported_intents' =>
                    $this->schema->documentation(),
                'filters' =>
                    $this->resolver->options(
                        $organization
                    ),
                'controlled_schema' => [
                    'intents' =>
                        $this->schema->supportedIntents(),
                    'metrics' =>
                        $this->schema->supportedMetrics(),
                    'entities' =>
                        $this->schema->supportedEntities(),
                    'phases' =>
                        $this->schema->supportedPhases(),
                    'batting_hands' =>
                        $this->schema->supportedBattingHands(),
                ],
                'security' => [
                    'generated_sql_allowed' => false,
                    'arbitrary_sql_allowed' => false,
                    'execution_mode' =>
                        'validated intent handlers only',
                ],
            ],
        ]);
    }

    public function parse(
        RunNaturalLanguageAnalyticsRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->access->assertView(
            $request,
            $organization
        );

        $validated = $request->validated();

        $controlled = $this->buildControlledQuery(
            $organization,
            $validated
        );

        return response()->json([
            'data' => [
                'natural_language_query' =>
                    $validated['query'],
                'controlled_query' =>
                    $controlled,
                'will_execute_arbitrary_sql' => false,
            ],
        ]);
    }

    public function run(
        RunNaturalLanguageAnalyticsRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->access->assertView(
            $request,
            $organization
        );

        $validated = $request->validated();

        $history = NlAnalyticsQuery::query()->create([
            'organization_id' =>
                $organization->id,
            'user_id' =>
                $request->user()->id,
            'natural_language_query' =>
                $validated['query'],
            'status' => 'pending',
        ]);

        try {
            $controlled =
                $this->buildControlledQuery(
                    $organization,
                    $validated
                );

            $history->update([
                'intent' =>
                    $controlled['intent'],
                'controlled_query' =>
                    $controlled,
                'resolved_filters' =>
                    $this->filterSummary(
                        $controlled
                    ),
                'status' => 'running',
            ]);

            $result = $this->analytics->execute(
                $organization->id,
                $controlled
            );

            $explanation =
                $this->explanations->explain(
                    $controlled,
                    $result
                );

            $history->update([
                'structured_result' =>
                    $result,
                'explanation' =>
                    $explanation,
                'visualization' =>
                    $result['visualization'] ?? null,
                'status' => 'completed',
                'executed_at' => now(),
            ]);

            return response()->json([
                'data' => [
                    'query_id' =>
                        $history->id,
                    'natural_language_query' =>
                        $validated['query'],
                    'controlled_query' =>
                        $controlled,
                    'result' =>
                        $result,
                    'explanation' =>
                        $explanation,
                    'visualization' =>
                        $result['visualization'] ?? [
                            'type' => 'table',
                        ],
                    'security' => [
                        'generated_sql_used' => false,
                        'arbitrary_sql_used' => false,
                        'execution_mode' =>
                            'controlled analytics intent',
                    ],
                ],
            ]);
        } catch (ValidationException $exception) {
            $history->update([
                'status' => 'rejected',
                'errors' =>
                    $exception->errors(),
            ]);

            throw $exception;
        } catch (\Throwable $exception) {
            $history->update([
                'status' => 'failed',
                'errors' => [
                    'analytics' => [
                        $exception->getMessage(),
                    ],
                ],
            ]);

            throw $exception;
        }
    }

    public function history(
        Request $request,
        Organization $organization
    ): JsonResponse {
        $this->access->assertView(
            $request,
            $organization
        );

        $rows = NlAnalyticsQuery::query()
            ->where(
                'organization_id',
                $organization->id
            )
            ->with(
                'user:id,name,email'
            )
            ->orderByDesc('created_at')
            ->limit(75)
            ->get([
                'id',
                'organization_id',
                'user_id',
                'natural_language_query',
                'intent',
                'controlled_query',
                'resolved_filters',
                'structured_result',
                'explanation',
                'visualization',
                'status',
                'errors',
                'executed_at',
                'created_at',
            ]);

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function showHistory(
        Request $request,
        Organization $organization,
        NlAnalyticsQuery $nlAnalyticsQuery
    ): JsonResponse {
        $this->access->assertView(
            $request,
            $organization
        );

        abort_unless(
            (int) $nlAnalyticsQuery->organization_id ===
                (int) $organization->id,
            404
        );

        return response()->json([
            'data' =>
                $nlAnalyticsQuery->load(
                    'user:id,name,email'
                ),
        ]);
    }

    private function buildControlledQuery(
        Organization $organization,
        array $validated
    ): array {
        $controlled = $this->parser->parse(
            $validated['query']
        );

        $controlled = $this->resolver->resolve(
            $organization,
            $validated['query'],
            $controlled,
            [
                'team_id' =>
                    $validated['team_id'] ?? null,
                'season_id' =>
                    $validated['season_id'] ?? null,
                'opponent_team_id' =>
                    $validated['opponent_team_id'] ?? null,
                'venue_id' =>
                    $validated['venue_id'] ?? null,
                'format' =>
                    $validated['format'] ?? null,
                'date_from' =>
                    $validated['date_from'] ?? null,
                'date_to' =>
                    $validated['date_to'] ?? null,
            ]
        );

        return $this->validator->validate(
            $organization,
            $controlled
        );
    }

    private function filterSummary(
        array $controlled
    ): array {
        return collect($controlled)
            ->only([
                'team_id',
                'team_name',
                'season_id',
                'season_name',
                'opponent_team_id',
                'opponent_name',
                'venue_id',
                'venue_name',
                'format',
                'phase',
                'batting_hand',
                'player_id',
                'player_name',
                'batter_id',
                'batter_name',
                'bowler_id',
                'bowler_name',
                'date_from',
                'date_to',
                'last_n_matches',
            ])
            ->filter(
                fn ($value) =>
                    $value !== null &&
                    $value !== '' &&
                    $value !== 'all'
            )
            ->all();
    }
}
