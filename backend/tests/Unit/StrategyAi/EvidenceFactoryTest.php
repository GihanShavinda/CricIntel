<?php

namespace Tests\Unit\StrategyAi;

use App\Services\StrategyAi\EvidenceFactory;
use PHPUnit\Framework\TestCase;

class EvidenceFactoryTest extends TestCase
{
    public function test_evidence_contains_provenance_metadata(): void
    {
        $factory = new EvidenceFactory();

        $id = $factory->add(
            'powerplay_strike_rate',
            142.5,
            'runs_per_100_balls',
            40,
            [
                'type' => 'player',
                'id' => 10,
                'name' => 'Batter A',
            ],
            [
                'tables' => ['deliveries', 'overs'],
                'scope' => 'powerplay',
                'cutoff' => '2026-10-06T10:00:00+00:00',
            ]
        );

        $this->assertSame('E0001', $id);

        $item = $factory->items()[0];

        $this->assertSame('powerplay_strike_rate', $item['metric']);
        $this->assertSame(142.5, $item['value']);
        $this->assertSame(40, $item['sample_size']);
        $this->assertArrayHasKey('source', $item);
        $this->assertSame(
            ['deliveries', 'overs'],
            $item['source']['tables']
        );
    }
}
