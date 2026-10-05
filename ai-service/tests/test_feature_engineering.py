import pandas as pd

from app.feature_engineering import build_batter_features


def test_batter_rolling_features_are_shifted_and_do_not_see_target_row():
    frame = pd.DataFrame([
        {
            "scheduled_at": pd.Timestamp("2026-01-01", tz="UTC"),
            "match_id": 1,
            "innings_number": 1,
            "player_id": 7,
            "batting_team_id": 10,
            "opponent_team_id": 20,
            "venue_id": 1,
            "runs": 10,
            "balls": 10,
            "dismissed": 1,
            "team_total": 100,
            "max_overs": 20,
        },
        {
            "scheduled_at": pd.Timestamp("2026-01-02", tz="UTC"),
            "match_id": 2,
            "innings_number": 1,
            "player_id": 7,
            "batting_team_id": 10,
            "opponent_team_id": 20,
            "venue_id": 1,
            "runs": 100,
            "balls": 50,
            "dismissed": 0,
            "team_total": 180,
            "max_overs": 20,
        },
    ])

    result = build_batter_features(frame)

    second = result.iloc[1]
    assert second["recent_avg_runs_5"] == 10
    assert second["venue_prior_avg_runs"] == 10
    assert second["opponent_prior_avg_runs"] == 10


def test_first_observation_has_zero_prior_features():
    frame = pd.DataFrame([
        {
            "scheduled_at": pd.Timestamp("2026-01-01", tz="UTC"),
            "match_id": 1,
            "innings_number": 1,
            "player_id": 7,
            "batting_team_id": 10,
            "opponent_team_id": 20,
            "venue_id": 1,
            "runs": 50,
            "balls": 30,
            "dismissed": 1,
            "team_total": 150,
            "max_overs": 20,
        },
    ])

    result = build_batter_features(frame)
    first = result.iloc[0]

    assert first["career_prior_innings"] == 0
    assert first["recent_avg_runs_5"] == 0
    assert first["venue_prior_avg_runs"] == 0
