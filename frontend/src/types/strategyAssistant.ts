export interface StrategyAssistantMatchOption {
  id: number;
  status: string;
  max_overs?: number | null;
  scheduled_at: string;
  home_team_id: number;
  home_team_name: string;
  away_team_id: number;
  away_team_name: string;
  venue_id?: number | null;
  venue_name?: string | null;
}

export interface StrategyAssistantStatus {
  enabled: boolean;
  kill_switch_active: boolean;
  service: {
    reachable: boolean;
    service?: Record<string, unknown> | null;
    error?: string | null;
  };
  rules: string[];
}

export interface StrategyEvidence {
  id: string;
  metric: string;
  value: unknown;
  unit?: string | null;
  sample_size?: number | null;
  entity: Record<string, unknown>;
  source: Record<string, unknown>;
}

export interface DeterministicRecommendation {
  key: string;
  title: string;
  recommendation: string;
  confidence: 'high' | 'moderate' | 'low';
  evidence_ids: string[];
  limitations: string[];
}

export interface StrategyContext {
  schema_version: string;
  generated_at: string;
  match: Record<string, any>;
  available_players: Array<Record<string, any>>;
  recent_form: Array<Record<string, any>>;
  player_data: Record<string, any>;
  opponent_data: Record<string, any>;
  venue_statistics: Record<string, any>;
  matchups: Array<Record<string, any>>;
  training_context: Record<string, any>;
  scouting_context: Record<string, any>;
  selected_squad: Record<string, any>;
  limitations: string[];
  evidence: StrategyEvidence[];
}

export interface StrategyAssistantContextPayload {
  context: StrategyContext;
  deterministic_recommendations: DeterministicRecommendation[];
  assistant_enabled: boolean;
}

export interface GroundedAssistantItem {
  statement: string;
  evidence_ids: string[];
  confidence: 'high' | 'moderate' | 'low';
}

export interface GroundedAssistantResponse {
  claims: GroundedAssistantItem[];
  recommendations: GroundedAssistantItem[];
  limitations: string[];
  coach_note: string;
}

export interface StrategyAssistantAnswer {
  run_id: number;
  question: string;
  response: GroundedAssistantResponse;
  deterministic_recommendations: DeterministicRecommendation[];
  evidence: StrategyEvidence[];
  context_hash: string;
  provider?: string | null;
  model?: string | null;
  generated_at?: string | null;
}

export interface StrategyAssistantHistoryRow {
  id: number;
  match_id: number;
  question: string;
  validation_status: string;
  validated_response?: GroundedAssistantResponse | null;
  deterministic_recommendations?: DeterministicRecommendation[] | null;
  validation_errors?: string[] | null;
  provider?: string | null;
  model?: string | null;
  generated_at?: string | null;
  created_at: string;
  user?: {
    id: number;
    name: string;
    email?: string | null;
  } | null;
}
