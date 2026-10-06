import {
  useEffect,
  useMemo,
  useState,
} from 'react';

import {
  Link,
} from 'react-router-dom';

import {
  useAuth,
} from '../auth/AuthContext';

import {
  listOrganizations,
} from '../api/organizations';

import {
  AppIcon,
  type AppIconName,
} from '../components/AppIcon';

import {
  AppLayout,
} from '../components/AppLayout';

import type {
  Organization,
} from '../types/organization';

type Capability = {
  milestone: string;
  title: string;
  description: string;
  icon: AppIconName;
};

const capabilities: Capability[] = [
  {
    milestone: 'P1',
    title: 'Identity & Access',
    description:
      'Sanctum authentication, roles and protected operations.',
    icon: 'shield',
  },
  {
    milestone: 'P2',
    title: 'Organization Core',
    description:
      'Organizations, clubs, teams and seasons.',
    icon: 'organization',
  },
  {
    milestone: 'P3',
    title: 'Player Management',
    description:
      'Player profiles, team membership and availability.',
    icon: 'players',
  },
  {
    milestone: 'P4',
    title: 'Competition Operations',
    description:
      'Venues, tournaments, registrations and fixtures.',
    icon: 'trophy',
  },
  {
    milestone: 'P5',
    title: 'Match Engine',
    description:
      'Deterministic innings, overs and ball-by-ball scoring.',
    icon: 'activity',
  },
  {
    milestone: 'P6',
    title: 'Cricket Statistics',
    description:
      'Batting, bowling, fielding and match statistics.',
    icon: 'analytics',
  },
  {
    milestone: 'P7',
    title: 'Analytics',
    description:
      'Trend dashboards, charts and comparisons.',
    icon: 'analytics',
  },
  {
    milestone: 'P8',
    title: 'Realtime Match Centre',
    description:
      'Live match updates powered by Laravel Reverb.',
    icon: 'activity',
  },
  {
    milestone: 'P9',
    title: 'Selection',
    description:
      'Squads, playing XI, batting order and bowling roles.',
    icon: 'teams',
  },
  {
    milestone: 'P10',
    title: 'Training & Development',
    description:
      'Sessions, attendance, fitness and development plans.',
    icon: 'training',
  },
  {
    milestone: 'P11',
    title: 'Scouting & Recruitment',
    description:
      'Prospect reports, ratings, media and recruitment.',
    icon: 'scouting',
  },
  {
    milestone: 'P12',
    title: 'Opponent Intelligence',
    description:
      'Deterministic opponent, matchup and partnership analytics.',
    icon: 'target',
  },
  {
    milestone: 'P13',
    title: 'Tactical Planning',
    description:
      'Collaborative match strategy, discussions, mentions and audit history.',
    icon: 'strategy',
  },
  {
    milestone: 'P14',
    title: 'Predictive Analytics',
    description:
      'Leakage-controlled score, economy and team-total predictions with uncertainty.',
    icon: 'analytics',
  },
  {
    milestone: 'P15',
    title: 'AI Strategy Assistant',
    description:
      'Evidence-grounded tactical explanations with provenance and hallucination validation.',
    icon: 'strategy',
  },
  {
    milestone: 'P16',
    title: 'Natural-Language Analytics',
    description:
      'Controlled analytics intents, validated filters and automatic cricket visualizations.',
    icon: 'analytics',
  },
];

export function DashboardPage() {
  const {
    user,
  } = useAuth();

  const [
    organizations,
    setOrganizations,
  ] = useState<Organization[]>([]);

  const [
    organizationTotal,
    setOrganizationTotal,
  ] = useState(0);

  const [
    loadingOrganizations,
    setLoadingOrganizations,
  ] = useState(true);

  const [
    organizationError,
    setOrganizationError,
  ] = useState('');

  const roles =
    user?.roles ?? [];

  const isAdministrator =
    roles.includes('Administrator');

  useEffect(() => {
    let active = true;

    const load =
      async () => {
        setLoadingOrganizations(true);
        setOrganizationError('');

        try {
          const result =
            await listOrganizations({
              per_page: 6,
            });

          if (!active) {
            return;
          }

          setOrganizations(
            Array.isArray(result.data)
              ? result.data
              : [],
          );

          setOrganizationTotal(
            Number(
              result.meta?.total ??
                result.data?.length ??
                0,
            ),
          );
        } catch (error) {
          console.error(
            'Dashboard organizations failed:',
            error,
          );

          if (active) {
            setOrganizationError(
              'Unable to load organization overview.',
            );
          }
        } finally {
          if (active) {
            setLoadingOrganizations(false);
          }
        }
      };

    void load();

    return () => {
      active = false;
    };
  }, []);

  const primaryOrganization =
    organizations[0] ?? null;

  const quickActions =
    useMemo(() => {
      const items: Array<{
        title: string;
        description: string;
        to: string;
        icon: AppIconName;
        accent: string;
      }> = [
        {
          title: 'Organizations',
          description:
            'Open your cricket organizations and operational workspaces.',
          to: '/organizations',
          icon: 'organization',
          accent: 'orange',
        },
      ];

      if (primaryOrganization) {
        const id =
          primaryOrganization.id;

        items.push(
          {
            title: 'Match Centre',
            description:
              'Open matches, scoring and live match operations.',
            to: `/organizations/${id}/matches`,
            icon: 'activity',
            accent: 'red',
          },
          {
            title: 'Analytics',
            description:
              'Review deterministic cricket statistics and trends.',
            to: `/organizations/${id}/analytics`,
            icon: 'analytics',
            accent: 'blue',
          },
          {
            title: 'Training',
            description:
              'Manage sessions, attendance, fitness and development.',
            to: `/organizations/${id}/training`,
            icon: 'training',
            accent: 'green',
          },
          {
            title: 'Scouting',
            description:
              'Review prospects, reports, ratings and recruitment.',
            to: `/organizations/${id}/scouting`,
            icon: 'scouting',
            accent: 'purple',
          },
          {
            title: 'Opponent Intel',
            description:
              'Explore matchup, batter, bowler and partnership tendencies.',
            to: `/organizations/${id}/opponent-intelligence`,
            icon: 'target',
            accent: 'slate',
          },
          {
            title: 'Tactical Strategy',
            description:
              'Build and collaborate on match plans, notes and assignments.',
            to: `/organizations/${id}/strategy`,
            icon: 'strategy',
            accent: 'orange',
          },
          {
            title: 'Predictive Analytics',
            description:
              'Review leakage-controlled forecasts, uncertainty and form trends.',
            to: `/organizations/${id}/predictive`,
            icon: 'analytics',
            accent: 'blue',
          },
        );
      }

      if (isAdministrator) {
        items.push({
          title: 'Administration',
          description:
            'Manage administrative access and platform controls.',
          to: '/admin',
          icon: 'shield',
          accent: 'dark',
        });
      }

      return items;
    }, [
      primaryOrganization,
      isAdministrator,
    ]);

  const displayDate =
    new Intl.DateTimeFormat(
      undefined,
      {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      },
    ).format(new Date());

  return (
    <AppLayout>
      <section className="dashboard-hero">
        <div className="dashboard-hero-copy">
          <p className="dashboard-kicker">
            CricIntel Command Centre
          </p>

          <h1>
            Welcome back,
            {' '}
            <span>
              {user?.name ??
                'CricIntel User'}
            </span>
          </h1>

          <p>
            Your cricket operations,
            match intelligence,
            player development and
            recruitment workflows are
            available from one
            professional workspace.
          </p>

          <div className="dashboard-hero-actions">
            <Link
              to="/organizations"
              className="dashboard-primary-action"
            >
              <AppIcon
                name="organization"
                size={18}
              />

              Open organizations

              <AppIcon
                name="arrowRight"
                size={17}
              />
            </Link>

            {primaryOrganization && (
              <Link
                to={`/organizations/${primaryOrganization.id}`}
                className="dashboard-secondary-action"
              >
                Continue
                {' '}
                {primaryOrganization.short_name ??
                  primaryOrganization.name}

                <AppIcon
                  name="arrowRight"
                  size={17}
                />
              </Link>
            )}
          </div>
        </div>

        <div className="dashboard-hero-panel">
          <div className="dashboard-date-row">
            <AppIcon
              name="calendar"
              size={18}
            />

            <span>
              {displayDate}
            </span>
          </div>

          <div className="dashboard-session-card">
            <span className="ci-status-dot online" />

            <div>
              <strong>
                Platform operational
              </strong>

              <small>
                Authenticated CricIntel
                session
              </small>
            </div>
          </div>

          <div className="dashboard-role-stack">
            <span>
              Access profile
            </span>

            <div>
              {roles.length ? (
                roles.map(
                  (role) => (
                    <strong
                      key={role}
                    >
                      {role}
                    </strong>
                  ),
                )
              ) : (
                <strong>
                  User
                </strong>
              )}
            </div>
          </div>
        </div>
      </section>

      <section className="dashboard-stat-grid">
        <article className="dashboard-stat-card">
          <span className="dashboard-stat-icon">
            <AppIcon
              name="organization"
              size={21}
            />
          </span>

          <div>
            <strong>
              {loadingOrganizations
                ? '…'
                : organizationTotal}
            </strong>

            <span>
              Accessible organizations
            </span>
          </div>

          <small>
            Membership-scoped access
          </small>
        </article>

        <article className="dashboard-stat-card">
          <span className="dashboard-stat-icon blue">
            <AppIcon
              name="shield"
              size={21}
            />
          </span>

          <div>
            <strong>
              {roles.length}
            </strong>

            <span>
              Active roles
            </span>
          </div>

          <small>
            Role-based authorization
          </small>
        </article>

        <article className="dashboard-stat-card">
          <span className="dashboard-stat-icon green">
            <AppIcon
              name="activity"
              size={21}
            />
          </span>

          <div>
            <strong>
              16
            </strong>

            <span>
              Completed milestones
            </span>
          </div>

          <small>
            P1 through P16
          </small>
        </article>

        <article className="dashboard-stat-card">
          <span className="dashboard-stat-icon purple">
            <AppIcon
              name="analytics"
              size={21}
            />
          </span>

          <div>
            <strong>
              Deterministic
            </strong>

            <span>
              Analytics foundation
            </span>
          </div>

          <small>
            No LLM recommendations yet
          </small>
        </article>
      </section>

      <section className="dashboard-section">
        <div className="dashboard-section-heading">
          <div>
            <p>
              Fast access
            </p>

            <h2>
              Quick actions
            </h2>

            <span>
              Jump directly into your
              most important cricket
              workflows.
            </span>
          </div>
        </div>

        <div className="dashboard-quick-grid">
          {quickActions.map(
            (action) => (
              <Link
                className={`dashboard-quick-card ${action.accent}`}
                to={action.to}
                key={action.title}
              >
                <span className="dashboard-quick-icon">
                  <AppIcon
                    name={action.icon}
                    size={22}
                  />
                </span>

                <div>
                  <strong>
                    {action.title}
                  </strong>

                  <span>
                    {action.description}
                  </span>
                </div>

                <AppIcon
                  name="arrowRight"
                  size={18}
                />
              </Link>
            ),
          )}
        </div>
      </section>

      <div className="dashboard-two-column">
        <section className="dashboard-section">
          <div className="dashboard-section-heading">
            <div>
              <p>
                Workspace
              </p>

              <h2>
                Organizations overview
              </h2>

              <span>
                Open an accessible
                organization-specific
                cricket workspace.
              </span>
            </div>

            <Link
              to="/organizations"
              className="dashboard-text-link"
            >
              View all
              <AppIcon
                name="arrowRight"
                size={15}
              />
            </Link>
          </div>

          {organizationError && (
            <div className="dashboard-inline-error">
              {organizationError}
            </div>
          )}

          <div className="dashboard-organization-list">
            {loadingOrganizations && (
              <>
                <div className="dashboard-org-skeleton" />
                <div className="dashboard-org-skeleton" />
                <div className="dashboard-org-skeleton" />
              </>
            )}

            {!loadingOrganizations &&
              organizations.map(
                (organization) => (
                  <Link
                    to={`/organizations/${organization.id}`}
                    className="dashboard-organization-row"
                    key={organization.id}
                  >
                    <span className="dashboard-org-logo">
                      {organization.logo_url ? (
                        <img
                          src={organization.logo_url}
                          alt=""
                        />
                      ) : (
                        (
                          organization.short_name ??
                          organization.name
                        )
                          .slice(0, 2)
                          .toUpperCase()
                      )}
                    </span>

                    <div>
                      <strong>
                        {organization.name}
                      </strong>

                      <span>
                        {organization.country ??
                          'Country not set'}
                        {' · '}
                        {organization.status}
                      </span>
                    </div>

                    <div className="dashboard-org-meta">
                      <span>
                        {organization.clubs_count ??
                          '—'}
                        {' '}
                        clubs
                      </span>

                      <span>
                        {organization.seasons_count ??
                          '—'}
                        {' '}
                        seasons
                      </span>
                    </div>

                    <AppIcon
                      name="chevronRight"
                      size={17}
                    />
                  </Link>
                ),
              )}

            {!loadingOrganizations &&
              !organizationError &&
              organizations.length ===
                0 && (
                <div className="dashboard-empty-state">
                  <AppIcon
                    name="organization"
                    size={28}
                  />

                  <strong>
                    No organizations
                    available
                  </strong>

                  <span>
                    Create an organization
                    or ask an administrator
                    to add your membership.
                  </span>

                  <Link
                    to="/organizations"
                    className="button"
                  >
                    Open organizations
                  </Link>
                </div>
              )}
          </div>
        </section>

        <section className="dashboard-section">
          <div className="dashboard-section-heading">
            <div>
              <p>
                Access
              </p>

              <h2>
                Your workspace profile
              </h2>

              <span>
                Current session and
                authorization context.
              </span>
            </div>
          </div>

          <div className="dashboard-profile-card">
            <div className="dashboard-profile-head">
              <span className="dashboard-profile-avatar">
                {(user?.name ??
                  'CricIntel User')
                  .split(/\s+/)
                  .filter(Boolean)
                  .slice(0, 2)
                  .map((part) =>
                    part
                      .charAt(0)
                      .toUpperCase(),
                  )
                  .join('')}
              </span>

              <div>
                <strong>
                  {user?.name ??
                    'CricIntel User'}
                </strong>

                <span>
                  {user?.email}
                </span>
              </div>
            </div>

            <div className="dashboard-profile-detail">
              <span>
                Account status
              </span>

              <strong>
                {user?.status ??
                  'Active'}
              </strong>
            </div>

            <div className="dashboard-profile-detail">
              <span>
                Last login
              </span>

              <strong>
                {user?.last_login_at
                  ? new Date(
                      user.last_login_at,
                    ).toLocaleString()
                  : 'Current session'}
              </strong>
            </div>

            <div className="dashboard-profile-roles">
              <span>
                Roles
              </span>

              <div>
                {roles.map(
                  (role) => (
                    <strong
                      key={role}
                    >
                      {role}
                    </strong>
                  ),
                )}
              </div>
            </div>
          </div>
        </section>
      </div>

      <section className="dashboard-section">
        <div className="dashboard-section-heading">
          <div>
            <p>
              Platform capability
            </p>

            <h2>
              CricIntel P1–P16
            </h2>

            <span>
              A consolidated view of the
              cricket-management and
              intelligence capabilities
              currently implemented.
            </span>
          </div>

          <span className="dashboard-completion-badge">
            16 / 16 operational
          </span>
        </div>

        <div className="dashboard-capability-grid">
          {capabilities.map(
            (capability) => (
              <article
                className="dashboard-capability-card"
                key={capability.milestone}
              >
                <div className="dashboard-capability-top">
                  <span className="dashboard-capability-icon">
                    <AppIcon
                      name={capability.icon}
                      size={19}
                    />
                  </span>

                  <strong>
                    {capability.milestone}
                  </strong>
                </div>

                <h3>
                  {capability.title}
                </h3>

                <p>
                  {capability.description}
                </p>

                <span className="dashboard-capability-status">
                  <span className="ci-status-dot online" />
                  Operational
                </span>
              </article>
            ),
          )}
        </div>
      </section>

      <section className="dashboard-system-strip">
        <div>
          <span className="dashboard-system-icon">
            <AppIcon
              name="shield"
              size={21}
            />
          </span>

          <div>
            <strong>
              Secure operational foundation
            </strong>

            <span>
              Sanctum session authentication,
              role-based authorization,
              deterministic statistics and
              realtime match infrastructure.
            </span>
          </div>
        </div>

        <div className="dashboard-system-pills">
          <span>
            Laravel API
          </span>

          <span>
            React + TypeScript
          </span>

          <span>
            PostgreSQL
          </span>

          <span>
            Reverb
          </span>
        </div>
      </section>
    </AppLayout>
  );
}
