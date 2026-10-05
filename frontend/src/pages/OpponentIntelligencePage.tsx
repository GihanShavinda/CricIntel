import {
  useEffect,
  useMemo,
  useState,
} from 'react';

import {
  useParams,
  useSearchParams,
} from 'react-router-dom';

import {
  getOpponentAnalyticsOptions,
  getOpponentBatterAnalytics,
  getOpponentBowlerAnalytics,
  getOpponentMatchup,
  getOpponentTeamProfile,
  type OpponentFilters,
} from '../api/opponentAnalytics';

import {
  AppLayout,
} from '../components/AppLayout';

import {
  BattingPhaseChart,
  BowlingPhaseChart,
} from '../components/opponent/PhaseAnalyticsChart';

import {
  SampleSizeNotice,
} from '../components/opponent/SampleSizeNotice';

import {
  ZoneHeatmap,
} from '../components/opponent/ZoneHeatmap';

import type {
  BatterAnalytics,
  BowlerAnalytics,
  MatchupAnalytics,
  OpponentAnalyticsOptions,
  TeamOpponentProfile,
} from '../types/opponentAnalytics';

export function OpponentIntelligencePage() {
  const organizationId = Number(useParams().organizationId);
  const [searchParams, setSearchParams] = useSearchParams();

  const [options, setOptions] = useState<OpponentAnalyticsOptions>({
    teams: [],
    players: [],
  });

  const [teamProfile, setTeamProfile] = useState<TeamOpponentProfile | null>(null);
  const [batter, setBatter] = useState<BatterAnalytics | null>(null);
  const [bowler, setBowler] = useState<BowlerAnalytics | null>(null);
  const [matchup, setMatchup] = useState<MatchupAnalytics | null>(null);
  const [error, setError] = useState('');

  const teamId = Number(searchParams.get('team_id') ?? 0);
  const batterId = Number(searchParams.get('batter_id') ?? 0);
  const bowlerId = Number(searchParams.get('bowler_id') ?? 0);
  const from = searchParams.get('from') ?? '';
  const to = searchParams.get('to') ?? '';

  const filters = useMemo<OpponentFilters>(
    () => ({
      from: from || undefined,
      to: to || undefined,
    }),
    [from, to],
  );

  const setParam = (
    key: string,
    value: string,
  ) => {
    const next = new URLSearchParams(searchParams);

    if (value) {
      next.set(key, value);
    } else {
      next.delete(key);
    }

    setSearchParams(next);
  };

  useEffect(() => {
    void getOpponentAnalyticsOptions(organizationId)
      .then(setOptions)
      .catch((caught) => {
        console.error(caught);
        setError('Unable to load opponent analytics options.');
      });
  }, [organizationId]);

  useEffect(() => {
    if (!teamId) {
      setTeamProfile(null);
      return;
    }

    setError('');

    void getOpponentTeamProfile(
      organizationId,
      teamId,
      filters,
    )
      .then(setTeamProfile)
      .catch((caught) => {
        console.error(caught);
        setError('Unable to load opponent team analytics.');
      });
  }, [organizationId, teamId, from, to]);

  useEffect(() => {
    if (!batterId) {
      setBatter(null);
      return;
    }

    void getOpponentBatterAnalytics(
      organizationId,
      batterId,
      filters,
    )
      .then(setBatter)
      .catch((caught) => {
        console.error(caught);
        setError('Unable to load batter analytics.');
      });
  }, [organizationId, batterId, from, to]);

  useEffect(() => {
    if (!bowlerId) {
      setBowler(null);
      return;
    }

    void getOpponentBowlerAnalytics(
      organizationId,
      bowlerId,
      filters,
    )
      .then(setBowler)
      .catch((caught) => {
        console.error(caught);
        setError('Unable to load bowler analytics.');
      });
  }, [organizationId, bowlerId, from, to]);

  useEffect(() => {
    if (!batterId || !bowlerId) {
      setMatchup(null);
      return;
    }

    void getOpponentMatchup(
      organizationId,
      batterId,
      bowlerId,
      filters,
    )
      .then(setMatchup)
      .catch((caught) => {
        console.error(caught);
        setError('Unable to load batter-vs-bowler matchup.');
      });
  }, [organizationId, batterId, bowlerId, from, to]);

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <p className="eyebrow">P12 Deterministic Analytics</p>
          <h1>Opponent Intelligence</h1>
          <p>
            Historical opponent tendencies calculated directly from
            CricIntel's stored match and delivery data. No LLM
            recommendations are used.
          </p>
        </div>
      </div>

      {error && (
        <div className="form-error-message">{error}</div>
      )}

      <section className="profile-section">
        <div className="section-heading">
          <div>
            <h2>Analysis filters</h2>
            <p>
              Date filters affect every statistic and matchup shown below.
            </p>
          </div>
        </div>

        <div className="opponent-filter-grid">
          <label>
            Opponent team
            <select
              value={teamId || ''}
              onChange={(event) =>
                setParam('team_id', event.target.value)
              }
            >
              <option value="">Select team</option>
              {options.teams.map((team) => (
                <option key={team.id} value={team.id}>
                  {team.name}
                </option>
              ))}
            </select>
          </label>

          <label>
            From
            <input
              type="date"
              value={from}
              onChange={(event) =>
                setParam('from', event.target.value)
              }
            />
          </label>

          <label>
            To
            <input
              type="date"
              value={to}
              onChange={(event) =>
                setParam('to', event.target.value)
              }
            />
          </label>
        </div>
      </section>

      {teamProfile && (
        <>
          <div className="summary-grid">
            <article>
              <strong>{teamProfile.sample.matches}</strong>
              <span>Matches in sample</span>
            </article>
            <article>
              <strong>{teamProfile.sample.deliveries}</strong>
              <span>Recorded deliveries</span>
            </article>
            <article>
              <strong>{teamProfile.batters.length}</strong>
              <span>Observed batters</span>
            </article>
            <article>
              <strong>{teamProfile.bowlers.length}</strong>
              <span>Observed bowlers</span>
            </article>
          </div>

          <div className="sample-limit-banner">
            {teamProfile.sample.message}
          </div>

          <section className="profile-section">
            <div className="section-heading">
              <div>
                <h2>Opponent batters</h2>
                <p>
                  Select a batter for scoring zones, dismissals,
                  bowling-type splits and phase behavior.
                </p>
              </div>
            </div>

            <div className="opponent-player-grid">
              {teamProfile.batters.map((row) => (
                <button
                  type="button"
                  key={row.player.id}
                  className={
                    batterId === row.player.id
                      ? 'opponent-player-card selected'
                      : 'opponent-player-card'
                  }
                  onClick={() =>
                    setParam('batter_id', String(row.player.id))
                  }
                >
                  <strong>{row.player.name ?? `Player ${row.player.id}`}</strong>
                  <span>Runs {row.summary.runs}</span>
                  <span>SR {row.summary.strike_rate ?? '—'}</span>
                  <span>Dot {row.summary.dot_ball_percentage ?? '—'}%</span>
                </button>
              ))}
            </div>
          </section>

          <section className="profile-section">
            <div className="section-heading">
              <div>
                <h2>Opponent bowlers</h2>
                <p>
                  Select a bowler for phase economy, wicket rate,
                  handedness and line/length tendencies.
                </p>
              </div>
            </div>

            <div className="opponent-player-grid">
              {teamProfile.bowlers.map((row) => (
                <button
                  type="button"
                  key={row.player.id}
                  className={
                    bowlerId === row.player.id
                      ? 'opponent-player-card selected'
                      : 'opponent-player-card'
                  }
                  onClick={() =>
                    setParam('bowler_id', String(row.player.id))
                  }
                >
                  <strong>{row.player.name ?? `Player ${row.player.id}`}</strong>
                  <span>Wkts {row.summary.wickets}</span>
                  <span>Eco {row.summary.economy ?? '—'}</span>
                  <span>
                    Boundary {row.summary.boundary_conceded_percentage ?? '—'}%
                  </span>
                </button>
              ))}
            </div>
          </section>

          <section className="profile-section">
            <div className="section-heading">
              <div>
                <h2>Partnership intelligence</h2>
                <p>
                  Frequent pairs, partnership run rate and recorded
                  dismissal points.
                </p>
              </div>
            </div>

            <div className="table-wrap">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>Partnership</th>
                    <th>Innings together</th>
                    <th>Runs</th>
                    <th>Balls</th>
                    <th>Run rate</th>
                    <th>Dismissal points</th>
                  </tr>
                </thead>
                <tbody>
                  {teamProfile.partnerships.map((partnership) => (
                    <tr key={partnership.player_ids.join('-')}>
                      <td>
                        {partnership.players.map((p) => p.name).join(' + ')}
                      </td>
                      <td>{partnership.innings_together}</td>
                      <td>{partnership.runs}</td>
                      <td>{partnership.balls}</td>
                      <td>{partnership.run_rate ?? '—'}</td>
                      <td>
                        {partnership.dismissal_points.length
                          ? partnership.dismissal_points
                              .map((point) =>
                                `${point.over ?? '?'}.${point.ball ?? '?'} ${point.wicket_type ?? ''}`
                              )
                              .join(', ')
                          : '—'}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
        </>
      )}

      {batter && (
        <section className="profile-section">
          <div className="section-heading">
            <div>
              <h2>Batter profile — {batter.player.name}</h2>
              <p>
                Preferred scoring zones, dismissal patterns, bowling
                type matchups and phase behavior.
              </p>
            </div>
          </div>

          <SampleSizeNotice sample={batter.sample_size} />

          <div className="opponent-metric-grid">
            <article><strong>{batter.summary.strike_rate ?? '—'}</strong><span>Strike rate</span></article>
            <article><strong>{batter.summary.dot_ball_percentage ?? '—'}%</strong><span>Dot-ball %</span></article>
            <article><strong>{batter.summary.boundaries}</strong><span>Boundaries</span></article>
            <article><strong>{batter.summary.dismissals}</strong><span>Dismissals</span></article>
          </div>

          {batter.calculated_insights.length > 0 && (
            <div className="calculated-insights">
              {batter.calculated_insights.map((insight) => (
                <p key={insight}>{insight}</p>
              ))}
            </div>
          )}

          <div className="opponent-two-column">
            <div>
              <h3>Phase behavior</h3>
              <BattingPhaseChart phases={batter.phase_behavior} />
            </div>

            <div>
              <h3>Preferred scoring zones</h3>
              <ZoneHeatmap zones={batter.preferred_scoring_zones} />
            </div>
          </div>

          <h3>Vs bowling type</h3>
          <div className="table-wrap">
            <table className="data-table">
              <thead>
                <tr>
                  <th>Type</th>
                  <th>Runs</th>
                  <th>Balls</th>
                  <th>Strike rate</th>
                  <th>Dismissals</th>
                  <th>Sample</th>
                </tr>
              </thead>
              <tbody>
                {Object.entries(batter.vs_bowling_type).map(([type, row]) => (
                  <tr key={type}>
                    <td>{type.replaceAll('_', ' ')}</td>
                    <td>{row.runs}</td>
                    <td>{row.balls}</td>
                    <td>{row.strike_rate ?? '—'}</td>
                    <td>{row.dismissals}</td>
                    <td>{row.sample_size.level}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <h3>Dismissal patterns</h3>
          <div className="opponent-pattern-list">
            {batter.dismissal_patterns.map((pattern) => (
              <article
                key={`${pattern.wicket_type}-${pattern.bowling_category}`}
              >
                <strong>{pattern.count}</strong>
                <span>{pattern.wicket_type}</span>
                <small>{pattern.bowling_category.replaceAll('_', ' ')}</small>
              </article>
            ))}
          </div>

          <Limitations items={batter.limitations} />
        </section>
      )}

      {bowler && (
        <section className="profile-section">
          <div className="section-heading">
            <div>
              <h2>Bowler profile — {bowler.player.name}</h2>
              <p>
                Phase economy, wickets, batter handedness, boundary rate
                and recorded line/length tendencies.
              </p>
            </div>
          </div>

          <SampleSizeNotice sample={bowler.sample_size} />

          <div className="opponent-metric-grid">
            <article><strong>{bowler.summary.economy ?? '—'}</strong><span>Economy</span></article>
            <article><strong>{bowler.summary.wickets}</strong><span>Wickets</span></article>
            <article><strong>{bowler.summary.wicket_rate_per_100_balls ?? '—'}</strong><span>Wkts / 100 balls</span></article>
            <article><strong>{bowler.summary.boundary_conceded_percentage ?? '—'}%</strong><span>Boundary conceded %</span></article>
          </div>

          <BowlingPhaseChart phases={bowler.economy_by_phase} />

          <h3>Vs batter handedness</h3>
          <div className="opponent-pattern-list">
            {Object.entries(bowler.vs_batter_handedness).map(([hand, row]) => (
              <article key={hand}>
                <strong>{row.economy ?? '—'}</strong>
                <span>{hand}-hand batters</span>
                <small>{row.wickets} wickets · {row.balls} balls</small>
              </article>
            ))}
          </div>

          <h3>Recorded line / length tendencies</h3>
          <div className="table-wrap">
            <table className="data-table">
              <thead>
                <tr>
                  <th>Delivery type</th>
                  <th>Pitch zone</th>
                  <th>Balls</th>
                  <th>Runs conceded</th>
                  <th>Wickets</th>
                </tr>
              </thead>
              <tbody>
                {bowler.length_line_tendencies.map((row) => (
                  <tr key={`${row.delivery_type}-${row.pitch_zone}`}>
                    <td>{row.delivery_type}</td>
                    <td>{row.pitch_zone}</td>
                    <td>{row.balls}</td>
                    <td>{row.runs_conceded}</td>
                    <td>{row.wickets}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <Limitations items={bowler.limitations} />
        </section>
      )}

      <section className="profile-section">
        <div className="section-heading">
          <div>
            <h2>Batter vs bowler matchup</h2>
            <p>
              Choose any stored batter and bowler. The result is computed
              only from deliveries where they directly faced each other.
            </p>
          </div>
        </div>

        <div className="opponent-filter-grid matchup">
          <label>
            Batter
            <select
              value={batterId || ''}
              onChange={(event) =>
                setParam('batter_id', event.target.value)
              }
            >
              <option value="">Select batter</option>
              {options.players.map((player) => (
                <option key={player.id} value={player.id}>
                  {player.display_name ??
                    [player.first_name, player.last_name]
                      .filter(Boolean)
                      .join(' ')}
                </option>
              ))}
            </select>
          </label>

          <label>
            Bowler
            <select
              value={bowlerId || ''}
              onChange={(event) =>
                setParam('bowler_id', event.target.value)
              }
            >
              <option value="">Select bowler</option>
              {options.players.map((player) => (
                <option key={player.id} value={player.id}>
                  {player.display_name ??
                    [player.first_name, player.last_name]
                      .filter(Boolean)
                      .join(' ')}
                </option>
              ))}
            </select>
          </label>
        </div>

        {matchup && (
          <>
            <SampleSizeNotice sample={matchup.sample_size} />

            <div className="opponent-metric-grid">
              <article><strong>{matchup.runs}</strong><span>Runs</span></article>
              <article><strong>{matchup.balls}</strong><span>Balls</span></article>
              <article><strong>{matchup.strike_rate ?? '—'}</strong><span>Strike rate</span></article>
              <article><strong>{matchup.dismissals}</strong><span>Dismissals</span></article>
              <article><strong>{matchup.boundaries}</strong><span>Boundaries</span></article>
              <article><strong>{matchup.dots}</strong><span>Dot balls</span></article>
            </div>

            <BattingPhaseChart phases={matchup.phase_split} />
            <Limitations items={matchup.limitations} />
          </>
        )}
      </section>
    </AppLayout>
  );
}

function Limitations({
  items,
}: {
  items: string[];
}) {
  return (
    <div className="analytics-limitations">
      <strong>Data limitations</strong>
      <ul>
        {items.map((item) => (
          <li key={item}>{item}</li>
        ))}
      </ul>
    </div>
  );
}
