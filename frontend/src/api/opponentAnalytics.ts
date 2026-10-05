import { api } from './client';

import type {
  BatterAnalytics,
  BowlerAnalytics,
  MatchupAnalytics,
  OpponentAnalyticsOptions,
  PartnershipAnalytics,
  TeamOpponentProfile,
} from '../types/opponentAnalytics';

type DataResponse<T> = {
  data: T;
};

export type OpponentFilters = {
  from?: string;
  to?: string;
  tournament_id?: number;
  opponent_team_id?: number;
};

export async function getOpponentAnalyticsOptions(
  organizationId: number,
) {
  const response = await api.get<DataResponse<OpponentAnalyticsOptions>>(
    `/api/v1/organizations/${organizationId}/opponent-analytics/options`,
  );

  return response.data.data;
}

export async function getOpponentTeamProfile(
  organizationId: number,
  teamId: number,
  filters: OpponentFilters = {},
) {
  const response = await api.get<DataResponse<TeamOpponentProfile>>(
    `/api/v1/organizations/${organizationId}/opponent-analytics/teams/${teamId}`,
    { params: filters },
  );

  return response.data.data;
}

export async function getOpponentBatterAnalytics(
  organizationId: number,
  playerId: number,
  filters: OpponentFilters = {},
) {
  const response = await api.get<DataResponse<BatterAnalytics>>(
    `/api/v1/organizations/${organizationId}/opponent-analytics/batters/${playerId}`,
    { params: filters },
  );

  return response.data.data;
}

export async function getOpponentBowlerAnalytics(
  organizationId: number,
  playerId: number,
  filters: OpponentFilters = {},
) {
  const response = await api.get<DataResponse<BowlerAnalytics>>(
    `/api/v1/organizations/${organizationId}/opponent-analytics/bowlers/${playerId}`,
    { params: filters },
  );

  return response.data.data;
}

export async function getOpponentMatchup(
  organizationId: number,
  batterId: number,
  bowlerId: number,
  filters: OpponentFilters = {},
) {
  const response = await api.get<DataResponse<MatchupAnalytics>>(
    `/api/v1/organizations/${organizationId}/opponent-analytics/matchup`,
    {
      params: {
        batter_id: batterId,
        bowler_id: bowlerId,
        ...filters,
      },
    },
  );

  return response.data.data;
}

export async function getOpponentPartnerships(
  organizationId: number,
  teamId: number,
  filters: OpponentFilters = {},
) {
  const response = await api.get<DataResponse<PartnershipAnalytics[]>>(
    `/api/v1/organizations/${organizationId}/opponent-analytics/teams/${teamId}/partnerships`,
    { params: filters },
  );

  return response.data.data;
}
