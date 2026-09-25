import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';

import axios from 'axios';

import {
  api,
  initializeCsrf,
} from '../api/client';

import type {
  ApiResponse,
  Role,
  User,
} from '../types/auth';

interface RegisterPayload {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

interface LoginPayload {
  email: string;
  password: string;
  remember?: boolean;
}

interface AuthContextValue {
  user: User | null;
  loading: boolean;
  authenticated: boolean;
  login: (payload: LoginPayload) => Promise<void>;
  register: (payload: RegisterPayload) => Promise<void>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
  hasRole: (role: Role) => boolean;
  hasAnyRole: (roles: Role[]) => boolean;
}

const AuthContext =
  createContext<AuthContextValue | undefined>(
    undefined
  );

export function AuthProvider({
  children,
}: {
  children: ReactNode;
}) {
  const [user, setUser] =
    useState<User | null>(null);

  const [loading, setLoading] =
    useState(true);

  const refreshUser =
    useCallback(async () => {
      try {
        const response =
          await api.get<ApiResponse<User>>(
            '/api/v1/auth/me'
          );

        setUser(response.data.data);
      } catch (error) {
        if (
          axios.isAxiosError(error) &&
          error.response?.status === 401
        ) {
          setUser(null);
          return;
        }

        throw error;
      }
    }, []);

  useEffect(() => {
    const bootstrap = async () => {
      try {
        await refreshUser();
      } finally {
        setLoading(false);
      }
    };

    void bootstrap();
  }, [refreshUser]);

  const login = async (
    payload: LoginPayload
  ) => {
    await initializeCsrf();

    const response =
      await api.post<ApiResponse<User>>(
        '/api/v1/auth/login',
        payload
      );

    setUser(response.data.data);
  };

  const register = async (
    payload: RegisterPayload
  ) => {
    await initializeCsrf();

    const response =
      await api.post<ApiResponse<User>>(
        '/api/v1/auth/register',
        payload
      );

    setUser(response.data.data);
  };

  const logout = async () => {
    await api.post('/api/v1/auth/logout');
    setUser(null);
  };

  const hasRole = (role: Role) =>
    user?.roles.includes(role) ?? false;

  const hasAnyRole = (roles: Role[]) =>
    roles.some((role) =>
      user?.roles.includes(role)
    );

  const value = useMemo(
    () => ({
      user,
      loading,
      authenticated: user !== null,
      login,
      register,
      logout,
      refreshUser,
      hasRole,
      hasAnyRole,
    }),
    [user, loading, refreshUser]
  );

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error(
      'useAuth must be used inside AuthProvider.'
    );
  }

  return context;
}
