import { api } from './client';

import type {
  ScoutingComparisonRow,
  ScoutingMedia,
  ScoutingPage,
  ScoutingProfile,
  ScoutingReport,
} from '../types/scouting';

type DataResponse<T> = {
  data: T;
  message?: string;
};

export async function listScoutingProfiles(
  organizationId: number,
  params: Record<string, unknown> = {},
) {
  const response = await api.get<ScoutingPage>(
    `/api/v1/organizations/${organizationId}/scouting/profiles`,
    { params },
  );

  return response.data;
}

export async function createScoutingProfile(
  organizationId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.post<DataResponse<ScoutingProfile>>(
    `/api/v1/organizations/${organizationId}/scouting/profiles`,
    payload,
  );

  return response.data.data;
}

export async function getScoutingProfile(
  organizationId: number,
  profileId: number,
) {
  const response = await api.get<DataResponse<ScoutingProfile>>(
    `/api/v1/organizations/${organizationId}/scouting/profiles/${profileId}`,
  );

  return response.data.data;
}

export async function updateScoutingProfile(
  organizationId: number,
  profileId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.put<DataResponse<ScoutingProfile>>(
    `/api/v1/organizations/${organizationId}/scouting/profiles/${profileId}`,
    payload,
  );

  return response.data.data;
}

export async function deleteScoutingProfile(
  organizationId: number,
  profileId: number,
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/scouting/profiles/${profileId}`,
  );
}

export async function createScoutingReport(
  organizationId: number,
  profileId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.post<DataResponse<ScoutingReport>>(
    `/api/v1/organizations/${organizationId}/scouting/profiles/${profileId}/reports`,
    payload,
  );

  return response.data.data;
}

export async function getScoutingReport(
  organizationId: number,
  reportId: number,
) {
  const response = await api.get<DataResponse<ScoutingReport>>(
    `/api/v1/organizations/${organizationId}/scouting/reports/${reportId}`,
  );

  return response.data.data;
}

export async function updateScoutingReport(
  organizationId: number,
  reportId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.put<DataResponse<ScoutingReport>>(
    `/api/v1/organizations/${organizationId}/scouting/reports/${reportId}`,
    payload,
  );

  return response.data.data;
}

export async function deleteScoutingReport(
  organizationId: number,
  reportId: number,
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/scouting/reports/${reportId}`,
  );
}

export async function addScoutingMedia(
  organizationId: number,
  reportId: number,
  payload: FormData,
) {
  const response = await api.post<DataResponse<ScoutingMedia>>(
    `/api/v1/organizations/${organizationId}/scouting/reports/${reportId}/media`,
    payload,
    {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    },
  );

  return response.data.data;
}

export async function deleteScoutingMedia(
  organizationId: number,
  mediaId: number,
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/scouting/media/${mediaId}`,
  );
}

export async function addScoutingNote(
  organizationId: number,
  profileId: number,
  payload: {
    scouting_report_id?: number | null;
    note: string;
    is_private?: boolean;
  },
) {
  const response = await api.post(
    `/api/v1/organizations/${organizationId}/scouting/profiles/${profileId}/notes`,
    payload,
  );

  return response.data.data;
}

export async function compareScoutingProfiles(
  organizationId: number,
  profileIds: number[],
) {
  const response = await api.get<DataResponse<ScoutingComparisonRow[]>>(
    `/api/v1/organizations/${organizationId}/scouting/compare`,
    {
      params: {
        profile_ids: profileIds,
      },
    },
  );

  return response.data.data;
}

export async function convertScoutingProfile(
  organizationId: number,
  profileId: number,
  payload: Record<string, unknown>,
) {
  const response = await api.post(
    `/api/v1/organizations/${organizationId}/scouting/profiles/${profileId}/convert`,
    payload,
  );

  return response.data.data;
}
