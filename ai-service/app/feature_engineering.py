from __future__ import annotations

import numpy as np
import pandas as pd


BATTER_NUMERIC_FEATURES = [
    "career_prior_innings",
    "recent_avg_runs_5",
    "recent_avg_runs_10",
    "recent_avg_sr_5",
    "recent_dismissal_rate_10",
    "venue_prior_avg_runs",
    "opponent_prior_avg_runs",
    "max_overs",
]

BATTER_CATEGORICAL_FEATURES = [
    "player_id",
    "opponent_team_id",
    "venue_id",
]

BOWLER_NUMERIC_FEATURES = [
    "career_prior_innings",
    "recent_economy_5",
    "recent_economy_10",
    "recent_wickets_5",
    "recent_dot_pct_5",
    "venue_prior_economy",
    "opponent_prior_economy",
    "max_overs",
]

BOWLER_CATEGORICAL_FEATURES = [
    "player_id",
    "opponent_team_id",
    "venue_id",
]

TEAM_NUMERIC_FEATURES = [
    "career_prior_innings",
    "recent_total_5",
    "recent_total_10",
    "recent_run_rate_5",
    "venue_prior_total",
    "opponent_prior_total",
    "max_overs",
]

TEAM_CATEGORICAL_FEATURES = [
    "team_id",
    "opponent_team_id",
    "venue_id",
]


def _rolling_prior(series: pd.Series, window: int) -> pd.Series:
    return series.shift(1).rolling(window=window, min_periods=1).mean()


def _expanding_prior(series: pd.Series) -> pd.Series:
    return series.shift(1).expanding(min_periods=1).mean()


def _ensure_category_strings(frame: pd.DataFrame, columns: list[str]) -> pd.DataFrame:
    result = frame.copy()
    for column in columns:
        result[column] = result[column].fillna(-1).astype(str)
    return result


def build_batter_features(frame: pd.DataFrame) -> pd.DataFrame:
    if frame.empty:
        return frame

    df = frame.sort_values(
        ["scheduled_at", "match_id", "innings_number", "player_id"],
        kind="stable",
    ).copy()

    df["strike_rate"] = np.where(
        df["balls"] > 0,
        df["runs"] / df["balls"] * 100.0,
        0.0,
    )
    df["dismissed_flag"] = (df["dismissed"] > 0).astype(float)

    grouped = df.groupby("player_id", group_keys=False)
    df["career_prior_innings"] = grouped.cumcount()
    df["recent_avg_runs_5"] = grouped["runs"].transform(lambda s: _rolling_prior(s, 5))
    df["recent_avg_runs_10"] = grouped["runs"].transform(lambda s: _rolling_prior(s, 10))
    df["recent_avg_sr_5"] = grouped["strike_rate"].transform(lambda s: _rolling_prior(s, 5))
    df["recent_dismissal_rate_10"] = grouped["dismissed_flag"].transform(lambda s: _rolling_prior(s, 10))

    df["venue_prior_avg_runs"] = (
        df.groupby(["player_id", "venue_id"])["runs"]
        .transform(_expanding_prior)
    )
    df["opponent_prior_avg_runs"] = (
        df.groupby(["player_id", "opponent_team_id"])["runs"]
        .transform(_expanding_prior)
    )
    numeric = BATTER_NUMERIC_FEATURES
    df[numeric] = df[numeric].replace([np.inf, -np.inf], np.nan).fillna(0.0)
    return _ensure_category_strings(df, BATTER_CATEGORICAL_FEATURES)


def build_bowler_features(frame: pd.DataFrame) -> pd.DataFrame:
    if frame.empty:
        return frame

    df = frame.sort_values(
        ["scheduled_at", "match_id", "innings_number", "player_id"],
        kind="stable",
    ).copy()

    grouped = df.groupby("player_id", group_keys=False)
    df["career_prior_innings"] = grouped.cumcount()
    df["recent_economy_5"] = grouped["economy"].transform(lambda s: _rolling_prior(s, 5))
    df["recent_economy_10"] = grouped["economy"].transform(lambda s: _rolling_prior(s, 10))
    df["recent_wickets_5"] = grouped["wickets"].transform(lambda s: _rolling_prior(s, 5))
    df["recent_dot_pct_5"] = grouped["dot_pct"].transform(lambda s: _rolling_prior(s, 5))
    df["venue_prior_economy"] = (
        df.groupby(["player_id", "venue_id"])["economy"]
        .transform(_expanding_prior)
    )
    df["opponent_prior_economy"] = (
        df.groupby(["player_id", "opponent_team_id"])["economy"]
        .transform(_expanding_prior)
    )

    df[BOWLER_NUMERIC_FEATURES] = (
        df[BOWLER_NUMERIC_FEATURES]
        .replace([np.inf, -np.inf], np.nan)
        .fillna(0.0)
    )
    return _ensure_category_strings(df, BOWLER_CATEGORICAL_FEATURES)


def build_team_features(frame: pd.DataFrame) -> pd.DataFrame:
    if frame.empty:
        return frame

    df = frame.sort_values(
        ["scheduled_at", "match_id", "innings_number", "team_id"],
        kind="stable",
    ).copy()

    grouped = df.groupby("team_id", group_keys=False)
    df["career_prior_innings"] = grouped.cumcount()
    df["recent_total_5"] = grouped["total_runs"].transform(lambda s: _rolling_prior(s, 5))
    df["recent_total_10"] = grouped["total_runs"].transform(lambda s: _rolling_prior(s, 10))
    df["recent_run_rate_5"] = grouped["run_rate"].transform(lambda s: _rolling_prior(s, 5))
    df["venue_prior_total"] = (
        df.groupby(["team_id", "venue_id"])["total_runs"]
        .transform(_expanding_prior)
    )
    df["opponent_prior_total"] = (
        df.groupby(["team_id", "opponent_team_id"])["total_runs"]
        .transform(_expanding_prior)
    )

    df[TEAM_NUMERIC_FEATURES] = (
        df[TEAM_NUMERIC_FEATURES]
        .replace([np.inf, -np.inf], np.nan)
        .fillna(0.0)
    )
    return _ensure_category_strings(df, TEAM_CATEGORICAL_FEATURES)
