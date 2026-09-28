# CricIntel AI — Milestone 8 (P8)
## Real-Time Match Centre with Laravel Reverb

P8 adds real-time match updates over Laravel Reverb / WebSockets.

No AI is used.

## Architecture

```text
P5 scoring request
      ↓
MatchScoringService
      ↓
database transaction commits
      ↓
DeliveryRecorded / WicketRecorded /
InningsCompleted / MatchCompleted
      ↓
Laravel Broadcasting
      ↓
Reverb
      ↓
private-match.{matchId}
      ↓
Laravel Echo
      ↓
React Live Match Centre
```

Every event contains:
- event_id: UUID
- event_name
- match_id
- emitted_at
- deterministic live match snapshot

The React client keeps a bounded event-ID set to reject duplicates.

## Install Reverb / broadcasting

Run in backend:

```powershell
cd F:\My_Projects\CricIntel\cricintel\backend

php artisan install:broadcasting
```

When prompted, select Laravel Reverb.

If Reverb is not installed by the installer:

```powershell
composer require laravel/reverb
php artisan reverb:install
```

Then:

```powershell
php artisan optimize:clear
php artisan migrate
```

## Frontend dependencies

```powershell
cd F:\My_Projects\CricIntel\cricintel\frontend

npm install laravel-echo pusher-js
```

## Local development terminals

Terminal 1:

```powershell
cd F:\My_Projects\CricIntel\cricintel\backend
php artisan serve --host=localhost --port=8000
```

Terminal 2:

```powershell
cd F:\My_Projects\CricIntel\cricintel\backend
php artisan reverb:start --host=0.0.0.0 --port=8080
```

Terminal 3:

```powershell
cd F:\My_Projects\CricIntel\cricintel\backend
php artisan queue:work
```

Terminal 4:

```powershell
cd F:\My_Projects\CricIntel\cricintel\frontend
npm run dev
```

## Redis

Redis is optional for one local Reverb process.

Your existing local CricIntel project can keep:

```env
CACHE_STORE=file
QUEUE_CONNECTION=database
```

When Redis is available without Docker, you may use:

```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

For multiple Reverb servers, enable Reverb scaling and Redis according to Laravel Reverb configuration.

## Live route

```text
/organizations/{organizationId}/matches/{matchId}/live
```
