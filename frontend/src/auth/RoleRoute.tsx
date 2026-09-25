import type { ReactNode } from 'react';
import { Navigate } from 'react-router-dom';

import { useAuth } from './AuthContext';
import type { Role } from '../types/auth';

interface RoleRouteProps {
  allowedRoles: Role[];
  children: ReactNode;
}

export function RoleRoute({
  allowedRoles,
  children,
}: RoleRouteProps) {
  const {
    user,
    loading,
  } = useAuth();

  if (loading) {
    return <p>Loading...</p>;
  }

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  const authorized =
    allowedRoles.some((role) =>
      user.roles.includes(role)
    );

  if (!authorized) {
    return <Navigate to="/dashboard" replace />;
  }

  return <>{children}</>;
}
