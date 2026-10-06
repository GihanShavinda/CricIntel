from fastapi import FastAPI, HTTPException, Query

from .config import settings
from .assistant import StrategyAssistantService
from .dataset_builder import TrainingDatasetBuilder
from .model_registry import ModelRegistry
from .prediction import PredictionService
from .schemas import (
    BatterPredictionRequest,
    BowlerPredictionRequest,
    TeamTotalPredictionRequest,
    TrainRequest,
    StrategyAssistantRequest,
)
from .training import ModelTrainer


app = FastAPI(
    title=settings.app_name,
    version="15.0.0",
    description=(
        "Local CricIntel intelligence service for P14 predictive analytics and "
        "P15 grounded strategy assistance."
    ),
)

builder = TrainingDatasetBuilder()
registry = ModelRegistry()
trainer = ModelTrainer(builder=builder, registry=registry)
predictor = PredictionService(builder=builder, registry=registry)
assistant = StrategyAssistantService()


@app.get("/health")
def health() -> dict:
    return {
        "status": "ok",
        "service": settings.app_name,
        "version": "15.0.0",
        "llm_enabled": settings.strategy_assistant_enabled,
        "strategy_assistant_enabled": settings.strategy_assistant_enabled,
        "llm_provider": settings.llm_provider,
        "gambling_use": False,
    }


@app.get("/v1/readiness/{organization_id}")
def readiness(organization_id: int) -> dict:
    try:
        return builder.readiness(organization_id)
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc)) from exc


@app.post("/v1/train")
def train(request: TrainRequest) -> dict:
    try:
        results = trainer.train_many(
            request.organization_id,
            request.model_kinds,
            force=request.force,
        )
        return {
            "organization_id": request.organization_id,
            "results": [
                {
                    "model_kind": result.model_kind,
                    "status": result.status,
                    "metadata": result.metadata,
                }
                for result in results
            ],
        }
    except Exception as exc:
        raise HTTPException(status_code=500, detail=str(exc)) from exc


@app.get("/v1/models/{organization_id}/{model_kind}")
def versions(organization_id: int, model_kind: str) -> dict:
    return {
        "organization_id": organization_id,
        "model_kind": model_kind,
        "versions": registry.list_versions(organization_id, model_kind),
    }


@app.post("/v1/predict/batter-score")
def predict_batter(request: BatterPredictionRequest) -> dict:
    try:
        return predictor.batter_score(
            request.organization_id,
            request.player_id,
            request.opponent_team_id,
            request.venue_id,
            request.max_overs or 20,
        )
    except FileNotFoundError as exc:
        raise HTTPException(status_code=409, detail=str(exc)) from exc
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc


@app.post("/v1/predict/bowler-economy")
def predict_bowler(request: BowlerPredictionRequest) -> dict:
    try:
        return predictor.bowler_economy(
            request.organization_id,
            request.player_id,
            request.opponent_team_id,
            request.venue_id,
            request.max_overs or 20,
        )
    except FileNotFoundError as exc:
        raise HTTPException(status_code=409, detail=str(exc)) from exc
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc


@app.post("/v1/predict/team-total")
def predict_team_total(request: TeamTotalPredictionRequest) -> dict:
    try:
        return predictor.team_total(
            request.organization_id,
            request.team_id,
            request.opponent_team_id,
            request.venue_id,
            request.max_overs or 20,
        )
    except FileNotFoundError as exc:
        raise HTTPException(status_code=409, detail=str(exc)) from exc
    except ValueError as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc


@app.get("/v1/form/{organization_id}/players/{player_id}")
def player_form(organization_id: int, player_id: int) -> dict:
    try:
        return predictor.player_form_trend(organization_id, player_id)
    except Exception as exc:
        raise HTTPException(status_code=422, detail=str(exc)) from exc


@app.post("/v1/strategy-assistant/ask")
async def strategy_assistant_ask(
    request: StrategyAssistantRequest,
) -> dict:
    try:
        return await assistant.ask(
            question=request.question,
            context=request.context,
            deterministic_recommendations=request.deterministic_recommendations,
            rules=request.rules,
        )
    except RuntimeError as exc:
        raise HTTPException(
            status_code=503
            if "disabled" in str(exc).lower()
            or "kill switch" in str(exc).lower()
            else 502,
            detail=str(exc),
        ) from exc
    except Exception as exc:
        raise HTTPException(
            status_code=500,
            detail=str(exc),
        ) from exc
