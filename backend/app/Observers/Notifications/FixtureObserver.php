<?php

namespace App\Observers\Notifications;

use App\Events\Notifications\FixtureChanged;
use App\Models\Fixture;
use App\Services\Notifications\NotificationAudienceService;

class FixtureObserver
{
    public function __construct(
        private readonly NotificationAudienceService $audience
    ) {}

    public function updated(Fixture $fixture): void
    {
        $meaningful = [
            'scheduled_at',
            'venue_id',
            'home_team_id',
            'away_team_id',
            'status',
            'round',
        ];

        if (! $fixture->wasChanged($meaningful)) {
            return;
        }

        $fixture->loadMissing([
            'homeTeam',
            'awayTeam',
            'venue',
        ]);

        FixtureChanged::dispatch(
            (int) $fixture->organization_id,
            auth()->id(),
            $this->audience
                ->fixtureRecipients($fixture),
            [
                'fixture_id' => $fixture->id,
                'changes' => array_intersect_key(
                    $fixture->getChanges(),
                    array_flip($meaningful)
                ),
                'title' => 'Fixture changed',
                'message' =>
                    ($fixture->homeTeam?->name ?? 'Home team') .
                    ' vs ' .
                    ($fixture->awayTeam?->name ?? 'Away team') .
                    ' fixture details were updated.',
                'url' =>
                    "/organizations/{$fixture->organization_id}/fixtures",
            ]
        );
    }
}
