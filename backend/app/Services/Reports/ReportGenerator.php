<?php

namespace App\Services\Reports;

use App\Models\Organization;
use App\Models\ReportExport;
use Illuminate\Support\Facades\Storage;

class ReportGenerator
{
    public function __construct(
        private readonly ReportDataService $data,
        private readonly ReportExporterFactory $factory,
        private readonly ReportFilenameService $filenames
    ) {}

    public function generate(ReportExport $export): ReportExport
    {
        $export->loadMissing('organization');

        $export->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
        ]);

        $document = $this->data->build(
            $export->organization,
            $export->report_type,
            $export->filters ?? []
        );

        $subject = $document->subtitle ?: null;

        $filename = $this->filenames->make(
            $export->id,
            $export->report_type,
            $export->format,
            $export->filters ?? [],
            $subject
        );

        $directory = trim(config('reports.directory', 'reports'), '/')
            . "/{$export->organization_id}/"
            . now()->format('Y/m');

        $relativePath = "{$directory}/{$filename}";
        $disk = $export->disk ?: config('reports.disk', 'local');

        $filesystem = Storage::disk($disk);
        $filesystem->makeDirectory($directory);

        $absolutePath = $filesystem->path($relativePath);

        $exporter = $this->factory->make($export->format);
        $exporter->export($document, $absolutePath);

        $export->update([
            'status' => 'completed',
            'filename' => $filename,
            'path' => $relativePath,
            'file_size' => $filesystem->size($relativePath),
            'mime_type' => $exporter->mimeType(),
            'completed_at' => now(),
        ]);

        return $export->fresh();
    }
}
