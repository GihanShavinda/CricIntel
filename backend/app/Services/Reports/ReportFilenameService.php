<?php

namespace App\Services\Reports;

use Illuminate\Support\Str;

class ReportFilenameService
{
    public function make(
        int $exportId,
        string $reportType,
        string $format,
        array $filters,
        ?string $subject = null
    ): string {
        $label = config("reports.types.{$reportType}", $reportType);

        $datePart = $this->datePart($filters);
        $subjectPart = $subject
            ? '_' . Str::studly(Str::slug($subject, '_'))
            : '';

        return sprintf(
            'CricIntel_%s%s_%s_R%06d.%s',
            Str::studly(Str::slug($label, '_')),
            $subjectPart,
            $datePart,
            $exportId,
            $format
        );
    }

    private function datePart(array $filters): string
    {
        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;

        if ($from && $to) {
            return "{$from}_to_{$to}";
        }

        if ($from) {
            return "from_{$from}";
        }

        if ($to) {
            return "to_{$to}";
        }

        return now()->format('Y-m-d');
    }
}
