<?php

namespace Tests\Feature\NlAnalytics;

use App\Http\Controllers\Api\V1\NaturalLanguageAnalyticsController;
use App\Models\NlAnalyticsQuery;
use App\Services\NlAnalytics\ControlledAnalyticsService;
use App\Services\NlAnalytics\NaturalLanguageIntentParser;
use App\Services\NlAnalytics\PromptInjectionGuard;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class P16SmokeTest extends TestCase
{
    public function test_p16_classes_exist(): void
    {
        foreach ([
            NlAnalyticsQuery::class,
            NaturalLanguageAnalyticsController::class,
            NaturalLanguageIntentParser::class,
            ControlledAnalyticsService::class,
            PromptInjectionGuard::class,
        ] as $class) {
            $this->assertTrue(
                class_exists($class),
                "Missing P16 class: {$class}"
            );
        }
    }

    public function test_p16_history_table_exists(): void
    {
        $this->assertTrue(
            Schema::hasTable('nl_analytics_queries')
        );

        $this->assertTrue(
            Schema::hasColumns(
                'nl_analytics_queries',
                [
                    'organization_id',
                    'user_id',
                    'natural_language_query',
                    'intent',
                    'controlled_query',
                    'resolved_filters',
                    'structured_result',
                    'explanation',
                    'visualization',
                    'status',
                    'errors',
                    'executed_at',
                ]
            )
        );
    }

    public function test_p16_routes_are_registered(): void
    {
        $uris = collect(
            Route::getRoutes()
        )
            ->map(
                fn ($route) =>
                    $route->uri()
            )
            ->values();

        foreach (
            [
                'api/v1/organizations/{organization}/nl-analytics/options',
                'api/v1/organizations/{organization}/nl-analytics/parse',
                'api/v1/organizations/{organization}/nl-analytics/query',
                'api/v1/organizations/{organization}/nl-analytics/history',
                'api/v1/organizations/{organization}/nl-analytics/history/{nlAnalyticsQuery}',
            ] as $uri
        ) {
            $this->assertTrue(
                $uris->contains($uri),
                "Missing P16 route: {$uri}"
            );
        }
    }

    public function test_p16_has_no_sql_generation_field_in_controlled_schema(): void
    {
        $parser = app(
            NaturalLanguageIntentParser::class
        );

        $parsed = $parser->parse(
            'Show me our best death-over bowlers against left-handed batters this season.'
        );

        $this->assertArrayNotHasKey(
            'sql',
            $parsed
        );
        $this->assertArrayNotHasKey(
            'raw_sql',
            $parsed
        );
    }
}
