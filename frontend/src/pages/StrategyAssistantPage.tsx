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
  askStrategyAssistant,
  getStrategyAssistantContext,
  getStrategyAssistantHistory,
  getStrategyAssistantOptions,
  getStrategyAssistantStatus,
} from '../api/strategyAssistant';

import {
  AppIcon,
} from '../components/AppIcon';

import {
  AppLayout,
} from '../components/AppLayout';

import type {
  GroundedAssistantItem,
  StrategyAssistantAnswer,
  StrategyAssistantContextPayload,
  StrategyAssistantHistoryRow,
  StrategyAssistantMatchOption,
  StrategyAssistantStatus,
  StrategyEvidence,
} from '../types/strategyAssistant';

export function StrategyAssistantPage() {
  const organizationId =
    Number(
      useParams().organizationId,
    );

  const [
    matches,
    setMatches,
  ] = useState<
    StrategyAssistantMatchOption[]
  >([]);

  const [
    examples,
    setExamples,
  ] = useState<string[]>([]);

  const [
    status,
    setStatus,
  ] =
    useState<StrategyAssistantStatus | null>(
      null,
    );

  const [
    matchId,
    setMatchId,
  ] = useState<number | ''>('');

  const [
    contextPayload,
    setContextPayload,
  ] =
    useState<StrategyAssistantContextPayload | null>(
      null,
    );

  const [
    answer,
    setAnswer,
  ] =
    useState<StrategyAssistantAnswer | null>(
      null,
    );

  const [
    history,
    setHistory,
  ] = useState<
    StrategyAssistantHistoryRow[]
  >([]);

  const [
    question,
    setQuestion,
  ] = useState('');

  const [
    loadingContext,
    setLoadingContext,
  ] = useState(false);

  const [
    asking,
    setAsking,
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
          statusRow,
          optionRows,
          historyRows,
        ] =
          await Promise.all([
            getStrategyAssistantStatus(
              organizationId,
            ),
            getStrategyAssistantOptions(
              organizationId,
            ),
            getStrategyAssistantHistory(
              organizationId,
            ),
          ]);

        setStatus(statusRow);
        setMatches(
          optionRows.matches ?? [],
        );
        setExamples(
          optionRows.example_questions ??
            [],
        );
        setHistory(historyRows);

        if (
          matchId === '' &&
          optionRows.matches?.length
        ) {
          setMatchId(
            optionRows.matches[0].id,
          );
        }
      } catch (caught: any) {
        setError(
          caught?.response?.data
            ?.message ??
            'Unable to load the AI Strategy Assistant.',
        );
      }
    };

  useEffect(() => {
    void loadBase();
  }, [organizationId]);

  useEffect(() => {
    if (!matchId) {
      setContextPayload(null);
      return;
    }

    const loadContext =
      async () => {
        setLoadingContext(true);
        setError('');

        try {
          const data =
            await getStrategyAssistantContext(
              organizationId,
              Number(matchId),
            );

          setContextPayload(data);

          const rows =
            await getStrategyAssistantHistory(
              organizationId,
              Number(matchId),
            );

          setHistory(rows);
        } catch (caught: any) {
          setError(
            caught?.response?.data
              ?.message ??
              'Unable to build strategy context.',
          );
        } finally {
          setLoadingContext(false);
        }
      };

    void loadContext();
  }, [
    organizationId,
    matchId,
  ]);

  const selectedMatch =
    useMemo(
      () =>
        matches.find(
          (match) =>
            match.id ===
            Number(matchId),
        ) ?? null,
      [
        matches,
        matchId,
      ],
    );

  const evidenceMap =
    useMemo(() => {
      const rows =
        answer?.evidence ??
        contextPayload?.context
          .evidence ??
        [];

      return new Map(
        rows.map(
          (item) => [
            item.id,
            item,
          ],
        ),
      );
    }, [
      answer,
      contextPayload,
    ]);

  const contextCounts =
    useMemo(() => {
      const context =
        contextPayload?.context;

      if (!context) {
        return null;
      }

      return {
        availablePlayers:
          context.available_players
            ?.length ?? 0,
        recentForm:
          context.recent_form
            ?.length ?? 0,
        playerStats:
          context.player_data
            ?.career_stats?.length ??
          0,
        opponentStats:
          context.opponent_data
            ?.career_stats?.length ??
          0,
        matchups:
          context.matchups?.length ??
          0,
        evidence:
          context.evidence?.length ??
          0,
      };
    }, [contextPayload]);

  const submit =
    async (
      event:
        FormEvent<HTMLFormElement>,
    ) => {
      event.preventDefault();

      if (!matchId) {
        setError(
          'Select a match first.',
        );
        return;
      }

      if (!question.trim()) {
        return;
      }

      setAsking(true);
      setError('');
      setMessage('');
      setAnswer(null);

      try {
        const response =
          await askStrategyAssistant(
            organizationId,
            Number(matchId),
            question.trim(),
          );

        setAnswer(response);
        setMessage(
          'Grounded response validated against CricIntel evidence.',
        );

        const rows =
          await getStrategyAssistantHistory(
            organizationId,
            Number(matchId),
          );

        setHistory(rows);
      } catch (caught: any) {
        const validationErrors =
          caught?.response?.data
            ?.data
            ?.validation_errors;

        setError(
          Array.isArray(
            validationErrors,
          )
            ? validationErrors.join(
                ' ',
              )
            : caught?.response
                  ?.data?.message ??
                'Unable to generate a grounded strategy response.',
        );
      } finally {
        setAsking(false);
      }
    };

  const renderEvidence =
    (ids: string[]) => (
      <div className="strategy-ai-evidence-list">
        {ids.map((id) => {
          const evidence =
            evidenceMap.get(id);

          return (
            <span
              key={id}
              className="strategy-ai-evidence-chip"
              title={
                evidence
                  ? JSON.stringify(
                      evidence.source,
                    )
                  : 'Evidence reference'
              }
            >
              {id}
              {evidence && (
                <>
                  {' · '}
                  {
                    evidence.metric
                  }
                  {' = '}
                  {String(
                    evidence.value ??
                      'n/a',
                  )}
                  {evidence.unit
                    ? ` ${evidence.unit}`
                    : ''}
                </>
              )}
            </span>
          );
        })}
      </div>
    );

  const renderGroundedItems =
    (
      title: string,
      items:
        | GroundedAssistantItem[]
        | undefined,
    ) => {
      if (!items?.length) {
        return null;
      }

      return (
        <section className="strategy-ai-answer-section">
          <h3>
            {title}
          </h3>

          <div className="strategy-ai-answer-items">
            {items.map(
              (
                item,
                index,
              ) => (
                <article
                  key={`${title}-${index}`}
                >
                  <div className="strategy-ai-answer-item-top">
                    <span className={`strategy-ai-confidence ${item.confidence}`}>
                      {
                        item.confidence
                      }
                    </span>
                  </div>

                  <p>
                    {
                      item.statement
                    }
                  </p>

                  {renderEvidence(
                    item.evidence_ids,
                  )}
                </article>
              ),
            )}
          </div>
        </section>
      );
    };

  return (
    <AppLayout>
      <div className="page-heading strategy-ai-heading">
        <div>
          <p className="eyebrow">
            P15 Grounded AI
          </p>

          <h1>
            AI Strategy Assistant
          </h1>

          <p>
            Ask tactical questions over
            structured CricIntel evidence
            only. Statistical claims are
            validated before display.
          </p>
        </div>

        <div className="strategy-ai-status-stack">
          <span
            className={[
              'strategy-ai-status',
              status?.enabled
                ? 'enabled'
                : 'disabled',
            ].join(' ')}
          >
            <span className="ci-status-dot online" />

            {status?.enabled
              ? 'Assistant enabled'
              : 'Kill switch active'}
          </span>

          <span
            className={[
              'strategy-ai-status',
              status?.service
                ?.reachable
                ? 'enabled'
                : 'disabled',
            ].join(' ')}
          >
            FastAPI
            {' '}
            {status?.service
              ?.reachable
              ? 'reachable'
              : 'offline'}
          </span>
        </div>
      </div>

      <section className="strategy-ai-safety-banner">
        <AppIcon
          name="shield"
          size={18}
        />

        <div>
          <strong>
            Grounded assistant rules
          </strong>

          <span>
            No fabricated cricket
            statistics, no certainty
            claims, sample-size
            limitations must be surfaced,
            and the coach remains
            responsible for final
            decisions.
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

      <div className="strategy-ai-layout">
        <aside className="strategy-ai-control-panel">
          <section>
            <h2>
              Match context
            </h2>

            <label>
              Match
              <select
                value={matchId}
                onChange={(event) => {
                  setMatchId(
                    event.target.value
                      ? Number(
                          event.target
                            .value,
                        )
                      : '',
                  );
                  setAnswer(null);
                }}
              >
                <option value="">
                  Select match
                </option>

                {matches.map(
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

            {selectedMatch && (
              <div className="strategy-ai-match-card">
                <strong>
                  {
                    selectedMatch.home_team_name
                  }
                  {' vs '}
                  {
                    selectedMatch.away_team_name
                  }
                </strong>

                <span>
                  {selectedMatch.venue_name ??
                    'Venue not set'}
                </span>

                <span>
                  {
                    selectedMatch.status
                  }
                  {' · '}
                  {selectedMatch.max_overs ??
                    20}
                  {' overs'}
                </span>
              </div>
            )}
          </section>

          <section>
            <h2>
              Context coverage
            </h2>

            {loadingContext ? (
              <p>
                Building grounded
                context...
              </p>
            ) : contextCounts ? (
              <div className="strategy-ai-context-grid">
                <div>
                  <strong>
                    {
                      contextCounts.availablePlayers
                    }
                  </strong>
                  <span>
                    Available players
                  </span>
                </div>

                <div>
                  <strong>
                    {
                      contextCounts.recentForm
                    }
                  </strong>
                  <span>
                    Recent-form rows
                  </span>
                </div>

                <div>
                  <strong>
                    {
                      contextCounts.playerStats
                    }
                  </strong>
                  <span>
                    Focus player stats
                  </span>
                </div>

                <div>
                  <strong>
                    {
                      contextCounts.opponentStats
                    }
                  </strong>
                  <span>
                    Opponent stats
                  </span>
                </div>

                <div>
                  <strong>
                    {
                      contextCounts.matchups
                    }
                  </strong>
                  <span>
                    Matchups
                  </span>
                </div>

                <div>
                  <strong>
                    {
                      contextCounts.evidence
                    }
                  </strong>
                  <span>
                    Evidence records
                  </span>
                </div>
              </div>
            ) : (
              <p>
                Select a match to
                inspect its evidence.
              </p>
            )}
          </section>

          <section>
            <h2>
              Example questions
            </h2>

            <div className="strategy-ai-example-list">
              {examples.map(
                (example) => (
                  <button
                    type="button"
                    key={example}
                    onClick={() =>
                      setQuestion(
                        example,
                      )
                    }
                  >
                    {example}
                  </button>
                ),
              )}
            </div>
          </section>
        </aside>

        <main className="strategy-ai-main">
          <section className="strategy-ai-question-panel">
            <form
              onSubmit={(event) =>
                void submit(event)
              }
            >
              <label>
                Tactical question
                <textarea
                  value={question}
                  onChange={(event) =>
                    setQuestion(
                      event.target
                        .value,
                    )
                  }
                  placeholder="Which bowlers have performed best at the death against left-handed batters?"
                  required
                />
              </label>

              <div className="strategy-ai-question-actions">
                <span>
                  Only structured
                  CricIntel context is
                  sent to the configured
                  LLM.
                </span>

                <button
                  type="submit"
                  disabled={
                    !matchId ||
                    !status?.enabled ||
                    asking
                  }
                >
                  <AppIcon
                    name="strategy"
                    size={17}
                  />

                  {asking
                    ? 'Validating response...'
                    : 'Ask assistant'}
                </button>
              </div>
            </form>
          </section>

          {contextPayload
            ?.deterministic_recommendations
            ?.length ? (
            <section className="strategy-ai-deterministic-panel">
              <div className="section-heading">
                <div>
                  <h2>
                    Deterministic
                    recommendations
                  </h2>

                  <p>
                    Calculated without
                    an LLM from stored
                    CricIntel evidence.
                  </p>
                </div>
              </div>

              <div className="strategy-ai-deterministic-grid">
                {contextPayload
                  .deterministic_recommendations
                  .map(
                    (
                      recommendation,
                    ) => (
                      <article
                        key={
                          recommendation.key
                        }
                      >
                        <span className={`strategy-ai-confidence ${recommendation.confidence}`}>
                          {
                            recommendation.confidence
                          }
                        </span>

                        <h3>
                          {
                            recommendation.title
                          }
                        </h3>

                        <p>
                          {
                            recommendation.recommendation
                          }
                        </p>

                        {renderEvidence(
                          recommendation.evidence_ids,
                        )}

                        {recommendation.limitations?.map(
                          (
                            limitation,
                          ) => (
                            <small
                              key={
                                limitation
                              }
                            >
                              {
                                limitation
                              }
                            </small>
                          ),
                        )}
                      </article>
                    ),
                  )}
              </div>
            </section>
          ) : null}

          {answer && (
            <section className="strategy-ai-answer-panel">
              <div className="strategy-ai-answer-heading">
                <div>
                  <p className="eyebrow">
                    Validated AI
                    explanation
                  </p>

                  <h2>
                    Grounded response
                  </h2>

                  <span>
                    {answer.model ??
                      'Configured model'}
                    {' · '}
                    context
                    {' '}
                    {answer.context_hash.slice(
                      0,
                      12,
                    )}
                  </span>
                </div>

                <span className="strategy-ai-validated-badge">
                  <AppIcon
                    name="shield"
                    size={15}
                  />
                  Evidence validated
                </span>
              </div>

              {renderGroundedItems(
                'Evidence-backed findings',
                answer.response.claims,
              )}

              {renderGroundedItems(
                'Advisory recommendations',
                answer.response
                  .recommendations,
              )}

              {answer.response
                .limitations?.length >
                0 && (
                <section className="strategy-ai-limitations">
                  <h3>
                    Limitations
                  </h3>

                  <ul>
                    {answer.response
                      .limitations
                      .map(
                        (
                          limitation,
                        ) => (
                          <li
                            key={
                              limitation
                            }
                          >
                            {
                              limitation
                            }
                          </li>
                        ),
                      )}
                  </ul>
                </section>
              )}

              <div className="strategy-ai-coach-note">
                {
                  answer.response
                    .coach_note
                }
              </div>
            </section>
          )}

          {!answer && (
            <section className="strategy-ai-empty-answer">
              <AppIcon
                name="strategy"
                size={30}
              />

              <strong>
                No AI explanation
                generated
              </strong>

              <span>
                Review deterministic
                recommendations first,
                then ask a grounded
                question when the
                assistant is enabled.
              </span>
            </section>
          )}

          <section className="strategy-ai-history-panel">
            <div className="section-heading">
              <div>
                <h2>
                  Assistant audit history
                </h2>

                <p>
                  AI outputs are stored
                  separately from
                  authoritative cricket
                  data.
                </p>
              </div>
            </div>

            <div className="strategy-ai-history-list">
              {history.map(
                (row) => (
                  <article
                    key={row.id}
                  >
                    <span className={`strategy-ai-run-status ${row.validation_status}`}>
                      {
                        row.validation_status
                      }
                    </span>

                    <div>
                      <strong>
                        {row.question}
                      </strong>

                      <span>
                        {row.user?.name ??
                          'User'}
                        {' · '}
                        {new Date(
                          row.created_at,
                        ).toLocaleString()}
                      </span>
                    </div>

                    <small>
                      {row.model ??
                        'No validated model response'}
                    </small>
                  </article>
                ),
              )}

              {history.length ===
                0 && (
                <div className="strategy-ai-history-empty">
                  No assistant runs for
                  this match yet.
                </div>
              )}
            </div>
          </section>
        </main>
      </div>
    </AppLayout>
  );
}
