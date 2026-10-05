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
  createStrategyAssignment,
  createStrategyAttachment,
  createStrategyComment,
  createTacticalNote,
  getStrategyOptions,
  getStrategyPlan,
  getStrategyVersions,
  resolveTacticalNote,
  setStrategyLock,
  updateStrategyAssignment,
  updateStrategyPlan,
  updateStrategySection,
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
  StrategyAttachment,
  StrategyOptions,
  StrategyPlan,
  StrategySection,
  StrategyVersion,
} from '../types/strategy';

type WorkspaceTab =
  | 'board'
  | 'discussions'
  | 'assignments'
  | 'attachments'
  | 'history';

export function TacticalWorkspacePage() {
  const params =
    useParams();

  const organizationId =
    Number(
      params.organizationId,
    );

  const planId =
    Number(
      params.planId,
    );

  const {
    user,
  } = useAuth();

  const [
    plan,
    setPlan,
  ] = useState<StrategyPlan | null>(
    null,
  );

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
    versions,
    setVersions,
  ] = useState<StrategyVersion[]>(
    [],
  );

  const [
    activeTab,
    setActiveTab,
  ] = useState<WorkspaceTab>(
    'board',
  );

  const [
    editingSectionId,
    setEditingSectionId,
  ] = useState<number | null>(
    null,
  );

  const [
    sectionDraft,
    setSectionDraft,
  ] = useState('');

  const [
    attachmentType,
    setAttachmentType,
  ] = useState<
    StrategyAttachment['attachment_type']
  >('player');

  const [
    error,
    setError,
  ] = useState('');

  const [
    message,
    setMessage,
  ] = useState('');

  const roles =
    user?.roles ?? [];

  const isAdministrator =
    roles.includes('Administrator');

  const isCoach =
    roles.includes('Coach');

  const canManage =
    isAdministrator ||
    isCoach;

  const canCollaborate =
    canManage ||
    roles.includes('Analyst') ||
    roles.includes('Selector') ||
    roles.includes('Team Manager');

  const load =
    async () => {
      setError('');

      try {
        const [
          planRow,
          optionRows,
          versionRows,
        ] =
          await Promise.all([
            getStrategyPlan(
              organizationId,
              planId,
            ),
            getStrategyOptions(
              organizationId,
            ),
            getStrategyVersions(
              organizationId,
              planId,
            ),
          ]);

        setPlan(planRow);
        setOptions(optionRows);
        setVersions(versionRows);
      } catch (caught) {
        console.error(caught);
        setError(
          'Unable to load tactical workspace.',
        );
      }
    };

  useEffect(() => {
    void load();
  }, [
    organizationId,
    planId,
  ]);

  const match =
    useMemo(
      () =>
        options.matches.find(
          (row) =>
            row.id ===
            plan?.match_id,
        ) ?? null,
      [
        options.matches,
        plan?.match_id,
      ],
    );

  const currentUserMention =
    plan?.mentions?.filter(
      (mention) =>
        mention.mentioned_user_id ===
          user?.id &&
        !mention.read_at,
    ).length ?? 0;

  const openDiscussions =
    plan?.notes?.filter(
      (note) =>
        note.status ===
        'Open',
    ).length ?? 0;

  const pendingAssignments =
    plan?.assignments?.filter(
      (assignment) =>
        assignment.status !==
        'Done',
    ).length ?? 0;

  const saveSection =
    async (
      section:
        StrategySection,
    ) => {
      setError('');
      setMessage('');

      try {
        await updateStrategySection(
          organizationId,
          planId,
          section.id,
          {
            content:
              sectionDraft ||
              null,
          },
        );

        setEditingSectionId(
          null,
        );
        setMessage(
          `${section.title} saved.`,
        );
        await load();
      } catch (caught: any) {
        setError(
          caught?.response?.data
            ?.message ??
            'Unable to save strategy section.',
        );
      }
    };

  const submitPlanSettings =
    async (
      event:
        FormEvent<HTMLFormElement>,
    ) => {
      event.preventDefault();

      if (!plan) {
        return;
      }

      const data =
        new FormData(
          event.currentTarget,
        );

      try {
        const updated =
          await updateStrategyPlan(
            organizationId,
            planId,
            {
              match_id:
                plan.match_id,
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
                data.get(
                  'venue_id',
                )
                  ? Number(
                      data.get(
                        'venue_id',
                      ),
                    )
                  : null,
              title:
                data.get(
                  'title',
                ),
              status:
                data.get(
                  'status',
                ),
              summary:
                data.get(
                  'summary',
                ) ||
                null,
            },
          );

        setPlan(updated);
        setMessage(
          'Strategy plan details updated.',
        );
      } catch (caught: any) {
        setError(
          caught?.response?.data
            ?.message ??
            'Unable to update strategy plan.',
        );
      }
    };

  const submitNote =
    async (
      event:
        FormEvent<HTMLFormElement>,
    ) => {
      event.preventDefault();

      const form =
        event.currentTarget;

      const data =
        new FormData(form);

      try {
        await createTacticalNote(
          organizationId,
          planId,
          {
            strategy_section_id:
              data.get(
                'strategy_section_id',
              )
                ? Number(
                    data.get(
                      'strategy_section_id',
                    ),
                  )
                : null,
            title:
              data.get('title') ||
              null,
            body:
              data.get('body'),
            mention_user_ids:
              data
                .getAll(
                  'mention_user_ids',
                )
                .map(Number),
          },
        );

        form.reset();
        setMessage(
          'Tactical note added.',
        );
        await load();
      } catch (caught: any) {
        setError(
          caught?.response?.data
            ?.message ??
            'Unable to create tactical note.',
        );
      }
    };

  const submitComment =
    async (
      event:
        FormEvent<HTMLFormElement>,
      noteId: number,
    ) => {
      event.preventDefault();

      const form =
        event.currentTarget;

      const data =
        new FormData(form);

      try {
        await createStrategyComment(
          organizationId,
          planId,
          noteId,
          {
            body:
              data.get('body'),
            mention_user_ids:
              data
                .getAll(
                  'mention_user_ids',
                )
                .map(Number),
          },
        );

        form.reset();
        await load();
      } catch (caught: any) {
        setError(
          caught?.response?.data
            ?.message ??
            'Unable to add comment.',
        );
      }
    };

  const submitAssignment =
    async (
      event:
        FormEvent<HTMLFormElement>,
    ) => {
      event.preventDefault();

      const form =
        event.currentTarget;

      const data =
        new FormData(form);

      try {
        await createStrategyAssignment(
          organizationId,
          planId,
          {
            strategy_section_id:
              data.get(
                'strategy_section_id',
              )
                ? Number(
                    data.get(
                      'strategy_section_id',
                    ),
                  )
                : null,
            assigned_to:
              Number(
                data.get(
                  'assigned_to',
                ),
              ),
            title:
              data.get('title'),
            description:
              data.get(
                'description',
              ) ||
              null,
            status: 'Todo',
            due_at:
              data.get('due_at') ||
              null,
          },
        );

        form.reset();
        setMessage(
          'Strategy assignment created.',
        );
        await load();
      } catch (caught: any) {
        setError(
          caught?.response?.data
            ?.message ??
            'Unable to create assignment.',
        );
      }
    };

  const attachmentEntityOptions =
    useMemo(() => {
      switch (
        attachmentType
      ) {
        case 'player':
          return options.players.map(
            (player) => ({
              id: player.id,
              label:
                `${player.display_name} · ${player.primary_role}`,
            }),
          );

        case 'match':
          return options.matches.map(
            (row) => ({
              id: row.id,
              label:
                `${row.home_team_name} vs ${row.away_team_name}`,
            }),
          );

        case 'scouting_report':
          return options.scouting_reports.map(
            (report) => ({
              id: report.id,
              label:
                `${report.display_name} · ${report.report_date}`,
            }),
          );

        case 'scouting_media':
          return options.scouting_media.map(
            (media) => ({
              id: media.id,
              label:
                `${media.display_name} · ${media.caption ?? media.original_filename ?? media.media_type}`,
            }),
          );

        default:
          return [];
      }
    }, [
      attachmentType,
      options,
    ]);

  const submitAttachment =
    async (
      event:
        FormEvent<HTMLFormElement>,
    ) => {
      event.preventDefault();

      const form =
        event.currentTarget;

      const data =
        new FormData(form);

      data.set(
        'source_type',
        'plan',
      );

      data.set(
        'source_id',
        String(planId),
      );

      if (
        ['file', 'url'].includes(
          attachmentType,
        )
      ) {
        data.delete(
          'entity_id',
        );
      }

      try {
        await createStrategyAttachment(
          organizationId,
          planId,
          data,
        );

        form.reset();
        setAttachmentType(
          'player',
        );
        setMessage(
          'Attachment added.',
        );
        await load();
      } catch (caught: any) {
        setError(
          caught?.response?.data
            ?.message ??
            'Unable to add attachment.',
        );
      }
    };

  if (!plan) {
    return (
      <AppLayout>
        <div
          className={
            error
              ? 'page-state error-state'
              : 'page-state'
          }
        >
          {error ||
            'Loading tactical workspace...'}
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <div className="strategy-workspace-heading">
        <div>
          <div className="strategy-workspace-breadcrumb">
            <Link
              to={`/organizations/${organizationId}/strategy`}
            >
              Tactical planning
            </Link>

            <AppIcon
              name="chevronRight"
              size={13}
            />

            <span>
              Match #{plan.match_id}
            </span>
          </div>

          <div className="strategy-title-line">
            <span className={`strategy-status ${plan.status.toLowerCase()}`}>
              {plan.status}
            </span>

            {plan.locked_at && (
              <span className="strategy-lock-badge">
                Locked
              </span>
            )}

            <h1>
              {plan.title}
            </h1>
          </div>

          <p>
            {match
              ? `${match.home_team_name} vs ${match.away_team_name} · ${String(match.scheduled_at).slice(0, 10)}`
              : `Match #${plan.match_id}`}
            {' · '}
            {plan.venue?.name ??
              match?.venue_name ??
              'Venue not set'}
          </p>
        </div>

        <div className="strategy-workspace-actions">
          <span className="strategy-collab-presence">
            <AppIcon
              name="players"
              size={16}
            />
            {
              options.collaborators
                .length
            }
            {' '}
            collaborators
          </span>

          {canManage && (
            <button
              type="button"
              className="secondary"
              onClick={async () => {
                try {
                  await setStrategyLock(
                    organizationId,
                    planId,
                    !plan.locked_at,
                  );

                  await load();
                } catch (
                  caught: any
                ) {
                  setError(
                    caught?.response
                      ?.data
                      ?.message ??
                      'Unable to change lock state.',
                  );
                }
              }}
            >
              {plan.locked_at
                ? 'Unlock plan'
                : 'Lock plan'}
            </button>
          )}
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

      <div className="strategy-context-grid">
        <article>
          <span>
            Opponent
          </span>
          <strong>
            {plan.opponent_team
              ?.name ??
              'Not selected'}
          </strong>
        </article>

        <article>
          <span>
            Venue
          </span>
          <strong>
            {plan.venue?.name ??
              match?.venue_name ??
              'Not selected'}
          </strong>
        </article>

        <article>
          <span>
            Open discussions
          </span>
          <strong>
            {openDiscussions}
          </strong>
        </article>

        <article>
          <span>
            Pending assignments
          </span>
          <strong>
            {pendingAssignments}
          </strong>
        </article>

        <article>
          <span>
            Your mentions
          </span>
          <strong>
            {currentUserMention}
          </strong>
        </article>
      </div>

      <nav className="strategy-tabs">
        {[
          [
            'board',
            'Strategy Board',
          ],
          [
            'discussions',
            `Discussions (${openDiscussions})`,
          ],
          [
            'assignments',
            `Assignments (${pendingAssignments})`,
          ],
          [
            'attachments',
            `Attachments (${plan.attachments?.length ?? 0})`,
          ],
          [
            'history',
            'Version History',
          ],
        ].map(
          ([key, label]) => (
            <button
              type="button"
              key={key}
              className={
                activeTab === key
                  ? 'active'
                  : ''
              }
              onClick={() =>
                setActiveTab(
                  key as WorkspaceTab,
                )
              }
            >
              {label}
            </button>
          ),
        )}
      </nav>

      {activeTab ===
        'board' && (
        <div className="strategy-board-layout">
          <div className="strategy-section-board">
            {(plan.sections ??
              []).map(
              (section) => (
                <article
                  key={section.id}
                  className="strategy-section-card"
                >
                  <header>
                    <div>
                      <span>
                        {section.section_key.replaceAll(
                          '_',
                          ' ',
                        )}
                      </span>

                      <h2>
                        {section.title}
                      </h2>
                    </div>

                    {canManage &&
                      !plan.locked_at && (
                      <button
                        type="button"
                        className="strategy-card-action"
                        onClick={() => {
                          setEditingSectionId(
                            section.id,
                          );
                          setSectionDraft(
                            section.content ??
                              '',
                          );
                        }}
                      >
                        Edit
                      </button>
                    )}
                  </header>

                  {editingSectionId ===
                  section.id ? (
                    <div className="strategy-section-editor">
                      <textarea
                        value={
                          sectionDraft
                        }
                        onChange={(
                          event,
                        ) =>
                          setSectionDraft(
                            event.target
                              .value,
                          )
                        }
                        placeholder={`Add ${section.title.toLowerCase()}...`}
                      />

                      <div>
                        <button
                          type="button"
                          className="secondary"
                          onClick={() =>
                            setEditingSectionId(
                              null,
                            )
                          }
                        >
                          Cancel
                        </button>

                        <button
                          type="button"
                          onClick={() =>
                            void saveSection(
                              section,
                            )
                          }
                        >
                          Save
                        </button>
                      </div>
                    </div>
                  ) : (
                    <div className="strategy-section-content">
                      {section.content ? (
                        <p>
                          {
                            section.content
                          }
                        </p>
                      ) : (
                        <span>
                          No tactical
                          content added
                          yet.
                        </span>
                      )}
                    </div>
                  )}

                  <footer>
                    <span>
                      Updated
                      {' '}
                      {new Date(
                        section.updated_at,
                      ).toLocaleString()}
                    </span>

                    {section.updater && (
                      <span>
                        by
                        {' '}
                        {
                          section
                            .updater
                            .name
                        }
                      </span>
                    )}
                  </footer>
                </article>
              ),
            )}
          </div>

          <aside className="strategy-board-sidebar">
            <section>
              <h3>
                Match context
              </h3>

              <dl>
                <div>
                  <dt>
                    Opponent
                  </dt>
                  <dd>
                    {plan
                      .opponent_team
                      ?.name ??
                      '—'}
                  </dd>
                </div>

                <div>
                  <dt>
                    Venue
                  </dt>
                  <dd>
                    {plan.venue
                      ?.name ??
                      match?.venue_name ??
                      '—'}
                  </dd>
                </div>

                <div>
                  <dt>
                    Pitch
                  </dt>
                  <dd>
                    {plan.venue
                      ?.pitch_type ??
                      'Not recorded'}
                  </dd>
                </div>

                <div>
                  <dt>
                    Available players
                  </dt>
                  <dd>
                    {
                      options.players.filter(
                        (player) =>
                          player.status.toLowerCase() ===
                          'active',
                      ).length
                    }
                  </dd>
                </div>
              </dl>
            </section>

            {canManage && (
              <section>
                <h3>
                  Plan settings
                </h3>

                <form
                  onSubmit={(
                    event,
                  ) =>
                    void submitPlanSettings(
                      event,
                    )
                  }
                >
                  <label>
                    Title
                    <input
                      name="title"
                      defaultValue={
                        plan.title
                      }
                      required
                    />
                  </label>

                  <label>
                    Opponent
                    <select
                      name="opponent_team_id"
                      defaultValue={
                        plan.opponent_team_id ??
                        ''
                      }
                    >
                      <option value="">
                        Select opponent
                      </option>

                      {options.teams.map(
                        (team) => (
                          <option
                            key={
                              team.id
                            }
                            value={
                              team.id
                            }
                          >
                            {
                              team.name
                            }
                          </option>
                        ),
                      )}
                    </select>
                  </label>

                  <label>
                    Venue
                    <select
                      name="venue_id"
                      defaultValue={
                        plan.venue_id ??
                        ''
                      }
                    >
                      <option value="">
                        Match venue
                      </option>

                      {options.venues.map(
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
                    Status
                    <select
                      name="status"
                      defaultValue={
                        plan.status
                      }
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

                  <label>
                    Summary
                    <textarea
                      name="summary"
                      defaultValue={
                        plan.summary ??
                        ''
                      }
                    />
                  </label>

                  <button
                    type="submit"
                    disabled={
                      Boolean(
                        plan.locked_at,
                      )
                    }
                  >
                    Save plan
                  </button>
                </form>
              </section>
            )}
          </aside>
        </div>
      )}

      {activeTab ===
        'discussions' && (
        <div className="strategy-collaboration-layout">
          <section className="strategy-discussion-column">
            {(plan.notes ??
              []).map(
              (note) => (
                <article
                  key={note.id}
                  className={`strategy-discussion-card ${note.status.toLowerCase()}`}
                >
                  <header>
                    <div>
                      <span>
                        {note.section
                          ?.title ??
                          'General strategy'}
                      </span>

                      <h3>
                        {note.title ??
                          'Tactical discussion'}
                      </h3>
                    </div>

                    <span className={`strategy-discussion-status ${note.status.toLowerCase()}`}>
                      {
                        note.status
                      }
                    </span>
                  </header>

                  <div className="strategy-discussion-author">
                    <strong>
                      {note.author
                        ?.name ??
                        'Collaborator'}
                    </strong>

                    <span>
                      {new Date(
                        note.created_at,
                      ).toLocaleString()}
                    </span>
                  </div>

                  <p>
                    {note.body}
                  </p>

                  <div className="strategy-comment-thread">
                    {(note.comments ??
                      []).map(
                      (comment) => (
                        <div
                          key={
                            comment.id
                          }
                          className="strategy-comment"
                        >
                          <div>
                            <strong>
                              {comment
                                .author
                                ?.name ??
                                'User'}
                            </strong>

                            <span>
                              {new Date(
                                comment.created_at,
                              ).toLocaleString()}
                            </span>
                          </div>

                          <p>
                            {
                              comment.body
                            }
                          </p>
                        </div>
                      ),
                    )}
                  </div>

                  {canCollaborate &&
                    !plan.locked_at && (
                    <form
                      className="strategy-comment-form"
                      onSubmit={(
                        event,
                      ) =>
                        void submitComment(
                          event,
                          note.id,
                        )
                      }
                    >
                      <input
                        name="body"
                        placeholder="Add comment..."
                        required
                      />

                      <select
                        name="mention_user_ids"
                        multiple
                        title="Mention collaborators"
                      >
                        {options.collaborators.map(
                          (
                            collaborator,
                          ) => (
                            <option
                              key={
                                collaborator.id
                              }
                              value={
                                collaborator.id
                              }
                            >
                              @
                              {
                                collaborator.name
                              }
                            </option>
                          ),
                        )}
                      </select>

                      <button type="submit">
                        Comment
                      </button>
                    </form>
                  )}

                  {canManage &&
                    !plan.locked_at && (
                    <footer>
                      <button
                        type="button"
                        className="secondary"
                        onClick={async () => {
                          try {
                            await resolveTacticalNote(
                              organizationId,
                              planId,
                              note.id,
                            );
                            await load();
                          } catch (
                            caught: any
                          ) {
                            setError(
                              caught
                                ?.response
                                ?.data
                                ?.message ??
                                'Unable to change discussion state.',
                            );
                          }
                        }}
                      >
                        {note.status ===
                        'Resolved'
                          ? 'Reopen discussion'
                          : 'Resolve discussion'}
                      </button>
                    </footer>
                  )}
                </article>
              ),
            )}

            {(plan.notes ??
              []).length ===
              0 && (
              <div className="strategy-empty-panel">
                No tactical discussions
                yet.
              </div>
            )}
          </section>

          <aside className="strategy-collaboration-sidebar">
            {canCollaborate &&
              !plan.locked_at && (
              <section>
                <h2>
                  Create tactical note
                </h2>

                <p>
                  Mention analysts,
                  selectors or managers to
                  bring them into the
                  discussion.
                </p>

                <form
                  onSubmit={(
                    event,
                  ) =>
                    void submitNote(
                      event,
                    )
                  }
                >
                  <label>
                    Section
                    <select name="strategy_section_id">
                      <option value="">
                        General
                      </option>

                      {plan.sections?.map(
                        (
                          section,
                        ) => (
                          <option
                            key={
                              section.id
                            }
                            value={
                              section.id
                            }
                          >
                            {
                              section.title
                            }
                          </option>
                        ),
                      )}
                    </select>
                  </label>

                  <label>
                    Title
                    <input
                      name="title"
                      placeholder="Powerplay matchup concern"
                    />
                  </label>

                  <label>
                    Note
                    <textarea
                      name="body"
                      placeholder="Write tactical note..."
                      required
                    />
                  </label>

                  <label>
                    @ Mention
                    <select
                      name="mention_user_ids"
                      multiple
                    >
                      {options.collaborators.map(
                        (
                          collaborator,
                        ) => (
                          <option
                            key={
                              collaborator.id
                            }
                            value={
                              collaborator.id
                            }
                          >
                            @
                            {
                              collaborator.name
                            }
                            {' · '}
                            {
                              collaborator.roles?.join(
                                ', ',
                              )
                            }
                          </option>
                        ),
                      )}
                    </select>
                  </label>

                  <button type="submit">
                    Add note
                  </button>
                </form>
              </section>
            )}
          </aside>
        </div>
      )}

      {activeTab ===
        'assignments' && (
        <div className="strategy-collaboration-layout">
          <section className="strategy-assignment-board">
            {[
              'Todo',
              'In Progress',
              'Done',
            ].map(
              (status) => (
                <div
                  key={status}
                  className="strategy-assignment-column"
                >
                  <header>
                    <h2>
                      {status}
                    </h2>

                    <span>
                      {
                        plan.assignments?.filter(
                          (
                            assignment,
                          ) =>
                            assignment.status ===
                            status,
                        ).length ??
                        0
                      }
                    </span>
                  </header>

                  <div>
                    {plan.assignments
                      ?.filter(
                        (
                          assignment,
                        ) =>
                          assignment.status ===
                          status,
                      )
                      .map(
                        (
                          assignment,
                        ) => (
                          <article
                            key={
                              assignment.id
                            }
                            className="strategy-assignment-card"
                          >
                            <span>
                              {assignment
                                .section
                                ?.title ??
                                'General'}
                            </span>

                            <strong>
                              {
                                assignment.title
                              }
                            </strong>

                            <p>
                              {assignment.description ??
                                'No description.'}
                            </p>

                            <div>
                              <span>
                                {assignment
                                  .assignee
                                  ?.name ??
                                  'Unassigned'}
                              </span>

                              <span>
                                {assignment.due_at
                                  ? new Date(
                                      assignment.due_at,
                                    ).toLocaleDateString()
                                  : 'No due date'}
                              </span>
                            </div>

                            {(canManage ||
                              assignment.assigned_to ===
                                user?.id) &&
                              !plan.locked_at && (
                              <select
                                value={
                                  assignment.status
                                }
                                onChange={async (
                                  event,
                                ) => {
                                  try {
                                    await updateStrategyAssignment(
                                      organizationId,
                                      planId,
                                      assignment.id,
                                      {
                                        strategy_section_id:
                                          assignment.strategy_section_id ??
                                          null,
                                        assigned_to:
                                          assignment.assigned_to,
                                        title:
                                          assignment.title,
                                        description:
                                          assignment.description ??
                                          null,
                                        status:
                                          event.target.value,
                                        due_at:
                                          assignment.due_at ??
                                          null,
                                      },
                                    );

                                    await load();
                                  } catch (
                                    caught: any
                                  ) {
                                    setError(
                                      caught
                                        ?.response
                                        ?.data
                                        ?.message ??
                                        'Unable to update assignment.',
                                    );
                                  }
                                }}
                              >
                                <option>
                                  Todo
                                </option>
                                <option>
                                  In Progress
                                </option>
                                <option>
                                  Done
                                </option>
                              </select>
                            )}
                          </article>
                        ),
                      )}
                  </div>
                </div>
              ),
            )}
          </section>

          {canManage &&
            !plan.locked_at && (
            <aside className="strategy-collaboration-sidebar">
              <section>
                <h2>
                  New assignment
                </h2>

                <form
                  onSubmit={(
                    event,
                  ) =>
                    void submitAssignment(
                      event,
                    )
                  }
                >
                  <label>
                    Title
                    <input
                      name="title"
                      required
                    />
                  </label>

                  <label>
                    Assignee
                    <select
                      name="assigned_to"
                      required
                    >
                      <option value="">
                        Select collaborator
                      </option>

                      {options.collaborators.map(
                        (
                          collaborator,
                        ) => (
                          <option
                            key={
                              collaborator.id
                            }
                            value={
                              collaborator.id
                            }
                          >
                            {
                              collaborator.name
                            }
                            {' · '}
                            {
                              collaborator.roles?.join(
                                ', ',
                              )
                            }
                          </option>
                        ),
                      )}
                    </select>
                  </label>

                  <label>
                    Section
                    <select name="strategy_section_id">
                      <option value="">
                        General
                      </option>

                      {plan.sections?.map(
                        (
                          section,
                        ) => (
                          <option
                            key={
                              section.id
                            }
                            value={
                              section.id
                            }
                          >
                            {
                              section.title
                            }
                          </option>
                        ),
                      )}
                    </select>
                  </label>

                  <label>
                    Due date
                    <input
                      name="due_at"
                      type="datetime-local"
                    />
                  </label>

                  <label>
                    Description
                    <textarea name="description" />
                  </label>

                  <button type="submit">
                    Assign
                  </button>
                </form>
              </section>
            </aside>
          )}
        </div>
      )}

      {activeTab ===
        'attachments' && (
        <div className="strategy-collaboration-layout">
          <section className="strategy-attachment-grid">
            {(plan.attachments ??
              []).map(
              (
                attachment,
              ) => (
                <article
                  key={
                    attachment.id
                  }
                  className="strategy-attachment-card"
                >
                  <span className="strategy-attachment-icon">
                    <AppIcon
                      name={
                        attachment.attachment_type ===
                        'player'
                          ? 'players'
                          : attachment.attachment_type ===
                              'match'
                            ? 'activity'
                            : attachment.attachment_type.startsWith(
                                  'scouting',
                                )
                              ? 'scouting'
                              : 'fixture'
                      }
                      size={19}
                    />
                  </span>

                  <div>
                    <strong>
                      {attachment.label ??
                        attachment.original_filename ??
                        attachment.attachment_type.replaceAll(
                          '_',
                          ' ',
                        )}
                    </strong>

                    <span>
                      {attachment.attachment_type.replaceAll(
                        '_',
                        ' ',
                      )}
                      {' · '}
                      {attachment.uploader
                        ?.name ??
                        'User'}
                    </span>
                  </div>

                  {(attachment.file_url ||
                    attachment.external_url) && (
                    <a
                      href={
                        attachment.file_url ??
                        attachment.external_url ??
                        '#'
                      }
                      target="_blank"
                      rel="noreferrer"
                    >
                      Open
                    </a>
                  )}
                </article>
              ),
            )}

            {(plan.attachments ??
              []).length ===
              0 && (
              <div className="strategy-empty-panel">
                No attachments yet.
              </div>
            )}
          </section>

          {canCollaborate &&
            !plan.locked_at && (
            <aside className="strategy-collaboration-sidebar">
              <section>
                <h2>
                  Attach context
                </h2>

                <p>
                  Attach a player,
                  match, scouting report,
                  scouting media, file or
                  external URL.
                </p>

                <form
                  onSubmit={(
                    event,
                  ) =>
                    void submitAttachment(
                      event,
                    )
                  }
                >
                  <label>
                    Type
                    <select
                      name="attachment_type"
                      value={
                        attachmentType
                      }
                      onChange={(
                        event,
                      ) =>
                        setAttachmentType(
                          event.target
                            .value as StrategyAttachment['attachment_type'],
                        )
                      }
                    >
                      <option value="player">
                        Player
                      </option>
                      <option value="match">
                        Match
                      </option>
                      <option value="scouting_report">
                        Scouting report
                      </option>
                      <option value="scouting_media">
                        Scouting media
                      </option>
                      <option value="file">
                        File
                      </option>
                      <option value="url">
                        External URL
                      </option>
                    </select>
                  </label>

                  {attachmentEntityOptions.length >
                    0 && (
                    <label>
                      Linked item
                      <select
                        name="entity_id"
                        required
                      >
                        <option value="">
                          Select item
                        </option>

                        {attachmentEntityOptions.map(
                          (
                            item,
                          ) => (
                            <option
                              key={
                                item.id
                              }
                              value={
                                item.id
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
                  )}

                  {attachmentType ===
                    'file' && (
                    <label>
                      File
                      <input
                        name="file"
                        type="file"
                        required
                      />
                    </label>
                  )}

                  {attachmentType ===
                    'url' && (
                    <label>
                      URL
                      <input
                        name="external_url"
                        type="url"
                        required
                      />
                    </label>
                  )}

                  <label>
                    Label
                    <input
                      name="label"
                      placeholder="Why this attachment matters"
                    />
                  </label>

                  <button type="submit">
                    Attach
                  </button>
                </form>
              </section>
            </aside>
          )}
        </div>
      )}

      {activeTab ===
        'history' && (
        <section className="strategy-history">
          <div className="section-heading">
            <div>
              <h2>
                Version history &
                audit trail
              </h2>

              <p>
                Every saved strategy
                change is recorded with
                actor, version and event
                metadata.
              </p>
            </div>
          </div>

          <div className="strategy-timeline">
            {versions.map(
              (version) => (
                <article
                  key={version.id}
                >
                  <span className="strategy-version-number">
                    v
                    {
                      version.version_number
                    }
                  </span>

                  <div>
                    <strong>
                      {
                        version.change_summary
                      }
                    </strong>

                    <span>
                      {version.actor
                        ?.name ??
                        'System'}
                      {' · '}
                      {new Date(
                        version.created_at,
                      ).toLocaleString()}
                    </span>

                    <small>
                      {
                        version.event_type
                      }
                      {' · '}
                      {
                        version.entity_type
                      }
                    </small>
                  </div>
                </article>
              ),
            )}
          </div>
        </section>
      )}
    </AppLayout>
  );
}
