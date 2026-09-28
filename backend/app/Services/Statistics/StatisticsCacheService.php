<?php

namespace App\Services\Statistics;

use Closure;
use Illuminate\Support\Facades\Cache;

class StatisticsCacheService
{
    private const TTL_SECONDS = 900;

    public function remember(int $organizationId, string $scope, array $parts, Closure $callback): mixed
    {
        $version = $this->version($organizationId);
        $suffix = implode(':', array_map(
            static fn ($part) => is_scalar($part) || $part === null
                ? (string) ($part ?? 'all')
                : sha1(json_encode($part)),
            $parts
        ));

        return Cache::remember(
            "cricintel:stats:org:{$organizationId}:v{$version}:{$scope}:{$suffix}",
            self::TTL_SECONDS,
            $callback
        );
    }

    public function invalidateOrganization(int $organizationId): void
    {
        $key = $this->versionKey($organizationId);

        if (! Cache::has($key)) {
            Cache::forever($key, 1);
            return;
        }

        Cache::increment($key);
    }

    public function version(int $organizationId): int
    {
        return (int) Cache::rememberForever(
            $this->versionKey($organizationId),
            static fn () => 1
        );
    }

    private function versionKey(int $organizationId): string
    {
        return "cricintel:stats:org:{$organizationId}:version";
    }
}
