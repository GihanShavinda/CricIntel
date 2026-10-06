<?php

namespace App\Observers\Notifications;

use App\Events\Notifications\PlayerRemoved;
use App\Events\Notifications\PlayerSelected;
use App\Models\MatchSquadPlayer;
use App\Services\Notifications\NotificationAudienceService;

class MatchSquadPlayerObserver
{
    public function __construct(
        private readonly NotificationAudienceService $audience
    ) {}

    public function created(
        MatchSquadPlayer $selection
    ): void {
        $selection->loadMissing([
            'player',
            'matchSquad.team',
        ]);

        PlayerSelected::dispatch(
            (int) $selection->matchSquad->organization_id,
            auth()->id(),
            $this->audience
                ->matchSquadPlayerRecipients($selection),
            [
                'player_id' => $selection->player_id,
                'match_squad_id' => $selection->match_squad_id,
                'match_id' => $selection->matchSquad->match_id,
                'team_id' => $selection->matchSquad->team_id,
                'title' => 'Player selected',
                'message' =>
                    ($selection->player?->display_name ?? 'A player') .
                    ' was selected for ' .
                    ($selection->matchSquad->team?->name ?? 'the team') .
                    '.',
                'url' =>
                    "/organizations/{$selection->matchSquad->organization_id}/matches/{$selection->matchSquad->match_id}/squad",
            ]
        );
    }

    public function updated(
        MatchSquadPlayer $selection
    ): void {
        if (
            ! $selection->wasChanged('selection_status') ||
            $selection->selection_status !== 'Selected'
        ) {
            return;
        }

        $this->created($selection);
    }

    public function deleting(
        MatchSquadPlayer $selection
    ): void {
        $selection->loadMissing([
            'player',
            'matchSquad.team',
        ]);

        PlayerRemoved::dispatch(
            (int) $selection->matchSquad->organization_id,
            auth()->id(),
            $this->audience
                ->matchSquadPlayerRecipients($selection),
            [
                'player_id' => $selection->player_id,
                'match_squad_id' => $selection->match_squad_id,
                'match_id' => $selection->matchSquad->match_id,
                'team_id' => $selection->matchSquad->team_id,
                'title' => 'Player removed',
                'message' =>
                    ($selection->player?->display_name ?? 'A player') .
                    ' was removed from ' .
                    ($selection->matchSquad->team?->name ?? 'the team') .
                    ' match squad.',
                'url' =>
                    "/organizations/{$selection->matchSquad->organization_id}/matches/{$selection->matchSquad->match_id}/squad",
            ]
        );
    }
}
