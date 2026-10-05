from __future__ import annotations

import math
from dataclasses import dataclass
from typing import Any

import numpy as np
import pandas as pd
from sklearn.base import clone
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import RandomForestRegressor
from sklearn.impute import SimpleImputer
from sklearn.linear_model import Ridge
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder, StandardScaler

from .config import settings
from .dataset_builder import DatasetSpec, TrainingDatasetBuilder
from .model_registry import ModelRegistry
from .splitting import temporal_split


@dataclass
class TrainingResult:
    model_kind: str
    status: str
    metadata: dict[str, Any]


class ModelTrainer:
    def __init__(
        self,
        builder: TrainingDatasetBuilder | None = None,
        registry: ModelRegistry | None = None,
    ) -> None:
        self.builder = builder or TrainingDatasetBuilder()
        self.registry = registry or ModelRegistry()

    def train_many(
        self,
        organization_id: int,
        model_kinds: list[str],
        force: bool = False,
    ) -> list[TrainingResult]:
        return [
            self.train(organization_id, model_kind, force=force)
            for model_kind in model_kinds
        ]

    def train(
        self,
        organization_id: int,
        model_kind: str,
        force: bool = False,
    ) -> TrainingResult:
        spec = self._dataset(model_kind, organization_id)
        minimum = self._minimum_rows(model_kind)

        match_count = int(spec.frame["match_id"].nunique()) if not spec.frame.empty else 0

        if (len(spec.frame) < minimum or match_count < 12) and not force:
            return TrainingResult(
                model_kind=model_kind,
                status="not_ready",
                metadata={
                    "rows": len(spec.frame),
                    "matches": match_count,
                    "minimum_rows": minimum,
                    "minimum_matches": 12,
                    "reason": (
                        "Training blocked because the leakage-safe dataset is too small. "
                        "Collect more completed CricIntel match data."
                    ),
                },
            )

        if len(spec.frame) < 12 or match_count < 3:
            return TrainingResult(
                model_kind=model_kind,
                status="not_ready",
                metadata={
                    "rows": len(spec.frame),
                    "minimum_rows": max(12, minimum),
                    "minimum_matches": 3,
                    "reason": "At least 12 rows across three matches are required even when force=true.",
                },
            )

        split = temporal_split(spec.frame)
        feature_columns = spec.numeric_features + spec.categorical_features

        x_train = split.train[feature_columns]
        y_train = split.train[spec.target]
        x_val = split.validation[feature_columns]
        y_val = split.validation[spec.target]
        x_test = split.test[feature_columns]
        y_test = split.test[spec.target]

        baseline = self._baseline_pipeline(spec)
        improved = self._improved_pipeline(spec)

        baseline.fit(x_train, y_train)
        improved.fit(x_train, y_train)

        baseline_val = self._metrics(y_val, baseline.predict(x_val))
        improved_val = self._metrics(y_val, improved.predict(x_val))

        selected_name, selected = (
            ("improved_random_forest", improved)
            if improved_val["mae"] <= baseline_val["mae"]
            else ("baseline_ridge", baseline)
        )

        selected_val_predictions = selected.predict(x_val)
        residuals = np.abs(np.asarray(y_val) - selected_val_predictions)
        q80 = float(np.quantile(residuals, 0.80)) if len(residuals) else 0.0
        q90 = float(np.quantile(residuals, 0.90)) if len(residuals) else q80

        test_metrics = self._metrics(y_test, selected.predict(x_test))
        feature_importance = self._feature_importance(selected, spec)

        # After model selection and untouched test evaluation, fit the selected
        # algorithm on train + validation. The held-out test partition remains
        # unseen by the deployed model.
        deployed = clone(selected)
        fit_frame = pd.concat([split.train, split.validation], ignore_index=True)
        deployed.fit(
            fit_frame[feature_columns],
            fit_frame[spec.target],
        )

        metadata = self.registry.save(
            organization_id,
            model_kind,
            deployed,
            {
                "selected_model": selected_name,
                "target": spec.target,
                "numeric_features": spec.numeric_features,
                "categorical_features": spec.categorical_features,
                "rows": len(spec.frame),
                "train_rows": len(split.train),
                "validation_rows": len(split.validation),
                "test_rows": len(split.test),
                "deployed_fit_rows": len(fit_frame),
                "date_range": {
                    "start": str(spec.frame["scheduled_at"].min()),
                    "end": str(spec.frame["scheduled_at"].max()),
                },
                "baseline_validation_metrics": baseline_val,
                "improved_validation_metrics": improved_val,
                "test_metrics": test_metrics,
                "uncertainty": {
                    "method": "absolute validation residual quantile",
                    "q80": q80,
                    "q90": q90,
                },
                "feature_importance": feature_importance,
                "leakage_controls": [
                    "Chronological train/validation/test split; no random split.",
                    "Every rolling and expanding historical feature is shifted by one observation.",
                    "Target-innings outcomes are never included in target-innings input features.",
                    "Test rows occur after training/validation rows in time.",
                ],
            },
        )

        return TrainingResult(
            model_kind=model_kind,
            status="trained",
            metadata=metadata,
        )

    def _dataset(self, model_kind: str, organization_id: int) -> DatasetSpec:
        if model_kind == "batter_score":
            return self.builder.batter_dataset(organization_id)
        if model_kind == "bowler_economy":
            return self.builder.bowler_dataset(organization_id)
        if model_kind == "team_total":
            return self.builder.team_dataset(organization_id)
        raise ValueError(f"Unsupported model kind: {model_kind}")

    @staticmethod
    def _minimum_rows(model_kind: str) -> int:
        if model_kind == "batter_score":
            return settings.min_batter_rows
        if model_kind == "bowler_economy":
            return settings.min_bowler_rows
        if model_kind == "team_total":
            return settings.min_team_rows
        return 10**9

    @staticmethod
    def _preprocessor(spec: DatasetSpec) -> ColumnTransformer:
        numeric = Pipeline([
            ("imputer", SimpleImputer(strategy="median")),
            ("scale", StandardScaler()),
        ])
        categorical = Pipeline([
            ("imputer", SimpleImputer(strategy="most_frequent")),
            ("onehot", OneHotEncoder(handle_unknown="ignore", sparse_output=False)),
        ])

        return ColumnTransformer([
            ("numeric", numeric, spec.numeric_features),
            ("categorical", categorical, spec.categorical_features),
        ])

    def _baseline_pipeline(self, spec: DatasetSpec) -> Pipeline:
        return Pipeline([
            ("preprocess", self._preprocessor(spec)),
            ("model", Ridge(alpha=1.0)),
        ])

    def _improved_pipeline(self, spec: DatasetSpec) -> Pipeline:
        return Pipeline([
            ("preprocess", self._preprocessor(spec)),
            (
                "model",
                RandomForestRegressor(
                    n_estimators=300,
                    max_depth=8,
                    min_samples_leaf=3,
                    random_state=settings.random_state,
                    n_jobs=-1,
                ),
            ),
        ])

    @staticmethod
    def _metrics(y_true: pd.Series, y_pred: np.ndarray) -> dict[str, float | None]:
        if len(y_true) == 0:
            return {"mae": None, "rmse": None, "r2": None}

        mae = float(mean_absolute_error(y_true, y_pred))
        rmse = float(math.sqrt(mean_squared_error(y_true, y_pred)))
        r2 = float(r2_score(y_true, y_pred)) if len(y_true) >= 2 else None

        return {
            "mae": round(mae, 4),
            "rmse": round(rmse, 4),
            "r2": round(r2, 4) if r2 is not None else None,
        }

    @staticmethod
    def _feature_importance(pipeline: Pipeline, spec: DatasetSpec) -> list[dict[str, float | str]]:
        model = pipeline.named_steps["model"]
        preprocess = pipeline.named_steps["preprocess"]

        try:
            names = list(preprocess.get_feature_names_out())
        except Exception:
            names = [*spec.numeric_features, *spec.categorical_features]

        if hasattr(model, "feature_importances_"):
            values = model.feature_importances_
        elif hasattr(model, "coef_"):
            values = np.abs(np.ravel(model.coef_))
        else:
            return []

        pairs = sorted(
            zip(names, values),
            key=lambda item: float(item[1]),
            reverse=True,
        )[:15]

        return [
            {"feature": str(name), "importance": round(float(value), 6)}
            for name, value in pairs
        ]
