<?php

namespace App\Observers;

use App\Models\CricketMatch;
use App\Models\Delivery;
use App\Models\Innings;
use App\Models\Wicket;
use App\Services\Statistics\StatisticsCacheService;
use Illuminate\Database\Eloquent\Model;

class StatisticsCacheInvalidationObserver
{
    public function __construct(
        private readonly StatisticsCacheService $cache
    ) {}

    public function saved(Model $model): void
    {
        $this->invalidate($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidate($model);
    }

    private function invalidate(Model $model): void
    {
        $organizationId = match (true) {
            $model instanceof CricketMatch => $model->organization_id,
            $model instanceof Innings => $model->match?->organization_id,
            $model instanceof Delivery => $model->innings?->match?->organization_id,
            $model instanceof Wicket => $model->innings?->match?->organization_id,
            default => null,
        };

        if ($organizationId) {
            $this->cache->invalidateOrganization((int) $organizationId);
        }
    }
}
