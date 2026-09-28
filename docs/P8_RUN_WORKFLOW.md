# P8 Local Run Workflow

Open four PowerShell terminals.

## Terminal 1 - Laravel API

```powershell
cd F:\My_Projects\CricIntel\cricintel\backend
php artisan serve --host=localhost --port=8000
```

## Terminal 2 - Reverb

```powershell
cd F:\My_Projects\CricIntel\cricintel\backend
php artisan reverb:start --host=0.0.0.0 --port=8080
```

Use debug mode when troubleshooting:

```powershell
php artisan reverb:start --host=0.0.0.0 --port=8080 --debug
```

## Terminal 3 - Queue

```powershell
cd F:\My_Projects\CricIntel\cricintel\backend
php artisan queue:work
```

Restart the queue after changing PHP event classes:

```powershell
php artisan queue:restart
php artisan queue:work
```

## Terminal 4 - React

```powershell
cd F:\My_Projects\CricIntel\cricintel\frontend
npm run dev
```

Then open the Live Match Centre from Matches or:

```text
/organizations/{organizationId}/matches/{matchId}/live
```

Use another browser/tab with the Match Operator. Record a delivery. The live page should
change without browser refresh.
