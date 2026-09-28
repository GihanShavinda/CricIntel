import {
  useQuery,
} from '@tanstack/react-query';

import {
  comparePlayers,
  getAnalyticsDashboard,
  getAnalyticsOptions,
} from '../api/analytics';

import type {
  AnalyticsFilterValues,
} from '../types/analytics';

export const analyticsKeys = {
  all: (
    organizationId: number
  ) => [
    'analytics',
    organizationId,
  ] as const,

  options: (
    organizationId: number
  ) => [
    ...analyticsKeys.all(
      organizationId
    ),
    'options',
  ] as const,

  dashboard: (
    organizationId: number,
    filters:
      AnalyticsFilterValues
  ) => [
    ...analyticsKeys.all(
      organizationId
    ),
    'dashboard',
    filters,
  ] as const,

  comparison: (
    organizationId: number,
    playerA: number,
    playerB: number,
    filters:
      AnalyticsFilterValues
  ) => [
    ...analyticsKeys.all(
      organizationId
    ),
    'comparison',
    playerA,
    playerB,
    filters,
  ] as const,
};

export function useAnalyticsOptions(
  organizationId: number
) {
  return useQuery({
    queryKey:
      analyticsKeys.options(
        organizationId
      ),
    queryFn: () =>
      getAnalyticsOptions(
        organizationId
      ),
    staleTime:
      5 * 60 * 1000,
  });
}

export function useAnalyticsDashboard(
  organizationId: number,
  filters:
    AnalyticsFilterValues
) {
  return useQuery({
    queryKey:
      analyticsKeys.dashboard(
        organizationId,
        filters
      ),
    queryFn: () =>
      getAnalyticsDashboard(
        organizationId,
        filters
      ),
    enabled:
      Boolean(
        organizationId &&
          filters.team_id
      ),
    staleTime:
      60 * 1000,
    gcTime:
      10 * 60 * 1000,
    retry: 1,
  });
}

export function usePlayerComparison(
  organizationId: number,
  playerA: number,
  playerB: number,
  filters:
    AnalyticsFilterValues
) {
  return useQuery({
    queryKey:
      analyticsKeys.comparison(
        organizationId,
        playerA,
        playerB,
        filters
      ),
    queryFn: () =>
      comparePlayers(
        organizationId,
        playerA,
        playerB,
        filters
      ),
    enabled:
      Boolean(
        organizationId &&
          playerA &&
          playerB &&
          playerA !== playerB
      ),
    staleTime:
      60 * 1000,
    gcTime:
      10 * 60 * 1000,
    retry: 1,
  });
}
