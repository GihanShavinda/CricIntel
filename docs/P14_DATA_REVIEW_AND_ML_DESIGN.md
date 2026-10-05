# CricIntel P14 — Data Review and ML Design

## 1. Data currently available in CricIntel

P14 is built only around data already introduced by P3–P6/P12.

### Match context

CricIntel stores:

- organization
- fixture
- scheduled date/time
- home and away teams
- venue
- match status
- maximum overs
- toss fields
- winner/result fields

### Innings outcomes

CricIntel stores:

- batting team
- bowling team
- innings number
- final/current runs
- wickets
- legal balls
- overs completed
- innings status

### Ball-by-ball data

CricIntel stores:

- batter
- non-striker
- bowler
- runs off bat
- extras
- total runs
- legal-ball flag
- wicket
- wicket type
- dismissed player
- fielder
- shot type where recorded
- delivery type where recorded
- pitch zone where recorded
- ball speed where recorded

### Player context

CricIntel stores:

- player identity
- role
- batting style
- bowling style
- team membership/history
- availability/status

## 2. What P14 can realistically predict

Schema availability does not prove that the current organization already has enough rows.
Therefore P14 has a runtime **readiness review**. Training is blocked until the
minimum leakage-safe dataset threshold is met.

### A. Expected batter score range — supported when ready

**Problem:** regression.

**Target:** batter runs in a completed innings.

**Inputs available before the target innings:**

- prior innings count
- last-5 batting average runs
- last-10 batting average runs
- last-5 strike rate
- last-10 dismissal rate
- prior average runs at selected venue
- prior average runs against selected opponent
- match maximum overs
- player identity
- opponent identity
- venue identity

**Output:** point estimate plus empirical 80% uncertainty interval.

### B. Bowler expected economy — supported when ready

**Problem:** regression.

**Target:** bowler economy in a completed innings with at least one over bowled.

**Inputs:**

- prior bowling innings count
- last-5 economy
- last-10 economy
- last-5 wickets
- last-5 dot-ball percentage
- prior venue economy
- prior opponent economy
- maximum overs
- player/opponent/venue identity

### C. Expected team total — supported conservatively when ready

**Problem:** regression.

P14 trains this target only on **completed first innings**.
Second-innings chase totals are deliberately excluded because successful chases are
censored by the target and would distort a general team-total model.

Inputs use only prior team innings:

- prior innings count
- last-5 total
- last-10 total
- last-5 run rate
- prior venue total
- prior opponent total
- maximum overs
- team/opponent/venue identity

### D. Player form trend — supported without ML

A recent-innings linear trend is statistically simpler and more transparent than
training a supervised model for this task. P14 therefore keeps it deterministic.

## 3. Predictions not enabled in P14

### Wicket-risk periods

Not enabled as a predictive model yet. Although wickets and over numbers exist, the
schema alone does not demonstrate enough over-level labelled history for a reliable
classification problem across organizations.

### Direct matchup effectiveness prediction

Not enabled. Batter-vs-bowler pair samples are typically sparse. P12 continues to
provide deterministic matchup statistics until runtime data coverage justifies a
future predictive model.

## 4. Leakage prevention

P14 uses several mandatory controls:

1. Only completed innings are used for batter/bowler targets.
2. Team-total training uses completed first innings only.
3. Every rolling/expanding historical feature is shifted by one observation.
4. Target-innings values are never used as target-innings inputs.
5. Train/validation/test split is chronological.
6. Entire matches stay in exactly one partition; rows from one match cannot be
   split between training and evaluation.
7. The improved model is selected only from validation performance.
8. The held-out test set is not used to select the model.

## 5. Models

### Baseline

`Ridge` regression over:

- standardized numeric features
- one-hot categorical context

### Improved candidate

`RandomForestRegressor`.

The improved candidate is used only when its **validation MAE is no worse than the
baseline MAE**. Otherwise P14 keeps the baseline model.

## 6. Metrics

P14 records:

- MAE — primary model-selection metric
- RMSE — penalizes larger misses
- R² — descriptive goodness-of-fit when evaluation sample permits it

Cricket predictions should not be judged using accuracy/classification terminology
for these continuous targets.

## 7. Uncertainty

The displayed interval is derived from the 80th percentile of absolute validation
residuals around the selected model's prediction.

This is an empirical uncertainty interval, not a guarantee or formal probability
that the cricket outcome must fall inside it.

## 8. Explainability

P14 exposes top global feature importances from the selected trained model.

- Random Forest: impurity-based feature importance
- Ridge baseline: absolute coefficient magnitude

The UI explicitly states that feature importance does not establish causation.

## 9. Safety / intended use

Every prediction response includes the disclaimer:

> Predictive coaching support only. This is not a guaranteed outcome and is not
> intended for gambling or wagering decisions.

No gambling-specific market, odds, stake, bet, profit, or wagering functionality is
implemented.

No LLM is used in P14.
