<?php

namespace App\Services\Reports;

use App\Services\Reports\Exporters\CsvReportExporter;
use App\Services\Reports\Exporters\ExcelReportExporter;
use App\Services\Reports\Exporters\PdfReportExporter;
use App\Services\Reports\Exporters\ReportExporter;
use InvalidArgumentException;

class ReportExporterFactory
{
    public function make(string $format): ReportExporter
    {
        return match ($format) {
            'pdf' => app(PdfReportExporter::class),
            'xlsx' => app(ExcelReportExporter::class),
            'csv' => app(CsvReportExporter::class),
            default => throw new InvalidArgumentException("Unsupported report format: {$format}"),
        };
    }
}
