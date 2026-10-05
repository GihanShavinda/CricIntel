export type PredictiveModelKind =
  | 'batter_score'
  | 'bowler_economy'
  | 'team_total';

export interface PredictiveReadinessProblem {
  title?: string;
  rows?: number;
  minimum_rows?: number;
  realistic: boolean;
  trained_model_required: boolean;
  reason: string;
}

export interface PredictiveReadiness {
  organization_id: number;
  available_data: {
    batter_innings_rows: number;
    bowler_innings_rows: number;
    team_innings_rows: number;
    batter_unique_players: number;
    bowler_unique_players: number;
    matches: number;
  };
  problems: Record<string, PredictiveReadinessProblem>;
}

export interface PredictiveOptions {
  players: Array<{
    id: number;
    display_name: string;
    primary_role?: string | null;
    batting_style?: string | null;
    bowling_style?: string | null;
    status?: string | null;
  }>;
  teams: Array<{
    id: number;
    name: string;
    short_name?: string | null;
  }>;
  venues: Array<{
    id: number;
    name: string;
    city?: string | null;
    country?: string | null;
    pitch_type?: string | null;
  }>;
}

export interface PredictionInterval {
  lower: number;
  prediction: number;
  upper: number;
  level: number;
}

export interface PredictionResult {
  model_kind: PredictiveModelKind;
  model_version: string;
  subject_id: number;
  prediction: number;
  interval: PredictionInterval;
  confidence_label: 'low' | 'moderate' | 'higher';
  sample_size: number;
  feature_values: Record<string, number | string | null>;
  explainability: Array<{
    feature: string;
    importance: number;
  }>;
  limitations: string[];
  disclaimer: string;
}

export interface PlayerFormTrend {
  player_id: number;
  sample_size: number;
  trend: 'improving' | 'declining' | 'stable' | 'insufficient_data';
  slope_runs_per_innings: number | null;
  recent_runs: number[];
  message: string;
  disclaimer: string;
}

export interface ModelTrainingResult {
  model_kind: PredictiveModelKind;
  status: 'trained' | 'not_ready';
  metadata: Record<string, any>;
}
