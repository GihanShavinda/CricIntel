import {
  useMemo,
} from 'react';

import {
  useSearchParams,
} from 'react-router-dom';

import type {
  AnalyticsFilterValues,
} from '../types/analytics';

const numericKeys = new Set([
  'team_id',
  'season_id',
  'tournament_id',
  'opponent_id',
  'venue_id',
  'batting_position',
  'limit',
]);

export function useAnalyticsUrlFilters() {
  const [
    searchParams,
    setSearchParams,
  ] = useSearchParams();

  const filters =
    useMemo(() => {
      const result:
        AnalyticsFilterValues =
          {};

      searchParams.forEach(
        (value, key) => {
          if (
            numericKeys.has(
              key
            )
          ) {
            (
              result as Record<
                string,
                unknown
              >
            )[key] =
              Number(value);
          } else {
            (
              result as Record<
                string,
                unknown
              >
            )[key] =
              value;
          }
        }
      );

      return result;
    }, [searchParams]);

  const setFilters = (
    next:
      AnalyticsFilterValues
  ) => {
    const params =
      new URLSearchParams();

    Object.entries(
      next
    ).forEach(
      ([key, value]) => {
        if (
          value !==
            undefined &&
          value !== null &&
          value !== ''
        ) {
          params.set(
            key,
            String(value)
          );
        }
      }
    );

    setSearchParams(
      params,
      {
        replace: true,
      }
    );
  };

  return {
    filters,
    setFilters,
  };
}
