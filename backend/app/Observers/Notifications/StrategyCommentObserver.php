<?php

namespace App\Observers\Notifications;

use App\Events\Notifications\CoachCommentAdded;
use App\Models\StrategyComment;
use App\Services\Notifications\NotificationAudienceService;

class StrategyCommentObserver
{
    public function __construct(
        private readonly NotificationAudienceService $audience
    ) {}

    public function created(
        StrategyComment $comment
    ): void {
        if (
            ! $this->audience->isCoachLike(
                (int) $comment->author_id
            )
        ) {
            return;
        }

        $comment->loadMissing([
            'author',
            'note.plan',
        ]);

        $plan = $comment->note?->plan;

        if (! $plan) {
            return;
        }

        $recipients =
            $this->audience
                ->coachCommentRecipients($comment);

        if ($recipients === []) {
            return;
        }

        CoachCommentAdded::dispatch(
            (int) $plan->organization_id,
            (int) $comment->author_id,
            $recipients,
            [
                'strategy_plan_id' =>
                    $plan->id,
                'tactical_note_id' =>
                    $comment->tactical_note_id,
                'comment_id' =>
                    $comment->id,
                'title' => 'Coach comment added',
                'message' =>
                    ($comment->author?->name ?? 'A coach') .
                    ' added a tactical comment.',
                'url' =>
                    "/organizations/{$plan->organization_id}/strategy/{$plan->id}",
            ]
        );
    }
}
