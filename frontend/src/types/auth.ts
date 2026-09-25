export type Role =
  | 'Administrator'
  | 'Coach'
  | 'Analyst'
  | 'Selector'
  | 'Team Manager'
  | 'Player';

export interface User {
  id: number;
  name: string;
  email: string;
  status: string;
  roles: Role[];
  last_login_at: string | null;
  created_at: string | null;
}

export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
}

export interface ApiError {
  success: false;
  message: string;
  errors?: Record<string, string[]>;
}
