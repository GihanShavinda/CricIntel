import { api } from './client';

import type {
  ModelTrainingResult,
  PlayerFormTrend,
  PredictionResult,
  PredictiveModelKind,
  PredictiveOptions,
  PredictiveReadiness,
} from '../types/predictive';

type DataResponse<T> = {
  data: T;
  message?: string;
};

export async function getPredictiveOptions(
  organizationId: number,
) {
  const response = await api.get<DataResponse<PredictiveOptions>>(
    `/api/v1/organizations/${organizationId}/predictive/options`,
  );

  return response.data.data;
}

export async function getPredictiveReadiness(
  organizationId: number,
) {
  const response = await api.get<DataResponse<PredictiveReadiness>>(
    `/api/v1/organizations/${organizationId}/predictive/readiness`,
  );

  return response.data.data;
}

export async function trainPredictiveModels(
  organizationId: number,
  modelKinds: PredictiveModelKind[],
  force = false,
) {
  const response = await api.post<DataResponse<{
    organization_id: number;
    results: ModelTrainingResult[];
  }>>(
    `/api/v1/organizations/${organizationId}/predictive/train`,
    {
      model_kinds: modelKinds,
      force,
    },
  );

  return response.data.data;
}

export async function predictBatterScore(
  organizationId: number,
  playerId: number,
  context: Record<string, unknown>,
) {
  const response = await api.post<DataResponse<PredictionResult>>(
    `/api/v1/organizations/${organizationId}/predictive/batters/${playerId}/score`,
    context,
  );

  return response.data.data;
}

export async function predictBowlerEconomy(
  organizationId: number,
  playerId: number,
  context: Record<string, unknown>,
) {
  const response = await api.post<DataResponse<PredictionResult>>(
    `/api/v1/organizations/${organizationId}/predictive/bowlers/${playerId}/economy`,
    context,
  );

  return response.data.data;
}

export async function predictTeamTotal(
  organizationId: number,
  teamId: number,
  context: Record<string, unknown>,
) {
  const response = await api.post<DataResponse<PredictionResult>>(
    `/api/v1/organizations/${organizationId}/predictive/teams/${teamId}/total`,
    context,
  );

  return response.data.data;
}

export async function getPlayerFormTrend(
  organizationId: number,
  playerId: number,
) {
  const response = await api.get<DataResponse<PlayerFormTrend>>(
    `/api/v1/organizations/${organizationId}/predictive/players/${playerId}/form`,
  );

  return response.data.data;
}
