/*
|--------------------------------------------------------------------------
| Fixture Team
|--------------------------------------------------------------------------
*/

export interface MatchFixtureTeam {
  id: number;
  name: string;

  short_name?:
    string | null;
}

/*
|--------------------------------------------------------------------------
| Match Fixture
|--------------------------------------------------------------------------
*/

export interface MatchFixture {
  id: number;

  home_team_id: number;
  away_team_id: number;

  home_team?:
    MatchFixtureTeam;

  away_team?:
    MatchFixtureTeam;

  venue?: {
    id: number;
    name: string;
  } | null;

  scheduled_at?:
    string;

  match_number?:
    number | null;
}

/*
|--------------------------------------------------------------------------
| Delivery
|--------------------------------------------------------------------------
*/

export interface DeliveryScore {
  id: number;

  sequence_number: number;
  ball_number: number;

  batter_id: number;
  bowler_id: number;

  runs_off_bat: number;
  extra_runs: number;
  total_runs: number;

  extra_type: string;

  is_legal: boolean;
  is_free_hit: boolean;

  wicket: boolean;

  wicket_type:
    string | null;

  dismissed_player_id:
    number | null;

  fielder_id:
    number | null;
}

/*
|--------------------------------------------------------------------------
| Innings
|--------------------------------------------------------------------------
*/

export interface InningsScore {
  id: number;

  innings_number: number;

  batting_team_id: number;
  bowling_team_id: number;

  status: string;

  score: {
    runs: number;
    wickets: number;
    overs: number;

    display: string;

    target:
      number | null;

    runs_required:
      number | null;
  };

  striker_id:
    number | null;

  non_striker_id:
    number | null;

  current_bowler_id:
    number | null;

  free_hit_next: boolean;

  deliveries:
    DeliveryScore[];
}

/*
|--------------------------------------------------------------------------
| Full Match Scorecard
|--------------------------------------------------------------------------
*/

export interface MatchScorecard {
  id: number;

  fixture_id: number;

  status: string;

  result_type:
    string | null;

  winner_team_id:
    number | null;

  player_of_match_id:
    number | null;

  target_runs:
    number | null;

  max_overs:
    number | null;

  toss_winner_id?:
    number | null;

  toss_decision?:
    string | null;

  fixture?:
    MatchFixture;

  innings:
    InningsScore[];
}

/*
|--------------------------------------------------------------------------
| Match List Item
|--------------------------------------------------------------------------
*/

export interface MatchListItem {
  id: number;

  fixture_id: number;

  status: string;

  result_type:
    string | null;

  winner_team_id:
    number | null;

  target_runs:
    number | null;

  max_overs:
    number | null;

  toss_winner_id?:
    number | null;

  toss_decision?:
    string | null;

  fixture?:
    MatchFixture;
}