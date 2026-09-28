export type LivePlayer = {
  id: number;
  name: string;
};

export type LiveDelivery = {
  id: number;
  sequence_number?: number;
  ball_number: number;
  runs_off_bat: number;
  extra_runs: number;
  total_runs: number;
  extra_type: string;
  wicket: boolean;
  wicket_type?: string | null;
  is_legal: boolean;
  batter_name?: string | null;
  bowler_name?: string | null;
};

export type LiveInnings = {
  id: number;
  innings_number: number;
  batting_team_id: number;
  bowling_team_id: number;
  runs: number;
  wickets: number;
  legal_balls: number;
  overs: string;
  status: string;
};

export type LiveMatchSnapshot = {
  match_id: number;
  status: string;
  result_type?: string | null;
  winner_team_id?: number | null;
  target?: number | null;
  current_innings_id?: number | null;
  score: {
    runs: number;
    wickets: number;
    overs: string;
    legal_balls: number;
    batting_team_id: number;
    bowling_team_id: number;
  } | null;
  run_rate: number;
  required_run_rate: number | null;
  partnership: {
    runs: number;
    balls: number;
  };
  last_five_overs: {
    runs: number;
    wickets: number;
  };
  current_batter: LivePlayer | null;
  current_bowler: LivePlayer | null;
  last_over: LiveDelivery[];
  recent_deliveries: LiveDelivery[];
  innings: LiveInnings[];
};

export type LiveMatchEvent = {
  event_id: string;
  event_name: string;
  match_id: number;
  emitted_at: string;
  snapshot: LiveMatchSnapshot;
};
