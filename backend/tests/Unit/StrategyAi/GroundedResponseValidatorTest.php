<?php

namespace Tests\Unit\StrategyAi;

use App\Services\StrategyAi\GroundedResponseValidator;
use PHPUnit\Framework\TestCase;

class GroundedResponseValidatorTest extends TestCase
{
    private function context(): array
    {
        return [
            'evidence' => [
                [
                    'id' => 'E0001',
                    'metric' => 'bowling_phase_economy',
                    'value' => 6.25,
                    'unit' => 'runs_per_6_balls',
                    'sample_size' => 24,
                    'entity' => [
                        'type' => 'player',
                        'id' => 7,
                        'name' => 'Nimal',
                    ],
                    'source' => [
                        'tables' => ['deliveries'],
                    ],
                ],
            ],
        ];
    }

    public function test_accepts_claim_with_known_evidence_and_supported_number(): void
    {
        $validator = new GroundedResponseValidator();

        $result = $validator->validate([
            'claims' => [
                [
                    'statement' =>
                        'Nimal recorded an economy of 6.25 in this supported split.',
                    'evidence_ids' => ['E0001'],
                    'confidence' => 'moderate',
                ],
            ],
            'recommendations' => [],
            'limitations' => [
                'Historical samples do not guarantee future outcomes.',
            ],
        ], $this->context());

        $this->assertSame('accepted', $result['status']);
        $this->assertSame([], $result['errors']);
    }

    public function test_rejects_unknown_evidence_id(): void
    {
        $validator = new GroundedResponseValidator();

        $result = $validator->validate([
            'claims' => [
                [
                    'statement' => 'This claim is unsupported.',
                    'evidence_ids' => ['E9999'],
                    'confidence' => 'high',
                ],
            ],
        ], $this->context());

        $this->assertSame('rejected', $result['status']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_rejects_fabricated_numeric_statistic(): void
    {
        $validator = new GroundedResponseValidator();

        $result = $validator->validate([
            'claims' => [
                [
                    'statement' =>
                        'Nimal has an economy of 99.9 in this split.',
                    'evidence_ids' => ['E0001'],
                    'confidence' => 'high',
                ],
            ],
        ], $this->context());

        $this->assertSame('rejected', $result['status']);

        $this->assertStringContainsString(
            '99.9',
            implode(' ', $result['errors'])
        );
    }

    public function test_rejects_statistical_claim_without_evidence(): void
    {
        $validator = new GroundedResponseValidator();

        $result = $validator->validate([
            'claims' => [
                [
                    'statement' => 'A player is the best death bowler.',
                    'evidence_ids' => [],
                    'confidence' => 'high',
                ],
            ],
        ], $this->context());

        $this->assertSame('rejected', $result['status']);
    }
}
