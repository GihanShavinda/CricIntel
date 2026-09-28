import { api } from "./client";
import type {
  AnalyticsDashboard,
  AnalyticsFilterValues,
  AnalyticsOptions,
  PlayerComparisonResponse,
} from "../types/analytics";

type ApiResponse<T> = {
  data: T;
};

function cleanParams(
  filters: Record<string, unknown>,
): Record<string, unknown> {
  return Object.fromEntries(
    Object.entries(filters).filter(
      ([, value]) => value !== undefined && value !== null && value !== "",
    ),
  );
}

export async function getAnalyticsOptions(
  organizationId: number,
): Promise<AnalyticsOptions> {
  const response = await api.get<ApiResponse<AnalyticsOptions>>(
    `/api/v1/organizations/${organizationId}/analytics/options`,
  );

  return response.data.data;
}

export async function getAnalyticsDashboard(
  organizationId: number,
  filters: AnalyticsFilterValues,
): Promise<AnalyticsDashboard> {
  const response = await api.get<ApiResponse<AnalyticsDashboard>>(
    `/api/v1/organizations/${organizationId}/analytics/dashboard`,
    {
      params: cleanParams(filters),
    },
  );

  return response.data.data;
}

export async function comparePlayers(
  organizationId: number,
  playerA: number,
  playerB: number,
  filters: Omit<AnalyticsFilterValues, "team_id"> = {},
): Promise<PlayerComparisonResponse> {
  const response = await api.get<ApiResponse<PlayerComparisonResponse>>(
    `/api/v1/organizations/${organizationId}/analytics/players/compare`,
    {
      params: cleanParams({
        player_a: playerA,
        player_b: playerB,
        ...filters,
      }),
    },
  );

  return response.data.data;
}
