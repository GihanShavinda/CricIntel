<?php

namespace App\Services\Reports\Exporters;

use App\Services\Reports\ReportDocument;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfReportExporter implements ReportExporter
{
    public function export(ReportDocument $document, string $absolutePath): void
    {
        $pdf = Pdf::loadView('reports.professional', [
            'report' => $document->toArray(),
        ])->setPaper('a4');

        file_put_contents($absolutePath, $pdf->output());
    }

    public function mimeType(): string
    {
        return 'application/pdf';
    }
}
