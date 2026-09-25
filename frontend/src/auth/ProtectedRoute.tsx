import type { ReactNode } from 'react';
import { Navigate } from 'react-router-dom';

import { useAuth } from './AuthContext';

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
    return <p>Loading CricIntel...</p>;
  }

  if (!authenticated) {
    return <Navigate to="/login" replace />;
  }

  return <>{children}</>;
}
