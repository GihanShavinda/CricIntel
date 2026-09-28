import { api } from './client';

import type {
  ApiResponse,
} from '../types/auth';

import type {
  Club,
  Organization,
  Paginated,
  Season,
  Team,
} from '../types/organization';

/*
|--------------------------------------------------------------------------
| Organizations
|--------------------------------------------------------------------------
*/

export async function listOrganizations(
  params: Record<string, unknown> = {}
) {
  const response =
    await api.get<
      ApiResponse<
        Paginated<Organization>
      >
    >(
      '/api/v1/organizations',
      { params }
    );

  return response.data.data;
}

export async function getOrganization(
  id: number
) {
  const response =
    await api.get<
      ApiResponse<Organization>
    >(
      `/api/v1/organizations/${id}`
    );

  return response.data.data;
}

export async function createOrganization(
  form: FormData
) {
  const response =
    await api.post<
      ApiResponse<Organization>
    >(
      '/api/v1/organizations',
      form
    );

  return response.data.data;
}

export async function deleteOrganization(
  id: number
) {
  await api.delete(
    `/api/v1/organizations/${id}`
  );
}

/*
|--------------------------------------------------------------------------
| Clubs
|--------------------------------------------------------------------------
*/

export async function listClubs(
  organizationId: number,
  params: Record<string, unknown> = {}
) {
  const response =
    await api.get<
      ApiResponse<
        Paginated<Club>
      >
    >(
      `/api/v1/organizations/${organizationId}/clubs`,
      { params }
    );

  return response.data.data;
}

export async function createClub(
  organizationId: number,
  form: FormData
) {
  const response =
    await api.post<
      ApiResponse<Club>
    >(
      `/api/v1/organizations/${organizationId}/clubs`,
      form
    );

  return response.data.data;
}

export async function deleteClub(
  organizationId: number,
  clubId: number
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/clubs/${clubId}`
  );
}

/*
|--------------------------------------------------------------------------
| Teams
|--------------------------------------------------------------------------
*/

export async function listTeams(
  organizationId: number,
  params: Record<string, unknown> = {}
) {
  const response =
    await api.get<
      ApiResponse<
        Paginated<Team>
      >
    >(
      `/api/v1/organizations/${organizationId}/teams`,
      { params }
    );

  return response.data.data;
}

export async function createTeam(
  organizationId: number,
  payload: Record<string, unknown>
) {
  const response =
    await api.post<
      ApiResponse<Team>
    >(
      `/api/v1/organizations/${organizationId}/teams`,
      payload
    );

  return response.data.data;
}

export async function deleteTeam(
  organizationId: number,
  teamId: number
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/teams/${teamId}`
  );
}

/*
|--------------------------------------------------------------------------
| Seasons
|--------------------------------------------------------------------------
*/

export async function listSeasons(
  organizationId: number,
  params: Record<string, unknown> = {}
) {
  const response =
    await api.get<
      ApiResponse<
        Paginated<Season>
      >
    >(
      `/api/v1/organizations/${organizationId}/seasons`,
      { params }
    );

  return response.data.data;
}

export async function createSeason(
  organizationId: number,
  payload: Record<string, unknown>
) {
  const response =
    await api.post<
      ApiResponse<Season>
    >(
      `/api/v1/organizations/${organizationId}/seasons`,
      payload
    );

  return response.data.data;
}

export async function deleteSeason(
  organizationId: number,
  seasonId: number
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/seasons/${seasonId}`
  );
}

/*
|--------------------------------------------------------------------------
| Season Team Assignment
|--------------------------------------------------------------------------
*/

export async function syncSeasonTeams(
  organizationId: number,
  seasonId: number,
  teamIds: number[]
) {
  const response =
    await api.post<
      ApiResponse<Season>
    >(
      `/api/v1/organizations/${organizationId}/seasons/${seasonId}/teams/sync`,
      {
        team_ids: teamIds,
      }
    );

  return response.data.data;
}