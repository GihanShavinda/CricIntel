import {
  Link,
  useParams,
} from 'react-router-dom';

import {
  AppLayout,
} from '../components/AppLayout';

import {
  useLiveMatch,
} from '../hooks/useLiveMatch';

import type {
  LiveDelivery,
} from '../types/liveMatch';

function deliveryLabel(
  delivery: LiveDelivery
) {
  if (delivery.wicket) {
    return 'W';
  }

  if (
    delivery.extra_type ===
    'wide'
  ) {
    return `${
      delivery.total_runs
    }wd`;
  }

  if (
    delivery.extra_type ===
    'no_ball'
  ) {
    return `${
      delivery.total_runs
    }nb`;
  }

  if (
    delivery.extra_type ===
    'bye'
  ) {
    return `${
      delivery.total_runs
    }b`;
  }

  if (
    delivery.extra_type ===
    'leg_bye'
  ) {
    return `${
      delivery.total_runs
    }lb`;
  }

  return String(
    delivery.total_runs
  );
}

function connectionLabel(
  state: string
) {
  return state.replace(
    '_',
    ' '
  );
}

export function LiveMatchCentrePage() {
  const organizationId =
    Number(
      useParams()
        .organizationId
    );

  const matchId =
    Number(
      useParams()
        .matchId
    );

  const {
    snapshot,
    loading,
    error,
    connectionState,
    refresh,
  } =
    useLiveMatch(
      organizationId,
      matchId
    );

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <p className="eyebrow">
            P8 Real-Time
          </p>

          <h1>
            Live Match Centre
          </h1>

          <p>
            Live deterministic
            match state delivered
            over Laravel Reverb.
          </p>
        </div>

        <div className="live-heading-actions">
          <span
            className={
              `connection-pill ${connectionState}`
            }
          >
            <span />
            {
              connectionLabel(
                connectionState
              )
            }
          </span>

          <Link
            className="analytics-heading-link"
            to={
              `/organizations/${organizationId}/matches/${matchId}/scorecard`
            }
          >
            Full scorecard
          </Link>
        </div>
      </div>

      {loading && (
        <div className="analytics-state">
          <div className="analytics-spinner" />
          <strong>
            Loading live match
          </strong>
        </div>
      )}

      {error && (
        <div className="analytics-state analytics-error">
          <strong>
            Unable to load match
          </strong>
          <span>
            {error}
          </span>
          <button
            type="button"
            onClick={() =>
              void refresh()
            }
          >
            Retry
          </button>
        </div>
      )}

      {!loading &&
        !error &&
        snapshot &&
        !snapshot.score && (
          <div className="analytics-state">
            <strong>
              Match has no active
              innings yet
            </strong>
            <span>
              Start the match and
              innings from the match
              operator.
            </span>
          </div>
        )}

      {snapshot?.score && (
        <>
          <section className="live-match-scoreboard">
            <div className="live-primary-score">
              <span>
                Live Score
              </span>

              <strong>
                {
                  snapshot.score
                    .runs
                }
                /
                {
                  snapshot.score
                    .wickets
                }
              </strong>

              <small>
                {
                  snapshot.score
                    .overs
                }{' '}
                overs
              </small>
            </div>

            <LiveMetric
              label="Run Rate"
              value={
                snapshot.run_rate
                  .toFixed(2)
              }
            />

            <LiveMetric
              label="Required RR"
              value={
                snapshot.required_run_rate ===
                null
                  ? '—'
                  : snapshot.required_run_rate.toFixed(
                      2
                    )
              }
            />

            <LiveMetric
              label="Partnership"
              value={
                `${snapshot.partnership.runs} (${snapshot.partnership.balls})`
              }
            />

            <LiveMetric
              label="Last 5 Overs"
              value={
                `${snapshot.last_five_overs.runs}/${snapshot.last_five_overs.wickets}`
              }
            />

            <LiveMetric
              label="Target"
              value={
                snapshot.target ??
                '—'
              }
            />
          </section>

          <div className="live-match-grid">
            <section className="live-panel">
              <div className="live-panel-heading">
                <div>
                  <h2>
                    At the Crease
                  </h2>
                  <p>
                    Current batter
                    and bowler
                  </p>
                </div>
              </div>

              <div className="current-players-grid">
                <article>
                  <span>
                    Batter
                  </span>
                  <strong>
                    {
                      snapshot
                        .current_batter
                        ?.name ??
                      'Awaiting batter'
                    }
                  </strong>
                </article>

                <article>
                  <span>
                    Bowler
                  </span>
                  <strong>
                    {
                      snapshot
                        .current_bowler
                        ?.name ??
                      'Awaiting bowler'
                    }
                  </strong>
                </article>
              </div>
            </section>

            <section className="live-panel">
              <div className="live-panel-heading">
                <div>
                  <h2>
                    Last Over
                  </h2>
                  <p>
                    Delivery-by-delivery
                  </p>
                </div>
              </div>

              {snapshot.last_over
                .length ? (
                <div className="delivery-ball-strip">
                  {snapshot.last_over.map(
                    (
                      delivery
                    ) => (
                      <span
                        key={
                          delivery.id
                        }
                        className={
                          delivery.wicket
                            ? 'wicket'
                            : ''
                        }
                      >
                        {
                          deliveryLabel(
                            delivery
                          )
                        }
                      </span>
                    )
                  )}
                </div>
              ) : (
                <p className="muted">
                  No deliveries yet.
                </p>
              )}
            </section>

            <section className="live-panel live-panel-wide">
              <div className="live-panel-heading">
                <div>
                  <h2>
                    Recent Deliveries
                  </h2>
                  <p>
                    Latest live events
                  </p>
                </div>
              </div>

              {snapshot
                .recent_deliveries
                .length ? (
                <div className="recent-delivery-list">
                  {snapshot.recent_deliveries.map(
                    (
                      delivery
                    ) => (
                      <article
                        key={
                          delivery.id
                        }
                      >
                        <span className="delivery-result">
                          {
                            deliveryLabel(
                              delivery
                            )
                          }
                        </span>

                        <div>
                          <strong>
                            {
                              delivery.bowler_name ??
                              'Bowler'
                            }
                            {' → '}
                            {
                              delivery.batter_name ??
                              'Batter'
                            }
                          </strong>

                          <small>
                            {
                              delivery.wicket
                                ? `Wicket${
                                    delivery.wicket_type
                                      ? ` · ${delivery.wicket_type}`
                                      : ''
                                  }`
                                : delivery.extra_type !==
                                    'none'
                                  ? delivery.extra_type.replace(
                                      '_',
                                      ' '
                                    )
                                  : `${delivery.total_runs} run${
                                      delivery.total_runs ===
                                      1
                                        ? ''
                                        : 's'
                                    }`
                            }
                          </small>
                        </div>
                      </article>
                    )
                  )}
                </div>
              ) : (
                <p className="muted">
                  No recent
                  deliveries.
                </p>
              )}
            </section>

            <section className="live-panel live-panel-wide">
              <div className="live-panel-heading">
                <div>
                  <h2>
                    Live Scorecard
                  </h2>
                  <p>
                    Current match
                    innings
                  </p>
                </div>
              </div>

              <div className="analytics-table-wrap">
                <table className="analytics-table">
                  <thead>
                    <tr>
                      <th>
                        Innings
                      </th>
                      <th>
                        Team
                      </th>
                      <th>
                        Score
                      </th>
                      <th>
                        Overs
                      </th>
                      <th>
                        Status
                      </th>
                    </tr>
                  </thead>

                  <tbody>
                    {snapshot.innings.map(
                      (
                        innings
                      ) => (
                        <tr
                          key={
                            innings.id
                          }
                        >
                          <td>
                            {
                              innings.innings_number
                            }
                          </td>

                          <td>
                            Team{' '}
                            {
                              innings.batting_team_id
                            }
                          </td>

                          <td>
                            {
                              innings.runs
                            }
                            /
                            {
                              innings.wickets
                            }
                          </td>

                          <td>
                            {
                              innings.overs
                            }
                          </td>

                          <td>
                            {
                              innings.status
                            }
                          </td>
                        </tr>
                      )
                    )}
                  </tbody>
                </table>
              </div>
            </section>
          </div>

          {snapshot.status ===
            'Completed' && (
            <div className="match-result-banner">
              Match completed
              {snapshot.winner_team_id
                ? ` · Winner team ${snapshot.winner_team_id}`
                : ''}
              {snapshot.result_type
                ? ` · ${snapshot.result_type}`
                : ''}
            </div>
          )}
        </>
      )}
    </AppLayout>
  );
}

function LiveMetric({
  label,
  value,
}: {
  label: string;
  value:
    | string
    | number;
}) {
  return (
    <article className="live-metric-card">
      <span>
        {label}
      </span>

      <strong>
        {value}
      </strong>
    </article>
  );
}
