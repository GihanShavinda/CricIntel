# CricIntel AI — Milestone 14 (P14)

P14 introduces **predictive analytics for coaching support**.

There is **no LLM in P14**.
Predictions are **not guaranteed outcomes** and are **not designed for gambling,
betting, wagering, odds, staking, or profit decisions**.

## What was reviewed first

P14 was designed against CricIntel's existing stored match, innings, delivery,
player, team, venue and fixture data. The detailed review is in:

`P14_DATA_REVIEW_AND_ML_DESIGN.md`

Because schema availability does not prove that a particular organization already
contains enough historical observations, the FastAPI service performs a live
**readiness review** against the current PostgreSQL database.

## Models implemented

### 1. Expected batter score range

Regression target: batter runs in a completed innings.

Output:

- point estimate
- empirical 80% uncertainty interval
- confidence label
- sample size
- feature values
- global feature importance
- limitations/disclaimer

### 2. Bowler expected economy

Regression target: bowler economy in a completed innings with at least six legal
balls.

### 3. Expected team total

Regression target: completed **first-innings** team total.

Second innings are deliberately excluded because successful chases are censored by
the target and would bias a general team-total model.

### 4. Player form trend

Implemented deterministically rather than through a supervised model.
It uses a linear trend over recent completed batting innings.

## Not enabled yet

P14 deliberately does **not** train:

- wicket-risk-period prediction
- predictive batter-vs-bowler matchup effectiveness

The runtime schema alone does not guarantee sufficient labelled history or pairwise
coverage for those problems. P12 descriptive analytics remains the safer source for
those questions.

## Feature engineering

All supervised feature sets use only information available before the target
observation.

Examples include:

- prior innings count
- rolling last-5 / last-10 performance
- prior venue performance
- prior opponent performance
- maximum overs
- player/team/opponent/venue identity

## Leakage prevention

P14 enforces:

1. completed innings only for player targets
2. completed first innings only for team-total targets
3. rolling and expanding features shifted by one historical observation
4. no target-innings outcome in target-innings features
5. chronological split
6. whole matches kept in one split only
7. model selection on validation MAE only
8. held-out test metrics recorded after selection

## Train / validation / test

Split is chronological by whole match:

- first ~70% matches: training
- next ~15%: validation
- final ~15%: test

Rows from the same match cannot appear in different partitions.

## Baseline and improved model

Baseline:

- `Ridge`
- standardized numeric features
- one-hot categorical features

Improved candidate:

- `RandomForestRegressor`

The Random Forest is selected only when its validation MAE is no worse than the
baseline. Otherwise CricIntel keeps Ridge.

## Metrics

Stored for validation/test:

- MAE
- RMSE
- R² when the split has enough rows

## Uncertainty

P14 calculates an empirical interval from the 80th percentile of absolute
validation residuals.

The interval is uncertainty guidance, not a guarantee.

## Model versioning

Models are versioned locally under:

```text
ai-service/models/{organization_id}/{model_kind}/{version}/
├── model.joblib
└── metadata.json
```

Each model kind also has `latest.json`.

Metadata contains:

- model version
- training date
- selected model
- dataset row counts
- chronological date range
- baseline validation metrics
- improved validation metrics
- held-out test metrics
- uncertainty residual quantiles
- feature importance
- leakage controls

Generated model binaries are ignored by Git.

# Local architecture

```text
React frontend :5173
        |
        v
Laravel API :8000
        |
        v
FastAPI predictive service :8100
        |
        v
PostgreSQL :5433
```

No Docker is used.

# Apply backend files

Copy P14 files into:

```text
F:\My_Projects\CricIntel\cricintel
```

P14 adds **no Laravel database migration**. It trains from authoritative existing
CricIntel data and keeps ML model versions in the local Python model registry.

Optional backend `.env` values:

```env
PREDICTIVE_ANALYTICS_ENABLED=true
PREDICTIVE_ANALYTICS_URL=http://127.0.0.1:8100
PREDICTIVE_ANALYTICS_TIMEOUT=15
PREDICTIVE_ANALYTICS_TRAINING_TIMEOUT=180
```

Defaults already match these values, so they are optional for local development.

Run backend checks:

```powershell
cd F:\My_Projects\CricIntel\cricintel\backend

php artisan optimize:clear
php artisan route:list | findstr predictive
php artisan test --filter=P14
php artisan test
```

Then start Laravel:

```powershell
php artisan serve --host=localhost --port=8000
```

# Start Python predictive service

Open a separate PowerShell terminal:

```powershell
cd F:\My_Projects\CricIntel\cricintel\ai-service

python -m venv .venv
.\.venv\Scripts\Activate.ps1

python -m pip install --upgrade pip
pip install -r requirements.txt

Copy-Item .env.example .env
```

Review `.env`. For the current CricIntel PostgreSQL setup it should contain:

```env
DATABASE_URL=postgresql+psycopg://cricintel_user:YourStrongPassword123@127.0.0.1:5433/cricintel
```

Use your real PostgreSQL password.

Run Python tests:

```powershell
pytest -q
```

Start FastAPI:

```powershell
uvicorn app.main:app --host 127.0.0.1 --port 8100 --reload
```

Health URL:

```text
http://127.0.0.1:8100/health
```

Swagger:

```text
http://127.0.0.1:8100/docs
```

# Frontend

Open another PowerShell terminal:

```powershell
cd F:\My_Projects\CricIntel\cricintel\frontend
npm install
npm run build
npm run dev
```

Open:

```text
http://localhost:5173/organizations/34/predictive
```

# Normal terminals for full P1–P14 system

For all features including realtime match scoring:

1. Laravel backend — port 8000
2. React/Vite frontend — port 5173
3. Laravel Reverb — port 8080
4. FastAPI predictive service — port 8100

Queue worker remains an additional terminal only when you are testing workflows that
actually use queued jobs.

# First P14 workflow

1. Start PostgreSQL.
2. Start FastAPI on port 8100.
3. Start Laravel on port 8000.
4. Start React on port 5173.
5. Open an organization.
6. Open **Predictive Analytics**.
7. Review the live readiness cards.
8. If models are statistically ready, Coach/Admin selects **Train ready models**.
9. Generate a batter-score, bowler-economy, or team-total prediction.
10. Review interval, confidence, model version, feature importance and limitations.

If readiness is blocked, this is expected behavior. Add more completed match history
rather than lowering thresholds just to obtain a prediction.

# P14 API routes

Laravel:

```text
GET  /api/v1/organizations/{organization}/predictive/options
GET  /api/v1/organizations/{organization}/predictive/readiness
POST /api/v1/organizations/{organization}/predictive/train
GET  /api/v1/organizations/{organization}/predictive/models/{modelKind}
POST /api/v1/organizations/{organization}/predictive/batters/{player}/score
POST /api/v1/organizations/{organization}/predictive/bowlers/{player}/economy
POST /api/v1/organizations/{organization}/predictive/teams/{team}/total
GET  /api/v1/organizations/{organization}/predictive/players/{player}/form
```

FastAPI:

```text
GET  /health
GET  /v1/readiness/{organization_id}
POST /v1/train
GET  /v1/models/{organization_id}/{model_kind}
POST /v1/predict/batter-score
POST /v1/predict/bowler-economy
POST /v1/predict/team-total
GET  /v1/form/{organization_id}/players/{player_id}
```

STOP after P14.
