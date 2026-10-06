<?php

namespace Tests\Unit\Notifications;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Notifications\NotificationPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_and_realtime_are_enabled_by_default(): void
    {
        $user = User::factory()->create();

        $channels = app(
            NotificationPreferenceService::class
        )->channelsFor(
            $user,
            'analyst_mentioned',
            false
        );

        $this->assertContains(
            'database',
            $channels
        );

        $this->assertContains(
            'broadcast',
            $channels
        );

        $this->assertNotContains(
            'mail',
            $channels
        );
    }

    public function test_email_is_enabled_by_default_for_recommended_event(): void
    {
        $user = User::factory()->create();

        $channels = app(
            NotificationPreferenceService::class
        )->channelsFor(
            $user,
            'fixture_changed',
            true
        );

        $this->assertContains(
            'mail',
            $channels
        );
    }

    public function test_saved_preference_overrides_defaults(): void
    {
        $user = User::factory()->create();

        NotificationPreference::query()->create([
            'user_id' => $user->id,
            'event_type' => 'fixture_changed',
            'database_enabled' => true,
            'email_enabled' => false,
            'realtime_enabled' => false,
        ]);

        $channels = app(
            NotificationPreferenceService::class
        )->channelsFor(
            $user,
            'fixture_changed',
            true
        );

        $this->assertSame(
            ['database'],
            $channels
        );
    }
}
