<?php

namespace App\Services\OpponentAnalytics;

class OpponentAnalyticsCalculator
{
    public function phaseForOver(int $overNumber, ?int $maxOvers): string
    {
        $maxOvers = $maxOvers ?: 20;

        if ($maxOvers <= 20) {
            if ($overNumber <= 6) {
                return 'Powerplay';
            }

            if ($overNumber <= 15) {
                return 'Middle';
            }

            return 'Death';
        }

        if ($maxOvers <= 50) {
            if ($overNumber <= 10) {
                return 'Powerplay';
            }

            if ($overNumber <= 40) {
                return 'Middle';
            }

            return 'Death';
        }

        $firstCut = max(1, (int) floor($maxOvers * 0.2));
        $secondCut = max($firstCut + 1, (int) floor($maxOvers * 0.8));

        if ($overNumber <= $firstCut) {
            return 'Powerplay';
        }

        if ($overNumber <= $secondCut) {
            return 'Middle';
        }

        return 'Death';
    }

    public function bowlingCategory(?string $style): string
    {
        $value = mb_strtolower(trim((string) $style));

        if ($value === '') {
            return 'unknown';
        }

        if (
            str_contains($value, 'off spin') ||
            str_contains($value, 'off-spin') ||
            str_contains($value, 'offbreak') ||
            str_contains($value, 'off break')
        ) {
            return 'off_spin';
        }

        if (
            str_contains($value, 'left arm orthodox') ||
            str_contains($value, 'left-arm orthodox') ||
            str_contains($value, 'slow left arm') ||
            str_contains($value, 'left arm spin') ||
            str_contains($value, 'left-arm spin') ||
            str_contains($value, 'chinaman')
        ) {
            return 'left_arm_spin';
        }

        $isLeftArm = str_contains($value, 'left arm') || str_contains($value, 'left-arm');
        $isPace = (
            str_contains($value, 'fast') ||
            str_contains($value, 'medium') ||
            str_contains($value, 'pace') ||
            str_contains($value, 'seam')
        );

        if ($isLeftArm && $isPace) {
            return 'left_arm_pace';
        }

        if (
            str_contains($value, 'spin') ||
            str_contains($value, 'leg break') ||
            str_contains($value, 'leg-break') ||
            str_contains($value, 'googly') ||
            str_contains($value, 'orthodox')
        ) {
            return 'spin';
        }

        if ($isPace) {
            return 'pace';
        }

        return 'unknown';
    }

    public function battingHand(?string $style): string
    {
        $value = mb_strtolower(trim((string) $style));

        if (str_contains($value, 'left')) {
            return 'left';
        }

        if (str_contains($value, 'right')) {
            return 'right';
        }

        return 'unknown';
    }

    public function strikeRate(int|float $runs, int $balls): ?float
    {
        if ($balls <= 0) {
            return null;
        }

        return round(((float) $runs / $balls) * 100, 2);
    }

    public function economy(int|float $runsConceded, int $legalBalls): ?float
    {
        if ($legalBalls <= 0) {
            return null;
        }

        return round(((float) $runsConceded / $legalBalls) * 6, 2);
    }

    public function wicketRate(int $wickets, int $legalBalls): ?float
    {
        if ($legalBalls <= 0) {
            return null;
        }

        return round(($wickets / $legalBalls) * 100, 2);
    }

    public function percentage(int|float $numerator, int|float $denominator): ?float
    {
        if ($denominator <= 0) {
            return null;
        }

        return round(((float) $numerator / $denominator) * 100, 2);
    }

    public function sampleSize(int $balls): array
    {
        if ($balls <= 0) {
            return [
                'balls' => 0,
                'level' => 'none',
                'message' => 'No recorded deliveries are available for this split.',
            ];
        }

        if ($balls < 12) {
            return [
                'balls' => $balls,
                'level' => 'very_small',
                'message' => 'Very small sample: fewer than 12 balls. Treat the result as descriptive only.',
            ];
        }

        if ($balls < 30) {
            return [
                'balls' => $balls,
                'level' => 'small',
                'message' => 'Small sample: fewer than 30 balls. The result may be unstable.',
            ];
        }

        return [
            'balls' => $balls,
            'level' => 'adequate',
            'message' => 'Sample contains at least 30 balls.',
        ];
    }

    public function normalizedDismissalWicket(array|object $delivery): bool
    {
        $wicket = (bool) data_get($delivery, 'wicket', false);

        if (! $wicket) {
            return false;
        }

        $type = mb_strtolower((string) data_get($delivery, 'wicket_type', ''));

        return ! in_array($type, [
            'run_out',
            'run out',
            'retired_hurt',
            'retired hurt',
            'obstructing_field',
            'obstructing field',
        ], true);
    }
}
