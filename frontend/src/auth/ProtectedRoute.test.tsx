import {
  render,
  screen,
} from '@testing-library/react';

import {
  MemoryRouter,
} from 'react-router-dom';

import {
  describe,
  expect,
  it,
  vi,
} from 'vitest';

import { ProtectedRoute } from './ProtectedRoute';

vi.mock('./AuthContext', () => ({
  useAuth: () => ({
    user: null,
    loading: false,
    authenticated: false,
  }),
}));

describe('ProtectedRoute', () => {
  it(
    'does not render protected content when unauthenticated',
    () => {
      render(
        <MemoryRouter>
          <ProtectedRoute>
            <p>Secret dashboard</p>
          </ProtectedRoute>
        </MemoryRouter>
      );

      expect(
        screen.queryByText(
          'Secret dashboard'
        )
      ).not.toBeInTheDocument();
    }
  );
});
