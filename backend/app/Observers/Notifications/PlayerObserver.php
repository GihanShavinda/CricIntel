<?php

namespace App\Observers\Notifications;

use App\Events\Notifications\PlayerMarkedInjured;
use App\Models\Player;
use App\Services\Notifications\NotificationAudienceService;

class PlayerObserver
{
    public function __construct(
        private readonly NotificationAudienceService $audience
    ) {}

    public function updated(Player $player): void
    {
        if (! $player->wasChanged('fitness_status')) {
            return;
        }

        if (
            ! str_contains(
                mb_strtolower(
                    (string) $player->fitness_status
                ),
                'injur'
            )
        ) {
            return;
        }

        PlayerMarkedInjured::dispatch(
            (int) $player->organization_id,
            auth()->id(),
            $this->audience
                ->playerAvailabilityRecipients($player),
            [
                'player_id' => $player->id,
                'fitness_status' =>
                    $player->fitness_status,
                'title' => 'Player marked injured',
                'message' =>
                    "{$player->display_name} has been marked {$player->fitness_status}.",
                'url' =>
                    "/organizations/{$player->organization_id}/players/{$player->id}",
            ]
        );
    }
}
