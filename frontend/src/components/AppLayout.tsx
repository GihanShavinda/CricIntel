import {
  useEffect,
  useMemo,
  useRef,
  useState,
  type ReactNode,
} from 'react';

import {
  Link,
  NavLink,
  useLocation,
  useNavigate,
  useParams,
} from 'react-router-dom';

import {
  useAuth,
} from '../auth/AuthContext';

import {
  AppIcon,
  type AppIconName,
} from './AppIcon';

import {
  NotificationCenter,
} from './notifications/NotificationCenter';

type Props = {
  children: ReactNode;
};

type NavigationItem = {
  label: string;
  description: string;
  to: string;
  icon: AppIconName;
  end?: boolean;
  administratorOnly?: boolean;
};

type NavigationSection = {
  label: string;
  items: NavigationItem[];
};

const routeLabels: Record<string, string> = {
  dashboard: 'Dashboard',
  organizations: 'Organizations',
  clubs: 'Clubs',
  teams: 'Teams',
  seasons: 'Seasons',
  players: 'Players',
  venues: 'Venues',
  tournaments: 'Tournaments',
  fixtures: 'Fixtures',
  matches: 'Matches',
  analytics: 'Analytics',
  training: 'Training',
  scouting: 'Scouting',
  strategy: 'Tactical Strategy',
  predictive: 'Predictive Analytics',
  'strategy-assistant': 'AI Strategy Assistant',
  'nl-analytics': 'Natural-Language Analytics',
  notifications: 'Notifications',
  'notification-preferences': 'Notification Preferences',
  'opponent-intelligence': 'Opponent Intelligence',
  admin: 'Administration',
  squad: 'Squad',
  'playing-xi': 'Playing XI',
  development: 'Development',
  reports: 'Reports',
};

export function AppLayout({
  children,
}: Props) {
  const {
    user,
    logout,
  } = useAuth();

  const navigate = useNavigate();
  const location = useLocation();
  const params = useParams();

  const searchInputRef =
    useRef<HTMLInputElement | null>(null);

  const roles =
    user?.roles ?? [];

  const isAdministrator =
    roles.includes('Administrator');

  const organizationId =
    Number(params.organizationId) || null;

  const [
    sidebarCollapsed,
    setSidebarCollapsed,
  ] = useState(() => {
    try {
      return (
        window.localStorage.getItem(
          'cricintel.sidebar.collapsed',
        ) === 'true'
      );
    } catch {
      return false;
    }
  });

  const [
    mobileSidebarOpen,
    setMobileSidebarOpen,
  ] = useState(false);

  const [
    searchOpen,
    setSearchOpen,
  ] = useState(false);

  const [
    searchTerm,
    setSearchTerm,
  ] = useState('');

  const [
    profileOpen,
    setProfileOpen,
  ] = useState(false);

  useEffect(() => {
    try {
      window.localStorage.setItem(
        'cricintel.sidebar.collapsed',
        String(sidebarCollapsed),
      );
    } catch {
      // Local storage is optional. The UI still works without it.
    }
  }, [sidebarCollapsed]);

  useEffect(() => {
    setMobileSidebarOpen(false);
    setProfileOpen(false);
  }, [location.pathname]);

  useEffect(() => {
    const handleKeyboard = (
      event: KeyboardEvent,
    ) => {
      const target =
        event.target as HTMLElement | null;

      const isTyping =
        target?.tagName === 'INPUT' ||
        target?.tagName === 'TEXTAREA' ||
        target?.tagName === 'SELECT' ||
        target?.isContentEditable;

      if (
        (event.ctrlKey || event.metaKey) &&
        event.key.toLowerCase() === 'k'
      ) {
        event.preventDefault();
        setSearchOpen(true);
        return;
      }

      if (
        event.key === '/' &&
        !isTyping
      ) {
        event.preventDefault();
        setSearchOpen(true);
        return;
      }

      if (
        event.key === 'Escape'
      ) {
        setSearchOpen(false);
        setProfileOpen(false);
        setMobileSidebarOpen(false);
      }
    };

    window.addEventListener(
      'keydown',
      handleKeyboard,
    );

    return () => {
      window.removeEventListener(
        'keydown',
        handleKeyboard,
      );
    };
  }, []);

  useEffect(() => {
    if (!searchOpen) {
      return;
    }

    const timeout =
      window.setTimeout(() => {
        searchInputRef.current?.focus();
      }, 50);

    return () => {
      window.clearTimeout(timeout);
    };
  }, [searchOpen]);

  const navigation =
    useMemo<NavigationSection[]>(() => {
      const sections: NavigationSection[] = [
        {
          label: 'Workspace',
          items: [
            {
              label: 'Dashboard',
              description:
                'Overview and quick actions',
              to: '/dashboard',
              icon: 'dashboard',
              end: true,
            },
            {
              label: 'Organizations',
              description:
                'Organizations and memberships',
              to: '/organizations',
              icon: 'organization',
              end: true,
            },
            {
              label: 'Administration',
              description:
                'Roles and administrative controls',
              to: '/admin',
              icon: 'shield',
              administratorOnly: true,
            },
          ],
        },
      ];

      if (organizationId) {
        sections.push(
          {
            label: 'Cricket Operations',
            items: [
              {
                label: 'Clubs',
                description:
                  'Manage cricket clubs',
                to: `/organizations/${organizationId}/clubs`,
                icon: 'clubs',
              },
              {
                label: 'Teams',
                description:
                  'Teams and squad structures',
                to: `/organizations/${organizationId}/teams`,
                icon: 'teams',
              },
              {
                label: 'Players',
                description:
                  'Profiles and memberships',
                to: `/organizations/${organizationId}/players`,
                icon: 'players',
              },
              {
                label: 'Seasons',
                description:
                  'Competition periods',
                to: `/organizations/${organizationId}/seasons`,
                icon: 'seasons',
              },
              {
                label: 'Venues',
                description:
                  'Grounds and locations',
                to: `/organizations/${organizationId}/venues`,
                icon: 'venues',
              },
              {
                label: 'Tournaments',
                description:
                  'Competition registration',
                to: `/organizations/${organizationId}/tournaments`,
                icon: 'trophy',
              },
              {
                label: 'Fixtures',
                description:
                  'Scheduling and calendar',
                to: `/organizations/${organizationId}/fixtures`,
                icon: 'fixture',
              },
            ],
          },
          {
            label: 'Match & Intelligence',
            items: [
              {
                label: 'Match Centre',
                description:
                  'Matches and live scoring',
                to: `/organizations/${organizationId}/matches`,
                icon: 'activity',
              },
              {
                label: 'Analytics',
                description:
                  'Statistics and comparisons',
                to: `/organizations/${organizationId}/analytics`,
                icon: 'analytics',
              },
              {
                label: 'Opponent Intelligence',
                description:
                  'Deterministic opponent analysis',
                to: `/organizations/${organizationId}/opponent-intelligence`,
                icon: 'target',
              },
              {
                label: 'Tactical Workspace',
                description:
                  'Collaborative match strategy planning',
                to: `/organizations/${organizationId}/strategy`,
                icon: 'strategy',
              },
              {
                label: 'Predictive Analytics',
                description:
                  'Leakage-controlled coaching predictions',
                to: `/organizations/${organizationId}/predictive`,
                icon: 'analytics',
              },
              {
                label: 'AI Strategy Assistant',
                description:
                  'Grounded tactical explanations over CricIntel evidence',
                to: `/organizations/${organizationId}/strategy-assistant`,
                icon: 'strategy',
              },
              {
                label: 'Natural-Language Analytics',
                description:
                  'Controlled cricket analytics from natural-language requests',
                to: `/organizations/${organizationId}/nl-analytics`,
                icon: 'analytics',
              },
              {
                label: 'Reports',
                description:
                  'Professional PDF, Excel and CSV reporting',
                to: `/organizations/${organizationId}/reports`,
                icon: 'reports',
              },
            ],
          },
          {
            label: 'Performance',
            items: [
              {
                label: 'Training',
                description:
                  'Training and development',
                to: `/organizations/${organizationId}/training`,
                icon: 'training',
              },
              {
                label: 'Scouting',
                description:
                  'Scouting and recruitment',
                to: `/organizations/${organizationId}/scouting`,
                icon: 'scouting',
              },
            ],
          },
        );
      }

      return sections
        .map((section) => ({
          ...section,
          items: section.items.filter(
            (item) =>
              !item.administratorOnly ||
              isAdministrator,
          ),
        }))
        .filter(
          (section) =>
            section.items.length > 0,
        );
    }, [
      organizationId,
      isAdministrator,
    ]);

  const searchableItems =
    useMemo(
      () =>
        navigation.flatMap(
          (section) =>
            section.items.map(
              (item) => ({
                ...item,
                section:
                  section.label,
              }),
            ),
        ),
      [navigation],
    );

  const filteredSearchItems =
    useMemo(() => {
      const query =
        searchTerm
          .trim()
          .toLowerCase();

      if (!query) {
        return searchableItems;
      }

      return searchableItems.filter(
        (item) =>
          item.label
            .toLowerCase()
            .includes(query) ||
          item.description
            .toLowerCase()
            .includes(query) ||
          item.section
            .toLowerCase()
            .includes(query),
      );
    }, [
      searchableItems,
      searchTerm,
    ]);

  const breadcrumbs =
    useMemo(() => {
      const parts =
        location.pathname
          .split('/')
          .filter(Boolean);

      if (
        parts.length === 0
      ) {
        return [
          {
            label: 'Dashboard',
            path: '/dashboard',
          },
        ];
      }

      const crumbs: Array<{
        label: string;
        path: string;
      }> = [];

      let current = '';

      parts.forEach(
        (
          part,
          index,
        ) => {
          current += `/${part}`;

          const previous =
            parts[index - 1];

          const isNumeric =
            /^\d+$/.test(part);

          let label =
            routeLabels[part] ??
            part
              .replaceAll('-', ' ')
              .replace(
                /\b\w/g,
                (character) =>
                  character.toUpperCase(),
              );

          if (
            isNumeric &&
            previous === 'organizations'
          ) {
            label =
              `Organization #${part}`;
          } else if (isNumeric) {
            label =
              `#${part}`;
          }

          crumbs.push({
            label,
            path: current,
          });
        },
      );

      return crumbs;
    }, [location.pathname]);

  const primaryRole =
    roles[0] ?? 'User';

  const userInitials =
    (user?.name ?? 'CricIntel User')
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((part) =>
        part
          .charAt(0)
          .toUpperCase(),
      )
      .join('');

  const handleLogout =
    async () => {
      try {
        await logout();

        navigate('/login', {
          replace: true,
        });
      } catch (error) {
        console.error(
          'Logout failed:',
          error,
        );
      }
    };

  const openSearchResult = (
    to: string,
  ) => {
    setSearchOpen(false);
    setSearchTerm('');
    navigate(to);
  };

  return (
    <div
      className={[
        'ci-shell',
        sidebarCollapsed
          ? 'ci-shell-sidebar-collapsed'
          : '',
      ]
        .filter(Boolean)
        .join(' ')}
    >
      <aside
        className={[
          'ci-sidebar',
          mobileSidebarOpen
            ? 'mobile-open'
            : '',
        ]
          .filter(Boolean)
          .join(' ')}
      >
        <div className="ci-sidebar-brand-row">
          <Link
            to="/dashboard"
            className="ci-brand"
          >
            <span className="ci-brand-mark">
              CI
            </span>

            <span className="ci-brand-copy">
              <strong>
                CricIntel
              </strong>

              <small>
                Cricket Intelligence
              </small>
            </span>
          </Link>

          <button
            type="button"
            className="ci-icon-button ci-sidebar-close-mobile"
            aria-label="Close navigation"
            onClick={() =>
              setMobileSidebarOpen(false)
            }
          >
            <AppIcon
              name="x"
              size={18}
            />
          </button>
        </div>

        <div className="ci-sidebar-workspace">
          <span className="ci-workspace-dot" />

          <div>
            <strong>
              CricIntel Platform
            </strong>

            <small>
              P1–P18 operational
            </small>
          </div>
        </div>

        <nav className="ci-sidebar-nav">
          {navigation.map(
            (section) => (
              <section
                className="ci-nav-section"
                key={section.label}
              >
                <p className="ci-nav-section-label">
                  {section.label}
                </p>

                <div className="ci-nav-items">
                  {section.items.map(
                    (item) => (
                      <NavLink
                        key={item.to}
                        to={item.to}
                        end={item.end}
                        title={
                          sidebarCollapsed
                            ? item.label
                            : undefined
                        }
                        className={({
                          isActive,
                        }) =>
                          [
                            'ci-nav-item',
                            isActive
                              ? 'active'
                              : '',
                          ]
                            .filter(Boolean)
                            .join(' ')
                        }
                      >
                        <span className="ci-nav-icon">
                          <AppIcon
                            name={item.icon}
                            size={19}
                          />
                        </span>

                        <span className="ci-nav-copy">
                          <strong>
                            {item.label}
                          </strong>

                          <small>
                            {item.description}
                          </small>
                        </span>
                      </NavLink>
                    ),
                  )}
                </div>
              </section>
            ),
          )}
        </nav>

        <div className="ci-sidebar-footer">
          <div className="ci-sidebar-status">
            <span className="ci-status-dot online" />

            <div>
              <strong>
                System online
              </strong>

              <small>
                Secure authenticated session
              </small>
            </div>
          </div>

          <button
            type="button"
            className="ci-sidebar-collapse"
            onClick={() =>
              setSidebarCollapsed(
                (current) =>
                  !current,
              )
            }
          >
            <AppIcon
              name={
                sidebarCollapsed
                  ? 'chevronRight'
                  : 'chevronLeft'
              }
              size={18}
            />

            <span>
              {sidebarCollapsed
                ? 'Expand'
                : 'Collapse sidebar'}
            </span>
          </button>
        </div>
      </aside>

      {mobileSidebarOpen && (
        <button
          type="button"
          className="ci-sidebar-backdrop"
          aria-label="Close sidebar"
          onClick={() =>
            setMobileSidebarOpen(false)
          }
        />
      )}

      <div className="ci-workspace">
        <header className="ci-topbar">
          <div className="ci-topbar-left">
            <button
              type="button"
              className="ci-icon-button ci-mobile-menu-button"
              aria-label="Open navigation"
              onClick={() =>
                setMobileSidebarOpen(true)
              }
            >
              <AppIcon
                name="menu"
                size={20}
              />
            </button>

            <div className="ci-breadcrumbs">
              {breadcrumbs
                .slice(-4)
                .map(
                  (
                    crumb,
                    index,
                    visibleCrumbs,
                  ) => (
                    <span
                      key={crumb.path}
                    >
                      {index > 0 && (
                        <AppIcon
                          name="chevronRight"
                          size={13}
                        />
                      )}

                      {index ===
                      visibleCrumbs.length -
                        1 ? (
                        <strong>
                          {crumb.label}
                        </strong>
                      ) : (
                        <Link
                          to={crumb.path}
                        >
                          {crumb.label}
                        </Link>
                      )}
                    </span>
                  ),
                )}
            </div>
          </div>

          <div className="ci-topbar-actions">
            <button
              type="button"
              className="ci-global-search-trigger"
              onClick={() =>
                setSearchOpen(true)
              }
            >
              <AppIcon
                name="search"
                size={18}
              />

              <span>
                Search navigation
              </span>

              <kbd>
                Ctrl K
              </kbd>
            </button>

            <Link
              to="/organizations"
              className="ci-topbar-quick-action"
            >
              <AppIcon
                name="plus"
                size={17}
              />

              <span>
                Quick action
              </span>
            </Link>

            <NotificationCenter />

            <div className="ci-topbar-health">
              <span className="ci-status-dot online" />

              <span>
                Operational
              </span>
            </div>

            <div className="ci-profile-menu-wrapper">
              <button
                type="button"
                className="ci-profile-trigger"
                aria-expanded={profileOpen}
                onClick={() =>
                  setProfileOpen(
                    (current) =>
                      !current,
                  )
                }
              >
                <span className="ci-user-avatar">
                  {userInitials || 'CI'}
                </span>

                <span className="ci-user-copy">
                  <strong>
                    {user?.name ??
                      'CricIntel User'}
                  </strong>

                  <small>
                    {primaryRole}
                  </small>
                </span>

                <AppIcon
                  name="chevronDown"
                  size={15}
                />
              </button>

              {profileOpen && (
                <div className="ci-profile-menu">
                  <div className="ci-profile-menu-user">
                    <span className="ci-user-avatar large">
                      {userInitials || 'CI'}
                    </span>

                    <div>
                      <strong>
                        {user?.name ??
                          'CricIntel User'}
                      </strong>

                      <span>
                        {user?.email ??
                          ''}
                      </span>
                    </div>
                  </div>

                  <div className="ci-profile-role-list">
                    {roles.map(
                      (role) => (
                        <span
                          key={role}
                        >
                          {role}
                        </span>
                      ),
                    )}
                  </div>

                  <button
                    type="button"
                    className="ci-profile-logout"
                    onClick={() =>
                      void handleLogout()
                    }
                  >
                    <AppIcon
                      name="logout"
                      size={17}
                    />

                    Logout securely
                  </button>
                </div>
              )}
            </div>
          </div>
        </header>

        <main className="ci-main-content">
          {children}
        </main>
      </div>

      {searchOpen && (
        <div
          className="ci-command-backdrop"
          role="presentation"
          onMouseDown={(event) => {
            if (
              event.target ===
              event.currentTarget
            ) {
              setSearchOpen(false);
            }
          }}
        >
          <section
            className="ci-command-palette"
            role="dialog"
            aria-modal="true"
            aria-label="CricIntel navigation search"
          >
            <div className="ci-command-input-row">
              <AppIcon
                name="search"
                size={20}
              />

              <input
                ref={searchInputRef}
                value={searchTerm}
                onChange={(event) =>
                  setSearchTerm(
                    event.target.value,
                  )
                }
                placeholder="Search CricIntel modules..."
              />

              <button
                type="button"
                className="ci-command-close"
                onClick={() =>
                  setSearchOpen(false)
                }
              >
                ESC
              </button>
            </div>

            <div className="ci-command-results">
              {filteredSearchItems.map(
                (item) => (
                  <button
                    type="button"
                    key={item.to}
                    className="ci-command-result"
                    onClick={() =>
                      openSearchResult(
                        item.to,
                      )
                    }
                  >
                    <span className="ci-command-result-icon">
                      <AppIcon
                        name={item.icon}
                        size={18}
                      />
                    </span>

                    <span>
                      <strong>
                        {item.label}
                      </strong>

                      <small>
                        {item.section}
                        {' · '}
                        {item.description}
                      </small>
                    </span>

                    <AppIcon
                      name="arrowRight"
                      size={17}
                    />
                  </button>
                ),
              )}

              {filteredSearchItems.length ===
                0 && (
                <div className="ci-command-empty">
                  No navigation items match
                  “{searchTerm}”.
                </div>
              )}
            </div>

            <footer className="ci-command-footer">
              <span>
                <kbd>↑</kbd>
                <kbd>↓</kbd>
                Browse
              </span>

              <span>
                <kbd>Esc</kbd>
                Close
              </span>
            </footer>
          </section>
        </div>
      )}
    </div>
  );
}
