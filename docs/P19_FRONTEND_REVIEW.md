# CricIntel P19 — Frontend Review

## Error boundaries
`AppErrorBoundary` wraps the route tree so an unexpected render failure shows a recovery screen instead of a blank application.

## Loading states
All route pages are lazy loaded. `RouteLoader` provides a consistent accessible loading state through React `Suspense`.

Existing page-level asynchronous operations should continue to expose:
- disabled submit buttons;
- loading labels;
- empty states;
- retry/error messages.

## Accessibility
P19 adds:
- visible `:focus-visible` outlines;
- reduced-motion support;
- `aria-live` loading/error regions;
- keyboard-safe native buttons/links.

Review every custom clickable element and replace non-semantic `<div onClick>` patterns with buttons or links.

## Responsive design
The P1–P18 global horizontal-overflow fix is preserved.
Wide datasets may scroll inside table wrappers, but the application shell itself must not scroll horizontally.

Critical viewport checks:
- 360×800;
- 390×844;
- 768×1024;
- 1366×768;
- 1440×900;
- 1920×1080.

## Performance
P19 changes all page imports in `App.tsx` to `React.lazy()` route chunks.

Continue to:
- memoize genuinely expensive derived data;
- paginate large tables;
- avoid rendering thousands of deliveries at once;
- use TanStack Query cache/stale times intentionally;
- avoid unnecessary refetch-on-focus for stable reference data.

## Query invalidation
P19 adds centralized `queryKeys`.

Mutations should invalidate the smallest relevant keys rather than clearing the entire query cache.

Examples:
- player edit → player + players list;
- delivery write → match + statistics;
- squad change → squad/Playing XI;
- report creation → reports history;
- notification mutation → notifications/unread count.

## Type safety
Avoid `any` in new code.

Prefer:
- API DTO interfaces;
- union types for statuses/formats/phases;
- typed React Query functions;
- exhaustive switch statements for domain enums.

P19 CI runs:

```text
npx tsc --noEmit
```

before production build.
