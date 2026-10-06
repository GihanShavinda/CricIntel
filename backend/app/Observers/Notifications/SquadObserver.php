<?php

namespace App\Observers\Notifications;

use App\Events\Notifications\SquadAnnounced;
use App\Models\Squad;
use App\Services\Notifications\NotificationAudienceService;

class SquadObserver
{
    public function __construct(
        private readonly NotificationAudienceService $audience
    ) {}

    public function updated(Squad $squad): void
    {
        if (
            ! $squad->wasChanged('status') ||
            $squad->status !== 'Finalized'
        ) {
            return;
        }

        $squad->loadMissing(['team', 'tournament']);

        SquadAnnounced::dispatch(
            (int) $squad->organization_id,
            auth()->id(),
            $this->audience->squadRecipients($squad),
            [
                'squad_id' => $squad->id,
                'team_id' => $squad->team_id,
                'tournament_id' => $squad->tournament_id,
                'title' => 'Squad announced',
                'message' =>
                    ($squad->team?->name ?? 'Team') .
                    ' squad has been finalized for ' .
                    ($squad->tournament?->name ?? 'the tournament') .
                    '.',
                'url' =>
                    "/organizations/{$squad->organization_id}/tournaments/{$squad->tournament_id}",
            ]
        );
    }
}
