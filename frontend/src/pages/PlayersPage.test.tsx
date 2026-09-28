import { render, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';

import { PlayersPage } from './PlayersPage';

vi.mock('../auth/AuthContext', () => ({
  useAuth: () => ({
    user: {
      id: 1,
      name: 'Admin',
      email: 'admin@example.com',
      status: 'active',
      roles: ['Administrator'],
    },
    authenticated: true,
    loading: false,
    logout: vi.fn(),
    hasRole: () => true,
  }),
}));

vi.mock('../api/players', () => ({
  listPlayers: vi.fn().mockResolvedValue({
    data: [],
    meta: {
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
    },
  }),
  deletePlayer: vi.fn(),
}));

describe('PlayersPage', () => {
  it('renders player management heading', async () => {
    render(
      <MemoryRouter initialEntries={['/organizations/1/players']}>
        <Routes>
          <Route
            path="/organizations/:organizationId/players"
            element={<PlayersPage />}
          />
        </Routes>
      </MemoryRouter>
    );

    expect(
      await screen.findByRole('heading', {
        name: 'Players',
      })
    ).toBeInTheDocument();
  });
});
