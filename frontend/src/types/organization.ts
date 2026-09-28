export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface Paginated<T> {
  data: T[];
  meta: PaginationMeta;
  links?: unknown;
}

export interface Organization {
  id: number;
  name: string;
  short_name: string | null;
  logo: string | null;
  logo_url: string | null;
  country: string | null;
  timezone: string;
  description: string | null;
  status: string;
  members_count?: number;
  clubs_count?: number;
  seasons_count?: number;
}

export interface Club {
  id: number;
  organization_id: number;
  name: string;
  code: string | null;
  logo: string | null;
  logo_url: string | null;
  location: string | null;
  founded_year: number | null;
  description: string | null;
  teams_count?: number;
}

export interface Team {
  id: number;
  club_id: number;
  club?: {
    id: number;
    name: string;
    organization_id: number;
  };
  name: string;
  short_name: string | null;
  gender: string | null;
  category: string | null;
  age_group: string | null;
  format_preferences: string[];
  home_ground: string | null;
  status: string;
}

export interface Season {
  id: number;
  organization_id: number;
  name: string;
  start_date: string;
  end_date: string;
  status: string;
  teams_count?: number;
  teams?: Team[];
}
