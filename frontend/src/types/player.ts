export interface PlayerPosition {
  id: number;
  position: string;
  priority: number;
}

export interface PlayerTeam {
  id: number;
  name: string;
  short_name: string | null;
  jersey_number: number | null;
  joined_at: string | null;
  left_at: string | null;
  is_current: boolean;
}

export interface PlayerAvailability {
  id: number;
  available_from: string;
  available_to: string | null;
  reason: string | null;
  status: 'Available' | 'Unavailable' | 'Partial';
}

export interface Player {
  id: number;
  organization_id: number;
  user_id: number | null;
  first_name: string;
  last_name: string;
  display_name: string;
  date_of_birth: string | null;
  nationality: string | null;
  photo: string | null;
  photo_url: string | null;
  primary_role:
    | 'Batter'
    | 'Bowler'
    | 'All-rounder'
    | 'Wicketkeeper'
    | 'Wicketkeeper-Batter';
  batting_style: 'Right-handed' | 'Left-handed' | null;
  bowling_style: string | null;
  fitness_status: 'Fit' | 'Under Observation' | 'Rehabilitation' | 'Unfit';
  status: 'Active' | 'Unavailable' | 'Injured' | 'Suspended' | 'Retired';
  notes: string | null;
  positions?: PlayerPosition[];
  teams?: PlayerTeam[];
  availability?: PlayerAvailability[];
}
