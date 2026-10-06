<?php

namespace Tests\Unit\Reports;

use Tests\TestCase;

class ReportFilenameServiceTest extends TestCase
{
    public function test_filename_is_professional_and_predictable(): void
    {
        $filename = app('App\Services\Reports\ReportFilenameService')->make(
            42,
            'player_performance',
            'pdf',
            [
                'date_from' => '2026-09-01',
                'date_to' => '2026-10-06',
            ],
            'Kamal Perera'
        );

        $this->assertSame(
            'CricIntel_PlayerPerformanceReport_KamalPerera_2026-09-01_to_2026-10-06_R000042.pdf',
            $filename
        );
    }
}
