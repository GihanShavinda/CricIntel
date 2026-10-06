<?php

namespace App\Services\StrategyAi;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StrategyAiClient
{
    public function ask(array $payload): array
    {
        if (! config('strategy_ai.enabled', false)) {
            throw new RuntimeException(
                'The CricIntel AI Strategy Assistant is disabled by the kill switch.'
            );
        }

        try {
            $response = $this->request()
                ->post('/v1/strategy-assistant/ask', $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'The local AI service is unavailable. Start FastAPI on port 8100.',
                previous: $exception
            );
        }

        if (! $response->successful()) {
            $detail = $response->json('detail')
                ?? $response->json('message')
                ?? $response->body();

            throw new RuntimeException(
                'Strategy AI service error: ' . mb_substr((string) $detail, 0, 1000)
            );
        }

        return $response->json();
    }

    public function health(): array
    {
        try {
            $response = $this->request(5)
                ->get('/health');

            return [
                'reachable' => $response->successful(),
                'service' => $response->json(),
            ];
        } catch (\Throwable $exception) {
            return [
                'reachable' => false,
                'service' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function request(?int $timeout = null): PendingRequest
    {
        return Http::baseUrl(
            rtrim((string) config('strategy_ai.base_url'), '/')
        )
            ->acceptJson()
            ->asJson()
            ->timeout(
                $timeout ?? config('strategy_ai.timeout_seconds', 45)
            );
    }
}
