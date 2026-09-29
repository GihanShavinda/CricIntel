<?php

namespace Tests\Feature\Scouting;

use App\Models\ScoutingMedia;
use App\Models\ScoutingNote;
use App\Models\ScoutingProfile;
use App\Models\ScoutingRating;
use App\Models\ScoutingReport;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class P11SmokeTest extends TestCase
{
    public function test_p11_models_exist(): void
    {
        $this->assertTrue(class_exists(ScoutingProfile::class));
        $this->assertTrue(class_exists(ScoutingReport::class));
        $this->assertTrue(class_exists(ScoutingRating::class));
        $this->assertTrue(class_exists(ScoutingMedia::class));
        $this->assertTrue(class_exists(ScoutingNote::class));
    }

    public function test_p11_tables_exist_after_migration(): void
    {
        foreach ([
            'scouting_profiles',
            'scouting_reports',
            'scouting_ratings',
            'scouting_media',
            'scouting_notes',
        ] as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Missing {$table}"
            );
        }
    }

    public function test_p11_routes_are_registered(): void
    {
        $uris = collect(Route::getRoutes())
            ->map(fn ($route) => $route->uri())
            ->values();

        foreach ([
            'api/v1/organizations/{organization}/scouting/profiles',
            'api/v1/organizations/{organization}/scouting/profiles/{scoutingProfile}',
            'api/v1/organizations/{organization}/scouting/profiles/{scoutingProfile}/reports',
            'api/v1/organizations/{organization}/scouting/reports/{scoutingReport}',
            'api/v1/organizations/{organization}/scouting/reports/{scoutingReport}/media',
            'api/v1/organizations/{organization}/scouting/media/{scoutingMedia}',
            'api/v1/organizations/{organization}/scouting/profiles/{scoutingProfile}/notes',
            'api/v1/organizations/{organization}/scouting/notes/{scoutingNote}',
            'api/v1/organizations/{organization}/scouting/compare',
            'api/v1/organizations/{organization}/scouting/profiles/{scoutingProfile}/convert',
        ] as $uri) {
            $this->assertTrue(
                $uris->contains($uri),
                "Missing route: {$uri}"
            );
        }
    }

    public function test_p11_core_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('scouting_profiles', [
            'organization_id',
            'display_name',
            'role',
            'current_team',
            'current_competition',
            'status',
            'converted_player_id',
        ]));

        $this->assertTrue(Schema::hasColumns('scouting_reports', [
            'scouting_profile_id',
            'competition',
            'report_date',
            'strengths',
            'weaknesses',
            'potential',
            'overall_recommendation',
        ]));

        $this->assertTrue(Schema::hasColumns('scouting_ratings', [
            'technical_rating',
            'tactical_rating',
            'physical_rating',
            'fielding_rating',
            'mental_decision_rating',
            'overall_rating',
        ]));

        $this->assertTrue(Schema::hasColumns('scouting_media', [
            'media_type',
            'video_url',
            'path',
            'original_filename',
            'mime_type',
            'size_bytes',
        ]));
    }
}
