<?php

namespace App\Services\Statistics;

class PlayerStatisticsService
{
    public function __construct(
        private readonly BattingStatisticsService $batting,
        private readonly BowlingStatisticsService $bowling,
        private readonly FieldingStatisticsService $fielding,
        private readonly StatisticsCacheService $cache,
    ) {}

    public function all(int $organizationId, int $playerId, array $filters = []): array
    {
        return $this->cache->remember(
            $organizationId,
            'player:all',
            [$playerId, $filters],
            fn () => [
                'batting' => $this->batting->forPlayer($organizationId, $playerId, $filters),
                'bowling' => $this->bowling->forPlayer($organizationId, $playerId, $filters),
                'fielding' => $this->fielding->forPlayer($organizationId, $playerId, $filters),
            ]
        );
    }

    public function batting(int $organizationId, int $playerId, array $filters = []): array
    {
        return $this->cache->remember(
            $organizationId,
            'player:batting',
            [$playerId, $filters],
            fn () => $this->batting->forPlayer($organizationId, $playerId, $filters)
        );
    }

    public function bowling(int $organizationId, int $playerId, array $filters = []): array
    {
        return $this->cache->remember(
            $organizationId,
            'player:bowling',
            [$playerId, $filters],
            fn () => $this->bowling->forPlayer($organizationId, $playerId, $filters)
        );
    }

    public function fielding(int $organizationId, int $playerId, array $filters = []): array
    {
        return $this->cache->remember(
            $organizationId,
            'player:fielding',
            [$playerId, $filters],
            fn () => $this->fielding->forPlayer($organizationId, $playerId, $filters)
        );
    }
}
