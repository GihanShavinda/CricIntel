from __future__ import annotations

from dataclasses import dataclass
from typing import Literal

import pandas as pd
from sqlalchemy import text

from .config import settings
from .db import get_engine
from .feature_engineering import (
    BATTER_CATEGORICAL_FEATURES,
    BATTER_NUMERIC_FEATURES,
    BOWLER_CATEGORICAL_FEATURES,
    BOWLER_NUMERIC_FEATURES,
    TEAM_CATEGORICAL_FEATURES,
    TEAM_NUMERIC_FEATURES,
    build_batter_features,
    build_bowler_features,
    build_team_features,
)


@dataclass
class DatasetSpec:
    frame: pd.DataFrame
    target: str
    numeric_features: list[str]
    categorical_features: list[str]


class TrainingDatasetBuilder:
    def batter_dataset(self, organization_id: int) -> DatasetSpec:
        sql = text("""
            WITH batter_innings AS (
                SELECT
                    m.organization_id,
                    m.id AS match_id,
                    i.id AS innings_id,
                    i.innings_number,
                    i.batting_team_id,
                    i.bowling_team_id AS opponent_team_id,
                    f.venue_id,
                    f.scheduled_at,
                    COALESCE(m.max_overs, 20) AS max_overs,
                    d.batter_id AS player_id,
                    SUM(d.runs_off_bat)::int AS runs,
                    COUNT(*) FILTER (WHERE d.is_legal = true)::int AS balls,
                    MAX(CASE WHEN d.dismissed_player_id = d.batter_id THEN 1 ELSE 0 END)::int AS dismissed,
                    MAX(i.runs)::int AS team_total
                FROM deliveries d
                JOIN innings i ON i.id = d.innings_id
                JOIN matches m ON m.id = i.match_id
                JOIN fixtures f ON f.id = m.fixture_id
                WHERE m.organization_id = :organization_id
                  AND d.batter_id IS NOT NULL
                  AND f.scheduled_at IS NOT NULL
                  AND COALESCE(i.status, '') IN ('Completed', 'completed')
                GROUP BY
                    m.organization_id,
                    m.id,
                    i.id,
                    i.innings_number,
                    i.batting_team_id,
                    i.bowling_team_id,
                    f.venue_id,
                    f.scheduled_at,
                    m.max_overs,
                    d.batter_id
            )
            SELECT *
            FROM batter_innings
            ORDER BY scheduled_at, match_id, innings_number, player_id
        """)

        frame = pd.read_sql(sql, get_engine(), params={"organization_id": organization_id})
        frame["scheduled_at"] = pd.to_datetime(frame["scheduled_at"], utc=True)
        frame = build_batter_features(frame)
        if not frame.empty:
            frame = frame[frame["career_prior_innings"] >= 2].copy()

        return DatasetSpec(
            frame=frame,
            target="runs",
            numeric_features=BATTER_NUMERIC_FEATURES,
            categorical_features=BATTER_CATEGORICAL_FEATURES,
        )

    def bowler_dataset(self, organization_id: int) -> DatasetSpec:
        sql = text("""
            WITH bowler_innings AS (
                SELECT
                    m.organization_id,
                    m.id AS match_id,
                    i.id AS innings_id,
                    i.innings_number,
                    i.bowling_team_id,
                    i.batting_team_id AS opponent_team_id,
                    f.venue_id,
                    f.scheduled_at,
                    COALESCE(m.max_overs, 20) AS max_overs,
                    d.bowler_id AS player_id,
                    COUNT(*) FILTER (WHERE d.is_legal = true)::int AS legal_balls,
                    SUM(
                        CASE
                            WHEN COALESCE(d.extra_type, 'none') IN ('bye', 'leg_bye', 'leg bye')
                                THEN d.runs_off_bat
                            ELSE d.runs_off_bat + d.extra_runs
                        END
                    )::int AS runs_conceded,
                    SUM(
                        CASE
                            WHEN d.wicket = true
                             AND COALESCE(LOWER(d.wicket_type), '') NOT IN (
                                'run_out', 'run out', 'retired_hurt', 'retired hurt',
                                'obstructing_field', 'obstructing field'
                             )
                            THEN 1 ELSE 0
                        END
                    )::int AS wickets,
                    COUNT(*) FILTER (
                        WHERE d.is_legal = true
                          AND d.total_runs = 0
                    )::int AS dot_balls
                FROM deliveries d
                JOIN innings i ON i.id = d.innings_id
                JOIN matches m ON m.id = i.match_id
                JOIN fixtures f ON f.id = m.fixture_id
                WHERE m.organization_id = :organization_id
                  AND d.bowler_id IS NOT NULL
                  AND f.scheduled_at IS NOT NULL
                  AND COALESCE(i.status, '') IN ('Completed', 'completed')
                GROUP BY
                    m.organization_id,
                    m.id,
                    i.id,
                    i.innings_number,
                    i.bowling_team_id,
                    i.batting_team_id,
                    f.venue_id,
                    f.scheduled_at,
                    m.max_overs,
                    d.bowler_id
            )
            SELECT *,
                CASE WHEN legal_balls > 0
                    THEN runs_conceded::float / legal_balls * 6.0
                    ELSE 0.0 END AS economy,
                CASE WHEN legal_balls > 0
                    THEN dot_balls::float / legal_balls * 100.0
                    ELSE 0.0 END AS dot_pct
            FROM bowler_innings
            WHERE legal_balls >= 6
            ORDER BY scheduled_at, match_id, innings_number, player_id
        """)

        frame = pd.read_sql(sql, get_engine(), params={"organization_id": organization_id})
        frame["scheduled_at"] = pd.to_datetime(frame["scheduled_at"], utc=True)
        frame = build_bowler_features(frame)
        if not frame.empty:
            frame = frame[frame["career_prior_innings"] >= 2].copy()

        return DatasetSpec(
            frame=frame,
            target="economy",
            numeric_features=BOWLER_NUMERIC_FEATURES,
            categorical_features=BOWLER_CATEGORICAL_FEATURES,
        )

    def team_dataset(self, organization_id: int) -> DatasetSpec:
        sql = text("""
            SELECT
                m.organization_id,
                m.id AS match_id,
                i.id AS innings_id,
                i.innings_number,
                i.batting_team_id AS team_id,
                i.bowling_team_id AS opponent_team_id,
                f.venue_id,
                f.scheduled_at,
                COALESCE(m.max_overs, 20) AS max_overs,
                i.runs::int AS total_runs,
                COALESCE(i.overs_completed, 0)::float AS overs_completed,
                CASE
                    WHEN COALESCE(i.overs_completed, 0) > 0
                    THEN i.runs::float / i.overs_completed
                    ELSE 0.0
                END AS run_rate
            FROM innings i
            JOIN matches m ON m.id = i.match_id
            JOIN fixtures f ON f.id = m.fixture_id
            WHERE m.organization_id = :organization_id
              AND f.scheduled_at IS NOT NULL
              AND COALESCE(i.status, '') IN ('Completed', 'completed')
              AND i.innings_number = 1
            ORDER BY f.scheduled_at, m.id, i.innings_number, i.batting_team_id
        """)

        frame = pd.read_sql(sql, get_engine(), params={"organization_id": organization_id})
        frame["scheduled_at"] = pd.to_datetime(frame["scheduled_at"], utc=True)
        frame = build_team_features(frame)
        if not frame.empty:
            frame = frame[frame["career_prior_innings"] >= 2].copy()

        return DatasetSpec(
            frame=frame,
            target="total_runs",
            numeric_features=TEAM_NUMERIC_FEATURES,
            categorical_features=TEAM_CATEGORICAL_FEATURES,
        )

    def readiness(self, organization_id: int) -> dict:
        batter = self.batter_dataset(organization_id).frame
        bowler = self.bowler_dataset(organization_id).frame
        team = self.team_dataset(organization_id).frame

        return {
            "organization_id": organization_id,
            "available_data": {
                "batter_innings_rows": len(batter),
                "bowler_innings_rows": len(bowler),
                "team_innings_rows": len(team),
                "batter_unique_players": int(batter["player_id"].nunique()) if not batter.empty else 0,
                "bowler_unique_players": int(bowler["player_id"].nunique()) if not bowler.empty else 0,
                "matches": int(pd.concat([
                    batter.get("match_id", pd.Series(dtype=int)),
                    bowler.get("match_id", pd.Series(dtype=int)),
                    team.get("match_id", pd.Series(dtype=int)),
                ]).nunique()),
            },
            "problems": {
                "batter_score": self._problem_readiness(
                    len(batter),
                    int(batter["match_id"].nunique()) if not batter.empty else 0,
                    settings.min_batter_rows,
                    "Expected batter score range",
                    "Regression at innings level using only pre-innings historical features.",
                ),
                "bowler_economy": self._problem_readiness(
                    len(bowler),
                    int(bowler["match_id"].nunique()) if not bowler.empty else 0,
                    settings.min_bowler_rows,
                    "Bowler expected economy",
                    "Regression at bowler-innings level using only prior bowling history.",
                ),
                "team_total": self._problem_readiness(
                    len(team),
                    int(team["match_id"].nunique()) if not team.empty else 0,
                    settings.min_team_rows,
                    "Expected team total",
                    "Regression at completed first-innings level. Requires more data than player models.",
                ),
                "player_form_trend": {
                    "realistic": True,
                    "trained_model_required": False,
                    "reason": "Rolling historical form trend can be computed deterministically without a supervised model.",
                },
                "wicket_risk_periods": {
                    "realistic": False,
                    "trained_model_required": False,
                    "reason": "Not enabled in P14 because over-level contextual labels and enough historical examples cannot be assumed from schema alone.",
                },
                "matchup_effectiveness": {
                    "realistic": False,
                    "trained_model_required": False,
                    "reason": "Direct batter-vs-bowler samples are usually sparse; P12 descriptive matchup analytics remain safer until readiness proves adequate coverage.",
                },
            },
        }

    @staticmethod
    def _problem_readiness(
        rows: int,
        matches: int,
        minimum: int,
        title: str,
        reason: str,
    ) -> dict:
        minimum_matches = 12
        ready = rows >= minimum and matches >= minimum_matches
        return {
            "title": title,
            "rows": rows,
            "minimum_rows": minimum,
            "matches": matches,
            "minimum_matches": minimum_matches,
            "realistic": ready,
            "trained_model_required": True,
            "reason": reason if ready else (
                f"Current leakage-safe sample: {rows} rows across {matches} matches. "
                f"Training requires at least {minimum} rows across {minimum_matches} matches."
            ),
        }
