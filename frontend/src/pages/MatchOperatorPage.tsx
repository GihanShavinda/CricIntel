import { useEffect, useMemo, useState, type FormEvent } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import {
  completeInnings,
  completeMatch,
  getScorecard,
  recordDelivery,
  startInnings,
  startMatch,
  undoLatestDelivery,
} from '../api/matches';
import { listPlayers } from '../api/players';
import { AppLayout } from '../components/AppLayout';

import type { Player } from '../types/player';
import type { InningsScore, MatchScorecard } from '../types/match';

const wicketTypes = [
  'bowled',
  'caught',
  'lbw',
  'run_out',
  'stumped',
  'hit_wicket',
  'obstructing_field',
];

export function MatchOperatorPage() {
  const organizationId = Number(useParams().organizationId);
  const matchId = Number(useParams().matchId);
  const navigate = useNavigate();

  const [match, setMatch] = useState<MatchScorecard | null>(null);
  const [players, setPlayers] = useState<Player[]>([]);
  const [message, setMessage] = useState('');
  const [runsOffBat, setRunsOffBat] = useState(0);
  const [extraType, setExtraType] = useState('none');
  const [extraRuns, setExtraRuns] = useState(0);
  const [wicket, setWicket] = useState(false);

  const load = async () => {
    const [scorecard, playerResult] = await Promise.all([
      getScorecard(organizationId, matchId),
      listPlayers(organizationId, { per_page: 100 }),
    ]);
    setMatch(scorecard);
    setPlayers(playerResult.data);
  };

  useEffect(() => {
    void load();
  }, [organizationId, matchId]);

  const activeInnings = useMemo(
    () => match?.innings.find((innings) => innings.status === 'In Progress') ?? null,
    [match]
  );

  const homeTeam = match?.fixture?.home_team;
  const awayTeam = match?.fixture?.away_team;

  const playersForTeam = (teamId: number | undefined) =>
    players.filter((player) => player.teams?.some((team) => team.id === teamId));

  const showError = (error: any, fallback: string) => {
    const errors = error?.response?.data?.errors;
    const first = errors ? Object.values(errors).flat().at(0) : null;
    setMessage(String(first ?? error?.response?.data?.message ?? fallback));
  };

  if (!match) {
    return <AppLayout><p>Loading match...</p></AppLayout>;
  }

  const firstInningsComplete = match.innings.some(
    (innings) => innings.innings_number === 1 && innings.status === 'Completed'
  );

  const nextInningsNumber = firstInningsComplete ? 2 : 1;

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <h1>Live Scoring Operator</h1>
          <p>
            {homeTeam?.name ?? 'Home'} vs {awayTeam?.name ?? 'Away'} · {match.status}
          </p>
        </div>
        <button
          type="button"
          onClick={() =>
            navigate(`/organizations/${organizationId}/matches/${matchId}/scorecard`)
          }
        >
          View scorecard
        </button>
      </div>

      {message && <div className="form-error-message">{message}</div>}

      {activeInnings && (
        <div className="live-score-hero">
          <div>
            <span>Current score</span>
            <strong>{activeInnings.score.display}</strong>
            <small>{activeInnings.score.overs} overs</small>
          </div>
          {activeInnings.score.target && (
            <div>
              <span>Target</span>
              <strong>{activeInnings.score.target}</strong>
              <small>{activeInnings.score.runs_required} required</small>
            </div>
          )}
          {activeInnings.free_hit_next && (
            <div className="free-hit-badge">FREE HIT</div>
          )}
        </div>
      )}

      {match.status === 'Scheduled' && (
        <section className="profile-section">
          <h2>Start match</h2>
          <form
            onSubmit={async (event) => {
              event.preventDefault();
              const data = new FormData(event.currentTarget);
              setMessage('');
              try {
                await startMatch(organizationId, matchId, {
                  toss_winner_id: Number(data.get('toss_winner_id')),
                  toss_decision: data.get('toss_decision'),
                  max_overs: Number(data.get('max_overs')),
                });
                await load();
              } catch (error) {
                showError(error, 'Unable to start match.');
              }
            }}
          >
            <div className="form-grid">
              <label>
                Toss winner
                <select name="toss_winner_id" required>
                  <option value="">Select team</option>
                  {homeTeam && <option value={homeTeam.id}>{homeTeam.name}</option>}
                  {awayTeam && <option value={awayTeam.id}>{awayTeam.name}</option>}
                </select>
              </label>
              <label>
                Toss decision
                <select name="toss_decision" defaultValue="bat">
                  <option value="bat">Bat</option>
                  <option value="bowl">Bowl</option>
                </select>
              </label>
              <label>
                Maximum overs
                <input name="max_overs" type="number" min="1" defaultValue={match.max_overs ?? 20} />
              </label>
            </div>
            <div className="form-actions">
              <button type="submit">Start match</button>
            </div>
          </form>
        </section>
      )}

      {match.status === 'In Progress' && !activeInnings && nextInningsNumber <= 2 && (
        <StartInningsPanel
          inningsNumber={nextInningsNumber}
          homeTeam={homeTeam}
          awayTeam={awayTeam}
          players={players}
          onStart={async (payload) => {
            setMessage('');
            try {
              await startInnings(organizationId, matchId, payload);
              await load();
            } catch (error) {
              showError(error, 'Unable to start innings.');
            }
          }}
        />
      )}

      {activeInnings && (
        <section className="profile-section">
          <div className="section-heading">
            <div>
              <h2>Record delivery</h2>
              <p>All score changes are recorded transactionally.</p>
            </div>
            <div className="team-registration-actions">
              <button
                type="button"
                onClick={async () => {
                  setMessage('');
                  try {
                    await undoLatestDelivery(organizationId, matchId, activeInnings.id);
                    await load();
                  } catch (error) {
                    showError(error, 'Unable to undo delivery.');
                  }
                }}
              >
                Undo latest ball
              </button>
              <button
                type="button"
                onClick={async () => {
                  if (!window.confirm('Complete this innings?')) return;
                  await completeInnings(organizationId, matchId, activeInnings.id);
                  await load();
                }}
              >
                Complete innings
              </button>
            </div>
          </div>

          <form
            onSubmit={async (event) => {
              event.preventDefault();
              const data = new FormData(event.currentTarget);
              setMessage('');
              try {
                await recordDelivery(organizationId, matchId, activeInnings.id, {
                  batter_id: Number(data.get('batter_id')),
                  non_striker_id: Number(data.get('non_striker_id')),
                  bowler_id: Number(data.get('bowler_id')),
                  runs_off_bat: runsOffBat,
                  extra_type: extraType,
                  extra_runs: extraRuns,
                  wicket,
                  wicket_type: wicket ? data.get('wicket_type') : null,
                  dismissed_player_id: wicket && data.get('dismissed_player_id')
                    ? Number(data.get('dismissed_player_id'))
                    : null,
                  fielder_id: wicket && data.get('fielder_id')
                    ? Number(data.get('fielder_id'))
                    : null,
                  shot_type: data.get('shot_type') || null,
                  delivery_type: data.get('delivery_type') || null,
                  pitch_zone: data.get('pitch_zone') || null,
                  ball_speed: data.get('ball_speed') ? Number(data.get('ball_speed')) : null,
                });
                setRunsOffBat(0);
                setExtraType('none');
                setExtraRuns(0);
                setWicket(false);
                await load();
              } catch (error) {
                showError(error, 'Unable to record delivery.');
              }
            }}
          >
            <DeliveryPlayerSelectors
              innings={activeInnings}
              battingPlayers={playersForTeam(activeInnings.batting_team_id)}
              bowlingPlayers={playersForTeam(activeInnings.bowling_team_id)}
            />

            <div className="run-button-grid">
              {[0, 1, 2, 3, 4, 5, 6].map((run) => (
                <button
                  key={run}
                  type="button"
                  className={runsOffBat === run ? 'run-button active' : 'run-button'}
                  onClick={() => setRunsOffBat(run)}
                >
                  {run}
                </button>
              ))}
            </div>

            <div className="form-grid">
              <label>
                Extra type
                <select
                  value={extraType}
                  onChange={(event) => {
                    const value = event.target.value;
                    setExtraType(value);
                    if (value === 'wide' || value === 'no_ball') setExtraRuns(1);
                    if (value === 'none') setExtraRuns(0);
                  }}
                >
                  <option value="none">None</option>
                  <option value="wide">Wide</option>
                  <option value="no_ball">No ball</option>
                  <option value="bye">Bye</option>
                  <option value="leg_bye">Leg bye</option>
                  <option value="penalty">Penalty</option>
                </select>
              </label>
              <label>
                Extra runs
                <input
                  type="number"
                  min="0"
                  value={extraRuns}
                  onChange={(event) => setExtraRuns(Number(event.target.value))}
                />
              </label>
              <label>
                <span>Wicket</span>
                <input
                  type="checkbox"
                  checked={wicket}
                  onChange={(event) => setWicket(event.target.checked)}
                />
              </label>
            </div>

            {wicket && (
              <div className="form-grid">
                <label>
                  Wicket type
                  <select name="wicket_type" required>
                    {wicketTypes.map((type) => <option key={type} value={type}>{type}</option>)}
                  </select>
                </label>
                <label>
                  Dismissed player
                  <select name="dismissed_player_id" required>
                    <option value="">Select player</option>
                    {playersForTeam(activeInnings.batting_team_id).map((player) => (
                      <option key={player.id} value={player.id}>{player.display_name}</option>
                    ))}
                  </select>
                </label>
                <label>
                  Fielder
                  <select name="fielder_id">
                    <option value="">None</option>
                    {playersForTeam(activeInnings.bowling_team_id).map((player) => (
                      <option key={player.id} value={player.id}>{player.display_name}</option>
                    ))}
                  </select>
                </label>
              </div>
            )}

            <div className="form-grid">
              <label>Shot type<input name="shot_type" placeholder="Drive / Pull / Cut" /></label>
              <label>Delivery type<input name="delivery_type" placeholder="Yorker / Bouncer / Length" /></label>
              <label>Pitch zone<input name="pitch_zone" placeholder="Off stump / Middle / Leg" /></label>
              <label>Ball speed km/h<input name="ball_speed" type="number" step="0.1" min="0" /></label>
            </div>

            <div className="form-actions">
              <button type="submit">Record delivery</button>
            </div>
          </form>
        </section>
      )}

      {match.status === 'In Progress' && match.innings.length >= 2 && !activeInnings && (
        <section className="profile-section">
          <h2>Finish match</h2>
          <button
            onClick={async () => {
              await completeMatch(organizationId, matchId);
              await load();
            }}
          >
            Complete match
          </button>
        </section>
      )}
    </AppLayout>
  );
}

function StartInningsPanel({
  inningsNumber,
  homeTeam,
  awayTeam,
  players,
  onStart,
}: {
  inningsNumber: number;
  homeTeam?: { id: number; name: string };
  awayTeam?: { id: number; name: string };
  players: Player[];
  onStart: (payload: Record<string, unknown>) => Promise<void>;
}) {
  const [battingTeamId, setBattingTeamId] = useState(homeTeam?.id ?? 0);
  const bowlingTeamId = battingTeamId === homeTeam?.id ? awayTeam?.id : homeTeam?.id;

  const batters = players.filter((player) => player.teams?.some((team) => team.id === battingTeamId));
  const bowlers = players.filter((player) => player.teams?.some((team) => team.id === bowlingTeamId));

  return (
    <section className="profile-section">
      <h2>Start innings {inningsNumber}</h2>
      <form
        onSubmit={async (event: FormEvent<HTMLFormElement>) => {
          event.preventDefault();
          const data = new FormData(event.currentTarget);
          await onStart({
            batting_team_id: battingTeamId,
            bowling_team_id: bowlingTeamId,
            innings_number: inningsNumber,
            striker_id: Number(data.get('striker_id')),
            non_striker_id: Number(data.get('non_striker_id')),
            bowler_id: Number(data.get('bowler_id')),
          });
        }}
      >
        <div className="form-grid">
          <label>
            Batting team
            <select value={battingTeamId} onChange={(event) => setBattingTeamId(Number(event.target.value))}>
              {homeTeam && <option value={homeTeam.id}>{homeTeam.name}</option>}
              {awayTeam && <option value={awayTeam.id}>{awayTeam.name}</option>}
            </select>
          </label>
          <label>
            Striker
            <select name="striker_id" required>
              <option value="">Select batter</option>
              {batters.map((player) => <option key={player.id} value={player.id}>{player.display_name}</option>)}
            </select>
          </label>
          <label>
            Non-striker
            <select name="non_striker_id" required>
              <option value="">Select batter</option>
              {batters.map((player) => <option key={player.id} value={player.id}>{player.display_name}</option>)}
            </select>
          </label>
          <label>
            Opening bowler
            <select name="bowler_id" required>
              <option value="">Select bowler</option>
              {bowlers.map((player) => <option key={player.id} value={player.id}>{player.display_name}</option>)}
            </select>
          </label>
        </div>
        <div className="form-actions"><button type="submit">Start innings</button></div>
      </form>
    </section>
  );
}

function DeliveryPlayerSelectors({
  innings,
  battingPlayers,
  bowlingPlayers,
}: {
  innings: InningsScore;
  battingPlayers: Player[];
  bowlingPlayers: Player[];
}) {
  return (
    <div className="form-grid">
      <label>
        Batter
        <select name="batter_id" defaultValue={innings.striker_id ?? ''} required>
          <option value="">Select batter</option>
          {battingPlayers.map((player) => <option key={player.id} value={player.id}>{player.display_name}</option>)}
        </select>
      </label>
      <label>
        Non-striker
        <select name="non_striker_id" defaultValue={innings.non_striker_id ?? ''} required>
          <option value="">Select batter</option>
          {battingPlayers.map((player) => <option key={player.id} value={player.id}>{player.display_name}</option>)}
        </select>
      </label>
      <label>
        Bowler
        <select name="bowler_id" defaultValue={innings.current_bowler_id ?? ''} required>
          <option value="">Select bowler</option>
          {bowlingPlayers.map((player) => <option key={player.id} value={player.id}>{player.display_name}</option>)}
        </select>
      </label>
    </div>
  );
}
