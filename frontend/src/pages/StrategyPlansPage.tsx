import {
  useEffect,
  useMemo,
  useState,
  type FormEvent,
} from 'react';

import {
  Link,
  useParams,
} from 'react-router-dom';

import {
  createStrategyPlan,
  getStrategyOptions,
  listStrategyPlans,
} from '../api/strategy';

import {
  useAuth,
} from '../auth/AuthContext';

import {
  AppIcon,
} from '../components/AppIcon';

import {
  AppLayout,
} from '../components/AppLayout';

import type {
  StrategyOptions,
  StrategyPlan,
} from '../types/strategy';

export function StrategyPlansPage() {
  const organizationId =
    Number(useParams().organizationId);

  const {
    user,
  } = useAuth();

  const [
    plans,
    setPlans,
  ] = useState<StrategyPlan[]>([]);

  const [
    options,
    setOptions,
  ] = useState<StrategyOptions>({
    matches: [],
    teams: [],
    venues: [],
    players: [],
    collaborators: [],
    scouting_reports: [],
    scouting_media: [],
  });

  const [
    query,
    setQuery,
  ] = useState('');

  const [
    status,
    setStatus,
  ] = useState('');

  const [
    error,
    setError,
  ] = useState('');

  const [
    message,
    setMessage,
  ] = useState('');

  const [
    createOpen,
    setCreateOpen,
  ] = useState(false);

  const roles =
    user?.roles ?? [];

  const canManage =
    roles.includes('Administrator') ||
    roles.includes('Coach');

  const load =
    async () => {
      setError('');

      try {
        const [
          planRows,
          optionRows,
        ] =
          await Promise.all([
            listStrategyPlans(
              organizationId,
              {
                q:
                  query ||
                  undefined,
                status:
                  status ||
                  undefined,
              },
            ),
            getStrategyOptions(
              organizationId,
            ),
          ]);

        setPlans(planRows);
        setOptions(optionRows);
      } catch (caught) {
        console.error(caught);
        setError(
          'Unable to load tactical strategy plans.',
        );
      }
    };

  useEffect(() => {
    void load();
  }, [
    organizationId,
    query,
    status,
  ]);

  const matchById =
    useMemo(
      () =>
        new Map(
          options.matches.map(
            (match) => [
              match.id,
              match,
            ],
          ),
        ),
      [options.matches],
    );

  const submitPlan =
    async (
      event:
        FormEvent<HTMLFormElement>,
    ) => {
      event.preventDefault();
      setError('');
      setMessage('');

      const form =
        event.currentTarget;

      const data =
        new FormData(form);

      try {
        await createStrategyPlan(
          organizationId,
          {
            match_id:
              Number(
                data.get('match_id'),
              ),
            opponent_team_id:
              data.get(
                'opponent_team_id',
              )
                ? Number(
                    data.get(
                      'opponent_team_id',
                    ),
                  )
                : null,
            venue_id:
              data.get('venue_id')
                ? Number(
                    data.get(
                      'venue_id',
                    ),
                  )
                : null,
            title:
              data.get('title'),
            status:
              data.get('status'),
            summary:
              data.get('summary') ||
              null,
          },
        );

        form.reset();
        setCreateOpen(false);
        setMessage(
          'Strategy plan created.',
        );
        await load();
      } catch (caught: any) {
        setError(
          caught?.response?.data
            ?.message ??
            'Unable to create strategy plan.',
        );
      }
    };

  return (
    <AppLayout>
      <div className="page-heading strategy-page-heading">
        <div>
          <p className="eyebrow">
            P13 Tactical Planning
          </p>

          <h1>
            Strategy Workspace
          </h1>

          <p>
            Jira-style cricket tactical
            planning for coaches,
            analysts and selectors.
          </p>
        </div>

        {canManage && (
          <button
            type="button"
            onClick={() =>
              setCreateOpen(
                (current) =>
                  !current,
              )
            }
          >
            <AppIcon
              name="plus"
              size={17}
            />

            New strategy plan
          </button>
        )}
      </div>

      {error && (
        <div className="form-error-message">
          {error}
        </div>
      )}

      {message && (
        <div className="form-success-message">
          {message}
        </div>
      )}

      <section className="strategy-summary-strip">
        <article>
          <strong>
            {plans.length}
          </strong>
          <span>
            Strategy plans
          </span>
        </article>

        <article>
          <strong>
            {
              plans.filter(
                (plan) =>
                  plan.status ===
                  'Active',
              ).length
            }
          </strong>
          <span>
            Active
          </span>
        </article>

        <article>
          <strong>
            {
              plans.reduce(
                (
                  total,
                  plan,
                ) =>
                  total +
                  (plan.notes_count ??
                    0),
                0,
              )
            }
          </strong>
          <span>
            Discussions
          </span>
        </article>

        <article>
          <strong>
            {
              plans.reduce(
                (
                  total,
                  plan,
                ) =>
                  total +
                  (plan.assignments_count ??
                    0),
                0,
              )
            }
          </strong>
          <span>
            Assignments
          </span>
        </article>
      </section>

      {createOpen &&
        canManage && (
        <section className="strategy-create-panel">
          <div className="section-heading">
            <div>
              <h2>
                Create match strategy
              </h2>

              <p>
                One tactical workspace is
                maintained for each match.
              </p>
            </div>
          </div>

          <form
            onSubmit={(event) =>
              void submitPlan(event)
            }
          >
            <div className="form-grid">
              <label>
                Match
                <select
                  name="match_id"
                  required
                >
                  <option value="">
                    Select match
                  </option>

                  {options.matches.map(
                    (match) => (
                      <option
                        key={match.id}
                        value={match.id}
                      >
                        {
                          match.home_team_name
                        }
                        {' vs '}
                        {
                          match.away_team_name
                        }
                        {' · '}
                        {String(
                          match.scheduled_at,
                        ).slice(
                          0,
                          10,
                        )}
                      </option>
                    ),
                  )}
                </select>
              </label>

              <label>
                Opponent
                <select
                  name="opponent_team_id"
                >
                  <option value="">
                    Select opponent
                  </option>

                  {options.teams.map(
                    (team) => (
                      <option
                        key={team.id}
                        value={team.id}
                      >
                        {team.name}
                      </option>
                    ),
                  )}
                </select>
              </label>
            </div>

            <div className="form-grid">
              <label>
                Venue
                <select
                  name="venue_id"
                >
                  <option value="">
                    Use match venue
                  </option>

                  {options.venues.map(
                    (venue) => (
                      <option
                        key={venue.id}
                        value={venue.id}
                      >
                        {venue.name}
                      </option>
                    ),
                  )}
                </select>
              </label>

              <label>
                Status
                <select
                  name="status"
                  defaultValue="Draft"
                >
                  <option>
                    Draft
                  </option>
                  <option>
                    Active
                  </option>
                  <option>
                    Archived
                  </option>
                </select>
              </label>
            </div>

            <label>
              Plan title
              <input
                name="title"
                required
                placeholder="Sri Lanka vs Australia — Match Strategy"
              />
            </label>

            <label>
              Summary
              <textarea
                name="summary"
                placeholder="High-level tactical objective for this match..."
              />
            </label>

            <div className="form-actions">
              <button
                type="button"
                className="secondary"
                onClick={() =>
                  setCreateOpen(false)
                }
              >
                Cancel
              </button>

              <button type="submit">
                Create workspace
              </button>
            </div>
          </form>
        </section>
      )}

      <section className="strategy-list-section">
        <div className="strategy-toolbar">
          <div className="strategy-search-control">
            <AppIcon
              name="search"
              size={17}
            />

            <input
              value={query}
              onChange={(event) =>
                setQuery(
                  event.target.value,
                )
              }
              placeholder="Search strategy plans..."
            />
          </div>

          <select
            value={status}
            onChange={(event) =>
              setStatus(
                event.target.value,
              )
            }
          >
            <option value="">
              All statuses
            </option>
            <option>
              Draft
            </option>
            <option>
              Active
            </option>
            <option>
              Archived
            </option>
          </select>
        </div>

        <div className="strategy-plan-grid">
          {plans.map(
            (plan) => {
              const match =
                matchById.get(
                  plan.match_id,
                );

              return (
                <Link
                  key={plan.id}
                  to={`/organizations/${organizationId}/strategy/${plan.id}`}
                  className="strategy-plan-card"
                >
                  <div className="strategy-plan-card-top">
                    <span className={`strategy-status ${plan.status.toLowerCase()}`}>
                      {plan.status}
                    </span>

                    {plan.locked_at && (
                      <span className="strategy-lock-badge">
                        Locked
                      </span>
                    )}
                  </div>

                  <h2>
                    {plan.title}
                  </h2>

                  <p>
                    {plan.summary ??
                      'No strategy summary has been added yet.'}
                  </p>

                  <div className="strategy-plan-context">
                    <span>
                      <AppIcon
                        name="teams"
                        size={15}
                      />

                      {plan.opponent_team
                        ?.name ??
                        'Opponent not set'}
                    </span>

                    <span>
                      <AppIcon
                        name="venues"
                        size={15}
                      />

                      {plan.venue
                        ?.name ??
                        match
                          ?.venue_name ??
                        'Venue not set'}
                    </span>

                    <span>
                      <AppIcon
                        name="calendar"
                        size={15}
                      />

                      {match
                        ? String(
                            match.scheduled_at,
                          ).slice(
                            0,
                            10,
                          )
                        : `Match #${plan.match_id}`}
                    </span>
                  </div>

                  <div className="strategy-plan-footer">
                    <span>
                      {
                        plan.notes_count ??
                        0
                      }
                      {' '}
                      discussions
                    </span>

                    <span>
                      {
                        plan.assignments_count ??
                        0
                      }
                      {' '}
                      assignments
                    </span>

                    <AppIcon
                      name="arrowRight"
                      size={17}
                    />
                  </div>
                </Link>
              );
            },
          )}

          {plans.length === 0 && (
            <div className="strategy-empty-card">
              <AppIcon
                name="strategy"
                size={30}
              />

              <strong>
                No strategy plans yet
              </strong>

              <span>
                Create a tactical workspace
                for an upcoming match.
              </span>
            </div>
          )}
        </div>
      </section>
    </AppLayout>
  );
}
