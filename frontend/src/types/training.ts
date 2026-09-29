export type TrainingSessionStatus =
  | 'Scheduled'
  | 'In Progress'
  | 'Completed'
  | 'Cancelled';

export type AttendanceStatus =
  | 'Present'
  | 'Absent'
  | 'Late'
  | 'Excused';

export interface TrainingTeam {
  id: number;
  name: string;
  short_name?: string | null;
}

export interface TrainingCoach {
  id: number;
  name: string;
  email?: string | null;
}

export interface TrainingPlayer {
  id: number;
  first_name?: string | null;
  last_name?: string | null;
  display_name?: string | null;
  primary_role?: string | null;
  fitness_status?: string | null;
}

export interface TrainingDrill {
  id: number;
  organization_id: number;
  name: string;
  category: string;
  objective?: string | null;
  duration_minutes?: number | null;
  difficulty: 'Beginner' | 'Intermediate' | 'Advanced';
  notes?: string | null;
  is_active: boolean;
}

export interface AttendanceEntry {
  id?: number;
  training_session_player_id?: number;
  status: AttendanceStatus;
  arrival_time?: string | null;
  notes?: string | null;
  marked_at?: string | null;
}

export interface TrainingSessionPlayer {
  id: number;
  player_id: number;
  player?: TrainingPlayer;
  attendance?: AttendanceEntry | null;
}

export interface TrainingSession {
  id: number;
  organization_id: number;
  team_id: number;
  coach_id?: number | null;
  session_date: string;
  start_time?: string | null;
  location?: string | null;
  duration_minutes: number;
  session_type: string;
  status: TrainingSessionStatus;
  notes?: string | null;
  team?: TrainingTeam;
  coach?: TrainingCoach | null;
  drills?: TrainingDrill[];
  session_players?: TrainingSessionPlayer[];
}

export interface FitnessTest {
  id: number;
  organization_id: number;
  player_id: number;
  training_session_id?: number | null;
  test_type: 'Yo-Yo Test' | 'Sprint' | 'Endurance' | 'Strength' | 'Custom';
  tested_at: string;
  value?: number | null;
  unit?: string | null;
  measurements?: Record<string, unknown> | null;
  notes?: string | null;
}

export interface PlayerAssessment {
  id: number;
  organization_id: number;
  player_id: number;
  training_session_id?: number | null;
  coach_id?: number | null;
  assessed_at: string;
  technical_rating?: number | null;
  tactical_rating?: number | null;
  fitness_rating?: number | null;
  attitude_rating?: number | null;
  strengths?: string | null;
  weaknesses?: string | null;
  notes?: string | null;
  coach?: TrainingCoach | null;
}

export interface TrainingObjective {
  id: number;
  organization_id: number;
  player_id: number;
  title: string;
  weakness?: string | null;
  statistic_scope?: string | null;
  metric_key?: string | null;
  observed_value?: number | null;
  target_value?: number | null;
  source_context?: Record<string, unknown> | null;
  status: 'Active' | 'Completed' | 'Paused' | 'Cancelled';
  target_date?: string | null;
  notes?: string | null;
}

export interface DevelopmentPlan {
  id: number;
  organization_id: number;
  player_id: number;
  coach_id?: number | null;
  title: string;
  weakness?: string | null;
  objectives?: string[] | null;
  objective_ids?: number[] | null;
  drill_ids?: number[] | null;
  start_date: string;
  target_date?: string | null;
  status: 'Active' | 'Completed' | 'Paused' | 'Cancelled';
  review_notes?: string | null;
  coach?: TrainingCoach | null;
}

export interface TrainingOptions {
  teams: TrainingTeam[];
  players: TrainingPlayer[];
  drills: TrainingDrill[];
  coaches: TrainingCoach[];
}
