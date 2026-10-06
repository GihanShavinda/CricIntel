<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StrategyAi\AskStrategyAssistantRequest;
use App\Models\AiStrategyRun;
use App\Models\Organization;
use App\Services\StrategyAi\DeterministicStrategyRecommendationService;
use App\Services\StrategyAi\GroundedResponseValidator;
use App\Services\StrategyAi\StrategyAiAccessService;
use App\Services\StrategyAi\StrategyAiClient;
use App\Services\StrategyAi\StrategyContextBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StrategyAiController extends Controller
{
    public function __construct(
        private readonly StrategyAiAccessService $access,
        private readonly StrategyContextBuilder $contextBuilder,
        private readonly DeterministicStrategyRecommendationService $deterministic,
        private readonly StrategyAiClient $client,
        private readonly GroundedResponseValidator $validator
    ) {}

    public function status(
        Request $request,
        Organization $organization
    ): JsonResponse {
        $this->access->assertView($request, $organization);

        return response()->json([
            'data' => [
                'enabled' => (bool) config('strategy_ai.enabled', false),
                'kill_switch_active' => ! config('strategy_ai.enabled', false),
                'service' => $this->client->health(),
                'rules' => [
                    'Never invent cricket statistics.',
                    'Never claim certainty.',
                    'Mention insufficient sample sizes.',
                    'Use only structured CricIntel context.',
                    'Coach remains responsible for final decisions.',
                ],
            ],
        ]);
    }

    public function options(
        Request $request,
        Organization $organization
    ): JsonResponse {
        $this->access->assertView($request, $organization);

        $matches = DB::table('matches as m')
            ->join('fixtures as f', 'f.id', '=', 'm.fixture_id')
            ->join('teams as ht', 'ht.id', '=', 'f.home_team_id')
            ->join('teams as at', 'at.id', '=', 'f.away_team_id')
            ->leftJoin('venues as v', 'v.id', '=', 'f.venue_id')
            ->where('m.organization_id', $organization->id)
            ->orderByDesc('f.scheduled_at')
            ->get([
                'm.id',
                'm.status',
                'm.max_overs',
                'f.scheduled_at',
                'f.home_team_id',
                'ht.name as home_team_name',
                'f.away_team_id',
                'at.name as away_team_name',
                'f.venue_id',
                'v.name as venue_name',
            ]);

        return response()->json([
            'data' => [
                'matches' => $matches,
                'example_questions' => [
                    'Which bowlers have performed best at the death against left-handed batters?',
                    'What are the opposition\'s main powerplay threats?',
                    'Which matchups deserve attention?',
                    'Explain the strengths and weaknesses of this proposed XI.',
                ],
            ],
        ]);
    }

    public function context(
        Request $request,
        Organization $organization,
        int $match
    ): JsonResponse {
        $this->access->assertView($request, $organization);

        $context = $this->contextBuilder->build(
            $organization,
            $match,
            $this->access->canSeeScouting($request)
        );

        $recommendations = $this->deterministic->build($context);

        return response()->json([
            'data' => [
                'context' => $context,
                'deterministic_recommendations' => $recommendations,
                'assistant_enabled' => (bool) config('strategy_ai.enabled', false),
            ],
        ]);
    }

    public function ask(
        AskStrategyAssistantRequest $request,
        Organization $organization,
        int $match
    ): JsonResponse {
        $this->access->assertView($request, $organization);

        abort_unless(
            config('strategy_ai.enabled', false),
            503,
            'The AI Strategy Assistant kill switch is active.'
        );

        $context = $this->contextBuilder->build(
            $organization,
            $match,
            $this->access->canSeeScouting($request)
        );

        $deterministic = $this->deterministic->build($context);
        $question = trim($request->validated('question'));

        $contextHash = hash(
            'sha256',
            json_encode(
                $context,
                JSON_UNESCAPED_SLASHES |
                JSON_UNESCAPED_UNICODE |
                JSON_PRESERVE_ZERO_FRACTION
            )
        );

        $run = AiStrategyRun::query()->create([
            'organization_id' => $organization->id,
            'match_id' => $match,
            'user_id' => $request->user()->id,
            'question' => $question,
            'context_hash' => $contextHash,
            'context_snapshot' => $context,
            'deterministic_recommendations' => $deterministic,
            'validation_status' => 'pending',
        ]);

        try {
            $raw = $this->client->ask([
                'question' => $question,
                'context' => $context,
                'deterministic_recommendations' => $deterministic,
                'rules' => [
                    'Never invent cricket statistics.',
                    'Never claim certainty.',
                    'Mention insufficient sample sizes.',
                    'Use only provided CricIntel context and evidence IDs.',
                    'Coach remains responsible for final decisions.',
                ],
            ]);
        } catch (RuntimeException $exception) {
            $run->update([
                'validation_status' => 'service_error',
                'validation_errors' => [$exception->getMessage()],
            ]);

            throw $exception;
        }

        $validation = $this->validator->validate(
            $raw['response'] ?? [],
            $context
        );

        $run->update([
            'provider' => $raw['provider'] ?? null,
            'model' => $raw['model'] ?? null,
            'raw_llm_response' => config('strategy_ai.store_raw_response', true)
                ? ($raw['response'] ?? null)
                : null,
            'validated_response' =>
                $validation['status'] === 'accepted'
                    ? $validation['response']
                    : null,
            'validation_status' => $validation['status'],
            'validation_errors' => $validation['errors'],
            'prompt_tokens' => data_get($raw, 'usage.prompt_tokens'),
            'completion_tokens' => data_get($raw, 'usage.completion_tokens'),
            'generated_at' => now(),
        ]);

        if ($validation['status'] !== 'accepted') {
            return response()->json([
                'message' =>
                    'The assistant response was rejected because it was not fully grounded in CricIntel evidence.',
                'data' => [
                    'run_id' => $run->id,
                    'validation_status' => 'rejected',
                    'validation_errors' => $validation['errors'],
                    'deterministic_recommendations' => $deterministic,
                ],
            ], 422);
        }

        return response()->json([
            'data' => [
                'run_id' => $run->id,
                'question' => $question,
                'response' => $validation['response'],
                'deterministic_recommendations' => $deterministic,
                'evidence' => $context['evidence'],
                'context_hash' => $contextHash,
                'provider' => $raw['provider'] ?? null,
                'model' => $raw['model'] ?? null,
                'generated_at' => $run->generated_at?->toIso8601String(),
            ],
        ]);
    }

    public function history(
        Request $request,
        Organization $organization
    ): JsonResponse {
        $this->access->assertView($request, $organization);

        $query = AiStrategyRun::query()
            ->where('organization_id', $organization->id)
            ->with('user:id,name,email')
            ->orderByDesc('created_at');

        if ($request->filled('match_id')) {
            $query->where('match_id', $request->integer('match_id'));
        }

        return response()->json([
            'data' => $query
                ->limit(50)
                ->get([
                    'id',
                    'organization_id',
                    'match_id',
                    'user_id',
                    'question',
                    'context_hash',
                    'deterministic_recommendations',
                    'validated_response',
                    'validation_status',
                    'validation_errors',
                    'provider',
                    'model',
                    'generated_at',
                    'created_at',
                ]),
        ]);
    }
}
