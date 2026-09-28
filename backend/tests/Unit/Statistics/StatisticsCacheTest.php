<?php

namespace Tests\Unit\Statistics;

use App\Services\Statistics\StatisticsCacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StatisticsCacheTest extends TestCase
{
    public function test_cache_version_increments_on_invalidation(): void
    {
        Cache::flush();

        $service = app(StatisticsCacheService::class);

        $this->assertSame(1, $service->version(99));

        $service->invalidateOrganization(99);

        $this->assertSame(2, $service->version(99));
    }

    public function test_versioned_key_prevents_stale_result_reuse(): void
    {
        Cache::flush();

        $service = app(StatisticsCacheService::class);

        $first = $service->remember(99, 'test', [1], fn () => 'first');
        $cached = $service->remember(99, 'test', [1], fn () => 'wrong');

        $this->assertSame('first', $first);
        $this->assertSame('first', $cached);

        $service->invalidateOrganization(99);

        $fresh = $service->remember(99, 'test', [1], fn () => 'second');

        $this->assertSame('second', $fresh);
    }
}
