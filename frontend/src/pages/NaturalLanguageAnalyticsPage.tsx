import {
  useEffect,
  useMemo,
  useState,
  type FormEvent,
} from 'react';

import {
  useParams,
} from 'react-router-dom';

import {
  getNaturalLanguageAnalyticsHistory,
  getNaturalLanguageAnalyticsOptions,
  parseNaturalLanguageAnalytics,
  runNaturalLanguageAnalytics,
  type NaturalLanguageAnalyticsPayload,
} from '../api/nlAnalytics';

import {
  AppIcon,
} from '../components/AppIcon';

import {
  AppLayout,
} from '../components/AppLayout';

import {
  AnalyticsResultChart,
} from '../components/nlAnalytics/AnalyticsResultChart';

import type {
  AnalyticsOptions,
  ControlledAnalyticsQuery,
  NaturalLanguageAnalyticsResponse,
  NlAnalyticsHistoryRow,
} from '../types/nlAnalytics';

const EMPTY_OPTIONS: AnalyticsOptions = {
  supported_intents: [],
  filters: {
    teams: [],
    seasons: [],
    venues: [],
    formats: [],
    players: [],
  },
  controlled_schema: {
    intents: [],
    metrics: [],
    entities: [],
    phases: [],
    batting_hands: [],
  },
  security: {
    generated_sql_allowed: false,
    arbitrary_sql_allowed: false,
    execution_mode: '',
  },
};

export function NaturalLanguageAnalyticsPage() {
  const organizationId =
    Number(
      useParams().organizationId,
    );

  const [
    options,
    setOptions,
  ] = useState<AnalyticsOptions>(
    EMPTY_OPTIONS,
  );

  const [
    history,
    setHistory,
  ] = useState<
    NlAnalyticsHistoryRow[]
  >([]);

  const [
    query,
    setQuery,
  ] = useState('');

  const [
    teamId,
    setTeamId,
  ] = useState('');

  const [
    seasonId,
    setSeasonId,
  ] = useState('');

  const [
    opponentTeamId,
    setOpponentTeamId,
  ] = useState('');

  const [
    venueId,
    setVenueId,
  ] = useState('');

  const [
    format,
    setFormat,
  ] = useState('');

  const [
    dateFrom,
    setDateFrom,
  ] = useState('');

  const [
    dateTo,
    setDateTo,
  ] = useState('');

  const [
    parsedQuery,
    setParsedQuery,
  ] =
    useState<ControlledAnalyticsQuery | null>(
      null,
    );

  const [
    result,
    setResult,
  ] =
    useState<NaturalLanguageAnalyticsResponse | null>(
      null,
    );

  const [
    loading,
    setLoading,
  ] = useState(false);

  const [
    parsing,
    setParsing,
  ] = useState(false);

  const [
    error,
    setError,
  ] = useState('');

  const [
    message,
    setMessage,
  ] = useState('');

  const loadBase =
    async () => {
      setError('');

      try {
        const [
          optionRows,
          historyRows,
        ] =
          await Promise.all([
            getNaturalLanguageAnalyticsOptions(
              organizationId,
            ),
            getNaturalLanguageAnalyticsHistory(
              organizationId,
            ),
          ]);

        setOptions(optionRows);
        setHistory(historyRows);
      } catch (caught: any) {
        setError(
          caught?.response?.data
            ?.message ??
            'Unable to load natural-language analytics.',
        );
      }
    };

  useEffect(() => {
    void loadBase();
  }, [organizationId]);

  const payload =
    useMemo<
      NaturalLanguageAnalyticsPayload
    >(
      () => ({
        query,
        team_id:
          teamId
            ? Number(teamId)
            : undefined,
        season_id:
          seasonId
            ? Number(seasonId)
            : undefined,
        opponent_team_id:
          opponentTeamId
            ? Number(
                opponentTeamId,
              )
            : undefined,
        venue_id:
          venueId
            ? Number(venueId)
            : undefined,
        format:
          format ||
          undefined,
        date_from:
          dateFrom ||
          undefined,
        date_to:
          dateTo ||
          undefined,
      }),
      [
        query,
        teamId,
        seasonId,
        opponentTeamId,
        venueId,
        format,
        dateFrom,
        dateTo,
      ],
    );

  const handlePreview =
    async () => {
      if (!query.trim()) {
        setError(
          'Enter an analytics question first.',
        );
        return;
      }

      setParsing(true);
      setError('');
      setMessage('');

      try {
        const response =
          await parseNaturalLanguageAnalytics(
            organizationId,
            payload,
          );

        setParsedQuery(
          response.controlled_query,
        );

        setMessage(
          'Request mapped to a validated controlled analytics schema.',
        );
      } catch (caught: any) {
        setParsedQuery(null);

        setError(
          extractApiError(
            caught,
            'Unable to parse this analytics request.',
          ),
        );
      } finally {
        setParsing(false);
      }
    };

  const handleRun =
    async (
      event:
        FormEvent<HTMLFormElement>,
    ) => {
      event.preventDefault();

      if (!query.trim()) {
        return;
      }

      setLoading(true);
      setError('');
      setMessage('');
      setResult(null);

      try {
        const response =
          await runNaturalLanguageAnalytics(
            organizationId,
            payload,
          );

        setParsedQuery(
          response.controlled_query,
        );

        setResult(response);

        setMessage(
          'Analytics query executed through validated CricIntel intent handlers.',
        );

        const rows =
          await getNaturalLanguageAnalyticsHistory(
            organizationId,
          );

        setHistory(rows);
      } catch (caught: any) {
        setError(
          extractApiError(
            caught,
            'Unable to execute this analytics request.',
          ),
        );
      } finally {
        setLoading(false);
      }
    };

  const selectExample =
    (example: string) => {
      setQuery(example);
      setParsedQuery(null);
      setResult(null);
      setError('');
      setMessage('');
    };

  const loadHistoryRow =
    (
      row:
        NlAnalyticsHistoryRow,
    ) => {
      setQuery(
        row.natural_language_query,
      );

      setParsedQuery(
        row.controlled_query ??
          null,
      );

      if (
        row.status ===
          'completed' &&
        row.structured_result &&
        row.controlled_query
      ) {
        setResult({
          query_id: row.id,
          natural_language_query:
            row.natural_language_query,
          controlled_query:
            row.controlled_query,
          result:
            row.structured_result,
          explanation:
            row.explanation ?? '',
          visualization:
            row.visualization ??
            row.structured_result
              .visualization,
          security: {
            generated_sql_used:
              false,
            arbitrary_sql_used:
              false,
            execution_mode:
              'controlled analytics intent',
          },
        });
      } else {
        setResult(null);
      }
    };

  return (
    <AppLayout>
      <div className="page-heading nl-analytics-heading">
        <div>
          <p className="eyebrow">
            P16 Controlled
            Analytics
          </p>

          <h1>
            Natural-Language
            Analytics
          </h1>

          <p>
            Ask cricket analytics
            questions in natural
            language. CricIntel
            converts them into a
            controlled schema,
            validates every filter
            and executes predefined
            analytics handlers.
          </p>
        </div>

        <div className="nl-analytics-security-pill">
          <AppIcon
            name="shield"
            size={16}
          />

          <div>
            <strong>
              No arbitrary SQL
            </strong>

            <span>
              Controlled intents
              only
            </span>
          </div>
        </div>
      </div>

      <section className="nl-analytics-security-banner">
        <AppIcon
          name="analytics"
          size={19}
        />

        <div>
          <strong>
            Natural language is
            never converted into
            executable SQL.
          </strong>

          <span>
            Requests are mapped to
            a finite intent schema,
            validated against
            CricIntel entities and
            filters, and then routed
            to predefined analytics
            services.
          </span>
        </div>
      </section>

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

      <div className="nl-analytics-layout">
        <aside className="nl-analytics-sidebar">
          <section>
            <h2>
              Supported questions
            </h2>

            <p>
              Choose an example or
              write your own request
              within the supported
              analytics vocabulary.
            </p>

            <div className="nl-analytics-example-list">
              {options.supported_intents.map(
                (intent) => (
                  <button
                    type="button"
                    key={intent.intent}
                    onClick={() =>
                      selectExample(
                        intent.example,
                      )
                    }
                  >
                    <span>
                      {
                        intent.description
                      }
                    </span>

                    <strong>
                      {
                        intent.example
                      }
                    </strong>
                  </button>
                ),
              )}
            </div>
          </section>

          <section>
            <h2>
              Optional filters
            </h2>

            <p>
              Use these when a
              phrase such as “our
              team” is ambiguous.
            </p>

            <div className="nl-analytics-filter-stack">
              <label>
                Team
                <select
                  value={teamId}
                  onChange={(
                    event,
                  ) =>
                    setTeamId(
                      event.target
                        .value,
                    )
                  }
                >
                  <option value="">
                    Infer from query
                  </option>

                  {options.filters.teams.map(
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

              <label>
                Season
                <select
                  value={seasonId}
                  onChange={(
                    event,
                  ) =>
                    setSeasonId(
                      event.target
                        .value,
                    )
                  }
                >
                  <option value="">
                    No explicit
                    season
                  </option>

                  {options.filters.seasons.map(
                    (season) => (
                      <option
                        key={
                          season.id
                        }
                        value={
                          season.id
                        }
                      >
                        {
                          season.name
                        }
                      </option>
                    ),
                  )}
                </select>
              </label>

              <label>
                Opponent
                <select
                  value={
                    opponentTeamId
                  }
                  onChange={(
                    event,
                  ) =>
                    setOpponentTeamId(
                      event.target
                        .value,
                    )
                  }
                >
                  <option value="">
                    Infer / none
                  </option>

                  {options.filters.teams.map(
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

              <label>
                Venue
                <select
                  value={venueId}
                  onChange={(
                    event,
                  ) =>
                    setVenueId(
                      event.target
                        .value,
                    )
                  }
                >
                  <option value="">
                    Infer / all
                  </option>

                  {options.filters.venues.map(
                    (venue) => (
                      <option
                        key={
                          venue.id
                        }
                        value={
                          venue.id
                        }
                      >
                        {
                          venue.name
                        }
                      </option>
                    ),
                  )}
                </select>
              </label>

              <label>
                Format
                <select
                  value={format}
                  onChange={(
                    event,
                  ) =>
                    setFormat(
                      event.target
                        .value,
                    )
                  }
                >
                  <option value="">
                    All formats
                  </option>

                  {options.filters.formats.map(
                    (value) => (
                      <option
                        key={value}
                        value={value}
                      >
                        {value}
                      </option>
                    ),
                  )}
                </select>
              </label>

              <div className="nl-analytics-date-grid">
                <label>
                  From
                  <input
                    type="date"
                    value={dateFrom}
                    onChange={(
                      event,
                    ) =>
                      setDateFrom(
                        event.target
                          .value,
                      )
                    }
                  />
                </label>

                <label>
                  To
                  <input
                    type="date"
                    value={dateTo}
                    onChange={(
                      event,
                    ) =>
                      setDateTo(
                        event.target
                          .value,
                      )
                    }
                  />
                </label>
              </div>
            </div>
          </section>
        </aside>

        <main className="nl-analytics-main">
          <section className="nl-analytics-query-panel">
            <form
              onSubmit={(event) =>
                void handleRun(
                  event,
                )
              }
            >
              <div className="nl-analytics-query-label">
                <div>
                  <span>
                    Ask CricIntel
                  </span>

                  <h2>
                    What do you want
                    to analyze?
                  </h2>
                </div>

                <span className="nl-analytics-safe-chip">
                  Validated schema
                </span>
              </div>

              <textarea
                value={query}
                onChange={(
                  event,
                ) => {
                  setQuery(
                    event.target
                      .value,
                  );
                  setParsedQuery(
                    null,
                  );
                }}
                placeholder="Show me our best death-over bowlers against left-handed batters this season."
                required
              />

              <div className="nl-analytics-query-actions">
                <span>
                  Supported filters:
                  metric, entity,
                  team, season,
                  opponent, venue,
                  format, phase and
                  date range.
                </span>

                <div>
                  <button
                    type="button"
                    className="secondary"
                    onClick={() =>
                      void handlePreview()
                    }
                    disabled={
                      parsing ||
                      loading
                    }
                  >
                    {parsing
                      ? 'Parsing...'
                      : 'Preview schema'}
                  </button>

                  <button
                    type="submit"
                    disabled={
                      loading ||
                      parsing
                    }
                  >
                    <AppIcon
                      name="analytics"
                      size={16}
                    />

                    {loading
                      ? 'Running analytics...'
                      : 'Run analytics'}
                  </button>
                </div>
              </div>
            </form>
          </section>

          {parsedQuery && (
            <section className="nl-analytics-schema-panel">
              <div className="section-heading">
                <div>
                  <h2>
                    Controlled query
                    schema
                  </h2>

                  <p>
                    This is the
                    validated
                    structure CricIntel
                    will execute. It
                    contains no SQL.
                  </p>
                </div>

                <span className="nl-analytics-no-sql-badge">
                  SQL generation:
                  OFF
                </span>
              </div>

              <div className="nl-analytics-schema-grid">
                {schemaEntries(
                  parsedQuery,
                ).map(
                  ([key, value]) => (
                    <div
                      key={key}
                    >
                      <span>
                        {prettyLabel(
                          key,
                        )}
                      </span>

                      <strong>
                        {String(
                          value,
                        )}
                      </strong>
                    </div>
                  ),
                )}
              </div>
            </section>
          )}

          {result && (
            <>
              <section className="nl-analytics-result-header">
                <div>
                  <p className="eyebrow">
                    Structured result
                  </p>

                  <h2>
                    {
                      result.result
                        .title
                    }
                  </h2>

                  <p>
                    {
                      result.explanation
                    }
                  </p>
                </div>

                <div className="nl-analytics-sample-card">
                  <strong>
                    Historical sample
                  </strong>

                  {Object.entries(
                    result.result
                      .sample ?? {},
                  ).map(
                    ([
                      key,
                      value,
                    ]) => (
                      <span
                        key={key}
                      >
                        {prettyLabel(
                          key,
                        )}
                        :
                        {' '}
                        {String(
                          value,
                        )}
                      </span>
                    ),
                  )}
                </div>
              </section>

              <section className="nl-analytics-chart-panel">
                <div className="section-heading">
                  <div>
                    <h2>
                      Automatic
                      visualization
                    </h2>

                    <p>
                      {
                        result.visualization
                          .type ===
                        'table'
                          ? 'This result is best represented as a table.'
                          : `${result.visualization.label ?? 'Metric'} rendered automatically from structured data.`
                      }
                    </p>
                  </div>
                </div>

                <AnalyticsResultChart
                  rows={
                    result.result
                      .rows
                  }
                  visualization={
                    result.visualization
                  }
                />
              </section>

              <section className="nl-analytics-table-panel">
                <div className="section-heading">
                  <div>
                    <h2>
                      Result table
                    </h2>

                    <p>
                      Structured values
                      returned by the
                      controlled
                      analytics
                      handler.
                    </p>
                  </div>
                </div>

                <div className="nl-analytics-table-wrap">
                  <table className="nl-analytics-table">
                    <thead>
                      <tr>
                        {result.result.columns.map(
                          (
                            column,
                          ) => (
                            <th
                              key={
                                column
                              }
                            >
                              {prettyLabel(
                                column,
                              )}
                            </th>
                          ),
                        )}
                      </tr>
                    </thead>

                    <tbody>
                      {result.result.rows.map(
                        (
                          row,
                          rowIndex,
                        ) => (
                          <tr
                            key={
                              rowIndex
                            }
                          >
                            {result.result.columns.map(
                              (
                                column,
                              ) => (
                                <td
                                  key={
                                    column
                                  }
                                >
                                  {formatCell(
                                    row[
                                      column
                                    ],
                                  )}
                                </td>
                              ),
                            )}
                          </tr>
                        ),
                      )}
                    </tbody>
                  </table>

                  {result.result.rows.length ===
                    0 && (
                    <div className="nl-analytics-empty-table">
                      No matching
                      cricket data
                      was found.
                    </div>
                  )}
                </div>

                <div className="nl-analytics-source-note">
                  <AppIcon
                    name="shield"
                    size={15}
                  />

                  <span>
                    Source:
                    {' '}
                    {String(
                      result.result
                        .source
                        ?.service ??
                        'CricIntel controlled analytics',
                    )}
                    .
                    {' '}
                    Arbitrary SQL:
                    {' '}
                    <strong>
                      No
                    </strong>
                    .
                  </span>
                </div>
              </section>
            </>
          )}

          {!result && (
            <section className="nl-analytics-empty-state">
              <AppIcon
                name="analytics"
                size={34}
              />

              <strong>
                No analytics result
                yet
              </strong>

              <span>
                Ask a supported
                cricket analytics
                question, preview
                the controlled
                schema, then run
                the query.
              </span>
            </section>
          )}

          <section className="nl-analytics-history-panel">
            <div className="section-heading">
              <div>
                <h2>
                  Query history
                </h2>

                <p>
                  Previous controlled
                  analytics requests
                  for this
                  organization.
                </p>
              </div>
            </div>

            <div className="nl-analytics-history-list">
              {history.map(
                (row) => (
                  <button
                    type="button"
                    key={row.id}
                    onClick={() =>
                      loadHistoryRow(
                        row,
                      )
                    }
                  >
                    <span className={`nl-analytics-history-status ${row.status}`}>
                      {row.status}
                    </span>

                    <div>
                      <strong>
                        {
                          row.natural_language_query
                        }
                      </strong>

                      <span>
                        {row.intent
                          ? prettyLabel(
                              row.intent,
                            )
                          : 'Unresolved intent'}
                        {' · '}
                        {row.user
                          ?.name ??
                          'User'}
                        {' · '}
                        {new Date(
                          row.created_at,
                        ).toLocaleString()}
                      </span>
                    </div>

                    <AppIcon
                      name="arrowRight"
                      size={15}
                    />
                  </button>
                ),
              )}

              {history.length ===
                0 && (
                <div className="nl-analytics-history-empty">
                  No natural-language
                  analytics queries
                  yet.
                </div>
              )}
            </div>
          </section>
        </main>
      </div>
    </AppLayout>
  );
}

function schemaEntries(
  schema: ControlledAnalyticsQuery,
) {
  return Object.entries(
    schema,
  ).filter(
    ([
      ,
      value,
    ]) =>
      value !== null &&
      value !== '' &&
      value !== 'all',
  );
}

function prettyLabel(
  value: string,
) {
  return value
    .replaceAll(
      '_',
      ' ',
    )
    .replace(
      /\b\w/g,
      (letter) =>
        letter.toUpperCase(),
    );
}

function formatCell(
  value: unknown,
) {
  if (
    value === null ||
    value === undefined
  ) {
    return '—';
  }

  if (
    typeof value ===
    'object'
  ) {
    if (
      'label' in
      (value as Record<
        string,
        unknown
      >)
    ) {
      return String(
        (
          value as Record<
            string,
            unknown
          >
        ).label,
      );
    }

    return JSON.stringify(
      value,
    );
  }

  if (
    typeof value ===
    'number'
  ) {
    return Number.isInteger(
      value,
    )
      ? String(value)
      : value.toFixed(2);
  }

  if (
    typeof value ===
      'string' &&
    /^\d{4}-\d{2}-\d{2}T/.test(
      value,
    )
  ) {
    return new Date(
      value,
    ).toLocaleString();
  }

  return String(value);
}

function extractApiError(
  caught: any,
  fallback: string,
) {
  const errors =
    caught?.response?.data
      ?.errors;

  if (
    errors &&
    typeof errors ===
      'object'
  ) {
    const messages =
      Object.values(
        errors,
      )
        .flat()
        .filter(
          (
            value,
          ): value is string =>
            typeof value ===
            'string',
        );

    if (messages.length) {
      return messages.join(
        ' ',
      );
    }
  }

  return (
    caught?.response?.data
      ?.message ??
    fallback
  );
}
