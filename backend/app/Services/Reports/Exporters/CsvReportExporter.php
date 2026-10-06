<?php

namespace App\Services\Reports\Exporters;

use App\Services\Reports\ReportDocument;

class CsvReportExporter implements ReportExporter
{
    public function export(ReportDocument $document, string $absolutePath): void
    {
        $handle = fopen($absolutePath, 'wb');

        fputcsv($handle, [$document->title]);
        fputcsv($handle, [$document->subtitle]);
        fputcsv($handle, []);

        foreach ($document->metadata as $key => $value) {
            if ($value !== null && $value !== '') {
                fputcsv($handle, [$key, $value]);
            }
        }

        fputcsv($handle, []);

        foreach ($document->summary as $item) {
            fputcsv($handle, [$item['label'], $item['value']]);
        }

        foreach ($document->tables as $table) {
            fputcsv($handle, []);
            fputcsv($handle, [$table['title']]);
            fputcsv($handle, $table['columns']);

            foreach ($table['rows'] as $row) {
                fputcsv($handle, $row);
            }
        }

        fclose($handle);
    }

    public function mimeType(): string
    {
        return 'text/csv';
    }
}
