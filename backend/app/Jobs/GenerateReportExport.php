<?php

namespace App\Jobs;

use App\Models\ReportExport;
use App\Services\Reports\ReportGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateReportExport implements ShouldQueue
{
    use Queueable;

    public int $tries;
    public int $timeout;

    public function __construct(
        public readonly int $reportExportId
    ) {
        $this->onQueue(config('reports.queue', 'reports'));
        $this->tries = config('reports.tries', 3);
        $this->timeout = config('reports.timeout', 120);
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return config('reports.backoff', [30, 120, 300]);
    }

    public function handle(ReportGenerator $generator): void
    {
        $export = ReportExport::query()->findOrFail($this->reportExportId);
        $generator->generate($export);
    }

    public function failed(\Throwable $exception): void
    {
        ReportExport::query()
            ->whereKey($this->reportExportId)
            ->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 4000),
                'failed_at' => now(),
            ]);
    }
}
