<?php

namespace App\Services\Strategy;

use App\Models\Mention;
use App\Models\Organization;
use App\Models\StrategyPlan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StrategyMentionService
{

    public function resolveUserIds(
        Organization $organization,
        string $body,
        array $explicitUserIds = []
    ): array {
        preg_match_all(
            '/@([a-z0-9._-]+)/i',
            $body,
            $matches
        );

        $tokens = collect($matches[1] ?? [])
            ->map(fn ($token) => mb_strtolower($token))
            ->unique();

        if ($tokens->isEmpty()) {
            return collect($explicitUserIds)
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $members = User::query()
            ->whereHas('organizations', function ($query) use ($organization) {
                $query->where('organizations.id', $organization->id)
                    ->where('organization_user.status', 'active');
            })
            ->get(['id', 'name', 'email']);

        $tokenIds = $members
            ->filter(fn (User $user) =>
                $tokens->contains($this->tokenFor($user))
            )
            ->pluck('id');

        return collect($explicitUserIds)
            ->merge($tokenIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function sync(
        StrategyPlan $plan,
        Organization $organization,
        string $sourceType,
        int $sourceId,
        array $userIds,
        User $mentionedBy
    ): Collection {
        $ids = collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        Mention::query()
            ->where('strategy_plan_id', $plan->id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete();

        if ($ids->isEmpty()) {
            return collect();
        }

        $eligible = User::query()
            ->whereIn('users.id', $ids)
            ->whereHas('organizations', function ($query) use ($organization) {
                $query->where('organizations.id', $organization->id)
                    ->where('organization_user.status', 'active');
            })
            ->with('roles:id,name')
            ->get();

        if ($eligible->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'mention_user_ids' =>
                    'Every mentioned user must be an active member of this organization.',
            ]);
        }

        return $eligible->map(function (User $user) use (
            $plan,
            $sourceType,
            $sourceId,
            $mentionedBy
        ) {
            return Mention::query()->create([
                'strategy_plan_id' => $plan->id,
                'mentioned_user_id' => $user->id,
                'mentioned_by' => $mentionedBy->id,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'token' => '@' . $this->tokenFor($user),
            ])->load([
                'mentionedUser:id,name,email',
                'mentionedBy:id,name',
            ]);
        });
    }

    public function tokenFor(User $user): string
    {
        $base = mb_strtolower(trim($user->name ?: explode('@', $user->email)[0]));
        $base = preg_replace('/[^a-z0-9]+/u', '.', $base) ?: 'user';

        $token = trim($base, '.');

        if ($token !== '') {
            return $token;
        }

        $emailLocal = mb_strtolower(explode('@', $user->email)[0] ?? '');
        $emailLocal = preg_replace('/[^a-z0-9]+/u', '.', $emailLocal) ?: '';
        $emailLocal = trim($emailLocal, '.');

        return $emailLocal !== ''
            ? $emailLocal
            : 'user' . ($user->id ?: 'unknown');
    }
}
