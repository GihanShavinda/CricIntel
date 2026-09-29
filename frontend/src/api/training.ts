import { api } from './client';

import type {
  AttendanceStatus,
  DevelopmentPlan,
  FitnessTest,
  PlayerAssessment,
  TrainingDrill,
  TrainingObjective,
  TrainingOptions,
  TrainingSession,
} from '../types/training';

type DataResponse<T> = {
  data: T;
  message?: string;
};

export async function getTrainingOptions(
  organizationId: number,
) {
  const response = await api.get<DataResponse<TrainingOptions>>(
    `/api/v1/organizations/${organizationId}/training/options`,
  );
  return response.data.data;
}

export async function listTrainingSessions(
  organizationId: number,
  params: {
    month?: string;
    team_id?: number;
  } = {},
) {
  const response = await api.get<DataResponse<TrainingSession[]>>(
    `/api/v1/organizations/${organizationId}/training/sessions`,
    { params },
  );
  return response.data.data;
}

export async function createTrainingSession(
  organizationId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.post<DataResponse<TrainingSession>>(
    `/api/v1/organizations/${organizationId}/training/sessions`,
    payload,
  );
  return response.data.data;
}

export async function getTrainingSession(
  organizationId: number,
  sessionId: number,
) {
  const response = await api.get<DataResponse<TrainingSession>>(
    `/api/v1/organizations/${organizationId}/training/sessions/${sessionId}`,
  );
  return response.data.data;
}

export async function updateTrainingSession(
  organizationId: number,
  sessionId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.put<DataResponse<TrainingSession>>(
    `/api/v1/organizations/${organizationId}/training/sessions/${sessionId}`,
    payload,
  );
  return response.data.data;
}

export async function deleteTrainingSession(
  organizationId: number,
  sessionId: number,
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/training/sessions/${sessionId}`,
  );
}

export async function listTrainingDrills(
  organizationId: number,
) {
  const response = await api.get<DataResponse<TrainingDrill[]>>(
    `/api/v1/organizations/${organizationId}/training/drills`,
  );
  return response.data.data;
}

export async function createTrainingDrill(
  organizationId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.post<DataResponse<TrainingDrill>>(
    `/api/v1/organizations/${organizationId}/training/drills`,
    payload,
  );
  return response.data.data;
}

export async function updateTrainingDrill(
  organizationId: number,
  drillId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.put<DataResponse<TrainingDrill>>(
    `/api/v1/organizations/${organizationId}/training/drills/${drillId}`,
    payload,
  );
  return response.data.data;
}

export async function deleteTrainingDrill(
  organizationId: number,
  drillId: number,
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/training/drills/${drillId}`,
  );
}

export async function syncTrainingSessionPlayers(
  organizationId: number,
  sessionId: number,
  playerIds: number[],
) {
  const response = await api.put<DataResponse<TrainingSession>>(
    `/api/v1/organizations/${organizationId}/training/sessions/${sessionId}/players`,
    { player_ids: playerIds },
  );
  return response.data.data;
}

export async function saveAttendance(
  organizationId: number,
  sessionId: number,
  entries: Array<{
    player_id: number;
    status: AttendanceStatus;
    arrival_time?: string | null;
    notes?: string | null;
  }>,
) {
  const response = await api.put<DataResponse<TrainingSession>>(
    `/api/v1/organizations/${organizationId}/training/sessions/${sessionId}/attendance`,
    { entries },
  );
  return response.data.data;
}

export async function listFitnessTests(
  organizationId: number,
  playerId: number,
  testType?: string,
) {
  const response = await api.get<DataResponse<FitnessTest[]>>(
    `/api/v1/organizations/${organizationId}/training/players/${playerId}/fitness-tests`,
    {
      params: testType
        ? { test_type: testType }
        : undefined,
    },
  );
  return response.data.data;
}

export async function createFitnessTest(
  organizationId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.post<DataResponse<FitnessTest>>(
    `/api/v1/organizations/${organizationId}/training/fitness-tests`,
    payload,
  );
  return response.data.data;
}

export async function updateFitnessTest(
  organizationId: number,
  fitnessTestId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.put<DataResponse<FitnessTest>>(
    `/api/v1/organizations/${organizationId}/training/fitness-tests/${fitnessTestId}`,
    payload,
  );
  return response.data.data;
}

export async function deleteFitnessTest(
  organizationId: number,
  fitnessTestId: number,
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/training/fitness-tests/${fitnessTestId}`,
  );
}

export async function listPlayerAssessments(
  organizationId: number,
  playerId: number,
) {
  const response = await api.get<DataResponse<PlayerAssessment[]>>(
    `/api/v1/organizations/${organizationId}/training/players/${playerId}/assessments`,
  );
  return response.data.data;
}

export async function createPlayerAssessment(
  organizationId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.post<DataResponse<PlayerAssessment>>(
    `/api/v1/organizations/${organizationId}/training/assessments`,
    payload,
  );
  return response.data.data;
}

export async function updatePlayerAssessment(
  organizationId: number,
  assessmentId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.put<DataResponse<PlayerAssessment>>(
    `/api/v1/organizations/${organizationId}/training/assessments/${assessmentId}`,
    payload,
  );
  return response.data.data;
}

export async function deletePlayerAssessment(
  organizationId: number,
  assessmentId: number,
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/training/assessments/${assessmentId}`,
  );
}

export async function listTrainingObjectives(
  organizationId: number,
  playerId: number,
) {
  const response = await api.get<DataResponse<TrainingObjective[]>>(
    `/api/v1/organizations/${organizationId}/training/players/${playerId}/objectives`,
  );
  return response.data.data;
}

export async function createTrainingObjective(
  organizationId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.post<DataResponse<TrainingObjective>>(
    `/api/v1/organizations/${organizationId}/training/objectives`,
    payload,
  );
  return response.data.data;
}

export async function updateTrainingObjective(
  organizationId: number,
  objectiveId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.put<DataResponse<TrainingObjective>>(
    `/api/v1/organizations/${organizationId}/training/objectives/${objectiveId}`,
    payload,
  );
  return response.data.data;
}

export async function deleteTrainingObjective(
  organizationId: number,
  objectiveId: number,
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/training/objectives/${objectiveId}`,
  );
}

export async function listDevelopmentPlans(
  organizationId: number,
  playerId: number,
) {
  const response = await api.get<DataResponse<DevelopmentPlan[]>>(
    `/api/v1/organizations/${organizationId}/training/players/${playerId}/development-plans`,
  );
  return response.data.data;
}

export async function createDevelopmentPlan(
  organizationId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.post<DataResponse<DevelopmentPlan>>(
    `/api/v1/organizations/${organizationId}/training/development-plans`,
    payload,
  );
  return response.data.data;
}

export async function updateDevelopmentPlan(
  organizationId: number,
  planId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.put<DataResponse<DevelopmentPlan>>(
    `/api/v1/organizations/${organizationId}/training/development-plans/${planId}`,
    payload,
  );
  return response.data.data;
}

export async function deleteDevelopmentPlan(
  organizationId: number,
  planId: number,
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/training/development-plans/${planId}`,
  );
}
