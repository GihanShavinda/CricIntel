export type AnalyticsIntent =
  | 'rank_bowlers_phase_vs_hand'
  | 'team_run_rate_trend'
  | 'batter_phase_performance'
  | 'bowler_phase_performance'
  | 'player_form_trend'
  | 'team_phase_scoring'
  | 'matchup_summary'
  | 'venue_scoring_summary'
  | 'opponent_phase_threats';

export interface ControlledAnalyticsQuery {
  intent: AnalyticsIntent;
  metric: string;
  entity: string;

  team_id?: number | null;
  team_name?: string | null;

  season_id?: number | null;
  season_name?: string | null;

  opponent_team_id?: number | null;
  opponent_name?: string | null;

  venue_id?: number | null;
  venue_name?: string | null;

  format?: string | null;
  phase: 'all' | 'powerplay' | 'middle' | 'death';
  batting_hand: 'all' | 'left' | 'right';

  player_id?: number | null;
  player_name?: string | null;

  batter_id?: number | null;
  batter_name?: string | null;

  bowler_id?: number | null;
  bowler_name?: string | null;

  date_from?: string | null;
  date_to?: string | null;

  last_n_matches?: number | null;
  limit: number;
  sort_direction: 'asc' | 'desc';
}

export interface AnalyticsIntentDefinition {
  intent: AnalyticsIntent;
  description: string;
  metrics: string[];
  entities: string[];
  example: string;
}

export interface AnalyticsOptions {
  supported_intents: AnalyticsIntentDefinition[];
  filters: {
    teams: Array<{
      id: number;
      name: string;
      short_name?: string | null;
    }>;
    seasons: Array<{
      id: number;
      name: string;
      start_date?: string | null;
      end_date?: string | null;
      status?: string | null;
    }>;
    venues: Array<{
      id: number;
      name: string;
      city?: string | null;
      country?: string | null;
    }>;
    formats: string[];
    players: Array<{
      id: number;
      display_name: string;
      primary_role?: string | null;
    }>;
  };
  controlled_schema: {
    intents: string[];
    metrics: string[];
    entities: string[];
    phases: string[];
    batting_hands: string[];
  };
  security: {
    generated_sql_allowed: boolean;
    arbitrary_sql_allowed: boolean;
    execution_mode: string;
  };
}

export interface AnalyticsVisualization {
  type: 'table' | 'bar' | 'line';
  x_key?: string;
  y_key?: string;
  label?: string;
}

export interface StructuredAnalyticsResult {
  title: string;
  columns: string[];
  rows: Array<Record<string, any>>;
  visualization: AnalyticsVisualization;
  sample: Record<string, any>;
  source: Record<string, any>;
}

export interface NaturalLanguageAnalyticsResponse {
  query_id: number;
  natural_language_query: string;
  controlled_query: ControlledAnalyticsQuery;
  result: StructuredAnalyticsResult;
  explanation: string;
  visualization: AnalyticsVisualization;
  security: {
    generated_sql_used: boolean;
    arbitrary_sql_used: boolean;
    execution_mode: string;
  };
}

export interface NlAnalyticsHistoryRow {
  id: number;
  organization_id: number;
  user_id?: number | null;
  natural_language_query: string;
  intent?: AnalyticsIntent | null;
  controlled_query?: ControlledAnalyticsQuery | null;
  resolved_filters?: Record<string, any> | null;
  structured_result?: StructuredAnalyticsResult | null;
  explanation?: string | null;
  visualization?: AnalyticsVisualization | null;
  status: string;
  errors?: Record<string, string[]> | null;
  executed_at?: string | null;
  created_at: string;
  user?: {
    id: number;
    name: string;
    email?: string | null;
  } | null;
}
