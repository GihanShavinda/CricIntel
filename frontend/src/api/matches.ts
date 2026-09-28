import { api } from './client';

import type {
  ApiResponse,
} from '../types/auth';

import type {
  MatchListItem,
  MatchScorecard,
} from '../types/match';

/*
|--------------------------------------------------------------------------
| Match List
|--------------------------------------------------------------------------
*/

export async function listMatches(
  organizationId: number
): Promise<MatchListItem[]> {
  const response =
    await api.get<
      ApiResponse<MatchListItem[]>
    >(
      `/api/v1/organizations/${organizationId}/matches`
    );

  return response.data.data;
}

/*
|--------------------------------------------------------------------------
| Create Match
|--------------------------------------------------------------------------
*/

export async function createMatch(
  organizationId: number,
  payload: Record<
    string,
    unknown
  >
): Promise<MatchScorecard> {
  const response =
    await api.post<
      ApiResponse<MatchScorecard>
    >(
      `/api/v1/organizations/${organizationId}/matches`,
      payload
    );

  return response.data.data;
}

/*
|--------------------------------------------------------------------------
| Match Scorecard
|--------------------------------------------------------------------------
*/

export async function getScorecard(
  organizationId: number,
  matchId: number
): Promise<MatchScorecard> {
  const response =
    await api.get<
      ApiResponse<MatchScorecard>
    >(
      `/api/v1/organizations/${organizationId}/matches/${matchId}/scorecard`
    );

  return response.data.data;
}

/*
|--------------------------------------------------------------------------
| Start Match
|--------------------------------------------------------------------------
*/

export async function startMatch(
  organizationId: number,
  matchId: number,
  payload: Record<
    string,
    unknown
  > = {}
): Promise<MatchScorecard> {
  const response =
    await api.post<
      ApiResponse<MatchScorecard>
    >(
      `/api/v1/organizations/${organizationId}/matches/${matchId}/start`,
      payload
    );

  return response.data.data;
}

/*
|--------------------------------------------------------------------------
| Start Innings
|--------------------------------------------------------------------------
*/

export async function startInnings(
  organizationId: number,
  matchId: number,
  payload: Record<
    string,
    unknown
  >
) {
  const response =
    await api.post<
      ApiResponse<unknown>
    >(
      `/api/v1/organizations/${organizationId}/matches/${matchId}/innings`,
      payload
    );

  return response.data.data;
}

/*
|--------------------------------------------------------------------------
| Record Delivery
|--------------------------------------------------------------------------
*/

export async function recordDelivery(
  organizationId: number,
  matchId: number,
  inningsId: number,
  payload: Record<
    string,
    unknown
  >
) {
  const response =
    await api.post<
      ApiResponse<unknown>
    >(
      `/api/v1/organizations/${organizationId}/matches/${matchId}/innings/${inningsId}/deliveries`,
      payload
    );

  return response.data.data;
}

/*
|--------------------------------------------------------------------------
| Undo Latest Delivery
|--------------------------------------------------------------------------
*/

export async function undoLatestDelivery(
  organizationId: number,
  matchId: number,
  inningsId: number
) {
  const response =
    await api.delete<
      ApiResponse<unknown>
    >(
      `/api/v1/organizations/${organizationId}/matches/${matchId}/innings/${inningsId}/deliveries/latest`
    );

  return response.data.data;
}

/*
|--------------------------------------------------------------------------
| Complete Innings
|--------------------------------------------------------------------------
*/

export async function completeInnings(
  organizationId: number,
  matchId: number,
  inningsId: number
) {
  const response =
    await api.post<
      ApiResponse<unknown>
    >(
      `/api/v1/organizations/${organizationId}/matches/${matchId}/innings/${inningsId}/complete`
    );

  return response.data.data;
}

/*
|--------------------------------------------------------------------------
| Complete Match
|--------------------------------------------------------------------------
*/

export async function completeMatch(
  organizationId: number,
  matchId: number,
  payload: Record<
    string,
    unknown
  > = {}
): Promise<MatchScorecard> {
  const response =
    await api.post<
      ApiResponse<MatchScorecard>
    >(
      `/api/v1/organizations/${organizationId}/matches/${matchId}/complete`,
      payload
    );

  return response.data.data;
}