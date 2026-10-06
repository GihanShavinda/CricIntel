<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\GenerateReportRequest;
use App\Jobs\GenerateReportExport;
use App\Models\Organization;
use App\Models\ReportExport;
use App\Services\Reports\ReportAccessService;
use App\Services\Reports\ReportDataService;
use App\Services\Reports\ReportGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportAccessService $access,
        private readonly ReportDataService $data,
        private readonly ReportGenerator $generator
    ) {}

    public function options(
        Request $request,
        Organization $organization
    ): JsonResponse {
        $this->access->authorize($request->user(), $organization);

        return response()->json([
            'data' => [
                'report_types' => collect(config('reports.types', []))
                    ->map(fn ($label, $value) => [
                        'value' => $value,
                        'label' => $label,
                    ])
                    ->values(),
                'formats' => collect(config('reports.formats', []))
                    ->map(fn ($label, $value) => [
                        'value' => $value,
                        'label' => $label,
                    ])
                    ->values(),
                'heavy_formats' => config('reports.heavy_formats', ['pdf', 'xlsx']),
                'entities' => $this->entityOptions($organization),
            ],
        ]);
    }

    public function preview(
        GenerateReportRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->access->authorize($request->user(), $organization);

        $validated = $request->validated();

        $document = $this->data->build(
            $organization,
            $validated['report_type'],
            $validated['filters'] ?? []
        );

        return response()->json([
            'data' => $document->toArray(),
        ]);
    }

    public function generate(
        GenerateReportRequest $request,
        Organization $organization
    ): JsonResponse {
        $this->access->authorize($request->user(), $organization);

        $validated = $request->validated();

        $export = ReportExport::query()->create([
            'organization_id' => $organization->id,
            'requested_by' => $request->user()->id,
            'report_type' => $validated['report_type'],
            'format' => $validated['format'],
            'status' => 'pending',
            'filters' => $validated['filters'] ?? [],
            'disk' => config('reports.disk', 'local'),
        ]);

        $heavy = in_array(
            $validated['format'],
            config('reports.heavy_formats', ['pdf', 'xlsx']),
            true
        );

        if ($heavy) {
            $export->update([
                'status' => 'queued',
                'queued_at' => now(),
            ]);

            GenerateReportExport::dispatch($export->id);

            return response()->json([
                'message' => 'Report export queued.',
                'data' => $this->serialize($export->fresh()),
            ], 202);
        }

        $completed = $this->generator->generate($export);

        return response()->json([
            'message' => 'Report generated.',
            'data' => $this->serialize($completed),
        ], 201);
    }

    public function history(
        Request $request,
        Organization $organization
    ): JsonResponse {
        $this->access->authorize($request->user(), $organization);

        $query = ReportExport::query()
            ->where('organization_id', $organization->id)
            ->where('requested_by', $request->user()->id)
            ->latest();

        if ($request->filled('report_type')) {
            $query->where('report_type', $request->string('report_type')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return response()->json([
            'data' => $query->paginate(
                min(max($request->integer('per_page', 20), 1), 100)
            )->through(fn (ReportExport $export) => $this->serialize($export)),
        ]);
    }

    public function show(
        Request $request,
        Organization $organization,
        ReportExport $reportExport
    ): JsonResponse {
        $this->authorizeExport($request, $organization, $reportExport);

        return response()->json([
            'data' => $this->serialize($reportExport),
        ]);
    }

    public function download(
        Request $request,
        Organization $organization,
        ReportExport $reportExport
    ): BinaryFileResponse {
        $this->authorizeExport($request, $organization, $reportExport);

        abort_unless(
            $reportExport->status === 'completed'
                && $reportExport->path
                && Storage::disk($reportExport->disk)->exists($reportExport->path),
            404,
            'Generated report file is not available.'
        );

        return response()->download(
            Storage::disk($reportExport->disk)->path($reportExport->path),
            $reportExport->filename,
            ['Content-Type' => $reportExport->mime_type]
        );
    }

    public function destroy(
        Request $request,
        Organization $organization,
        ReportExport $reportExport
    ): JsonResponse {
        $this->authorizeExport($request, $organization, $reportExport);

        if ($reportExport->path) {
            Storage::disk($reportExport->disk)->delete($reportExport->path);
        }

        $reportExport->delete();

        return response()->json([
            'message' => 'Report export deleted.',
        ]);
    }

    private function authorizeExport(
        Request $request,
        Organization $organization,
        ReportExport $export
    ): void {
        $this->access->authorize($request->user(), $organization);

        abort_unless(
            (int) $export->organization_id === (int) $organization->id
                && (int) $export->requested_by === (int) $request->user()->id,
            404
        );
    }


    private function entityOptions(Organization $organization): array
    {
        return [
            'players' => $this->tableOptions('players', ['display_name', 'first_name', 'last_name'], $organization->id, 'Player'),
            'teams' => $this->tableOptions('teams', ['name'], $organization->id, 'Team'),
            'matches' => $this->tableOptions('matches', ['name', 'title'], $organization->id, 'Match'),
            'tournaments' => $this->tableOptions('tournaments', ['name'], $organization->id, 'Tournament'),
            'venues' => $this->tableOptions('venues', ['name'], $organization->id, 'Venue'),
            'training_sessions' => $this->tableOptions('training_sessions', ['session_type', 'session_date'], $organization->id, 'Training'),
            'scouting_reports' => $this->tableOptions('scouting_reports', ['title', 'recommendation'], $organization->id, 'Scouting Report'),
            'strategy_plans' => $this->tableOptions('strategy_plans', ['title', 'name'], $organization->id, 'Strategy Plan'),
        ];
    }

    private function tableOptions(
        string $table,
        array $labelColumns,
        int $organizationId,
        string $fallbackPrefix
    ): array {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $query = DB::table($table);

        if (Schema::hasColumn($table, 'organization_id')) {
            $query->where('organization_id', $organizationId);
        }

        $availableLabelColumns = array_values(array_filter(
            $labelColumns,
            fn ($column) => Schema::hasColumn($table, $column)
        ));

        return $query
            ->orderBy('id')
            ->limit(500)
            ->get()
            ->map(function ($row) use ($availableLabelColumns, $fallbackPrefix) {
                $parts = [];

                foreach ($availableLabelColumns as $column) {
                    $value = $row->{$column} ?? null;

                    if ($value !== null && $value !== '') {
                        $parts[] = (string) $value;
                    }
                }

                $id = (int) ($row->id ?? 0);

                return [
                    'id' => $id,
                    'label' => $parts !== []
                        ? implode(' — ', $parts)
                        : "{$fallbackPrefix} #{$id}",
                ];
            })
            ->filter(fn ($item) => $item['id'] > 0)
            ->values()
            ->all();
    }

    private function serialize(ReportExport $export): array
    {
        return [
            'id' => $export->id,
            'organization_id' => $export->organization_id,
            'report_type' => $export->report_type,
            'report_label' => config("reports.types.{$export->report_type}", $export->report_type),
            'format' => $export->format,
            'status' => $export->status,
            'filters' => $export->filters ?? [],
            'filename' => $export->filename,
            'file_size' => $export->file_size,
            'mime_type' => $export->mime_type,
            'error_message' => $export->error_message,
            'queued_at' => optional($export->queued_at)?->toIso8601String(),
            'started_at' => optional($export->started_at)?->toIso8601String(),
            'completed_at' => optional($export->completed_at)?->toIso8601String(),
            'failed_at' => optional($export->failed_at)?->toIso8601String(),
            'created_at' => optional($export->created_at)?->toIso8601String(),
            'download_url' => $export->status === 'completed'
                ? "/api/v1/organizations/{$export->organization_id}/reports/exports/{$export->id}/download"
                : null,
        ];
    }
}
