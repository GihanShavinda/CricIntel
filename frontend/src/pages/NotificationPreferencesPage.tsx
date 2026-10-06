import {
  useEffect,
  useState,
} from 'react';

import {
  getNotificationPreferences,
  updateNotificationPreferences,
} from '../api/notifications';

import {
  AppLayout,
} from '../components/AppLayout';

import type {
  NotificationPreference,
} from '../types/notifications';

export function NotificationPreferencesPage() {
  const [
    preferences,
    setPreferences,
  ] = useState<
    NotificationPreference[]
  >([]);

  const [
    loading,
    setLoading,
  ] = useState(true);

  const [
    saving,
    setSaving,
  ] = useState(false);

  const [
    error,
    setError,
  ] = useState('');

  const [
    message,
    setMessage,
  ] = useState('');

  useEffect(() => {
    const load =
      async () => {
        try {
          setPreferences(
            await getNotificationPreferences(),
          );
        } catch (caught: any) {
          setError(
            caught?.response
              ?.data?.message ??
              'Unable to load notification preferences.',
          );
        } finally {
          setLoading(false);
        }
      };

    void load();
  }, []);

  const toggle =
    (
      eventType: string,
      key:
        | 'database_enabled'
        | 'email_enabled'
        | 'realtime_enabled',
    ) => {
      setPreferences(
        (current) =>
          current.map(
            (preference) =>
              preference.event_type ===
              eventType
                ? {
                    ...preference,
                    [key]:
                      !preference[
                        key
                      ],
                  }
                : preference,
          ),
      );
    };

  const save =
    async () => {
      setSaving(true);
      setError('');
      setMessage('');

      try {
        const updated =
          await updateNotificationPreferences(
            preferences,
          );

        setPreferences(
          updated,
        );

        setMessage(
          'Notification preferences saved.',
        );
      } catch (caught: any) {
        setError(
          caught?.response?.data
            ?.message ??
            'Unable to save notification preferences.',
        );
      } finally {
        setSaving(false);
      }
    };

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <p className="eyebrow">
            P17 Preferences
          </p>

          <h1>
            Notification
            Preferences
          </h1>

          <p>
            Choose how CricIntel
            delivers each event:
            in-app database
            notification, email and
            real-time Reverb update.
          </p>
        </div>

        <button
          type="button"
          disabled={
            saving ||
            loading
          }
          onClick={() =>
            void save()
          }
        >
          {saving
            ? 'Saving...'
            : 'Save preferences'}
        </button>
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

      <section className="ci-preferences-card">
        <div className="ci-preferences-header">
          <div>
            <strong>
              Event delivery
            </strong>

            <span>
              Email defaults are
              enabled only for
              events where direct
              delivery is useful.
            </span>
          </div>

          <div className="ci-preferences-column-labels">
            <span>
              In-app
            </span>
            <span>
              Email
            </span>
            <span>
              Realtime
            </span>
          </div>
        </div>

        <div className="ci-preferences-list">
          {loading && (
            <div className="ci-notification-page-empty">
              Loading
              preferences...
            </div>
          )}

          {!loading &&
            preferences.map(
              (
                preference,
              ) => (
                <div
                  className="ci-preference-row"
                  key={
                    preference.event_type
                  }
                >
                  <div>
                    <strong>
                      {
                        preference.label
                      }
                    </strong>

                    <span>
                      {preference.event_type.replaceAll(
                        '_',
                        ' ',
                      )}
                    </span>
                  </div>

                  <label>
                    <input
                      type="checkbox"
                      checked={
                        preference.database_enabled
                      }
                      onChange={() =>
                        toggle(
                          preference.event_type,
                          'database_enabled',
                        )
                      }
                    />

                    <span>
                      In-app
                    </span>
                  </label>

                  <label>
                    <input
                      type="checkbox"
                      checked={
                        preference.email_enabled
                      }
                      onChange={() =>
                        toggle(
                          preference.event_type,
                          'email_enabled',
                        )
                      }
                    />

                    <span>
                      Email
                    </span>
                  </label>

                  <label>
                    <input
                      type="checkbox"
                      checked={
                        preference.realtime_enabled
                      }
                      onChange={() =>
                        toggle(
                          preference.event_type,
                          'realtime_enabled',
                        )
                      }
                    />

                    <span>
                      Realtime
                    </span>
                  </label>
                </div>
              ),
            )}
        </div>
      </section>

      <section className="ci-notification-info-card">
        <strong>
          Delivery behavior
        </strong>

        <p>
          Database notifications
          appear in the CricIntel
          notification center.
          Realtime notifications
          arrive over the private
          Reverb user channel.
          Email notifications are
          queued and retried using
          the P17 retry strategy.
        </p>
      </section>
    </AppLayout>
  );
}
