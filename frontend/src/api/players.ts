import { api } from './client';
import type { ApiResponse } from '../types/auth';
import type { Paginated } from '../types/organization';
import type { Player } from '../types/player';

export async function listPlayers(
  organizationId: number,
  params: Record<string, unknown> = {}
) {
  const response = await api.get<ApiResponse<Paginated<Player>>>(
    `/api/v1/organizations/${organizationId}/players`,
    { params }
  );

  return response.data.data;
}

export async function getPlayer(
  organizationId: number,
  playerId: number
) {
  const response = await api.get<ApiResponse<Player>>(
    `/api/v1/organizations/${organizationId}/players/${playerId}`
  );

  return response.data.data;
}

export async function createPlayer(
  organizationId: number,
  form: FormData
) {
  const response = await api.post<ApiResponse<Player>>(
    `/api/v1/organizations/${organizationId}/players`,
    form
  );

  return response.data.data;
}

export async function updatePlayer(
  organizationId: number,
  playerId: number,
  form: FormData
) {
  form.set('_method', 'PUT');

  const response = await api.post<ApiResponse<Player>>(
    `/api/v1/organizations/${organizationId}/players/${playerId}`,
    form
  );

  return response.data.data;
}

export async function deletePlayer(
  organizationId: number,
  playerId: number
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/players/${playerId}`
  );
}

export async function syncPlayerTeams(
  organizationId: number,
  playerId: number,
  memberships: Array<{
    team_id: number;
    jersey_number: number | null;
    joined_at: string | null;
    left_at: string | null;
    is_current: boolean;
  }>
) {
  const response = await api.post<ApiResponse<Player>>(
    `/api/v1/organizations/${organizationId}/players/${playerId}/teams/sync`,
    { memberships }
  );

  return response.data.data;
}

export async function addPlayerAvailability(
  organizationId: number,
  playerId: number,
  payload: {
    available_from: string;
    available_to?: string | null;
    reason?: string | null;
    status: 'Available' | 'Unavailable' | 'Partial';
  }
) {
  const response = await api.post<ApiResponse<Player>>(
    `/api/v1/organizations/${organizationId}/players/${playerId}/availability`,
    payload
  );

  return response.data.data;
}
