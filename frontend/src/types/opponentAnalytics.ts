export interface SampleSize {
  balls: number;
  level: 'none' | 'very_small' | 'small' | 'adequate';
  message: string;
}

export interface PhaseBatting {
  runs: number;
  balls: number;
  strike_rate: number | null;
  dot_ball_percentage: number | null;
  boundaries: number;
  sample_size: SampleSize;
}

export interface PhaseBowling {
  balls: number;
  runs_conceded: number;
  wickets: number;
  economy: number | null;
  wicket_rate_per_100_balls: number | null;
  sample_size: SampleSize;
}

export interface BatterAnalytics {
  player: {
    id: number;
    name?: string | null;
    batting_style?: string | null;
    primary_role?: string | null;
  };
  summary: {
    runs: number;
    balls: number;
    strike_rate: number | null;
    dot_balls: number;
    dot_ball_percentage: number | null;
    boundaries: number;
    dismissals: number;
  };
  preferred_scoring_zones: Array<{
    zone: string;
    runs: number;
    balls: number;
    strike_rate: number | null;
    boundaries: number;
  }>;
  dismissal_patterns: Array<{
    wicket_type: string;
    bowling_category: string;
    count: number;
  }>;
  vs_bowling_type: Record<string, {
    runs: number;
    balls: number;
    strike_rate: number | null;
    dismissals: number;
    sample_size: SampleSize;
  }>;
  phase_behavior: Record<string, PhaseBatting>;
  calculated_insights: string[];
  sample_size: SampleSize;
  limitations: string[];
}

export interface BowlerAnalytics {
  player: {
    id: number;
    name?: string | null;
    bowling_style?: string | null;
    primary_role?: string | null;
  };
  summary: {
    balls: number;
    runs_conceded: number;
    wickets: number;
    economy: number | null;
    wicket_rate_per_100_balls: number | null;
    boundaries_conceded: number;
    boundary_conceded_percentage: number | null;
  };
  economy_by_phase: Record<string, PhaseBowling>;
  vs_batter_handedness: Record<string, PhaseBowling>;
  length_line_tendencies: Array<{
    delivery_type: string;
    pitch_zone: string;
    balls: number;
    runs_conceded: number;
    wickets: number;
  }>;
  sample_size: SampleSize;
  limitations: string[];
}

export interface PartnershipAnalytics {
  player_ids: number[];
  players: Array<{id: number; name: string}>;
  innings_together: number;
  runs: number;
  balls: number;
  run_rate: number | null;
  dismissal_points: Array<{
    over?: number | null;
    ball?: number | null;
    dismissed_player_id?: number | null;
    wicket_type?: string | null;
  }>;
}

export interface TeamOpponentProfile {
  team_id: number;
  filters: Record<string, unknown>;
  batters: BatterAnalytics[];
  bowlers: BowlerAnalytics[];
  partnerships: PartnershipAnalytics[];
  sample: {
    deliveries: number;
    matches: number;
    message: string;
  };
}

export interface MatchupAnalytics {
  batter: {
    id: number;
    name: string;
    batting_style?: string | null;
  };
  bowler: {
    id: number;
    name: string;
    bowling_style?: string | null;
  };
  runs: number;
  balls: number;
  strike_rate: number | null;
  dismissals: number;
  boundaries: number;
  dots: number;
  phase_split: Record<string, PhaseBatting>;
  sample_size: SampleSize;
  limitations: string[];
}

export interface OpponentAnalyticsOptions {
  teams: Array<{
    id: number;
    name: string;
    short_name?: string | null;
  }>;
  players: Array<{
    id: number;
    display_name?: string | null;
    first_name?: string | null;
    last_name?: string | null;
    primary_role?: string | null;
    batting_style?: string | null;
    bowling_style?: string | null;
  }>;
}
