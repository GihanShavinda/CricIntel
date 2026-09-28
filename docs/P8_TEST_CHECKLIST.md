# P8 Test Checklist

1. `php artisan route:list` shows `/api/broadcasting/auth`.
2. `php artisan route:list --path=live` shows the live snapshot endpoint.
3. `php artisan reverb:start --debug` accepts the WebSocket connection.
4. `php artisan queue:work` processes broadcast jobs.
5. Live page shows Connected.
6. Record a normal delivery: score changes once.
7. Record a wicket: wicket count and recent event update.
8. Complete innings: live snapshot changes innings state.
9. Complete match: final status/result appears.
10. Stop Reverb: UI changes to disconnected/reconnecting.
11. Start Reverb again: client reconnects and refreshes snapshot.
12. Replay the same event_id in a frontend test: duplicate is rejected.
13. Unauthorized organization member cannot authorize `private-match.{id}`.
14. Authorized organization member can subscribe.
15. Open two live clients; both receive the same committed snapshot.
16. Undo a delivery (if optional broadcast patch is applied): both clients correct immediately.

Run:

```powershell
php artisan test --filter=Realtime
```

Frontend:

```powershell
npm test -- eventDeduplicator
npm run build
```
