import {
  api,
} from './client';

import type {
  ApiResponse,
} from '../types/auth';

import type {
  Paginated,
} from '../types/organization';

import type {
  Fixture,
  Tournament,
  Venue,
} from '../types/competition';

export async function listVenues(
  organizationId: number,
  params: Record<
    string,
    unknown
  > = {}
) {
  const response =
    await api.get<
      ApiResponse<
        Paginated<Venue>
      >
    >(
      `/api/v1/organizations/${organizationId}/venues`,
      {
        params,
      }
    );

  return response.data.data;
}

export async function getVenue(
  organizationId: number,
  venueId: number
) {
  const response =
    await api.get<
      ApiResponse<Venue>
    >(
      `/api/v1/organizations/${organizationId}/venues/${venueId}`
    );

  return response.data.data;
}

export async function createVenue(
  organizationId: number,
  payload: Record<
    string,
    unknown
  >
) {
  const response =
    await api.post<
      ApiResponse<Venue>
    >(
      `/api/v1/organizations/${organizationId}/venues`,
      payload
    );

  return response.data.data;
}

export async function updateVenue(
  organizationId: number,
  venueId: number,
  payload: Record<
    string,
    unknown
  >
) {
  const response =
    await api.put<
      ApiResponse<Venue>
    >(
      `/api/v1/organizations/${organizationId}/venues/${venueId}`,
      payload
    );

  return response.data.data;
}

export async function deleteVenue(
  organizationId: number,
  venueId: number
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/venues/${venueId}`
  );
}

export async function listTournaments(
  organizationId: number,
  params: Record<
    string,
    unknown
  > = {}
) {
  const response =
    await api.get<
      ApiResponse<
        Paginated<Tournament>
      >
    >(
      `/api/v1/organizations/${organizationId}/tournaments`,
      {
        params,
      }
    );

  return response.data.data;
}

export async function getTournament(
  organizationId: number,
  tournamentId: number
) {
  const response =
    await api.get<
      ApiResponse<Tournament>
    >(
      `/api/v1/organizations/${organizationId}/tournaments/${tournamentId}`
    );

  return response.data.data;
}

export async function createTournament(
  organizationId: number,
  payload: Record<
    string,
    unknown
  >
) {
  const response =
    await api.post<
      ApiResponse<Tournament>
    >(
      `/api/v1/organizations/${organizationId}/tournaments`,
      payload
    );

  return response.data.data;
}

export async function updateTournament(
  organizationId: number,
  tournamentId: number,
  payload: Record<
    string,
    unknown
  >
) {
  const response =
    await api.put<
      ApiResponse<Tournament>
    >(
      `/api/v1/organizations/${organizationId}/tournaments/${tournamentId}`,
      payload
    );

  return response.data.data;
}

export async function deleteTournament(
  organizationId: number,
  tournamentId: number
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/tournaments/${tournamentId}`
  );
}

export async function syncTournamentTeams(
  organizationId: number,
  tournamentId: number,
  teams: Array<{
    team_id: number;
    seed: number | null;
    status:
      | 'registered'
      | 'withdrawn'
      | 'eliminated';
  }>
) {
  const response =
    await api.post<
      ApiResponse<Tournament>
    >(
      `/api/v1/organizations/${organizationId}/tournaments/${tournamentId}/teams/sync`,
      {
        teams,
      }
    );

  return response.data.data;
}

export async function listFixtures(
  organizationId: number,
  params: Record<
    string,
    unknown
  > = {}
) {
  const response =
    await api.get<
      ApiResponse<
        Paginated<Fixture>
      >
    >(
      `/api/v1/organizations/${organizationId}/fixtures`,
      {
        params,
      }
    );

  return response.data.data;
}

export async function getFixture(
  organizationId: number,
  fixtureId: number
) {
  const response =
    await api.get<
      ApiResponse<Fixture>
    >(
      `/api/v1/organizations/${organizationId}/fixtures/${fixtureId}`
    );

  return response.data.data;
}

export async function createFixture(
  organizationId: number,
  payload: Record<
    string,
    unknown
  >
) {
  const response =
    await api.post<
      ApiResponse<Fixture>
    >(
      `/api/v1/organizations/${organizationId}/fixtures`,
      payload
    );

  return response.data.data;
}

export async function updateFixture(
  organizationId: number,
  fixtureId: number,
  payload: Record<
    string,
    unknown
  >
) {
  const response =
    await api.put<
      ApiResponse<Fixture>
    >(
      `/api/v1/organizations/${organizationId}/fixtures/${fixtureId}`,
      payload
    );

  return response.data.data;
}

export async function deleteFixture(
  organizationId: number,
  fixtureId: number
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/fixtures/${fixtureId}`
  );
}