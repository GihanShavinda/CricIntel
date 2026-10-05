export type StrategyStatus =
  | 'Draft'
  | 'Active'
  | 'Archived';

export type AssignmentStatus =
  | 'Todo'
  | 'In Progress'
  | 'Done';

export interface StrategyUser {
  id: number;
  name: string;
  email?: string | null;
  roles?: string[];
}

export interface StrategySection {
  id: number;
  strategy_plan_id: number;
  section_key: string;
  title: string;
  content?: string | null;
  structured_data?: Record<string, unknown> | null;
  sort_order: number;
  updated_at: string;
  updater?: StrategyUser | null;
}

export interface StrategyComment {
  id: number;
  tactical_note_id: number;
  author_id?: number | null;
  body: string;
  is_resolution: boolean;
  created_at: string;
  author?: StrategyUser | null;
}

export interface TacticalNote {
  id: number;
  strategy_plan_id: number;
  strategy_section_id?: number | null;
  author_id?: number | null;
  title?: string | null;
  body: string;
  status: 'Open' | 'Resolved';
  resolved_at?: string | null;
  created_at: string;
  updated_at: string;
  author?: StrategyUser | null;
  resolver?: StrategyUser | null;
  section?: Pick<StrategySection, 'id' | 'title' | 'section_key'> | null;
  comments?: StrategyComment[];
}

export interface StrategyMention {
  id: number;
  strategy_plan_id: number;
  mentioned_user_id: number;
  mentioned_by?: number | null;
  source_type: 'note' | 'comment';
  source_id: number;
  token?: string | null;
  read_at?: string | null;
  created_at: string;
  mentioned_user?: StrategyUser | null;
  mentioner?: StrategyUser | null;
}

export interface StrategyAttachment {
  id: number;
  strategy_plan_id: number;
  uploaded_by?: number | null;
  source_type?: 'plan' | 'note' | 'comment' | null;
  source_id?: number | null;
  attachment_type:
    | 'player'
    | 'match'
    | 'scouting_report'
    | 'scouting_media'
    | 'file'
    | 'url';
  entity_id?: number | null;
  label?: string | null;
  original_filename?: string | null;
  mime_type?: string | null;
  size_bytes?: number | null;
  external_url?: string | null;
  file_url?: string | null;
  created_at: string;
  uploader?: StrategyUser | null;
}

export interface StrategyAssignment {
  id: number;
  strategy_plan_id: number;
  strategy_section_id?: number | null;
  assigned_to: number;
  assigned_by?: number | null;
  title: string;
  description?: string | null;
  status: AssignmentStatus;
  due_at?: string | null;
  completed_at?: string | null;
  created_at: string;
  section?: Pick<StrategySection, 'id' | 'title' | 'section_key'> | null;
  assignee?: StrategyUser | null;
  assigner?: StrategyUser | null;
}

export interface StrategyVersion {
  id: number;
  strategy_plan_id: number;
  version_number: number;
  event_type: string;
  entity_type: string;
  entity_id?: number | null;
  change_summary: string;
  snapshot?: Record<string, unknown> | null;
  changes?: Record<string, unknown> | null;
  created_at: string;
  actor?: StrategyUser | null;
}

export interface StrategyPlan {
  id: number;
  organization_id: number;
  match_id: number;
  opponent_team_id?: number | null;
  venue_id?: number | null;
  title: string;
  status: StrategyStatus;
  summary?: string | null;
  locked_at?: string | null;
  created_at: string;
  updated_at: string;
  opponent_team?: {
    id: number;
    name: string;
    short_name?: string | null;
  } | null;
  venue?: {
    id: number;
    name: string;
    city?: string | null;
    country?: string | null;
    pitch_type?: string | null;
  } | null;
  creator?: StrategyUser | null;
  updater?: StrategyUser | null;
  locker?: StrategyUser | null;
  sections?: StrategySection[];
  notes?: TacticalNote[];
  attachments?: StrategyAttachment[];
  assignments?: StrategyAssignment[];
  mentions?: StrategyMention[];
  versions?: StrategyVersion[];
  notes_count?: number;
  assignments_count?: number;
}

export interface StrategyOptions {
  matches: Array<{
    id: number;
    status: string;
    scheduled_at: string;
    home_team_id: number;
    home_team_name: string;
    away_team_id: number;
    away_team_name: string;
    venue_id?: number | null;
    venue_name?: string | null;
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
  players: Array<{
    id: number;
    display_name: string;
    primary_role: string;
    fitness_status: string;
    status: string;
    batting_style?: string | null;
    bowling_style?: string | null;
  }>;
  collaborators: StrategyUser[];
  scouting_reports: Array<{
    id: number;
    report_date: string;
    competition?: string | null;
    overall_recommendation: string;
    profile_id: number;
    display_name: string;
  }>;
  scouting_media: Array<{
    id: number;
    media_type: string;
    video_url?: string | null;
    original_filename?: string | null;
    caption?: string | null;
    display_name: string;
  }>;
}
