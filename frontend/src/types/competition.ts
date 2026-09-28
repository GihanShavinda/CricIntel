export interface Venue {
  id: number;
  organization_id: number;
  name: string;
  city: string | null;
  country: string | null;
  capacity: number | null;
  pitch_type: string | null;
  boundary_dimensions: Record<string, unknown> | null;
  notes: string | null;
  status: string;
}

export interface TournamentTeam {
  id: number;
  name: string;
  seed: number | null;
  status: string;
}

export interface Tournament {
  id: number;
  organization_id: number;
  season_id: number;
  competition_format_id: number | null;
  name: string;
  format: 'T20' | 'ODI' | 'Test' | 'T10' | 'Custom';
  start_date: string;
  end_date: string;
  status: string;
  organizer: string | null;
  rules_json: Record<string, unknown> | null;
  teams?: TournamentTeam[];
}

export interface FixtureTeam {
  id: number;
  name: string;
}

export interface Fixture {
  id: number;
  organization_id: number;
  tournament_id: number;
  home_team?: FixtureTeam;
  away_team?: FixtureTeam;
  venue?: {
    id: number;
    name: string;
  } | null;
  scheduled_at: string;
  match_number: number | null;
  round: string | null;
  status:
    | 'Scheduled'
    | 'Delayed'
    | 'In Progress'
    | 'Completed'
    | 'Abandoned'
    | 'Cancelled';
  notes: string | null;
}
