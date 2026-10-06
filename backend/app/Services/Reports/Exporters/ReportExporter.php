<?php

namespace App\Services\Reports\Exporters;

use App\Services\Reports\ReportDocument;

interface ReportExporter
{
    public function export(ReportDocument $document, string $absolutePath): void;

    public function mimeType(): string;
}
