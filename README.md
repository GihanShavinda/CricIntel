# CricIntel AI — P1 Foundation

This ZIP contains the source files for Milestone 1:

- Laravel REST API foundation
- PostgreSQL configuration
- Redis configuration
- Laravel Sanctum SPA authentication
- Registration / login / logout / current-user endpoint
- Database-backed RBAC roles
- Laravel Gates
- React + TypeScript + Vite auth pages
- AuthProvider
- Protected routes
- Role-aware navigation
- Backend tests
- Frontend smoke test

No cricket-domain entities are included.

## Important

Generated dependency folders are intentionally excluded:

- `backend/vendor`
- `frontend/node_modules`

After extracting, install dependencies locally.

## Recommended local URLs

- Backend: http://localhost:8000
- Frontend: http://localhost:5173

Use `localhost` consistently for both applications to avoid Sanctum cookie problems.

---

## Backend setup

Create a normal Laravel 12 project first if you are starting from an empty machine:

```powershell
composer create-project laravel/laravel backend "^12.0"
cd backend
php artisan install:api
composer require predis/predis
php artisan config:publish cors
```

Then merge/replace the files from this ZIP's `backend` directory into that Laravel project.

Copy environment template:

```powershell
copy .env.example .env
php artisan key:generate
```

Configure PostgreSQL credentials in `.env`.

Create database:

```sql
CREATE DATABASE cricintel;
CREATE USER cricintel_user WITH PASSWORD 'CHANGE_THIS_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE cricintel TO cricintel_user;
```

If necessary:

```sql
\c cricintel
GRANT ALL ON SCHEMA public TO cricintel_user;
ALTER SCHEMA public OWNER TO cricintel_user;
```

Run:

```powershell
php artisan migrate
php artisan db:seed
php artisan test
php artisan serve
```

## Redis on Windows / WSL

```bash
sudo apt update
sudo apt install redis-server
sudo service redis-server start
redis-cli ping
```

Expected:

```text
PONG
```

## Frontend setup

Create a React TypeScript Vite project if needed:

```powershell
npm create vite@latest frontend -- --template react-ts
cd frontend
npm install
npm install axios react-router-dom @tanstack/react-query react-hook-form
npm install -D vitest jsdom @testing-library/react @testing-library/jest-dom @testing-library/user-event
```

Then merge/replace the files from this ZIP's `frontend` directory.

Run:

```powershell
npm install
npm run dev
```

Tests:

```powershell
npm run test:run
```

Build check:

```powershell
npm run build
```

## Create development administrator

```powershell
cd backend
php artisan tinker
```

Then:

```php
use App\Models\User;
use App\Models\Role;

$admin = User::create([
    'name' => 'CricIntel Administrator',
    'email' => 'admin@cricintel.local',
    'password' => 'AdminPassword123',
]);

$role = Role::where('name', 'Administrator')->firstOrFail();
$admin->roles()->attach($role);
```

Development-only credentials:

- Email: `admin@cricintel.local`
- Password: `AdminPassword123`

Change them before any real deployment.

## API routes

- POST `/api/v1/auth/register`
- POST `/api/v1/auth/login`
- POST `/api/v1/auth/logout`
- GET `/api/v1/auth/me`
- GET `/api/v1/admin/ping`

Before login/register from a browser client, request:

- GET `/sanctum/csrf-cookie`

## P1 roles

- Administrator
- Coach
- Analyst
- Selector
- Team Manager
- Player
