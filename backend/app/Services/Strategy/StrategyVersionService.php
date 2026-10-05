<?php

namespace App\Services\Strategy;

use App\Models\StrategyPlan;
use App\Models\StrategyVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StrategyVersionService
{
    public function record(
        StrategyPlan $plan,
        ?User $actor,
        string $eventType,
        string $entityType,
        ?int $entityId,
        string $summary,
        mixed $before = null,
        mixed $after = null
    ): StrategyVersion {
        return DB::transaction(function () use (
            $plan,
            $actor,
            $eventType,
            $entityType,
            $entityId,
            $summary,
            $before,
            $after
        ) {
            $current = StrategyVersion::query()
                ->where('strategy_plan_id', $plan->id)
                ->orderByDesc('version_number')
                ->lockForUpdate()
                ->value('version_number');

            $next = ((int) $current) + 1;

            return StrategyVersion::query()->create([
                'strategy_plan_id' => $plan->id,
                'actor_id' => $actor?->id,
                'version_number' => $next,
                'event_type' => $eventType,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'change_summary' => $summary,
                'snapshot' => $this->workspaceSnapshot($plan),
                'changes' => [
                    'before' => $this->normalize($before),
                    'after' => $this->normalize($after),
                ],
                'created_at' => now(),
            ]);
        });
    }

    public function workspaceSnapshot(StrategyPlan $plan): array
    {
        return $plan->fresh()->load([
            'sections',
            'assignments',
        ])->toArray();
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof Model) {
            return $value->toArray();
        }

        return $value;
    }
}
