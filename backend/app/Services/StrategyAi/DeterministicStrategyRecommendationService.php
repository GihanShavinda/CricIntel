<?php

namespace App\Services\StrategyAi;

class DeterministicStrategyRecommendationService
{
    public function build(array $context): array
    {
        $recommendations = [];

        $deathCandidates = collect(
            data_get($context, 'player_data.bowling_phase_stats', [])
        )
            ->filter(fn (array $row) =>
                ($row['phase'] ?? null) === 'death' &&
                ($row['vs_batting_hand'] ?? null) === 'left' &&
                (int) ($row['balls'] ?? 0) >= 12
            )
            ->sortBy([
                ['economy', 'asc'],
                ['wickets', 'desc'],
            ])
            ->take(3)
            ->values();

        if ($deathCandidates->isNotEmpty()) {
            $best = $deathCandidates->first();

            $recommendations[] = [
                'key' => 'death_bowling_left_handers',
                'title' => 'Death bowling vs left-handed batters',
                'recommendation' =>
                    "{$best['player_name']} has the strongest recorded death-over economy " .
                    "against left-handed batters among the qualified selected/focus-team bowlers.",
                'confidence' => (int) $best['balls'] >= 30 ? 'moderate' : 'low',
                'evidence_ids' => array_values(array_filter([
                    $best['economy_evidence_id'] ?? null,
                    $best['wickets_evidence_id'] ?? null,
                    $best['balls_evidence_id'] ?? null,
                ])),
                'limitations' => [
                    'This is a deterministic ranking over historical CricIntel samples, not a guarantee.',
                    'Qualification requires at least 12 recorded legal balls in this split.',
                ],
            ];
        }

        $powerplayThreats = collect(
            data_get($context, 'opponent_data.batter_phase_stats', [])
        )
            ->filter(fn (array $row) =>
                ($row['phase'] ?? null) === 'powerplay' &&
                (int) ($row['balls'] ?? 0) >= 12
            )
            ->sortByDesc('strike_rate')
            ->take(3)
            ->values();

        if ($powerplayThreats->isNotEmpty()) {
            $best = $powerplayThreats->first();

            $recommendations[] = [
                'key' => 'opponent_powerplay_threat',
                'title' => 'Opponent powerplay threat',
                'recommendation' =>
                    "{$best['player_name']} has the highest recorded powerplay strike rate " .
                    "among opponent batters with a qualifying sample.",
                'confidence' => (int) $best['balls'] >= 30 ? 'moderate' : 'low',
                'evidence_ids' => array_values(array_filter([
                    $best['strike_rate_evidence_id'] ?? null,
                    $best['balls_evidence_id'] ?? null,
                ])),
                'limitations' => [
                    'Qualification requires at least 12 recorded legal balls.',
                    'Historical strike rate does not determine future performance.',
                ],
            ];
        }

        $matchups = collect(
            data_get($context, 'matchups', [])
        )
            ->filter(fn (array $row) =>
                (int) ($row['balls'] ?? 0) >= 6 ||
                (int) ($row['dismissals'] ?? 0) >= 1
            )
            ->sortByDesc(fn (array $row) =>
                ((int) ($row['dismissals'] ?? 0) * 1000) +
                (int) ($row['balls'] ?? 0)
            )
            ->take(5)
            ->values();

        if ($matchups->isNotEmpty()) {
            $top = $matchups->first();

            $recommendations[] = [
                'key' => 'direct_matchup_attention',
                'title' => 'Direct matchup to review',
                'recommendation' =>
                    "Review the historical matchup between {$top['batter_name']} and " .
                    "{$top['bowler_name']} before finalizing the plan.",
                'confidence' => (int) $top['balls'] >= 24 ? 'moderate' : 'low',
                'evidence_ids' => array_values(array_filter([
                    $top['runs_evidence_id'] ?? null,
                    $top['balls_evidence_id'] ?? null,
                    $top['dismissals_evidence_id'] ?? null,
                    $top['strike_rate_evidence_id'] ?? null,
                ])),
                'limitations' => [
                    'Direct batter-vs-bowler samples can be sparse.',
                    'This flags a matchup for review; it does not prescribe a guaranteed tactic.',
                ],
            ];
        }

        $xi = collect(data_get($context, 'selected_squad.playing_xi', []));
        if ($xi->isNotEmpty()) {
            $keepers = $xi->filter(fn (array $row) => (bool) ($row['is_wicketkeeper'] ?? false))->count();
            $captains = $xi->filter(fn (array $row) => (bool) ($row['is_captain'] ?? false))->count();
            $bowlers = $xi->filter(function (array $row) {
                $role = mb_strtolower((string) ($row['primary_role'] ?? ''));

                return str_contains($role, 'bowl') ||
                    str_contains($role, 'all');
            })->count();

            $recommendations[] = [
                'key' => 'xi_structure_check',
                'title' => 'Playing XI structure',
                'recommendation' =>
                    "The proposed XI contains {$xi->count()} players, {$captains} captain flag(s), " .
                    "{$keepers} wicketkeeper flag(s), and {$bowlers} bowling/all-round role(s).",
                'confidence' => 'high',
                'evidence_ids' => array_values(array_filter(
                    $xi->pluck('selection_evidence_id')->all()
                )),
                'limitations' => [
                    'Role labels are descriptive roster data and do not measure tactical quality.',
                ],
            ];
        }

        if ($recommendations === []) {
            $recommendations[] = [
                'key' => 'insufficient_deterministic_evidence',
                'title' => 'Insufficient deterministic evidence',
                'recommendation' =>
                    'CricIntel does not currently have enough qualified historical samples to create a deterministic tactical ranking for this match.',
                'confidence' => 'low',
                'evidence_ids' => [],
                'limitations' => [
                    'Add completed historical match data rather than lowering sample-size safeguards.',
                ],
            ];
        }

        return $recommendations;
    }
}
