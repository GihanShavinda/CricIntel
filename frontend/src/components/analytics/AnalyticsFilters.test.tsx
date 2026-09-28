import {
  render,
  screen,
} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import {
  AnalyticsFilters,
} from './AnalyticsFilters';

const options = {
  seasons: [
    {
      id: 1,
      name: '2026/27',
    },
  ],
  tournaments: [
    {
      id: 2,
      name: 'Premier T20',
      season_id: 1,
      format: 'T20',
    },
  ],
  teams: [
    {
      id: 10,
      name: 'Team A',
    },
    {
      id: 11,
      name: 'Team B',
    },
  ],
  venues: [
    {
      id: 4,
      name: 'National Ground',
    },
  ],
  players: [],
  formats: ['T20'],
};

describe(
  'AnalyticsFilters',
  () => {
    it(
      'updates team filter',
      async () => {
        const user =
          userEvent.setup();

        const onChange =
          vi.fn();

        render(
          <AnalyticsFilters
            options={options}
            filters={{}}
            onChange={
              onChange
            }
          />
        );

        await user.selectOptions(
          screen.getByLabelText(
            'Team'
          ),
          '10'
        );

        expect(
          onChange
        ).toHaveBeenCalledWith(
          expect.objectContaining({
            team_id: 10,
          })
        );
      }
    );

    it(
      'renders requested phase filters',
      () => {
        render(
          <AnalyticsFilters
            options={options}
            filters={{}}
            onChange={() => {}}
          />
        );

        expect(
          screen.getByRole(
            'option',
            {
              name:
                'Powerplay',
            }
          )
        ).toBeInTheDocument();

        expect(
          screen.getByRole(
            'option',
            {
              name:
                'Middle overs',
            }
          )
        ).toBeInTheDocument();

        expect(
          screen.getByRole(
            'option',
            {
              name:
                'Death overs',
            }
          )
        ).toBeInTheDocument();
      }
    );
  }
);
