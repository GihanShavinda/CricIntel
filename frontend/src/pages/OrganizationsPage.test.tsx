import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import { OrganizationsPage } from '../pages/OrganizationsPage';

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

vi.mock('../api/organizations', () => ({
  listOrganizations: vi.fn().mockResolvedValue({
    data: [],
    meta: {
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
    },
  }),
  createOrganization: vi.fn(),
  deleteOrganization: vi.fn(),
}));

describe('OrganizationsPage', () => {
  it('renders organization management heading', async () => {
    render(
      <MemoryRouter>
        <OrganizationsPage />
      </MemoryRouter>
    );

    expect(
      await screen.findByRole('heading', { name: 'Organizations' })
    ).toBeInTheDocument();
  });
});
