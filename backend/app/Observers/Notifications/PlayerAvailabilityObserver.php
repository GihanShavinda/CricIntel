<?php

namespace App\Observers\Notifications;

use App\Events\Notifications\PlayerAvailabilityChanged;
use App\Models\PlayerAvailability;
use App\Services\Notifications\NotificationAudienceService;

class PlayerAvailabilityObserver
{
    public function __construct(
        private readonly NotificationAudienceService $audience
    ) {}

    public function created(
        PlayerAvailability $availability
    ): void {
        $this->dispatchChange(
            $availability,
            'added'
        );
    }

    public function updated(
        PlayerAvailability $availability
    ): void {
        $this->dispatchChange(
            $availability,
            'updated'
        );
    }

    public function deleting(
        PlayerAvailability $availability
    ): void {
        $this->dispatchChange(
            $availability,
            'removed'
        );
    }

    private function dispatchChange(
        PlayerAvailability $availability,
        string $action
    ): void {
        $availability->loadMissing(
            'player'
        );

        $player = $availability->player;

        if (! $player) {
            return;
        }

        PlayerAvailabilityChanged::dispatch(
            (int) $player->organization_id,
            auth()->id(),
            $this->audience
                ->playerAvailabilityRecipients($player),
            [
                'player_id' => $player->id,
                'availability_id' => $availability->id,
                'status' => $availability->status,
                'action' => $action,
                'title' => 'Player availability changed',
                'message' =>
                    "{$player->display_name} availability was {$action}" .
                    (
                        $availability->status
                            ? " ({$availability->status})."
                            : '.'
                    ),
                'url' =>
                    "/organizations/{$player->organization_id}/players/{$player->id}",
            ]
        );
    }
}
