<?php

namespace Tests\Feature\Reports;

use App\Http\Controllers\Api\V1\ReportController;
use App\Jobs\GenerateReportExport;
use App\Models\ReportExport;
use App\Services\Reports\ReportDataService;
use App\Services\Reports\ReportGenerator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class P18SmokeTest extends TestCase
{
    public function test_p18_classes_exist(): void
    {
        foreach ([
            ReportController::class,
            GenerateReportExport::class,
            ReportExport::class,
            ReportDataService::class,
            ReportGenerator::class,
        ] as $class) {
            $this->assertTrue(class_exists($class), "Missing P18 class: {$class}");
        }
    }

    public function test_report_exports_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('report_exports'));

        $this->assertTrue(Schema::hasColumns('report_exports', [
            'organization_id',
            'requested_by',
            'report_type',
            'format',
            'status',
            'filters',
            'filename',
            'path',
            'file_size',
        ]));
    }

    public function test_p18_routes_are_registered(): void
    {
        $uris = collect(Route::getRoutes())
            ->map(fn ($route) => $route->uri())
            ->values();

        foreach ([
            'api/v1/organizations/{organization}/reports/options',
            'api/v1/organizations/{organization}/reports/preview',
            'api/v1/organizations/{organization}/reports/exports',
            'api/v1/organizations/{organization}/reports/exports/{reportExport}',
            'api/v1/organizations/{organization}/reports/exports/{reportExport}/download',
        ] as $uri) {
            $this->assertTrue($uris->contains($uri), "Missing P18 route: {$uri}");
        }
    }

    public function test_eight_report_types_are_configured(): void
    {
        $this->assertCount(8, config('reports.types'));
        $this->assertSame(['pdf', 'xlsx'], config('reports.heavy_formats'));
    }
}
