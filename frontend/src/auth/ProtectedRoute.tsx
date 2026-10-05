import type {
  ReactNode,
} from 'react';

import {
  Navigate,
} from 'react-router-dom';

import {
  useAuth,
} from './AuthContext';

export function ProtectedRoute({
  children,
}: {
  children: ReactNode;
}) {
  const {
    authenticated,
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
            Loading CricIntel
          </strong>

          <span>
            Restoring your secure
            workspace session...
          </span>
        </div>
      </div>
    );
  }

  if (!authenticated) {
    return (
      <Navigate
        to="/login"
        replace
      />
    );
  }

  return <>{children}</>;
}
