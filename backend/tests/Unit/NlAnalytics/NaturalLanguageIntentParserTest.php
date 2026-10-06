<?php

namespace Tests\Unit\NlAnalytics;

use App\Services\NlAnalytics\ControlledAnalyticsSchema;
use App\Services\NlAnalytics\NaturalLanguageIntentParser;
use App\Services\NlAnalytics\PromptInjectionGuard;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NaturalLanguageIntentParserTest extends TestCase
{
    private function parser(): NaturalLanguageIntentParser
    {
        return new NaturalLanguageIntentParser(
            new ControlledAnalyticsSchema(),
            new PromptInjectionGuard()
        );
    }

    public function test_parses_death_over_bowler_ranking(): void
    {
        $result = $this->parser()->parse(
            'Show me our best death-over bowlers against left-handed batters this season.'
        );

        $this->assertSame(
            'rank_bowlers_phase_vs_hand',
            $result['intent']
        );

        $this->assertSame(
            'bowler',
            $result['entity']
        );

        $this->assertSame(
            'economy',
            $result['metric']
        );

        $this->assertSame(
            'death',
            $result['phase']
        );

        $this->assertSame(
            'left',
            $result['batting_hand']
        );

        $this->assertSame(
            '__CURRENT__',
            $result['season_name']
        );

        $this->assertSame(
            'asc',
            $result['sort_direction']
        );
    }

    public function test_parses_middle_over_run_rate_trend(): void
    {
        $result = $this->parser()->parse(
            'Why did our middle-over run rate decrease during our previous four matches?'
        );

        $this->assertSame(
            'team_run_rate_trend',
            $result['intent']
        );

        $this->assertSame(
            'run_rate',
            $result['metric']
        );

        $this->assertSame(
            'team',
            $result['entity']
        );

        $this->assertSame(
            'middle',
            $result['phase']
        );

        $this->assertSame(
            4,
            $result['last_n_matches']
        );
    }

    public function test_parses_powerplay_opponent_threat_request(): void
    {
        $result = $this->parser()->parse(
            'Who are the opposition main powerplay threats?'
        );

        $this->assertSame(
            'opponent_phase_threats',
            $result['intent']
        );

        $this->assertSame(
            'powerplay',
            $result['phase']
        );

        $this->assertSame(
            'strike_rate',
            $result['metric']
        );

        $this->assertSame(
            'batter',
            $result['entity']
        );
    }

    public function test_rejects_unsupported_general_question(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->expectExceptionMessage(
            'CricIntel could not map this request to a supported analytics intent.'
        );

        $this->parser()->parse(
            'Tell me something interesting about cricket.'
        );
    }
}
