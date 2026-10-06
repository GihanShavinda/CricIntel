<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\Exporters\CsvReportExporter;
use App\Services\Reports\ReportDocument;
use PHPUnit\Framework\TestCase;

class CsvReportExporterTest extends TestCase
{
    public function test_csv_exporter_generates_report_file(): void
    {
        $document = new ReportDocument(
            title: 'Team Performance Report',
            subtitle: 'CricIntel XI',
            metadata: ['Generated' => '2026-10-06'],
            summary: [['label' => 'Matches', 'value' => 4]],
            sections: [],
            tables: [[
                'title' => 'Performance',
                'columns' => ['Match', 'Runs'],
                'rows' => [[1, 165], [2, 181]],
            ]],
            charts: []
        );

        $path = tempnam(sys_get_temp_dir(), 'cricintel-report-');

        (new CsvReportExporter())->export($document, $path);

        $contents = file_get_contents($path);

        $this->assertStringContainsString('Team Performance Report', $contents);
        $this->assertStringContainsString('Performance', $contents);
        $this->assertStringContainsString('181', $contents);

        @unlink($path);
    }
}
