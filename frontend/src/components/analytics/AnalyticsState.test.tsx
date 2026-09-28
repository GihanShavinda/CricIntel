import {
  render,
  screen,
} from '@testing-library/react';
import {
  describe,
  expect,
  it,
} from 'vitest';

import {
  AnalyticsEmpty,
  AnalyticsError,
  AnalyticsLoading,
} from './AnalyticsState';

describe(
  'AnalyticsState',
  () => {
    it(
      'renders loading state',
      () => {
        render(
          <AnalyticsLoading
            title="Loading analytics"
          />
        );

        expect(
          screen.getByText(
            'Loading analytics'
          )
        ).toBeInTheDocument();
      }
    );

    it(
      'renders empty state',
      () => {
        render(
          <AnalyticsEmpty
            title="No data"
          />
        );

        expect(
          screen.getByText(
            'No data'
          )
        ).toBeInTheDocument();
      }
    );

    it(
      'renders error state',
      () => {
        render(
          <AnalyticsError
            title="Failed"
          />
        );

        expect(
          screen.getByRole(
            'alert'
          )
        ).toBeInTheDocument();
      }
    );
  }
);
