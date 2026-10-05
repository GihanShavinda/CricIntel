from __future__ import annotations

import math
from typing import Any

import numpy as np
import pandas as pd
from sqlalchemy import text

from .dataset_builder import TrainingDatasetBuilder
from .db import get_engine
from .model_registry import ModelRegistry


DISCLAIMER = (
    "Predictive coaching support only. This is not a guaranteed outcome and "
    "is not intended for gambling or wagering decisions."
)


class PredictionService:
    def __init__(
        self,
        builder: TrainingDatasetBuilder | None = None,
        registry: ModelRegistry | None = None,
    ) -> None:
        self.builder = builder or TrainingDatasetBuilder()
        self.registry = registry or ModelRegistry()

    def batter_score(
        self,
        organization_id: int,
        player_id: int,
        opponent_team_id: int | None,
        venue_id: int | None,
        max_overs: int,
    ) -> dict[str, Any]:
        spec = self.builder.batter_dataset(organization_id)
        history = spec.frame[spec.frame["player_id"].astype(str) == str(player_id)].copy()
        if history.empty:
            raise ValueError("No leakage-safe batter history exists for this player.")

        row = {
            "career_prior_innings": int(len(history)),
            "recent_avg_runs_5": self._mean(history.tail(5)["runs"]),
            "recent_avg_runs_10": self._mean(history.tail(10)["runs"]),
            "recent_avg_sr_5": self._mean(history.tail(5)["strike_rate"]),
            "recent_dismissal_rate_10": self._mean(history.tail(10)["dismissed_flag"]),
            "venue_prior_avg_runs": self._conditional_mean(history, "venue_id", venue_id, "runs"),
            "opponent_prior_avg_runs": self._conditional_mean(history, "opponent_team_id", opponent_team_id, "runs"),
            "max_overs": int(max_overs),
            "player_id": str(player_id),
            "opponent_team_id": str(opponent_team_id if opponent_team_id is not None else -1),
            "venue_id": str(venue_id if venue_id is not None else -1),
        }
        return self._predict(
            organization_id,
            "batter_score",
            player_id,
            row,
            sample_size=len(history),
            floor=0.0,
        )

    def bowler_economy(
        self,
        organization_id: int,
        player_id: int,
        opponent_team_id: int | None,
        venue_id: int | None,
        max_overs: int,
    ) -> dict[str, Any]:
        spec = self.builder.bowler_dataset(organization_id)
        history = spec.frame[spec.frame["player_id"].astype(str) == str(player_id)].copy()
        if history.empty:
            raise ValueError("No leakage-safe bowler history exists for this player.")

        row = {
            "career_prior_innings": int(len(history)),
            "recent_economy_5": self._mean(history.tail(5)["economy"]),
            "recent_economy_10": self._mean(history.tail(10)["economy"]),
            "recent_wickets_5": self._mean(history.tail(5)["wickets"]),
            "recent_dot_pct_5": self._mean(history.tail(5)["dot_pct"]),
            "venue_prior_economy": self._conditional_mean(history, "venue_id", venue_id, "economy"),
            "opponent_prior_economy": self._conditional_mean(history, "opponent_team_id", opponent_team_id, "economy"),
            "max_overs": int(max_overs),
            "player_id": str(player_id),
            "opponent_team_id": str(opponent_team_id if opponent_team_id is not None else -1),
            "venue_id": str(venue_id if venue_id is not None else -1),
        }
        return self._predict(
            organization_id,
            "bowler_economy",
            player_id,
            row,
            sample_size=len(history),
            floor=0.0,
        )

    def team_total(
        self,
        organization_id: int,
        team_id: int,
        opponent_team_id: int | None,
        venue_id: int | None,
        max_overs: int,
    ) -> dict[str, Any]:
        spec = self.builder.team_dataset(organization_id)
        history = spec.frame[spec.frame["team_id"].astype(str) == str(team_id)].copy()
        if history.empty:
            raise ValueError("No leakage-safe completed-innings history exists for this team.")

        row = {
            "career_prior_innings": int(len(history)),
            "recent_total_5": self._mean(history.tail(5)["total_runs"]),
            "recent_total_10": self._mean(history.tail(10)["total_runs"]),
            "recent_run_rate_5": self._mean(history.tail(5)["run_rate"]),
            "venue_prior_total": self._conditional_mean(history, "venue_id", venue_id, "total_runs"),
            "opponent_prior_total": self._conditional_mean(history, "opponent_team_id", opponent_team_id, "total_runs"),
            "max_overs": int(max_overs),
            "team_id": str(team_id),
            "opponent_team_id": str(opponent_team_id if opponent_team_id is not None else -1),
            "venue_id": str(venue_id if venue_id is not None else -1),
        }
        return self._predict(
            organization_id,
            "team_total",
            team_id,
            row,
            sample_size=len(history),
            floor=0.0,
        )

    def player_form_trend(self, organization_id: int, player_id: int) -> dict[str, Any]:
        batter = self.builder.batter_dataset(organization_id).frame
        rows = batter[batter["player_id"].astype(str) == str(player_id)].copy().tail(10)

        if len(rows) < 3:
            return {
                "player_id": player_id,
                "sample_size": len(rows),
                "trend": "insufficient_data",
                "slope_runs_per_innings": None,
                "recent_runs": rows["runs"].tolist() if not rows.empty else [],
                "message": "At least three leakage-safe prior innings are required for a form trend.",
                "disclaimer": DISCLAIMER,
            }

        x = np.arange(len(rows), dtype=float)
        y = rows["runs"].to_numpy(dtype=float)
        slope = float(np.polyfit(x, y, 1)[0])

        if slope > 2.0:
            trend = "improving"
        elif slope < -2.0:
            trend = "declining"
        else:
            trend = "stable"

        return {
            "player_id": player_id,
            "sample_size": len(rows),
            "trend": trend,
            "slope_runs_per_innings": round(slope, 3),
            "recent_runs": [int(v) for v in rows["runs"].tolist()],
            "message": "Deterministic linear trend over the player's latest available innings; not a guaranteed future outcome.",
            "disclaimer": DISCLAIMER,
        }

    def _predict(
        self,
        organization_id: int,
        model_kind: str,
        subject_id: int,
        feature_values: dict[str, Any],
        sample_size: int,
        floor: float | None = None,
    ) -> dict[str, Any]:
        loaded = self.registry.load_latest(organization_id, model_kind)
        frame = pd.DataFrame([feature_values])
        prediction = float(loaded.pipeline.predict(frame)[0])
        q80 = float(loaded.metadata.get("uncertainty", {}).get("q80", 0.0))
        lower = prediction - q80
        upper = prediction + q80

        if floor is not None:
            prediction = max(floor, prediction)
            lower = max(floor, lower)
            upper = max(floor, upper)

        test_mae = loaded.metadata.get("test_metrics", {}).get("mae")
        confidence = self._confidence_label(sample_size, q80, prediction, test_mae)
        importance = loaded.metadata.get("feature_importance", [])[:8]

        limitations = [
            "The interval is an empirical validation-residual interval, not a guarantee.",
            "Predictions depend on the quality and quantity of stored CricIntel history.",
            "Unrecorded injuries, tactics, pitch changes, weather changes, and lineup changes are not inferred.",
            "The model is for coaching analysis only and must not be used for gambling or wagering.",
        ]

        return {
            "model_kind": model_kind,
            "model_version": loaded.metadata["version"],
            "subject_id": subject_id,
            "prediction": round(prediction, 2),
            "interval": {
                "lower": round(lower, 2),
                "prediction": round(prediction, 2),
                "upper": round(upper, 2),
                "level": 0.80,
            },
            "confidence_label": confidence,
            "sample_size": sample_size,
            "feature_values": feature_values,
            "explainability": importance,
            "limitations": limitations,
            "disclaimer": DISCLAIMER,
        }

    @staticmethod
    def _confidence_label(
        sample_size: int,
        half_width: float,
        prediction: float,
        test_mae: float | None,
    ) -> str:
        relative = half_width / max(abs(prediction), 1.0)
        if sample_size >= 12 and relative <= 0.25 and (test_mae is None or test_mae <= max(abs(prediction) * 0.35, 2.0)):
            return "higher"
        if sample_size >= 6 and relative <= 0.5:
            return "moderate"
        return "low"

    @staticmethod
    def _mean(series: pd.Series) -> float:
        if series.empty:
            return 0.0
        value = float(series.mean())
        return 0.0 if math.isnan(value) else round(value, 6)

    def _conditional_mean(
        self,
        frame: pd.DataFrame,
        column: str,
        value: int | None,
        target: str,
    ) -> float:
        if value is None:
            return self._mean(frame[target])
        subset = frame[frame[column].astype(str) == str(value)]
        return self._mean(subset[target]) if not subset.empty else self._mean(frame[target])
