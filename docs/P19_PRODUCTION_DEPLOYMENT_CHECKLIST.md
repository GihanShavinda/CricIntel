# CricIntel P19 — Production Deployment Checklist

This is a deployment-readiness checklist only. P19 does not deploy CricIntel.

## Application

- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] valid production `APP_KEY`
- [ ] correct HTTPS `APP_URL`
- [ ] exact `FRONTEND_URL`
- [ ] `php artisan optimize:clear` before changing configuration
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`

## Database

- [ ] PostgreSQL backup completed before migrations
- [ ] production DB account is not a PostgreSQL superuser
- [ ] SSL used when DB is remote
- [ ] P19 indexes reviewed on staging
- [ ] `php artisan migrate --force`
- [ ] never use `migrate:fresh` in production
- [ ] slow-query logs reviewed after realistic load

## Redis — native service, no Docker

- [ ] Redis installed directly on production Linux host or managed Redis selected
- [ ] Redis bound to private interface/localhost
- [ ] Redis authentication/network ACL configured where needed
- [ ] `CACHE_STORE=redis`
- [ ] `CRICINTEL_CACHE_STORE=redis`
- [ ] `QUEUE_CONNECTION=redis`
- [ ] Redis persistence/backup policy understood
- [ ] Redis memory eviction policy appropriate for cache workload

## Queues

- [ ] queue worker managed by Supervisor/systemd
- [ ] worker restarts automatically
- [ ] `notifications` queue processed
- [ ] `reports` queue processed
- [ ] default queue processed
- [ ] `failed_jobs` table exists
- [ ] alert/process defined for repeated failed jobs
- [ ] deploy procedure includes `php artisan queue:restart`

## Reverb

- [ ] Reverb managed as a persistent system service
- [ ] websocket reverse proxy configured
- [ ] TLS termination verified
- [ ] allowed origins restricted
- [ ] private channel authorization tested

## Authentication / session / CORS

- [ ] HTTPS enforced
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] production cookie domain verified
- [ ] `SANCTUM_STATEFUL_DOMAINS` contains only expected frontend host(s)
- [ ] `CORS_ALLOWED_ORIGINS` is exact
- [ ] CSRF login/register/logout flow tested
- [ ] session expiry tested
- [ ] 401/403 behavior verified

## Authorization

- [ ] Administrator override verified
- [ ] Coach permissions verified
- [ ] Analyst permissions verified
- [ ] Selector permissions verified
- [ ] Team Manager permissions verified
- [ ] Player restrictions verified
- [ ] cross-organization access tests pass

## Files

- [ ] upload size limits configured
- [ ] extension/MIME allow lists enforced
- [ ] private uploads stored outside public root
- [ ] download authorization checked
- [ ] report export directory writable by application user
- [ ] generated reports not exposed by predictable public URLs
- [ ] malware scanning decision documented

## Secrets

- [ ] `.env` excluded from Git
- [ ] DB password rotated for production
- [ ] Redis credentials protected
- [ ] SMTP credentials protected
- [ ] Reverb credentials protected
- [ ] cloud credentials protected
- [ ] LLM provider key protected
- [ ] CI secrets stored in GitHub secrets/environments
- [ ] no secrets present in frontend bundle

## AI

- [ ] P15 grounding validation enabled
- [ ] P16 arbitrary SQL remains impossible
- [ ] prompt-injection tests pass
- [ ] AI kill switch tested
- [ ] provider timeout configured
- [ ] no DB credentials sent to LLM
- [ ] AI output never directly mutates authoritative cricket state
- [ ] evidence/provenance retained for grounded outputs

## Logging / monitoring

- [ ] structured application logs collected
- [ ] request IDs visible in production logs
- [ ] slow requests visible
- [ ] slow queries visible
- [ ] 5xx monitoring configured
- [ ] queue failures monitored
- [ ] disk usage monitored
- [ ] PostgreSQL health monitored
- [ ] Redis health monitored
- [ ] Reverb process monitored
- [ ] logs do not contain passwords/tokens/API keys

## Frontend

- [ ] `npx tsc --noEmit`
- [ ] frontend component tests pass
- [ ] production React build succeeds
- [ ] lazy route chunks load after deployment
- [ ] no browser-level horizontal scrollbar
- [ ] keyboard navigation checked
- [ ] focus styles visible
- [ ] reduced-motion behavior checked
- [ ] Chrome desktop/mobile smoke test
- [ ] Firefox smoke test
- [ ] Edge smoke test

## Testing

- [ ] full Laravel suite passes
- [ ] statistics tests pass
- [ ] match-scoring tests pass
- [ ] P15 AI validation tests pass
- [ ] P16 natural-language analytics tests pass
- [ ] P17 notification tests pass
- [ ] P18 reporting tests pass
- [ ] P19 hardening tests pass
- [ ] Vitest component tests pass
- [ ] Playwright critical-flow test passes

## CI/CD

- [ ] GitHub Actions required on protected branch
- [ ] backend job green
- [ ] frontend job green
- [ ] dependency lockfiles committed
- [ ] migration review required in pull requests
- [ ] production deployment remains a separate approved step
- [ ] no Docker or Docker Compose required by the workflow

## Final pre-release commands

Backend:

```powershell
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan test
```

Frontend:

```powershell
npm ci
npx tsc --noEmit
npx vitest run
npm run build
```

After a real deployment later:

```powershell
php artisan queue:restart
```
