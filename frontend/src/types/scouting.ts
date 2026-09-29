export type ScoutingStatus =
  | 'Watching'
  | 'Shortlisted'
  | 'Recommended'
  | 'Rejected'
  | 'Converted';

export type ScoutingRecommendation =
  | 'Highly Recommend'
  | 'Recommend'
  | 'Monitor'
  | 'Do Not Recommend';

export interface ScoutingRating {
  id: number;
  scouting_report_id: number;
  technical_rating: number;
  tactical_rating: number;
  physical_rating: number;
  fielding_rating: number;
  mental_decision_rating: number;
  overall_rating: number;
}

export interface ScoutingMedia {
  id: number;
  scouting_report_id: number;
  media_type: 'Video URL' | 'File';
  video_url?: string | null;
  file_url?: string | null;
  original_filename?: string | null;
  mime_type?: string | null;
  size_bytes?: number | null;
  caption?: string | null;
}

export interface ScoutingNote {
  id: number;
  scouting_profile_id: number;
  scouting_report_id?: number | null;
  note: string;
  is_private: boolean;
  created_at: string;
  author?: {
    id: number;
    name: string;
  } | null;
}

export interface ScoutingReport {
  id: number;
  scouting_profile_id: number;
  scout_id?: number | null;
  competition?: string | null;
  report_date: string;
  observed_role?: string | null;
  strengths?: string | null;
  weaknesses?: string | null;
  potential?: number | null;
  overall_recommendation: ScoutingRecommendation;
  notes?: string | null;
  rating?: ScoutingRating | null;
  media?: ScoutingMedia[];
  scouting_notes?: ScoutingNote[];
  scout?: {
    id: number;
    name: string;
  } | null;
  profile?: ScoutingProfile;
}

export interface ScoutingProfile {
  id: number;
  organization_id: number;
  existing_player_id?: number | null;
  converted_player_id?: number | null;
  first_name: string;
  last_name?: string | null;
  display_name: string;
  date_of_birth?: string | null;
  nationality?: string | null;
  role?: string | null;
  batting_style?: string | null;
  bowling_style?: string | null;
  current_team?: string | null;
  current_competition?: string | null;
  source?: string | null;
  status: ScoutingStatus;
  summary?: string | null;
  reports?: ScoutingReport[];
  notes?: ScoutingNote[];
  converted_player?: {
    id: number;
    display_name: string;
  } | null;
}

export interface ScoutingPage {
  current_page: number;
  data: ScoutingProfile[];
  last_page: number;
  per_page: number;
  total: number;
}

export interface ScoutingComparisonRow {
  profile: Pick<
    ScoutingProfile,
    | 'id'
    | 'display_name'
    | 'role'
    | 'nationality'
    | 'current_team'
    | 'current_competition'
    | 'status'
  >;
  latest_report: {
    id: number;
    report_date: string;
    competition?: string | null;
    potential?: number | null;
    overall_recommendation: ScoutingRecommendation;
    rating?: ScoutingRating | null;
  } | null;
}
