import {
  api,
} from './client';

import type {
  StrategyAssignment,
  StrategyAttachment,
  StrategyComment,
  StrategyOptions,
  StrategyPlan,
  StrategySection,
  StrategyVersion,
  TacticalNote,
} from '../types/strategy';

type DataResponse<T> = {
  data: T;
  message?: string;
};

export async function getStrategyOptions(
  organizationId: number,
) {
  const response =
    await api.get<DataResponse<StrategyOptions>>(
      `/api/v1/organizations/${organizationId}/strategy/options`,
    );

  return response.data.data;
}

export async function listStrategyPlans(
  organizationId: number,
  params: Record<string, unknown> = {},
) {
  const response =
    await api.get<DataResponse<StrategyPlan[]>>(
      `/api/v1/organizations/${organizationId}/strategy/plans`,
      { params },
    );

  return response.data.data;
}

export async function createStrategyPlan(
  organizationId: number,
  payload: Record<string, unknown>,
) {
  const response =
    await api.post<DataResponse<StrategyPlan>>(
      `/api/v1/organizations/${organizationId}/strategy/plans`,
      payload,
    );

  return response.data.data;
}

export async function getStrategyPlan(
  organizationId: number,
  planId: number,
) {
  const response =
    await api.get<DataResponse<StrategyPlan>>(
      `/api/v1/organizations/${organizationId}/strategy/plans/${planId}`,
    );

  return response.data.data;
}

export async function updateStrategyPlan(
  organizationId: number,
  planId: number,
  payload: Record<string, unknown>,
) {
  const response =
    await api.put<DataResponse<StrategyPlan>>(
      `/api/v1/organizations/${organizationId}/strategy/plans/${planId}`,
      payload,
    );

  return response.data.data;
}

export async function deleteStrategyPlan(
  organizationId: number,
  planId: number,
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/strategy/plans/${planId}`,
  );
}

export async function updateStrategySection(
  organizationId: number,
  planId: number,
  sectionId: number,
  payload: {
    content?: string | null;
    structured_data?: Record<string, unknown> | null;
  },
) {
  const response =
    await api.put<DataResponse<StrategySection>>(
      `/api/v1/organizations/${organizationId}/strategy/plans/${planId}/sections/${sectionId}`,
      payload,
    );

  return response.data.data;
}

export async function getStrategyVersions(
  organizationId: number,
  planId: number,
) {
  const response =
    await api.get<DataResponse<StrategyVersion[]>>(
      `/api/v1/organizations/${organizationId}/strategy/plans/${planId}/versions`,
    );

  return response.data.data;
}

export async function setStrategyLock(
  organizationId: number,
  planId: number,
  locked: boolean,
) {
  const response =
    await api.post<DataResponse<StrategyPlan>>(
      `/api/v1/organizations/${organizationId}/strategy/plans/${planId}/${locked ? 'lock' : 'unlock'}`,
    );

  return response.data.data;
}

export async function createTacticalNote(
  organizationId: number,
  planId: number,
  payload: Record<string, unknown>,
) {
  const response =
    await api.post<DataResponse<TacticalNote>>(
      `/api/v1/organizations/${organizationId}/strategy/plans/${planId}/notes`,
      payload,
    );

  return response.data.data;
}

export async function updateTacticalNote(
  organizationId: number,
  planId: number,
  noteId: number,
  payload: Record<string, unknown>,
) {
  const response =
    await api.put<DataResponse<TacticalNote>>(
      `/api/v1/organizations/${organizationId}/strategy/plans/${planId}/notes/${noteId}`,
      payload,
    );

  return response.data.data;
}

export async function resolveTacticalNote(
  organizationId: number,
  planId: number,
  noteId: number,
) {
  const response =
    await api.post<DataResponse<TacticalNote>>(
      `/api/v1/organizations/${organizationId}/strategy/plans/${planId}/notes/${noteId}/resolve`,
    );

  return response.data.data;
}

export async function deleteTacticalNote(
  organizationId: number,
  planId: number,
  noteId: number,
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/strategy/plans/${planId}/notes/${noteId}`,
  );
}

export async function createStrategyComment(
  organizationId: number,
  planId: number,
  noteId: number,
  payload: Record<string, unknown>,
) {
  const response =
    await api.post<DataResponse<StrategyComment>>(
      `/api/v1/organizations/${organizationId}/strategy/plans/${planId}/notes/${noteId}/comments`,
      payload,
    );

  return response.data.data;
}

export async function createStrategyAttachment(
  organizationId: number,
  planId: number,
  payload: FormData,
) {
  const response =
    await api.post<DataResponse<StrategyAttachment>>(
      `/api/v1/organizations/${organizationId}/strategy/plans/${planId}/attachments`,
      payload,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      },
    );

  return response.data.data;
}

export async function deleteStrategyAttachment(
  organizationId: number,
  planId: number,
  attachmentId: number,
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/strategy/plans/${planId}/attachments/${attachmentId}`,
  );
}

export async function createStrategyAssignment(
  organizationId: number,
  planId: number,
  payload: Record<string, unknown>,
) {
  const response =
    await api.post<DataResponse<StrategyAssignment>>(
      `/api/v1/organizations/${organizationId}/strategy/plans/${planId}/assignments`,
      payload,
    );

  return response.data.data;
}

export async function updateStrategyAssignment(
  organizationId: number,
  planId: number,
  assignmentId: number,
  payload: Record<string, unknown>,
) {
  const response =
    await api.put<DataResponse<StrategyAssignment>>(
      `/api/v1/organizations/${organizationId}/strategy/plans/${planId}/assignments/${assignmentId}`,
      payload,
    );

  return response.data.data;
}
