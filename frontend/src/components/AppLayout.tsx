import type { ReactNode } from 'react';

import {
  Link,
  useNavigate,
} from 'react-router-dom';

import { useAuth } from '../auth/AuthContext';

export function AppLayout({
  children,
}: {
  children: ReactNode;
}) {
  const {
    user,
    logout,
    hasRole,
  } = useAuth();

  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <>
      <header>
        <strong>CricIntel AI</strong>

        <nav>
          <Link to="/dashboard">
            Dashboard
          </Link>

          {' '}

          {hasRole('Administrator') && (
            <Link to="/admin">
              Administration
            </Link>
          )}
        </nav>

        <div>
          {user?.name}
          {' — '}
          {user?.roles.join(', ')}
          {' '}
          <button onClick={handleLogout}>
            Logout
          </button>
        </div>
      </header>

      <main>{children}</main>
    </>
  );
}
