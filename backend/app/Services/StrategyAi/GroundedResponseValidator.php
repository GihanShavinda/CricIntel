<?php

namespace App\Services\StrategyAi;

class GroundedResponseValidator
{
    public function validate(array $raw, array $context): array
    {
        $errors = [];
        $evidence = collect($context['evidence'] ?? [])
            ->keyBy('id');

        $claims = $this->validateEvidenceBackedItems(
            $raw['claims'] ?? [],
            $evidence,
            'claims',
            $errors
        );

        $recommendations = $this->validateEvidenceBackedItems(
            $raw['recommendations'] ?? [],
            $evidence,
            'recommendations',
            $errors
        );

        $limitations = collect($raw['limitations'] ?? [])
            ->filter(fn ($item) => is_string($item) && trim($item) !== '')
            ->map(fn ($item) => trim($item))
            ->values()
            ->all();

        if ($claims === [] && $recommendations === []) {
            $errors[] =
                'The model returned no evidence-backed claims or recommendations.';
        }

        $status = $errors === [] ? 'accepted' : 'rejected';

        return [
            'status' => $status,
            'errors' => $errors,
            'response' => [
                'claims' => $claims,
                'recommendations' => $recommendations,
                'limitations' => $limitations,
                'coach_note' =>
                    'AI output is advisory only. The coach remains responsible for final cricket decisions.',
            ],
        ];
    }

    private function validateEvidenceBackedItems(
        mixed $items,
        $evidence,
        string $field,
        array &$errors,
        bool $allowEmptyEvidence = false
    ): array {
        if (! is_array($items)) {
            $errors[] = "{$field} must be an array.";

            return [];
        }

        $accepted = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                $errors[] = "{$field}.{$index} must be an object.";
                continue;
            }

            $text = trim((string) (
                $item['statement']
                ?? $item['text']
                ?? ''
            ));

            $ids = collect($item['evidence_ids'] ?? [])
                ->filter(fn ($id) => is_string($id) && $id !== '')
                ->unique()
                ->values()
                ->all();

            if ($text === '') {
                $errors[] = "{$field}.{$index} has no statement.";
                continue;
            }

            if ($ids === [] && ! $allowEmptyEvidence) {
                $errors[] =
                    "{$field}.{$index} has no evidence IDs.";
                continue;
            }

            $unknown = collect($ids)
                ->reject(fn ($id) => $evidence->has($id))
                ->values()
                ->all();

            if ($unknown !== []) {
                $errors[] =
                    "{$field}.{$index} references unknown evidence: " .
                    implode(', ', $unknown) . '.';
                continue;
            }

            if ($ids !== []) {
                $numberError = $this->validateNumbers(
                    $text,
                    $ids,
                    $evidence
                );

                if ($numberError) {
                    $errors[] = "{$field}.{$index}: {$numberError}";
                    continue;
                }
            }

            $accepted[] = [
                'statement' => $text,
                'evidence_ids' => $ids,
                'confidence' => $this->normalizeConfidence(
                    $item['confidence'] ?? 'low'
                ),
            ];
        }

        return $accepted;
    }

    private function validateNumbers(
        string $statement,
        array $evidenceIds,
        $evidence
    ): ?string {
        preg_match_all(
            '/(?<![A-Za-z])[-+]?\d+(?:\.\d+)?%?/',
            $statement,
            $matches
        );

        $numbers = collect($matches[0] ?? [])
            ->map(fn ($token) => rtrim($token, '%'))
            ->filter(fn ($token) => is_numeric($token))
            ->map(fn ($token) => (float) $token)
            ->values();

        if ($numbers->isEmpty()) {
            return null;
        }

        $allowed = collect($evidenceIds)
            ->flatMap(function ($id) use ($evidence) {
                $item = $evidence->get($id);

                return $this->extractNumbers($item);
            })
            ->unique(fn ($value) => sprintf('%.6f', $value))
            ->values();

        foreach ($numbers as $number) {
            $matched = $allowed->contains(
                fn ($allowedValue) =>
                    abs($allowedValue - $number) <= 0.011
            );

            if (! $matched) {
                return
                    "contains numeric value {$number} that is not present in the cited CricIntel evidence.";
            }
        }

        return null;
    }

    private function extractNumbers(mixed $value): array
    {
        $numbers = [];

        if (is_int($value) || is_float($value)) {
            return [(float) $value];
        }

        if (is_array($value)) {
            foreach ($value as $child) {
                $numbers = array_merge(
                    $numbers,
                    $this->extractNumbers($child)
                );
            }
        }

        return $numbers;
    }

    private function normalizeConfidence(mixed $value): string
    {
        $value = mb_strtolower((string) $value);

        return in_array($value, ['high', 'moderate', 'low'], true)
            ? $value
            : 'low';
    }
}
