import {
  useCallback,
  useEffect,
  useRef,
  useState,
} from 'react';

import {
  Link,
  useNavigate,
} from 'react-router-dom';

import {
  deleteNotification,
  listNotifications,
  markAllNotificationsRead,
  markNotificationRead,
} from '../../api/notifications';

import {
  useAuth,
} from '../../auth/AuthContext';

import {
  echo,
} from '../../realtime/echo';

import type {
  CricIntelNotification,
} from '../../types/notifications';

import {
  AppIcon,
} from '../AppIcon';

export function NotificationCenter() {
  const {
    user,
  } = useAuth();

  const navigate =
    useNavigate();

  const [
    open,
    setOpen,
  ] = useState(false);

  const [
    notifications,
    setNotifications,
  ] = useState<
    CricIntelNotification[]
  >([]);

  const [
    unreadCount,
    setUnreadCount,
  ] = useState(0);

  const [
    loading,
    setLoading,
  ] = useState(false);

  const wrapperRef =
    useRef<HTMLDivElement | null>(
      null,
    );

  const load =
    useCallback(
      async () => {
        if (!user) {
          return;
        }

        setLoading(true);

        try {
          const response =
            await listNotifications({
              per_page: 12,
            });

          setNotifications(
            response.data.data,
          );

          setUnreadCount(
            response.meta
              .unread_count,
          );
        } finally {
          setLoading(false);
        }
      },
      [user],
    );

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    if (!user?.id) {
      return;
    }

    const channelName =
      `App.Models.User.${user.id}`;

    const channel =
      echo.private(
        channelName,
      );

    channel.notification(
      (
        incoming:
          Record<
            string,
            unknown
          >,
      ) => {
        const notification:
          CricIntelNotification = {
            id:
              String(
                incoming.id ??
                `realtime-${Date.now()}`,
              ),
            type:
              String(
                incoming.type ??
                'notification',
              ),
            title:
              String(
                incoming.title ??
                'CricIntel notification',
              ),
            message:
              String(
                incoming.message ??
                '',
              ),
            url:
              typeof incoming.url ===
                'string'
                ? incoming.url
                : null,
            organization_id:
              typeof incoming.organization_id ===
                'number'
                ? incoming.organization_id
                : null,
            data:
              typeof incoming.data ===
                'object' &&
              incoming.data !==
                null
                ? incoming.data as
                    Record<
                      string,
                      unknown
                    >
                : {},
            read_at: null,
            created_at:
              typeof incoming.created_at ===
                'string'
                ? incoming.created_at
                : new Date().toISOString(),
          };

        setNotifications(
          (current) => [
            notification,
            ...current,
          ].slice(
            0,
            12,
          ),
        );

        setUnreadCount(
          (current) =>
            current + 1,
        );
      },
    );

    return () => {
      echo.leave(
        channelName,
      );
    };
  }, [user?.id]);

  useEffect(() => {
    const handleClickOutside =
      (
        event:
          MouseEvent,
      ) => {
        if (
          wrapperRef.current &&
          !wrapperRef.current.contains(
            event.target as Node,
          )
        ) {
          setOpen(false);
        }
      };

    document.addEventListener(
      'mousedown',
      handleClickOutside,
    );

    return () => {
      document.removeEventListener(
        'mousedown',
        handleClickOutside,
      );
    };
  }, []);

  const openNotification =
    async (
      notification:
        CricIntelNotification,
    ) => {
      if (
        !notification.read_at &&
        !notification.id.startsWith(
          'realtime-',
        )
      ) {
        try {
          await markNotificationRead(
            notification.id,
          );

          setNotifications(
            (current) =>
              current.map(
                (item) =>
                  item.id ===
                  notification.id
                    ? {
                        ...item,
                        read_at:
                          new Date()
                            .toISOString(),
                      }
                    : item,
              ),
          );

          setUnreadCount(
            (current) =>
              Math.max(
                0,
                current - 1,
              ),
          );
        } catch {
          // Navigation should not fail because read-state persistence failed.
        }
      }

      setOpen(false);

      if (
        notification.url
      ) {
        navigate(
          notification.url,
        );
      }
    };

  const markAll =
    async () => {
      await markAllNotificationsRead();

      setNotifications(
        (current) =>
          current.map(
            (item) => ({
              ...item,
              read_at:
                item.read_at ??
                new Date()
                  .toISOString(),
            }),
          ),
      );

      setUnreadCount(0);
    };

  const remove =
    async (
      id: string,
    ) => {
      if (
        id.startsWith(
          'realtime-',
        )
      ) {
        setNotifications(
          (current) =>
            current.filter(
              (item) =>
                item.id !== id,
            ),
        );

        return;
      }

      await deleteNotification(
        id,
      );

      setNotifications(
        (current) =>
          current.filter(
            (item) =>
              item.id !== id,
          ),
      );
    };

  return (
    <div
      className="ci-notification-center"
      ref={wrapperRef}
    >
      <button
        type="button"
        className="ci-icon-button ci-notification-trigger"
        aria-label="Open notifications"
        aria-expanded={open}
        onClick={() =>
          setOpen(
            (current) =>
              !current,
          )
        }
      >
        <AppIcon
          name="bell"
          size={19}
        />

        {unreadCount >
          0 && (
          <span className="ci-notification-badge">
            {unreadCount >
            99
              ? '99+'
              : unreadCount}
          </span>
        )}
      </button>

      {open && (
        <div className="ci-notification-popover">
          <div className="ci-notification-popover-header">
            <div>
              <strong>
                Notifications
              </strong>

              <span>
                {unreadCount}
                {' '}
                unread
              </span>
            </div>

            {unreadCount >
              0 && (
              <button
                type="button"
                onClick={() =>
                  void markAll()
                }
              >
                Mark all read
              </button>
            )}
          </div>

          <div className="ci-notification-list">
            {loading && (
              <div className="ci-notification-empty">
                Loading
                notifications...
              </div>
            )}

            {!loading &&
              notifications.map(
                (
                  notification,
                ) => (
                  <article
                    key={
                      notification.id
                    }
                    className={[
                      'ci-notification-item',
                      notification.read_at
                        ? ''
                        : 'unread',
                    ]
                      .filter(Boolean)
                      .join(' ')}
                  >
                    <button
                      type="button"
                      className="ci-notification-item-main"
                      onClick={() =>
                        void openNotification(
                          notification,
                        )
                      }
                    >
                      <span className="ci-notification-type-icon">
                        <AppIcon
                          name="bell"
                          size={15}
                        />
                      </span>

                      <span className="ci-notification-copy">
                        <strong>
                          {
                            notification.title
                          }
                        </strong>

                        <span>
                          {
                            notification.message
                          }
                        </span>

                        <small>
                          {formatTime(
                            notification.created_at,
                          )}
                        </small>
                      </span>
                    </button>

                    <button
                      type="button"
                      className="ci-notification-delete"
                      aria-label="Delete notification"
                      onClick={() =>
                        void remove(
                          notification.id,
                        )
                      }
                    >
                      <AppIcon
                        name="x"
                        size={13}
                      />
                    </button>
                  </article>
                ),
              )}

            {!loading &&
              notifications.length ===
                0 && (
                <div className="ci-notification-empty">
                  <AppIcon
                    name="bell"
                    size={24}
                  />

                  <strong>
                    You're all caught
                    up
                  </strong>

                  <span>
                    New CricIntel
                    activity will
                    appear here.
                  </span>
                </div>
              )}
          </div>

          <div className="ci-notification-popover-footer">
            <Link
              to="/notifications"
              onClick={() =>
                setOpen(false)
              }
            >
              View all
            </Link>

            <Link
              to="/notification-preferences"
              onClick={() =>
                setOpen(false)
              }
            >
              Preferences
            </Link>
          </div>
        </div>
      )}
    </div>
  );
}

function formatTime(
  value?: string | null,
) {
  if (!value) {
    return 'Just now';
  }

  const date =
    new Date(value);

  const diff =
    Date.now() -
    date.getTime();

  const minutes =
    Math.floor(
      diff / 60000,
    );

  if (minutes < 1) {
    return 'Just now';
  }

  if (minutes < 60) {
    return `${minutes}m ago`;
  }

  const hours =
    Math.floor(
      minutes / 60,
    );

  if (hours < 24) {
    return `${hours}h ago`;
  }

  return date.toLocaleDateString();
}
