<?php

namespace App\Services\Analytics;

use App\Models\Player;
use App\Services\Statistics\StatisticsCacheService;

class PlayerComparisonService
{
    public function __construct(
        private readonly FilteredPlayerStatisticsService $statistics,
        private readonly StatisticsCacheService $cache,
    ) {}

    public function compare(
        int $organizationId,
        int $playerA,
        int $playerB,
        array $filters = []
    ): array {
        return $this->cache->remember(
            $organizationId,
            'analytics:compare',
            [
                $playerA,
                $playerB,
                $filters,
            ],
            function () use (
                $organizationId,
                $playerA,
                $playerB,
                $filters
            ) {
                return [
                    'player_a' =>
                        $this->player(
                            $organizationId,
                            $playerA,
                            $filters
                        ),

                    'player_b' =>
                        $this->player(
                            $organizationId,
                            $playerB,
                            $filters
                        ),
                ];
            }
        );
    }

    private function player(
        int $organizationId,
        int $playerId,
        array $filters
    ): array {
        $player =
            Player::query()
                ->where(
                    'organization_id',
                    $organizationId
                )
                ->findOrFail(
                    $playerId
                );

        $stats =
            $this->statistics
                ->forPlayer(
                    $organizationId,
                    $playerId,
                    $filters
                );

        return [
            'player' => [
                'id' =>
                    $player->id,

                'name' =>
                    $player
                        ->display_name,

                'role' =>
                    $player
                        ->primary_role,

                'batting_style' =>
                    $player
                        ->batting_style,

                'bowling_style' =>
                    $player
                        ->bowling_style,
            ],

            ...$stats,
        ];
    }
}
