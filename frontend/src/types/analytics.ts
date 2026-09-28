export type AnalyticsFilterValues = {
  team_id?: number;
  season_id?: number;
  tournament_id?: number;
  opponent_id?: number;
  venue_id?: number;
  format?: string;
  batting_position?: number;
  bowling_type?: 'spin' | 'pace';
  phase?: 'powerplay' | 'middle' | 'death';
  from?: string;
  to?: string;
  limit?: number;
};

export type OptionItem = {
  id: number;
  name: string;
};

export type PlayerOption = {
  id: number;
  display_name: string;
  primary_role?: string | null;
};

export type TournamentOption = OptionItem & {
  season_id: number;
  format?: string | null;
};

export type AnalyticsOptions = {
  seasons: OptionItem[];
  tournaments: TournamentOption[];
  teams: OptionItem[];
  venues: OptionItem[];
  players: PlayerOption[];
  formats: string[];
};

export type TeamFormPoint = {
  match_id: number;
  date: string;
  result: 'W' | 'L' | 'NR';
  result_type?: string | null;
};

export type RunsTrendPoint = {
  match_id: number;
  date: string;
  runs: number;
  wickets: number;
  run_rate: number;
};

export type RunRateTrendPoint = {
  match_id: number;
  date: string;
  run_rate: number;
};

export type WicketTrendPoint = {
  match_id: number;
  date: string;
  wickets_lost: number;
};

export type WinLossPoint = {
  name: string;
  value: number;
};

export type TopBatter = {
  player_id: number;
  name: string;
  runs: number;
  balls: number;
  strike_rate: number;
  fours: number;
  sixes: number;
};

export type TopBowler = {
  player_id: number;
  name: string;
  wickets: number;
  runs_conceded: number;
  balls: number;
  economy: number;
};

export type PhasePoint = {
  phase: 'powerplay' | 'middle' | 'death';
  runs: number;
  balls: number;
  wickets: number;
  run_rate: number;
};

export type OpponentSummaryRow = {
  opponent_id: number;
  opponent: string;
  matches: number;
  wins: number;
  losses: number;
};

export type VenuePerformanceRow = {
  venue_id: number | null;
  venue: string;
  matches: number;
  wins: number;
  losses: number;
  win_percentage: number;
};

export type AnalyticsDashboard = {
  team_form: TeamFormPoint[];
  runs_trend: RunsTrendPoint[];
  run_rate_trend: RunRateTrendPoint[];
  wicket_trend: WicketTrendPoint[];
  win_loss: WinLossPoint[];
  top_batters: TopBatter[];
  top_bowlers: TopBowler[];
  phase_analysis: PhasePoint[];
  opponent_summary: OpponentSummaryRow[];
  venue_performance: VenuePerformanceRow[];
};

export type BattingStats = {
  matches: number;
  innings: number;
  runs: number;
  balls_faced: number;
  highest_score: number;
  average: number | null;
  strike_rate: number;
  fifties: number;
  hundreds: number;
  fours: number;
  sixes: number;
  boundary_percentage: number;
  dot_ball_percentage: number;
  powerplay_strike_rate: number;
  middle_over_strike_rate: number;
  death_over_strike_rate: number;
  spin_strike_rate: number;
  pace_strike_rate: number;
};

export type BowlingStats = {
  overs: string;
  balls: number;
  runs_conceded: number;
  wickets: number;
  average: number | null;
  economy: number;
  strike_rate: number | null;
  dot_percentage: number;
  boundary_conceded_percentage: number;
  maidens: number;
  powerplay_economy: number;
  middle_over_economy: number;
  death_over_economy: number;
};

export type FieldingStats = {
  catches: number;
  run_outs: number;
  stumpings: number;
  drops: number;
  fielding_opportunities: number;
  fielding_efficiency: number | null;
};

export type ComparedPlayer = {
  player: {
    id: number;
    name: string;
    role?: string | null;
    batting_style?: string | null;
    bowling_style?: string | null;
  };
  batting: BattingStats;
  bowling: BowlingStats;
  fielding: FieldingStats;
};

export type PlayerComparisonResponse = {
  player_a: ComparedPlayer;
  player_b: ComparedPlayer;
};
