<?php

namespace App\Observers\Notifications;

use App\Events\Notifications\TrainingAssigned;
use App\Models\TrainingSessionPlayer;
use App\Services\Notifications\NotificationAudienceService;

class TrainingSessionPlayerObserver
{
    public function __construct(
        private readonly NotificationAudienceService $audience
    ) {}

    public function created(
        TrainingSessionPlayer $assignment
    ): void {
        $assignment->loadMissing([
            'player',
            'trainingSession.team',
        ]);

        $session = $assignment->trainingSession;

        TrainingAssigned::dispatch(
            (int) $session->organization_id,
            auth()->id(),
            $this->audience
                ->trainingAssignmentRecipients($assignment),
            [
                'training_session_id' => $session->id,
                'player_id' => $assignment->player_id,
                'team_id' => $session->team_id,
                'title' => 'Training assigned',
                'message' =>
                    ($assignment->player?->display_name ?? 'Player') .
                    ' was assigned to ' .
                    $session->session_type .
                    ' training on ' .
                    optional($session->session_date)->format('Y-m-d') .
                    '.',
                'url' =>
                    "/organizations/{$session->organization_id}/training/sessions/{$session->id}",
            ]
        );
    }
}
