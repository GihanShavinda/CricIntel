import {
  useEffect,
  useState,
} from 'react';

import {
  useNavigate,
} from 'react-router-dom';

import {
  deleteNotification,
  listNotifications,
  markAllNotificationsRead,
  markNotificationRead,
  markNotificationUnread,
} from '../api/notifications';

import {
  AppIcon,
} from '../components/AppIcon';

import {
  AppLayout,
} from '../components/AppLayout';

import type {
  CricIntelNotification,
} from '../types/notifications';

export function NotificationsPage() {
  const navigate =
    useNavigate();

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
  ] = useState(true);

  const [
    unreadOnly,
    setUnreadOnly,
  ] = useState(false);

  const [
    error,
    setError,
  ] = useState('');

  const load =
    async () => {
      setLoading(true);
      setError('');

      try {
        const response =
          await listNotifications({
            unread_only:
              unreadOnly ||
              undefined,
            per_page: 100,
          });

        setNotifications(
          response.data.data,
        );

        setUnreadCount(
          response.meta
            .unread_count,
        );
      } catch (caught: any) {
        setError(
          caught?.response?.data
            ?.message ??
            'Unable to load notifications.',
        );
      } finally {
        setLoading(false);
      }
    };

  useEffect(() => {
    void load();
  }, [unreadOnly]);

  const toggleRead =
    async (
      notification:
        CricIntelNotification,
    ) => {
      const updated =
        notification.read_at
          ? await markNotificationUnread(
              notification.id,
            )
          : await markNotificationRead(
              notification.id,
            );

      setNotifications(
        (current) =>
          current.map(
            (item) =>
              item.id ===
              updated.id
                ? updated
                : item,
          ),
      );

      setUnreadCount(
        (current) =>
          notification.read_at
            ? current + 1
            : Math.max(
                0,
                current - 1,
              ),
      );
    };

  const remove =
    async (
      notification:
        CricIntelNotification,
    ) => {
      await deleteNotification(
        notification.id,
      );

      setNotifications(
        (current) =>
          current.filter(
            (item) =>
              item.id !==
              notification.id,
          ),
      );

      if (
        !notification.read_at
      ) {
        setUnreadCount(
          (current) =>
            Math.max(
              0,
              current - 1,
            ),
        );
      }
    };

  const markAll =
    async () => {
      await markAllNotificationsRead();

      setNotifications(
        (current) =>
          current.map(
            (notification) => ({
              ...notification,
              read_at:
                notification.read_at ??
                new Date()
                  .toISOString(),
            }),
          ),
      );

      setUnreadCount(0);
    };

  const open =
    async (
      notification:
        CricIntelNotification,
    ) => {
      if (
        !notification.read_at
      ) {
        await toggleRead(
          notification,
        );
      }

      if (
        notification.url
      ) {
        navigate(
          notification.url,
        );
      }
    };

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <p className="eyebrow">
            P17 Activity Center
          </p>

          <h1>
            Notifications
          </h1>

          <p>
            Team activity,
            selection updates,
            training changes,
            tactical collaboration
            and match events in one
            place.
          </p>
        </div>

        <div className="ci-notification-page-actions">
          <span>
            {unreadCount}
            {' '}
            unread
          </span>

          <button
            type="button"
            className="secondary"
            disabled={
              unreadCount === 0
            }
            onClick={() =>
              void markAll()
            }
          >
            Mark all read
          </button>
        </div>
      </div>

      {error && (
        <div className="form-error-message">
          {error}
        </div>
      )}

      <section className="ci-notification-page-card">
        <div className="ci-notification-page-toolbar">
          <div>
            <strong>
              Notification center
            </strong>

            <span>
              Database-backed
              CricIntel activity
              history
            </span>
          </div>

          <label className="ci-notification-filter">
            <input
              type="checkbox"
              checked={
                unreadOnly
              }
              onChange={(
                event,
              ) =>
                setUnreadOnly(
                  event.target
                    .checked,
                )
              }
            />

            Unread only
          </label>
        </div>

        <div className="ci-notification-page-list">
          {loading && (
            <div className="ci-notification-page-empty">
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
                    'ci-notification-page-item',
                    notification.read_at
                      ? ''
                      : 'unread',
                  ]
                    .filter(Boolean)
                    .join(' ')}
                >
                  <button
                    type="button"
                    className="ci-notification-page-main"
                    onClick={() =>
                      void open(
                        notification,
                      )
                    }
                  >
                    <span className="ci-notification-type-icon large">
                      <AppIcon
                        name="bell"
                        size={18}
                      />
                    </span>

                    <span className="ci-notification-page-copy">
                      <span className="ci-notification-page-title-row">
                        <strong>
                          {
                            notification.title
                          }
                        </strong>

                        {!notification.read_at && (
                          <span>
                            New
                          </span>
                        )}
                      </span>

                      <span>
                        {
                          notification.message
                        }
                      </span>

                      <small>
                        {notification.created_at
                          ? new Date(
                              notification.created_at,
                            ).toLocaleString()
                          : ''}
                      </small>
                    </span>
                  </button>

                  <div className="ci-notification-page-item-actions">
                    <button
                      type="button"
                      className="secondary"
                      onClick={() =>
                        void toggleRead(
                          notification,
                        )
                      }
                    >
                      {notification.read_at
                        ? 'Mark unread'
                        : 'Mark read'}
                    </button>

                    <button
                      type="button"
                      className="danger"
                      onClick={() =>
                        void remove(
                          notification,
                        )
                      }
                    >
                      Delete
                    </button>
                  </div>
                </article>
              ),
            )}

          {!loading &&
            notifications.length ===
              0 && (
              <div className="ci-notification-page-empty">
                <AppIcon
                  name="bell"
                  size={32}
                />

                <strong>
                  No notifications
                </strong>

                <span>
                  CricIntel activity
                  will appear here as
                  domain events occur.
                </span>
              </div>
            )}
        </div>
      </section>
    </AppLayout>
  );
}
