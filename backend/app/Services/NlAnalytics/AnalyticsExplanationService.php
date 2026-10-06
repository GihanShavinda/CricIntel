<?php

namespace App\Services\NlAnalytics;

class AnalyticsExplanationService
{
    public function explain(
        array $schema,
        array $result
    ): string {
        $rows = collect(
            $result['rows'] ?? []
        );

        if ($rows->isEmpty()) {
            return
                'CricIntel found no recorded data matching the validated filters. ' .
                'No statistical conclusion should be drawn from this request.';
        }

        return match ($schema['intent']) {
            'rank_bowlers_phase_vs_hand' =>
                $this->rankingExplanation(
                    $schema,
                    $rows->first()
                ),

            'team_run_rate_trend' =>
                $this->runRateTrendExplanation(
                    $schema,
                    $rows
                ),

            'batter_phase_performance' =>
                $this->rankingExplanation(
                    $schema,
                    $rows->first()
                ),

            'bowler_phase_performance' =>
                $this->rankingExplanation(
                    $schema,
                    $rows->first()
                ),

            'player_form_trend' =>
                $this->formExplanation(
                    $schema,
                    $rows
                ),

            'team_phase_scoring' =>
                $this->phaseScoringExplanation(
                    $rows
                ),

            'matchup_summary' =>
                $this->matchupExplanation(
                    $rows->first()
                ),

            'venue_scoring_summary' =>
                $this->venueExplanation(
                    $rows->first()
                ),

            'opponent_phase_threats' =>
                $this->rankingExplanation(
                    $schema,
                    $rows->first()
                ),

            default =>
                'CricIntel returned the structured analytics result shown below.',
        };
    }

    private function rankingExplanation(
        array $schema,
        array $top
    ): string {
        $name =
            $top['player_name']
            ?? $top['batter_name']
            ?? $top['bowler_name']
            ?? 'The leading row';

        $metric = $schema['metric'];
        $value = $top[$metric] ?? null;
        $sample = $top['balls'] ?? null;

        $text =
            "{$name} ranks first for the validated {$metric} query";

        if ($value !== null) {
            $text .= " with a recorded value of {$value}";
        }

        if ($sample !== null) {
            $text .= " across {$sample} legal balls";
        }

        $text .= '. ';

        if (
            is_numeric($sample) &&
            (int) $sample < 30
        ) {
            $text .=
                'The sample is limited, so this should be treated as a review signal rather than a strong conclusion.';
        } else {
            $text .=
                'This describes historical CricIntel data only and does not guarantee future performance.';
        }

        return $text;
    }

    private function runRateTrendExplanation(
        array $schema,
        $rows
    ): string {
        if ($rows->count() < 2) {
            return
                'Only one matching game was found, so CricIntel cannot establish a multi-match run-rate trend.';
        }

        $first = $rows->first();
        $last = $rows->last();

        $start = $first['run_rate'];
        $end = $last['run_rate'];
        $change =
            $start !== null &&
            $end !== null
                ? round(
                    $end - $start,
                    2
                )
                : null;

        $text =
            "Across the {$rows->count()} matching games, the selected phase run rate moved from {$start} to {$end}";

        if ($change !== null) {
            $direction =
                $change < 0
                    ? 'a decrease'
                    : (
                        $change > 0
                            ? 'an increase'
                            : 'no net change'
                    );

            $text .=
                ", which is {$direction} of " .
                abs($change) . '.';
        } else {
            $text .= '.';
        }

        if (
            isset(
                $first['dot_ball_pct'],
                $last['dot_ball_pct']
            )
        ) {
            $dotChange = round(
                (float) $last['dot_ball_pct'] -
                (float) $first['dot_ball_pct'],
                2
            );

            $text .=
                " Dot-ball percentage changed from {$first['dot_ball_pct']} to {$last['dot_ball_pct']}";

            if ($dotChange !== 0.0) {
                $text .=
                    ' (' .
                    ($dotChange > 0 ? '+' : '') .
                    "{$dotChange} percentage points)";
            }

            $text .= '.';
        }

        $text .=
            ' These are descriptive historical values; CricIntel is not claiming a single causal reason unless the stored data directly supports it.';

        return $text;
    }

    private function formExplanation(
        array $schema,
        $rows
    ): string {
        if ($rows->count() < 2) {
            return
                'CricIntel found fewer than two matching games, so a reliable form trend cannot be described.';
        }

        $metric = $schema['metric'];
        $first = $rows->first()[$metric] ?? null;
        $last = $rows->last()[$metric] ?? null;

        return
            "For the selected {$metric} metric, the recorded value moved from {$first} in the earliest displayed game to {$last} in the latest displayed game. " .
            'This is a historical trend, not a prediction.';
    }

    private function phaseScoringExplanation(
        $rows
    ): string {
        $best = $rows
            ->filter(
                fn ($row) =>
                    $row['run_rate'] !== null
            )
            ->sortByDesc('run_rate')
            ->first();

        if (! $best) {
            return
                'CricIntel does not have enough legal-ball data to compare scoring phases.';
        }

        return
            "The highest recorded phase run rate in the validated sample is {$best['run_rate']} during {$best['phase']}. " .
            'Use the table and chart to compare the remaining phases and their sample sizes.';
    }

    private function matchupExplanation(
        array $row
    ): string {
        return
            "{$row['batter_name']} has scored {$row['runs']} runs from {$row['balls']} recorded legal balls against {$row['bowler_name']}, " .
            "with a strike rate of {$row['strike_rate']} and {$row['dismissals']} recorded dismissal(s). " .
            'Direct matchup samples can be small, so this should be interpreted cautiously.';
    }

    private function venueExplanation(
        array $row
    ): string {
        if (
            ! $row['matches']
        ) {
            return
                'CricIntel has no completed first-innings samples matching this venue and filter set.';
        }

        return
            "Across {$row['matches']} matching completed games at {$row['venue_name']}, the recorded average first-innings total is {$row['average_first_innings_total']}, " .
            "with observed totals ranging from {$row['minimum_first_innings_total']} to {$row['maximum_first_innings_total']}.";
    }
}
