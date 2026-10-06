# CricIntel P19 — Implementation Matrix

## Backend quality
1. Laravel architecture — reviewed in `P19_BACKEND_QUALITY_REVIEW.md`.
2. Thin controllers — documented controller/service/action boundary.
3. Business logic services/actions — existing P12–P18 services retained; P19 hardening logic is service-based.
4. Validation — reusable secure upload rules and AI input boundary.
5. Policies/authorization — organization + role review documented.
6. API resources — stable-field/resource guidance documented.
7. Transactions — transaction and `afterCommit()` guidance documented.
8. Indexes — conditional production index migration.
9. N+1 — high-risk relationship list and eager-loading guidance.
10. Statistics queries — SQL-first aggregation/caching guidance.
11. Redis caching — Redis-ready `StatisticsCacheService`.
12. Cache invalidation — versioned organization statistics cache invalidation.
13. Queues — production Redis queue configuration + Supervisor example.
14. Failed jobs — safe failed-jobs migration + operational commands.
15. Rate limiting — `api`, `auth`, `ai`, `exports` limiters.
16. Error handling — request IDs, safe production settings, frontend error boundary.
17. Structured logs — structured request and slow-query logging.

## Security
18. Authentication review — Sanctum SPA/CSRF production review.
19. Authorization review — organization membership + roles.
20. File uploads — reusable allow-list/size rules.
21. Mass assignment — explicit fillable/validated-field review.
22. SQL injection — Query Builder/Eloquent and controlled P16 schema retained.
23. XSS — React/Blade safe rendering review.
24. CSRF/session — secure cookie/stateful-domain review.
25. Secrets — production environment template and secret checklist.
26. AI protections — shared P19 AI boundary plus P15/P16 protections.

## Frontend
27. Error boundaries — `AppErrorBoundary`.
28. Loading states — `Suspense` + `RouteLoader`.
29. Accessibility — focus-visible, aria-live, reduced motion.
30. Responsive — P1–P18 fixed shell overflow CSS retained.
31. Performance — all route pages lazy loaded.
32. Lazy routes — P19 `App.tsx`.
33. Query invalidation — centralized `queryKeys`.
34. Type safety — CI `tsc --noEmit` and typed P19 code.

## Testing
35. Backend unit — cache and AI guard tests.
36. Feature/API — P19 smoke/security/rate-limiter tests.
37. Statistics — existing statistics suite retained; P19 cache key test added.
38. Match scoring — existing P5/P6 regression suite remains required.
39. Frontend components — Vitest error-boundary/query-key tests.
40. E2E critical flow — Playwright overflow/login smoke flow.

## CI/CD
41. GitHub Actions — `.github/workflows/ci.yml`.
42. Laravel tests — CI backend job.
43. Frontend tests — Vitest CI step.
44. TypeScript/lint — CI `tsc` + optional lint script.
45. React production build — CI `npm run build`.
46. No Docker — native PostgreSQL in CI; native/managed Redis production guidance.

## Deployment
A deployment checklist is included, but P19 deliberately performs no deployment.
