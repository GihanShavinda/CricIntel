<?php

namespace App\Observers\Notifications;

use App\Events\Notifications\MatchStarting;
use App\Models\CricketMatch;
use App\Services\Notifications\NotificationAudienceService;

class CricketMatchObserver
{
    public function __construct(
        private readonly NotificationAudienceService $audience
    ) {}

    public function created(
        CricketMatch $match
    ): void {
        $this->dispatchIfStarting($match, true);
    }

    public function updated(
        CricketMatch $match
    ): void {
        $this->dispatchIfStarting($match, false);
    }

    private function dispatchIfStarting(
        CricketMatch $match,
        bool $created
    ): void {
        if (! $created && ! $match->wasChanged('status')) {
            return;
        }

        $status = mb_strtolower(
            (string) $match->status
        );

        if (
            ! in_array(
                $status,
                [
                    'live',
                    'in progress',
                    'in_progress',
                    'started',
                ],
                true
            )
        ) {
            return;
        }

        $match->loadMissing(
            'fixture.homeTeam',
            'fixture.awayTeam'
        );

        MatchStarting::dispatch(
            (int) $match->organization_id,
            auth()->id(),
            $this->audience
                ->matchRecipients($match),
            [
                'match_id' => $match->id,
                'fixture_id' => $match->fixture_id,
                'title' => 'Match starting',
                'message' =>
                    ($match->fixture?->homeTeam?->name ?? 'Home team') .
                    ' vs ' .
                    ($match->fixture?->awayTeam?->name ?? 'Away team') .
                    ' is starting.',
                'url' =>
                    "/organizations/{$match->organization_id}/matches/{$match->id}",
            ]
        );
    }
}
