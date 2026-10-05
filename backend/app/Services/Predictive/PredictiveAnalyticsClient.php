<?php

namespace App\Services\Predictive;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PredictiveAnalyticsClient
{
    public function health(): array
    {
        return $this->request()->get('/health')->throw()->json();
    }

    public function readiness(int $organizationId): array
    {
        return $this->request()
            ->get("/v1/readiness/{$organizationId}")
            ->throw()
            ->json();
    }

    public function train(
        int $organizationId,
        array $modelKinds,
        bool $force = false
    ): array {
        return $this->request(
            config('predictive.training_timeout_seconds', 180)
        )
            ->post('/v1/train', [
                'organization_id' => $organizationId,
                'model_kinds' => $modelKinds,
                'force' => $force,
            ])
            ->throw()
            ->json();
    }

    public function modelVersions(
        int $organizationId,
        string $modelKind
    ): array {
        return $this->request()
            ->get("/v1/models/{$organizationId}/{$modelKind}")
            ->throw()
            ->json();
    }

    public function predictBatterScore(array $payload): array
    {
        return $this->request()
            ->post('/v1/predict/batter-score', $payload)
            ->throw()
            ->json();
    }

    public function predictBowlerEconomy(array $payload): array
    {
        return $this->request()
            ->post('/v1/predict/bowler-economy', $payload)
            ->throw()
            ->json();
    }

    public function predictTeamTotal(array $payload): array
    {
        return $this->request()
            ->post('/v1/predict/team-total', $payload)
            ->throw()
            ->json();
    }

    public function playerForm(
        int $organizationId,
        int $playerId
    ): array {
        return $this->request()
            ->get("/v1/form/{$organizationId}/players/{$playerId}")
            ->throw()
            ->json();
    }

    private function request(?int $timeout = null): PendingRequest
    {
        if (! config('predictive.enabled', true)) {
            throw new RuntimeException(
                'Predictive analytics is disabled.'
            );
        }

        return Http::baseUrl(
            rtrim((string) config('predictive.base_url'), '/')
        )
            ->acceptJson()
            ->asJson()
            ->timeout(
                $timeout ??
                config('predictive.timeout_seconds', 15)
            );
    }
}
