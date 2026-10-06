<?php

namespace App\Services\Notifications;

use App\Models\AiStrategyRun;
use App\Models\CricketMatch;
use App\Models\Fixture;
use App\Models\MatchSquadPlayer;
use App\Models\Mention;
use App\Models\Player;
use App\Models\Squad;
use App\Models\StrategyComment;
use App\Models\TrainingSession;
use App\Models\TrainingSessionPlayer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificationAudienceService
{
    public function organizationStaff(
        int $organizationId,
        array $roles = [
            'Administrator',
            'Coach',
            'Analyst',
            'Selector',
            'Team Manager',
        ]
    ): array {
        $query = DB::table('organization_user as ou')
            ->join('users as u', 'u.id', '=', 'ou.user_id')
            ->join('role_user as ru', 'ru.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->where('ou.organization_id', $organizationId)
            ->whereIn('r.name', $roles);

        if (
            Schema::hasColumn(
                'organization_user',
                'status'
            )
        ) {
            $query->where('ou.status', 'active');
        }

        return $query
            ->distinct()
            ->pluck('u.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function playerUser(
        int $playerId
    ): array {
        $userId = Player::query()
            ->whereKey($playerId)
            ->value('user_id');

        return $userId
            ? [(int) $userId]
            : [];
    }

    public function teamPlayerUsers(
        int $teamId
    ): array {
        return DB::table('player_team as pt')
            ->join(
                'players as p',
                'p.id',
                '=',
                'pt.player_id'
            )
            ->where('pt.team_id', $teamId)
            ->whereNotNull('p.user_id')
            ->when(
                Schema::hasColumn(
                    'player_team',
                    'is_current'
                ),
                fn ($query) =>
                    $query->where(
                        'pt.is_current',
                        true
                    )
            )
            ->pluck('p.user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function squadRecipients(
        Squad $squad
    ): array {
        $playerUsers = DB::table(
            'squad_players as sp'
        )
            ->join(
                'players as p',
                'p.id',
                '=',
                'sp.player_id'
            )
            ->where(
                'sp.squad_id',
                $squad->id
            )
            ->whereNull('sp.removed_at')
            ->whereNotNull('p.user_id')
            ->pluck('p.user_id')
            ->all();

        return $this->merge(
            $playerUsers,
            $this->organizationStaff(
                $squad->organization_id
            )
        );
    }

    public function matchSquadPlayerRecipients(
        MatchSquadPlayer $selection
    ): array {
        $selection->loadMissing(
            'matchSquad'
        );

        return $this->merge(
            $this->playerUser(
                $selection->player_id
            ),
            $this->organizationStaff(
                $selection
                    ->matchSquad
                    ->organization_id,
                [
                    'Administrator',
                    'Coach',
                    'Selector',
                    'Team Manager',
                ]
            )
        );
    }

    public function trainingAssignmentRecipients(
        TrainingSessionPlayer $assignment
    ): array {
        $assignment->loadMissing(
            'trainingSession'
        );

        return $this->merge(
            $this->playerUser(
                $assignment->player_id
            ),
            array_filter([
                $assignment
                    ->trainingSession
                    ->coach_id,
            ])
        );
    }

    public function trainingChangedRecipients(
        TrainingSession $session
    ): array {
        $assignedPlayerIds =
            $session
                ->sessionPlayers()
                ->pluck('player_id');

        $playerUsers = Player::query()
            ->whereIn(
                'id',
                $assignedPlayerIds
            )
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->all();

        return $this->merge(
            $playerUsers,
            [$session->coach_id]
        );
    }

    public function fixtureRecipients(
        Fixture $fixture
    ): array {
        return $this->merge(
            $this->teamPlayerUsers(
                $fixture->home_team_id
            ),
            $this->teamPlayerUsers(
                $fixture->away_team_id
            ),
            $this->organizationStaff(
                $fixture->organization_id
            )
        );
    }

    public function matchRecipients(
        CricketMatch $match
    ): array {
        $match->loadMissing(
            'fixture'
        );

        if (! $match->fixture) {
            return $this->organizationStaff(
                $match->organization_id
            );
        }

        return $this->fixtureRecipients(
            $match->fixture
        );
    }

    public function tacticalReportRecipients(
        AiStrategyRun $run
    ): array {
        return $this->merge(
            [$run->user_id],
            $this->organizationStaff(
                $run->organization_id,
                [
                    'Administrator',
                    'Coach',
                    'Analyst',
                    'Selector',
                    'Team Manager',
                ]
            )
        );
    }

    public function playerAvailabilityRecipients(
        Player $player
    ): array {
        return $this->merge(
            $this->playerUser($player->id),
            $this->organizationStaff(
                $player->organization_id,
                [
                    'Administrator',
                    'Coach',
                    'Selector',
                    'Team Manager',
                ]
            )
        );
    }

    public function mentionRecipients(
        Mention $mention
    ): array {
        return [
            (int) $mention->mentioned_user_id,
        ];
    }

    public function coachCommentRecipients(
        StrategyComment $comment
    ): array {
        $comment->loadMissing(
            [
                'author',
                'note.plan',
            ]
        );

        $ids = [];

        if ($comment->note?->author_id) {
            $ids[] =
                (int) $comment->note->author_id;
        }

        $planId =
            $comment
                ->note
                ?->strategy_plan_id;

        if ($planId) {
            $mentioned = DB::table('mentions')
                ->where(
                    'strategy_plan_id',
                    $planId
                )
                ->where(
                    'source_type',
                    'comment'
                )
                ->where(
                    'source_id',
                    $comment->id
                )
                ->pluck(
                    'mentioned_user_id'
                )
                ->all();

            $ids = [
                ...$ids,
                ...$mentioned,
            ];
        }

        return $this->merge($ids);
    }

    public function isCoachLike(
        int $userId
    ): bool {
        return DB::table('role_user as ru')
            ->join(
                'roles as r',
                'r.id',
                '=',
                'ru.role_id'
            )
            ->where(
                'ru.user_id',
                $userId
            )
            ->whereIn(
                'r.name',
                [
                    'Administrator',
                    'Coach',
                ]
            )
            ->exists();
    }

    private function merge(
        ...$groups
    ): array {
        return collect($groups)
            ->flatten()
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
