export type ReportType =
  | 'player_performance'
  | 'match'
  | 'team_performance'
  | 'opponent'
  | 'training'
  | 'scouting'
  | 'tournament'
  | 'tactical_preparation';

export type ReportFormat =
  | 'pdf'
  | 'xlsx'
  | 'csv';

export interface ReportFilters {
  player_id?: number;
  match_id?: number;
  team_id?: number;
  opponent_team_id?: number;
  training_session_id?: number;
  scouting_report_id?: number;
  tournament_id?: number;
  strategy_plan_id?: number;
  season_id?: number;
  venue_id?: number;
  format?: string;
  phase?: 'all' | 'powerplay' | 'middle' | 'death';
  date_from?: string;
  date_to?: string;
}

export interface ReportOption {
  value: string;
  label: string;
}

export interface ReportEntityOption {
  id: number;
  label: string;
}

export interface ReportOptions {
  report_types: ReportOption[];
  formats: ReportOption[];
  heavy_formats: string[];
  entities?: {
    players?: ReportEntityOption[];
    teams?: ReportEntityOption[];
    matches?: ReportEntityOption[];
    tournaments?: ReportEntityOption[];
    venues?: ReportEntityOption[];
    training_sessions?: ReportEntityOption[];
    scouting_reports?: ReportEntityOption[];
    strategy_plans?: ReportEntityOption[];
  };
}

export interface ReportDocument {
  title: string;
  subtitle: string;
  metadata: Record<string, string | number | null>;
  summary: Array<{
    label: string;
    value: string | number;
  }>;
  sections: Array<{
    heading: string;
    body: string;
  }>;
  tables: Array<{
    title: string;
    columns: string[];
    rows: Array<Array<string | number | null>>;
  }>;
  charts: Array<{
    title: string;
    type: string;
    labels: string[];
    values: number[];
  }>;
  limitations: string[];
}

export interface ReportExport {
  id: number;
  organization_id: number;
  report_type: ReportType;
  report_label: string;
  format: ReportFormat;
  status: 'pending' | 'queued' | 'processing' | 'completed' | 'failed';
  filters: ReportFilters;
  filename?: string | null;
  file_size?: number | null;
  mime_type?: string | null;
  error_message?: string | null;
  queued_at?: string | null;
  started_at?: string | null;
  completed_at?: string | null;
  failed_at?: string | null;
  created_at?: string | null;
  download_url?: string | null;
}
