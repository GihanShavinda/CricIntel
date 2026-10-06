from datetime import date
from typing import Literal

from pydantic import BaseModel, Field


ModelKind = Literal[
    "batter_score",
    "bowler_economy",
    "team_total",
]


class AnalysisPeriod(BaseModel):
    from_date: date | None = None
    to_date: date | None = None


class TrainRequest(BaseModel):
    organization_id: int
    model_kinds: list[ModelKind] = [
        "batter_score",
        "bowler_economy",
        "team_total",
    ]
    force: bool = False


class PredictionContext(BaseModel):
    organization_id: int
    opponent_team_id: int | None = None
    venue_id: int | None = None
    max_overs: int | None = Field(default=20, ge=1, le=450)


class BatterPredictionRequest(PredictionContext):
    player_id: int


class BowlerPredictionRequest(PredictionContext):
    player_id: int


class TeamTotalPredictionRequest(PredictionContext):
    team_id: int


class PredictionInterval(BaseModel):
    lower: float
    prediction: float
    upper: float
    level: float = 0.8


class PredictionResponse(BaseModel):
    model_kind: ModelKind
    model_version: str
    subject_id: int
    prediction: float
    interval: PredictionInterval
    confidence_label: Literal["low", "moderate", "higher"]
    sample_size: int
    feature_values: dict[str, float | int | None]
    explainability: list[dict[str, float | str]]
    limitations: list[str]
    disclaimer: str = (
        "Predictive coaching support only. This is not a guaranteed outcome "
        "and is not intended for gambling or wagering decisions."
    )


class StrategyAssistantRequest(BaseModel):
    question: str = Field(min_length=3, max_length=1200)
    context: dict
    deterministic_recommendations: list[dict] = []
    rules: list[str] = []


class StrategyAssistantEvidenceItem(BaseModel):
    id: str
    metric: str
    value: object | None = None
    unit: str | None = None
    sample_size: int | float | None = None
    entity: dict = {}
    source: dict = {}


class StrategyAssistantClaim(BaseModel):
    statement: str
    evidence_ids: list[str]
    confidence: Literal["high", "moderate", "low"] = "low"


class StrategyAssistantStructuredResponse(BaseModel):
    claims: list[StrategyAssistantClaim] = []
    recommendations: list[StrategyAssistantClaim] = []
    limitations: list[str] = []
