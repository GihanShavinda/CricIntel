# CricIntel P19 — Backend Quality Review

P19 is a production-hardening milestone. It intentionally does not add a new CricIntel product domain.

## 1. Laravel architecture

Keep the existing domain modules and milestone boundaries, but use this rule:

`Controller → FormRequest/Policy → Service/Action → Model/Query → Resource`

Controllers should only:
- authorize;
- validate;
- call an application service/action;
- return a resource/response.

Large statistics, reporting, opponent intelligence, predictive analytics, selection, training, scouting and AI orchestration logic should remain outside controllers.

## 2. Validation

Continue using FormRequest classes for write endpoints. P19 adds `SecureUploadRules` for reusable upload validation.

Production rules:
- enforce server-side validation even when React validates;
- use explicit enum/allow-list validation;
- never trust route IDs without organization scoping;
- validate date ranges and numeric bounds;
- reject oversized AI inputs before provider calls.

## 3. Authorization

Administrator keeps the global `Gate::before` override.

Organization-scoped features must require both:
1. organization membership; and
2. an allowed role/policy.

Never authorize only from a client-supplied organization ID.

## 4. API resources

Existing endpoints should converge on stable resource shapes rather than exposing arbitrary Eloquent models.

When touching an endpoint:
- whitelist response fields;
- avoid returning internal secrets, password fields or storage paths;
- preserve P15/P16 evidence/provenance structures;
- serialize timestamps consistently as ISO-8601.

## 5. Transactions

Use `DB::transaction()` for multi-write workflows such as:
- match scoring + wicket/delivery state;
- squad/Playing XI changes;
- training assignments;
- scouting conversion;
- tactical collaboration writes;
- report metadata + related domain state when coupled.

Queued side effects should use `afterCommit()`.

## 6. Index review

P19 adds conditional PostgreSQL indexes for high-value joins/filter paths:
- deliveries by innings/over;
- deliveries by batter/innings;
- deliveries by bowler/innings;
- innings by match/number;
- matches by organization/fixture;
- fixtures by tournament/scheduled date;
- fixtures by home/away teams;
- player-team lookup;
- unread notification lookup;
- report export history.

Run `EXPLAIN (ANALYZE, BUFFERS)` before adding additional indexes.

## 7. N+1 review

High-risk areas:
- organization → clubs/teams/seasons;
- player lists with teams/availability;
- fixtures with venue/home/away teams;
- matches with innings and deliveries;
- squads with player/profile data;
- training session participants;
- scouting profiles with reports/ratings/media;
- tactical plan sections/comments/mentions;
- notification history actor/context.

Use `with()`, `withCount()`, select only required columns and pagination.

## 8. Statistics query optimization

Do not load all deliveries into PHP when SQL aggregation can calculate the result.

Preferred sequence:
1. apply organization/match/date filters in SQL;
2. restrict selected columns;
3. aggregate counts/sums/grouping in PostgreSQL;
4. cache stable derived results;
5. invalidate cache when scoring source rows change.

P19 provides `StatisticsCacheService` and versioned `CacheKeys`.

## 9. Redis caching

Development may continue with `CACHE_STORE=file` or `array`.

Production target:

```env
CACHE_STORE=redis
CRICINTEL_CACHE_STORE=redis
```

Use native Redis installation/service; P19 does not use Docker.

## 10. Cache invalidation

Statistics caches use an organization-level version key. Existing scoring observers should call:

```php
app(StatisticsCacheService::class)
    ->invalidateOrganization($organizationId);
```

after a source-of-truth score mutation.

Do not delete Redis keys by wildcard in request paths.

## 11. Queues and failed jobs

Production target:

```env
QUEUE_CONNECTION=redis
```

Queue categories already used by CricIntel:
- default;
- notifications;
- reports.

`failed_jobs` is ensured by P19. Use:

```powershell
php artisan queue:failed
php artisan queue:retry all
```

after investigating the failure.

## 12. Rate limiting

P19 named limiters:
- `api`;
- `auth`;
- `ai`;
- `exports`.

Authentication receives a tighter limit. The general authenticated API uses `throttle:api`.

Apply `throttle:ai` and `throttle:exports` to especially expensive routes when you next touch those route blocks.

## 13. Error handling

P19 adds request IDs and structured request completion/failure logs.

Do not expose stack traces when:

```env
APP_ENV=production
APP_DEBUG=false
```

Return useful 401/403/404/422/429/500 API responses without exposing SQL, credentials, provider secrets or internal filesystem paths.

## 14. Structured logs

P19 logs:
- request ID;
- HTTP method;
- path;
- user ID when authenticated;
- status code;
- duration;
- slow queries.

Never log:
- passwords;
- Sanctum/session tokens;
- LLM API keys;
- entire health records or private files;
- raw authorization headers.
