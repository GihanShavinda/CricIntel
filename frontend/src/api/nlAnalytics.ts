import {
  api,
} from './client';

import type {
  AnalyticsOptions,
  ControlledAnalyticsQuery,
  NaturalLanguageAnalyticsResponse,
  NlAnalyticsHistoryRow,
} from '../types/nlAnalytics';

type DataResponse<T> = {
  data: T;
  message?: string;
};

export interface NaturalLanguageAnalyticsPayload {
  query: string;
  team_id?: number;
  season_id?: number;
  opponent_team_id?: number;
  venue_id?: number;
  format?: string;
  date_from?: string;
  date_to?: string;
}

export async function getNaturalLanguageAnalyticsOptions(
  organizationId: number,
) {
  const response =
    await api.get<DataResponse<AnalyticsOptions>>(
      `/api/v1/organizations/${organizationId}/nl-analytics/options`,
    );

  return response.data.data;
}

export async function parseNaturalLanguageAnalytics(
  organizationId: number,
  payload: NaturalLanguageAnalyticsPayload,
) {
  const response =
    await api.post<DataResponse<{
      natural_language_query: string;
      controlled_query: ControlledAnalyticsQuery;
      will_execute_arbitrary_sql: boolean;
    }>>(
      `/api/v1/organizations/${organizationId}/nl-analytics/parse`,
      payload,
    );

  return response.data.data;
}

export async function runNaturalLanguageAnalytics(
  organizationId: number,
  payload: NaturalLanguageAnalyticsPayload,
) {
  const response =
    await api.post<DataResponse<NaturalLanguageAnalyticsResponse>>(
      `/api/v1/organizations/${organizationId}/nl-analytics/query`,
      payload,
    );

  return response.data.data;
}

export async function getNaturalLanguageAnalyticsHistory(
  organizationId: number,
) {
  const response =
    await api.get<DataResponse<NlAnalyticsHistoryRow[]>>(
      `/api/v1/organizations/${organizationId}/nl-analytics/history`,
    );

  return response.data.data;
}
