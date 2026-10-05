import type {
  ReactNode,
} from 'react';

import {
  Navigate,
} from 'react-router-dom';

import {
  useAuth,
} from './AuthContext';

import type {
  Role,
} from '../types/auth';

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
    return (
      <div className="ci-auth-loading">
        <div className="ci-auth-loading-card">
          <span className="ci-auth-loading-logo">
            CI
          </span>

          <div
            className="ci-auth-loading-spinner"
            aria-hidden="true"
          />

          <strong>
            Checking permissions
          </strong>

          <span>
            Verifying your CricIntel
            role access...
          </span>
        </div>
      </div>
    );
  }

  if (!user) {
    return (
      <Navigate
        to="/login"
        replace
      />
    );
  }

  const authorized =
    allowedRoles.some(
      (role) =>
        user.roles.includes(role),
    );

  if (!authorized) {
    return (
      <Navigate
        to="/dashboard"
        replace
      />
    );
  }

  return <>{children}</>;
}
