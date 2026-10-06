<?php

namespace App\Services\StrategyAi;

class EvidenceFactory
{
    private int $counter = 0;

    /** @var array<int, array<string, mixed>> */
    private array $items = [];

    public function add(
        string $metric,
        mixed $value,
        ?string $unit,
        int|float|null $sampleSize,
        array $entity,
        array $source,
        array $extra = []
    ): string {
        $this->counter++;
        $id = sprintf('E%04d', $this->counter);

        $this->items[] = [
            'id' => $id,
            'metric' => $metric,
            'value' => $value,
            'unit' => $unit,
            'sample_size' => $sampleSize,
            'entity' => $entity,
            'source' => $source,
            ...$extra,
        ];

        return $id;
    }

    public function items(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }
}
