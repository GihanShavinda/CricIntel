<?php

namespace App\Services\Reports;

final class ReportDocument
{
    public function __construct(
        public readonly string $title,
        public readonly string $subtitle,
        public readonly array $metadata,
        public readonly array $summary,
        public readonly array $sections,
        public readonly array $tables,
        public readonly array $charts,
        public readonly array $limitations = []
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'metadata' => $this->metadata,
            'summary' => $this->summary,
            'sections' => $this->sections,
            'tables' => $this->tables,
            'charts' => $this->charts,
            'limitations' => $this->limitations,
        ];
    }
}
