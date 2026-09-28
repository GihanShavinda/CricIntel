import { render, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import { FixturesPage } from './FixturesPage';

vi.mock('../auth/AuthContext', () => ({
  useAuth: () => ({
    user: { id: 1, name: 'Admin', email: 'admin@example.com', status: 'active', roles: ['Administrator'] },
    authenticated: true,
    loading: false,
    logout: vi.fn(),
    hasRole: () => true,
  }),
}));

vi.mock('../api/competitions', () => ({
  listFixtures: vi.fn().mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, per_page: 100, total: 0 } }),
  listTournaments: vi.fn().mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, per_page: 100, total: 0 } }),
  listVenues: vi.fn().mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, per_page: 100, total: 0 } }),
  createFixture: vi.fn(),
  deleteFixture: vi.fn(),
}));

vi.mock('../api/organizations', () => ({
  listTeams: vi.fn().mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, per_page: 100, total: 0 } }),
}));

describe('FixturesPage', () => {
  it('renders fixture calendar heading', async () => {
    render(
      <MemoryRouter initialEntries={['/organizations/1/fixtures']}>
        <Routes>
          <Route path="/organizations/:organizationId/fixtures" element={<FixturesPage />} />
        </Routes>
      </MemoryRouter>
    );

    expect(await screen.findByRole('heading', { name: 'Fixture Calendar' })).toBeInTheDocument();
  });
});
