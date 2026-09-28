import {
  useEffect,
  useMemo,
  useState,
} from 'react';

import {
  Link,
  useParams,
} from 'react-router-dom';

import axios from 'axios';

import {
  createMatch,
  listMatches,
  startMatch,
} from '../api/matches';

import {
  api,
} from '../api/client';

import {
  AppLayout,
} from '../components/AppLayout';

import type {
  ApiResponse,
} from '../types/auth';

import type {
  MatchListItem,
} from '../types/match';

/*
|--------------------------------------------------------------------------
| Local Fixture Type
|--------------------------------------------------------------------------
*/

interface FixtureOption {
  id: number;

  home_team_id: number;

  away_team_id: number;

  scheduled_at?:
    string | null;

  match_number?:
    number | null;

  home_team?: {
    id: number;

    name: string;

    short_name?:
      string | null;
  };

  away_team?: {
    id: number;

    name: string;

    short_name?:
      string | null;
  };
}

/*
|--------------------------------------------------------------------------
| Paginated Fixture Response
|--------------------------------------------------------------------------
*/

interface FixturePagination {
  data: FixtureOption[];

  current_page?: number;

  last_page?: number;

  per_page?: number;

  total?: number;
}

/*
|--------------------------------------------------------------------------
| Matches Page
|--------------------------------------------------------------------------
*/

export function MatchesPage() {
  const organizationId =
    Number(
      useParams()
        .organizationId
    );

  /*
  |--------------------------------------------------------------------------
  | State
  |--------------------------------------------------------------------------
  */

  const [
    matches,
    setMatches,
  ] =
    useState<
      MatchListItem[]
    >([]);

  const [
    fixtures,
    setFixtures,
  ] =
    useState<
      FixtureOption[]
    >([]);

  const [
    loading,
    setLoading,
  ] =
    useState(true);

  const [
    creating,
    setCreating,
  ] =
    useState(false);

  const [
    startingMatchId,
    setStartingMatchId,
  ] =
    useState<
      number | null
    >(null);

  const [
    error,
    setError,
  ] =
    useState('');

  const [
    success,
    setSuccess,
  ] =
    useState('');

  /*
  |--------------------------------------------------------------------------
  | Create Form
  |--------------------------------------------------------------------------
  */

  const [
    selectedFixtureId,
    setSelectedFixtureId,
  ] =
    useState<
      number | ''
    >('');

  const [
    tossWinnerId,
    setTossWinnerId,
  ] =
    useState<
      number | ''
    >('');

  const [
    tossDecision,
    setTossDecision,
  ] =
    useState<
      'bat' |
      'bowl' |
      ''
    >('');

  const [
    maxOvers,
    setMaxOvers,
  ] =
    useState('20');

  /*
  |--------------------------------------------------------------------------
  | Load Fixtures
  |--------------------------------------------------------------------------
  |
  | IMPORTANT:
  |
  | The fixtures endpoint is paginated.
  |
  | Response shape:
  |
  | {
  |   success: true,
  |   data: {
  |     data: [...]
  |   }
  | }
  |
  */

  const loadFixtures =
    async (): Promise<
      FixtureOption[]
    > => {
      const response =
        await api.get<
          ApiResponse<
            FixturePagination
          >
        >(
          `/api/v1/organizations/${organizationId}/fixtures`,
          {
            params: {
              per_page: 100,

              sort:
                'scheduled_at',

              direction:
                'asc',
            },
          }
        );

      const paginated =
        response.data.data;

      if (
        !paginated
      ) {
        return [];
      }

      if (
        !Array.isArray(
          paginated.data
        )
      ) {
        console.error(
          'Unexpected fixture API response:',
          response.data
        );

        return [];
      }

      return paginated.data;
    };

  /*
  |--------------------------------------------------------------------------
  | Load Page
  |--------------------------------------------------------------------------
  */

  const loadData =
    async () => {
      setLoading(
        true
      );

      setError('');

      try {
        const [
          matchData,
          fixtureData,
        ] =
          await Promise.all([
            listMatches(
              organizationId
            ),

            loadFixtures(),
          ]);

        /*
        |--------------------------------------------------------------------------
        | listMatches already returns MatchListItem[]
        |--------------------------------------------------------------------------
        */

        setMatches(
          Array.isArray(
            matchData
          )
            ? matchData
            : []
        );

        /*
        |--------------------------------------------------------------------------
        | loadFixtures always returns FixtureOption[]
        |--------------------------------------------------------------------------
        */

        setFixtures(
          Array.isArray(
            fixtureData
          )
            ? fixtureData
            : []
        );
      } catch (
        caught
      ) {
        console.error(
          'Unable to load matches:',
          caught
        );

        setMatches(
          []
        );

        setFixtures(
          []
        );

        setError(
          'Unable to load matches.'
        );
      } finally {
        setLoading(
          false
        );
      }
    };

  useEffect(() => {
    if (
      !organizationId
    ) {
      return;
    }

    void loadData();
  }, [
    organizationId,
  ]);

  /*
  |--------------------------------------------------------------------------
  | Existing Fixture IDs
  |--------------------------------------------------------------------------
  */

  const usedFixtureIds =
    useMemo(
      () =>
        new Set(
          (
            Array.isArray(
              matches
            )
              ? matches
              : []
          ).map(
            (
              match
            ) =>
              match.fixture_id
          )
        ),
      [
        matches,
      ]
    );

  /*
  |--------------------------------------------------------------------------
  | Fixtures Available For Match Creation
  |--------------------------------------------------------------------------
  */

  const availableFixtures =
    useMemo(
      () => {
        if (
          !Array.isArray(
            fixtures
          )
        ) {
          return [];
        }

        return fixtures.filter(
          (
            fixture
          ) =>
            !usedFixtureIds.has(
              fixture.id
            )
        );
      },
      [
        fixtures,
        usedFixtureIds,
      ]
    );

  /*
  |--------------------------------------------------------------------------
  | Selected Fixture
  |--------------------------------------------------------------------------
  */

  const selectedFixture =
    useMemo(
      () => {
        if (
          !Array.isArray(
            fixtures
          )
        ) {
          return null;
        }

        return (
          fixtures.find(
            (
              fixture
            ) =>
              fixture.id ===
              selectedFixtureId
          ) ??
          null
        );
      },
      [
        fixtures,
        selectedFixtureId,
      ]
    );

  /*
  |--------------------------------------------------------------------------
  | Team Name Helper
  |--------------------------------------------------------------------------
  */

  const teamName =
    (
      fixture:
        FixtureOption |
        undefined |
        null,

      side:
        'home' |
        'away'
    ) => {
      if (
        !fixture
      ) {
        return '—';
      }

      if (
        side ===
        'home'
      ) {
        return (
          fixture
            .home_team
            ?.name ??
          `Team ${fixture.home_team_id}`
        );
      }

      return (
        fixture
          .away_team
          ?.name ??
        `Team ${fixture.away_team_id}`
      );
    };

  /*
  |--------------------------------------------------------------------------
  | Fixture Label
  |--------------------------------------------------------------------------
  */

  const fixtureLabel =
    (
      fixture:
        FixtureOption |
        undefined |
        null
    ) => {
      if (
        !fixture
      ) {
        return 'Fixture';
      }

      return `${teamName(
        fixture,
        'home'
      )} vs ${teamName(
        fixture,
        'away'
      )}`;
    };

  /*
  |--------------------------------------------------------------------------
  | Find Fixture For Match
  |--------------------------------------------------------------------------
  */

  const findFixture =
    (
      match:
        MatchListItem
    ):
      FixtureOption |
      undefined => {

      if (
        match.fixture
      ) {
        return {
          id:
            match.fixture.id,

          home_team_id:
            match.fixture
              .home_team_id,

          away_team_id:
            match.fixture
              .away_team_id,

          scheduled_at:
            match.fixture
              .scheduled_at,

          match_number:
            match.fixture
              .match_number,

          home_team:
            match.fixture
              .home_team,

          away_team:
            match.fixture
              .away_team,
        };
      }

      if (
        !Array.isArray(
          fixtures
        )
      ) {
        return undefined;
      }

      return fixtures.find(
        (
          fixture
        ) =>
          fixture.id ===
          match.fixture_id
      );
    };

  /*
  |--------------------------------------------------------------------------
  | Create Match
  |--------------------------------------------------------------------------
  */

  const handleCreateMatch =
    async (
      event:
        React.FormEvent
    ) => {
      event.preventDefault();

      setError('');

      setSuccess('');

      if (
        !selectedFixtureId
      ) {
        setError(
          'Please select a fixture.'
        );

        return;
      }

      setCreating(
        true
      );

      try {
        const payload:
          Record<
            string,
            unknown
          > =
        {
          fixture_id:
            Number(
              selectedFixtureId
            ),

          max_overs:
            maxOvers
              ? Number(
                  maxOvers
                )
              : null,

          toss_winner_id:
            tossWinnerId
              ? Number(
                  tossWinnerId
                )
              : null,

          toss_decision:
            tossDecision ||
            null,
        };

        await createMatch(
          organizationId,
          payload
        );

        setSuccess(
          'Match created successfully.'
        );

        setSelectedFixtureId(
          ''
        );

        setTossWinnerId(
          ''
        );

        setTossDecision(
          ''
        );

        setMaxOvers(
          '20'
        );

        await loadData();
      } catch (
        caught
      ) {
        console.error(
          caught
        );

        if (
          axios.isAxiosError(
            caught
          )
        ) {
          const data =
            caught.response
              ?.data;

          const validationErrors =
            data?.errors;

          const firstError =
            validationErrors
              ? Object.values(
                  validationErrors
                )
                  .flat()
                  .at(0)
              : null;

          setError(
            String(
              firstError ??
                data
                  ?.message ??
                'Unable to create match.'
            )
          );
        } else {
          setError(
            'Unable to create match.'
          );
        }
      } finally {
        setCreating(
          false
        );
      }
    };

  /*
  |--------------------------------------------------------------------------
  | Start Match
  |--------------------------------------------------------------------------
  */

  const handleStartMatch =
    async (
      matchId: number
    ) => {
      setError('');

      setSuccess('');

      setStartingMatchId(
        matchId
      );

      try {
        await startMatch(
          organizationId,
          matchId
        );

        setSuccess(
          'Match started successfully.'
        );

        await loadData();
      } catch (
        caught
      ) {
        console.error(
          caught
        );

        if (
          axios.isAxiosError(
            caught
          )
        ) {
          setError(
            caught.response
              ?.data
              ?.message ??
              'Unable to start match.'
          );
        } else {
          setError(
            'Unable to start match.'
          );
        }
      } finally {
        setStartingMatchId(
          null
        );
      }
    };

  /*
  |--------------------------------------------------------------------------
  | Render
  |--------------------------------------------------------------------------
  */

  return (
    <AppLayout>

      <div className="page-heading">

        <div>

          <p className="eyebrow">
            Match Operations
          </p>

          <h1>
            Matches
          </h1>

          <p>
            Create matches,
            operate ball-by-ball
            scoring, monitor the
            real-time Match Centre
            and view scorecards.
          </p>

        </div>

      </div>

      {error && (
        <div className="form-error-message">
          {error}
        </div>
      )}

      {success && (
        <div className="form-success-message">
          {success}
        </div>
      )}

      {/* =====================================================
          CREATE MATCH
          ===================================================== */}

      <section className="form-card">

        <div className="section-heading">

          <div>

            <h2>
              Create Match
            </h2>

            <p>
              Create a CricIntel
              match from an existing
              fixture.
            </p>

          </div>

        </div>

        <form
          onSubmit={
            handleCreateMatch
          }
        >

          <div className="form-grid">

            {/* Fixture */}

            <label>

              Fixture

              <select
                value={
                  selectedFixtureId
                }
                onChange={(
                  event
                ) => {
                  const value =
                    event
                      .target
                      .value;

                  setSelectedFixtureId(
                    value
                      ? Number(
                          value
                        )
                      : ''
                  );

                  setTossWinnerId(
                    ''
                  );
                }}
              >

                <option value="">
                  Select fixture
                </option>

                {availableFixtures.map(
                  (
                    fixture
                  ) => (
                    <option
                      key={
                        fixture.id
                      }
                      value={
                        fixture.id
                      }
                    >
                      {
                        fixtureLabel(
                          fixture
                        )
                      }

                      {fixture
                        .scheduled_at
                        ? ` · ${fixture.scheduled_at}`
                        : ''}
                    </option>
                  )
                )}

              </select>

            </label>

            {/* Maximum Overs */}

            <label>

              Maximum Overs

              <input
                type="number"
                min="1"
                max="450"
                value={
                  maxOvers
                }
                onChange={(
                  event
                ) =>
                  setMaxOvers(
                    event
                      .target
                      .value
                  )
                }
              />

            </label>

            {/* Toss Winner */}

            <label>

              Toss Winner

              <select
                value={
                  tossWinnerId
                }
                disabled={
                  !selectedFixture
                }
                onChange={(
                  event
                ) =>
                  setTossWinnerId(
                    event
                      .target
                      .value
                      ? Number(
                          event
                            .target
                            .value
                        )
                      : ''
                  )
                }
              >

                <option value="">
                  Not decided
                </option>

                {selectedFixture && (
                  <>

                    <option
                      value={
                        selectedFixture
                          .home_team_id
                      }
                    >
                      {
                        teamName(
                          selectedFixture,
                          'home'
                        )
                      }
                    </option>

                    <option
                      value={
                        selectedFixture
                          .away_team_id
                      }
                    >
                      {
                        teamName(
                          selectedFixture,
                          'away'
                        )
                      }
                    </option>

                  </>
                )}

              </select>

            </label>

            {/* Toss Decision */}

            <label>

              Toss Decision

              <select
                value={
                  tossDecision
                }
                onChange={(
                  event
                ) =>
                  setTossDecision(
                    event
                      .target
                      .value as
                      | 'bat'
                      | 'bowl'
                      | ''
                  )
                }
              >

                <option value="">
                  Not decided
                </option>

                <option value="bat">
                  Bat
                </option>

                <option value="bowl">
                  Bowl
                </option>

              </select>

            </label>

          </div>

          <div className="form-actions">

            <button
              type="submit"
              disabled={
                creating ||
                !selectedFixtureId
              }
            >
              {creating
                ? 'Creating...'
                : 'Create Match'}
            </button>

          </div>

        </form>

      </section>

      {/* =====================================================
          MATCH LIST
          ===================================================== */}

      <section className="profile-section">

        <div className="section-heading">

          <div>

            <h2>
              Match Centre
            </h2>

            <p>
              Match scoring,
              live broadcasting and
              scorecards.
            </p>

          </div>

          <span className="status-badge">
            {matches.length}{' '}
            matches
          </span>

        </div>

        {loading ? (

          <div className="page-state">
            Loading matches...
          </div>

        ) : matches.length ===
          0 ? (

          <div className="page-state">
            No matches available.
          </div>

        ) : (

          <div className="table-wrap">

            <table className="data-table">

              <thead>

                <tr>

                  <th>
                    Match
                  </th>

                  <th>
                    Date
                  </th>

                  <th>
                    Overs
                  </th>

                  <th>
                    Target
                  </th>

                  <th>
                    Status
                  </th>

                  <th>
                    Result
                  </th>

                  <th>
                    Actions
                  </th>

                </tr>

              </thead>

              <tbody>

                {matches.map(
                  (
                    match
                  ) => {

                    const fixture =
                      findFixture(
                        match
                      );

                    return (

                      <tr
                        key={
                          match.id
                        }
                      >

                        <td>

                          <strong>
                            {
                              fixtureLabel(
                                fixture
                              )
                            }
                          </strong>

                          <div className="table-subtext">
                            Match #
                            {
                              match.id
                            }
                          </div>

                        </td>

                        <td>
                          {
                            fixture
                              ?.scheduled_at ??
                            '—'
                          }
                        </td>

                        <td>
                          {
                            match
                              .max_overs ??
                            '—'
                          }
                        </td>

                        <td>
                          {
                            match
                              .target_runs ??
                            '—'
                          }
                        </td>

                        <td>

                          <span className="status-badge">
                            {
                              match.status
                            }
                          </span>

                        </td>

                        <td>

                          {match.status ===
                          'Completed'
                            ? (
                              <>

                                <strong>
                                  {
                                    match
                                      .result_type ??
                                    'Completed'
                                  }
                                </strong>

                                {match
                                  .winner_team_id && (

                                  <div className="table-subtext">
                                    Winner team{' '}
                                    {
                                      match
                                        .winner_team_id
                                    }
                                  </div>

                                )}

                              </>
                            )
                            : '—'}

                        </td>

                        <td>

                          <div className="match-actions">

                            {match.status ===
                              'Scheduled' && (

                              <button
                                type="button"
                                disabled={
                                  startingMatchId ===
                                  match.id
                                }
                                onClick={() =>
                                  void handleStartMatch(
                                    match.id
                                  )
                                }
                              >
                                {
                                  startingMatchId ===
                                  match.id
                                    ? 'Starting...'
                                    : 'Start'
                                }
                              </button>

                            )}

                            {match.status !==
                              'Scheduled' &&
                              match.status !==
                                'Completed' && (

                                <Link
                                  className="button"
                                  to={
                                    `/organizations/${organizationId}/matches/${match.id}/operator`
                                  }
                                >
                                  Operator
                                </Link>

                              )}

                            <Link
                              className="button live-centre-button"
                              to={
                                `/organizations/${organizationId}/matches/${match.id}/live`
                              }
                            >
                              Live Centre
                            </Link>

                            <Link
                              className="button secondary"
                              to={
                                `/organizations/${organizationId}/matches/${match.id}/scorecard`
                              }
                            >
                              Scorecard
                            </Link>

                          </div>

                        </td>

                      </tr>

                    );
                  }
                )}

              </tbody>

            </table>

          </div>

        )}

      </section>

    </AppLayout>
  );
}