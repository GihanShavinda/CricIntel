import {
  useEffect,
  useMemo,
  useState,
} from 'react';

import {
  useParams,
} from 'react-router-dom';

import {
  deleteReportExport,
  downloadReportExport,
  generateReport,
  getReportExport,
  getReportOptions,
  listReportExports,
  previewReport,
} from '../api/reports';

import {
  AppIcon,
} from '../components/AppIcon';

import {
  AppLayout,
} from '../components/AppLayout';

import type {
  ReportDocument,
  ReportExport,
  ReportFilters,
  ReportFormat,
  ReportOptions,
  ReportType,
} from '../types/reports';

const emptyFilters: ReportFilters = {
  phase: 'all',
};

export function ReportsPage() {
  const params =
    useParams();

  const organizationId =
    Number(
      params.organizationId,
    );

  const [
    options,
    setOptions,
  ] = useState<
    ReportOptions | null
  >(null);

  const [
    reportType,
    setReportType,
  ] = useState<ReportType>(
    'player_performance',
  );

  const [
    format,
    setFormat,
  ] = useState<ReportFormat>(
    'pdf',
  );

  const [
    filters,
    setFilters,
  ] = useState<ReportFilters>(
    emptyFilters,
  );

  const [
    preview,
    setPreview,
  ] = useState<
    ReportDocument | null
  >(null);

  const [
    exports,
    setExports,
  ] = useState<ReportExport[]>(
    [],
  );

  const [
    loading,
    setLoading,
  ] = useState(true);

  const [
    previewing,
    setPreviewing,
  ] = useState(false);

  const [
    generating,
    setGenerating,
  ] = useState(false);

  const [
    error,
    setError,
  ] = useState('');

  const [
    message,
    setMessage,
  ] = useState('');

  const load =
    async () => {
      if (
        !organizationId
      ) {
        return;
      }

      setLoading(true);
      setError('');

      try {
        const [
          nextOptions,
          nextExports,
        ] = await Promise.all([
          getReportOptions(
            organizationId,
          ),
          listReportExports(
            organizationId,
          ),
        ]);

        setOptions(
          nextOptions,
        );

        setExports(
          nextExports,
        );
      } catch (caught: any) {
        setError(
          caught?.response?.data
            ?.message ??
            'Unable to load reporting workspace.',
        );
      } finally {
        setLoading(false);
      }
    };

  useEffect(() => {
    void load();
  }, [organizationId]);

  useEffect(() => {
    const hasPending =
      exports.some(
        (item) =>
          item.status ===
            'queued' ||
          item.status ===
            'processing',
      );

    if (!hasPending) {
      return;
    }

    const timer =
      window.setInterval(
        async () => {
          const updated =
            await Promise.all(
              exports.map(
                async (
                  item,
                ) => {
                  if (
                    item.status !==
                      'queued' &&
                    item.status !==
                      'processing'
                  ) {
                    return item;
                  }

                  try {
                    return await getReportExport(
                      organizationId,
                      item.id,
                    );
                  } catch {
                    return item;
                  }
                },
              ),
            );

          setExports(
            updated,
          );
        },
        3000,
      );

    return () => {
      window.clearInterval(
        timer,
      );
    };
  }, [
    exports,
    organizationId,
  ]);

  const currentDefinition =
    useMemo(
      () =>
        REPORT_FILTERS[
          reportType
        ],
      [reportType],
    );

  const setFilter =
    (
      key: keyof ReportFilters,
      value:
        | string
        | number
        | undefined,
    ) => {
      setFilters(
        (current) => ({
          ...current,
          [key]:
            value === ''
              ? undefined
              : value,
        }),
      );
    };

  const doPreview =
    async () => {
      setPreviewing(true);
      setError('');
      setMessage('');

      try {
        const result =
          await previewReport(
            organizationId,
            {
              report_type:
                reportType,
              format,
              filters,
            },
          );

        setPreview(
          result,
        );
      } catch (caught: any) {
        setError(
          firstError(
            caught,
            'Unable to build report preview.',
          ),
        );
      } finally {
        setPreviewing(false);
      }
    };

  const doGenerate =
    async () => {
      setGenerating(true);
      setError('');
      setMessage('');

      try {
        const result =
          await generateReport(
            organizationId,
            {
              report_type:
                reportType,
              format,
              filters,
            },
          );

        setExports(
          (current) => [
            result,
            ...current.filter(
              (item) =>
                item.id !==
                result.id,
            ),
          ],
        );

        setMessage(
          result.status ===
            'queued'
            ? 'Report queued. CricIntel will update the status automatically.'
            : 'Report generated successfully.',
        );
      } catch (caught: any) {
        setError(
          firstError(
            caught,
            'Unable to generate report.',
          ),
        );
      } finally {
        setGenerating(false);
      }
    };

  const removeExport =
    async (
      item: ReportExport,
    ) => {
      await deleteReportExport(
        organizationId,
        item.id,
      );

      setExports(
        (current) =>
          current.filter(
            (row) =>
              row.id !==
              item.id,
          ),
      );
    };

  if (
    loading
  ) {
    return (
      <AppLayout>
        <div className="page-state">
          Loading professional
          reporting...
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <div className="page-heading report-heading">
        <div>
          <p className="eyebrow">
            P18 Professional
            Reporting
          </p>

          <h1>
            Report Center
          </h1>

          <p>
            Build consistent
            CricIntel PDF, Excel
            and CSV exports using
            controlled report
            definitions, validated
            filters and stored
            export history.
          </p>
        </div>

        <div className="report-heading-badge">
          <AppIcon
            name="reports"
            size={18}
          />

          <span>
            8 report types
          </span>
        </div>
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

      <div className="report-workspace-grid">
        <section className="report-builder-card">
          <div className="report-card-heading">
            <div>
              <strong>
                Build report
              </strong>

              <span>
                Choose the report,
                export format and
                filters.
              </span>
            </div>
          </div>

          <div className="report-builder-body">
            <label>
              Report type

              <select
                value={
                  reportType
                }
                onChange={(
                  event,
                ) => {
                  setReportType(
                    event.target
                      .value as
                      ReportType,
                  );

                  setFilters({
                    ...emptyFilters,
                  });

                  setPreview(
                    null,
                  );
                }}
              >
                {options?.report_types.map(
                  (item) => (
                    <option
                      key={
                        item.value
                      }
                      value={
                        item.value
                      }
                    >
                      {
                        item.label
                      }
                    </option>
                  ),
                )}
              </select>
            </label>

            <label>
              Export format

              <select
                value={format}
                onChange={(
                  event,
                ) =>
                  setFormat(
                    event.target
                      .value as
                      ReportFormat,
                  )
                }
              >
                {options?.formats.map(
                  (item) => (
                    <option
                      key={
                        item.value
                      }
                      value={
                        item.value
                      }
                    >
                      {
                        item.label
                      }
                    </option>
                  ),
                )}
              </select>
            </label>

            <div className="report-filter-grid">
              {currentDefinition.map(
                (
                  filter,
                ) => (
                  <ReportFilterField
                    key={
                      filter.key
                    }
                    definition={
                      filter
                    }
                    value={
                      filters[
                        filter.key
                      ]
                    }
                    options={
                      options
                    }
                    onChange={(
                      value,
                    ) =>
                      setFilter(
                        filter.key,
                        value,
                      )
                    }
                  />
                ),
              )}

              <label>
                Date from

                <input
                  type="date"
                  value={
                    filters.date_from ??
                    ''
                  }
                  onChange={(
                    event,
                  ) =>
                    setFilter(
                      'date_from',
                      event.target
                        .value,
                    )
                  }
                />
              </label>

              <label>
                Date to

                <input
                  type="date"
                  value={
                    filters.date_to ??
                    ''
                  }
                  onChange={(
                    event,
                  ) =>
                    setFilter(
                      'date_to',
                      event.target
                        .value,
                    )
                  }
                />
              </label>

              <label>
                Match phase

                <select
                  value={
                    filters.phase ??
                    'all'
                  }
                  onChange={(
                    event,
                  ) =>
                    setFilter(
                      'phase',
                      event.target
                        .value,
                    )
                  }
                >
                  <option value="all">
                    All phases
                  </option>
                  <option value="powerplay">
                    Powerplay
                  </option>
                  <option value="middle">
                    Middle overs
                  </option>
                  <option value="death">
                    Death overs
                  </option>
                </select>
              </label>
            </div>

            <div className="report-builder-actions">
              <button
                type="button"
                className="secondary"
                disabled={
                  previewing
                }
                onClick={() =>
                  void doPreview()
                }
              >
                {previewing
                  ? 'Building preview...'
                  : 'Preview data'}
              </button>

              <button
                type="button"
                disabled={
                  generating
                }
                onClick={() =>
                  void doGenerate()
                }
              >
                <AppIcon
                  name="reports"
                  size={15}
                />

                {generating
                  ? 'Generating...'
                  : `Generate ${format.toUpperCase()}`}
              </button>
            </div>

            {options?.heavy_formats.includes(
              format,
            ) && (
              <p className="report-queue-note">
                PDF and Excel are
                generated through
                the report queue so
                heavy exports do not
                block the API
                request.
              </p>
            )}
          </div>
        </section>

        <section className="report-preview-card">
          <div className="report-card-heading">
            <div>
              <strong>
                Report preview
              </strong>

              <span>
                Structured data
                before export.
              </span>
            </div>
          </div>

          {!preview && (
            <div className="report-empty-state">
              <AppIcon
                name="reports"
                size={26}
              />

              <strong>
                No preview yet
              </strong>

              <span>
                Select filters and
                choose Preview data.
              </span>
            </div>
          )}

          {preview && (
            <div className="report-preview-body">
              <div className="report-preview-title">
                <span>
                  {
                    preview.title
                  }
                </span>

                <strong>
                  {
                    preview.subtitle
                  }
                </strong>
              </div>

              <div className="report-summary-grid">
                {preview.summary.map(
                  (
                    item,
                  ) => (
                    <article
                      key={
                        item.label
                      }
                    >
                      <strong>
                        {
                          item.value
                        }
                      </strong>

                      <span>
                        {
                          item.label
                        }
                      </span>
                    </article>
                  ),
                )}
              </div>

              {preview.charts.map(
                (
                  chart,
                ) => (
                  <div
                    className="report-preview-chart"
                    key={
                      chart.title
                    }
                  >
                    <strong>
                      {
                        chart.title
                      }
                    </strong>

                    {chart.labels.map(
                      (
                        label,
                        index,
                      ) => {
                        const max =
                          Math.max(
                            1,
                            ...chart.values,
                          );

                        const value =
                          chart.values[
                            index
                          ] ?? 0;

                        return (
                          <div
                            className="report-preview-bar-row"
                            key={
                              label
                            }
                          >
                            <span>
                              {
                                label
                              }
                            </span>

                            <div>
                              <i
                                style={{
                                  width:
                                    `${Math.min(
                                      100,
                                      value /
                                        max *
                                        100,
                                    )}%`,
                                }}
                              />
                            </div>

                            <b>
                              {
                                value
                              }
                            </b>
                          </div>
                        );
                      },
                    )}
                  </div>
                ),
              )}

              {preview.tables.map(
                (
                  table,
                ) => (
                  <div
                    className="report-preview-table"
                    key={
                      table.title
                    }
                  >
                    <strong>
                      {
                        table.title
                      }
                    </strong>

                    <div className="table-wrap">
                      <table className="data-table">
                        <thead>
                          <tr>
                            {table.columns.map(
                              (
                                column,
                              ) => (
                                <th
                                  key={
                                    column
                                  }
                                >
                                  {
                                    column
                                  }
                                </th>
                              ),
                            )}
                          </tr>
                        </thead>

                        <tbody>
                          {table.rows
                            .slice(
                              0,
                              8,
                            )
                            .map(
                              (
                                row,
                                rowIndex,
                              ) => (
                                <tr
                                  key={
                                    rowIndex
                                  }
                                >
                                  {row.map(
                                    (
                                      cell,
                                      cellIndex,
                                    ) => (
                                      <td
                                        key={
                                          cellIndex
                                        }
                                      >
                                        {String(
                                          cell ??
                                            '',
                                        )}
                                      </td>
                                    ),
                                  )}
                                </tr>
                              ),
                            )}
                        </tbody>
                      </table>
                    </div>
                  </div>
                ),
              )}
            </div>
          )}
        </section>
      </div>

      <section className="report-history-card">
        <div className="report-card-heading">
          <div>
            <strong>
              Generated exports
            </strong>

            <span>
              Stored metadata,
              queue status and
              professional
              filenames.
            </span>
          </div>

          <button
            type="button"
            className="secondary"
            onClick={() =>
              void load()
            }
          >
            Refresh
          </button>
        </div>

        <div className="report-history-list">
          {exports.map(
            (
              item,
            ) => (
              <article
                key={
                  item.id
                }
              >
                <div className="report-file-icon">
                  <AppIcon
                    name="reports"
                    size={17}
                  />
                </div>

                <div className="report-history-copy">
                  <strong>
                    {
                      item.filename ??
                      item.report_label
                    }
                  </strong>

                  <span>
                    {item.report_label}
                    {' • '}
                    {item.format.toUpperCase()}
                    {' • '}
                    {item.created_at
                      ? new Date(
                          item.created_at,
                        ).toLocaleString()
                      : ''}
                  </span>

                  {item.error_message && (
                    <small>
                      {
                        item.error_message
                      }
                    </small>
                  )}
                </div>

                <span
                  className={`report-status ${item.status}`}
                >
                  {
                    item.status
                  }
                </span>

                <div className="report-history-actions">
                  {item.status ===
                    'completed' && (
                    <button
                      type="button"
                      className="secondary"
                      onClick={() =>
                        void downloadReportExport(
                          organizationId,
                          item,
                        )
                      }
                    >
                      Download
                    </button>
                  )}

                  <button
                    type="button"
                    className="danger"
                    onClick={() =>
                      void removeExport(
                        item,
                      )
                    }
                  >
                    Delete
                  </button>
                </div>
              </article>
            ),
          )}

          {exports.length ===
            0 && (
            <div className="report-empty-state">
              No generated reports
              yet.
            </div>
          )}
        </div>
      </section>
    </AppLayout>
  );
}

type FilterDefinition = {
  key: keyof ReportFilters;
  label: string;
  entity:
    | 'players'
    | 'teams'
    | 'matches'
    | 'tournaments'
    | 'venues'
    | 'training_sessions'
    | 'scouting_reports'
    | 'strategy_plans';
};

const REPORT_FILTERS:
  Record<
    ReportType,
    FilterDefinition[]
  > = {
  player_performance: [
    {
      key: 'player_id',
      label: 'Player',
      entity: 'players',
    },
  ],

  match: [
    {
      key: 'match_id',
      label: 'Match',
      entity: 'matches',
    },
  ],

  team_performance: [
    {
      key: 'team_id',
      label: 'Team',
      entity: 'teams',
    },
  ],

  opponent: [
    {
      key: 'opponent_team_id',
      label: 'Opponent',
      entity: 'teams',
    },
  ],

  training: [
    {
      key: 'team_id',
      label: 'Team',
      entity: 'teams',
    },
    {
      key: 'training_session_id',
      label: 'Training session',
      entity: 'training_sessions',
    },
  ],

  scouting: [
    {
      key: 'scouting_report_id',
      label: 'Scouting report',
      entity: 'scouting_reports',
    },
  ],

  tournament: [
    {
      key: 'tournament_id',
      label: 'Tournament',
      entity: 'tournaments',
    },
  ],

  tactical_preparation: [
    {
      key: 'strategy_plan_id',
      label: 'Strategy plan',
      entity: 'strategy_plans',
    },
  ],
};

function ReportFilterField({
  definition,
  value,
  options,
  onChange,
}: {
  definition: FilterDefinition;
  value:
    | string
    | number
    | undefined;
  options:
    | ReportOptions
    | null;
  onChange:
    (
      value:
        | number
        | undefined,
    ) => void;
}) {
  const choices =
    options?.entities?.[
      definition.entity
    ] ?? [];

  return (
    <label>
      {definition.label}

      <select
        value={
          value ?? ''
        }
        onChange={(
          event,
        ) =>
          onChange(
            event.target.value
              ? Number(
                  event.target
                    .value,
                )
              : undefined,
          )
        }
      >
        <option value="">
          Select
          {' '}
          {definition.label.toLowerCase()}
        </option>

        {choices.map(
          (
            choice,
          ) => (
            <option
              key={
                choice.id
              }
              value={
                choice.id
              }
            >
              {
                choice.label
              }
            </option>
          ),
        )}
      </select>
    </label>
  );
}

function firstError(
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
    const first =
      Object.values(
        errors,
      )[0];

    if (
      Array.isArray(
        first,
      ) &&
      first.length > 0
    ) {
      return String(
        first[0],
      );
    }
  }

  return (
    caught?.response?.data
      ?.message ??
    fallback
  );
}
