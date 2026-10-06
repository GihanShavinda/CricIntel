import {
  api,
} from './client';

import type {
  CricIntelNotification,
  NotificationListResponse,
  NotificationPreference,
} from '../types/notifications';

export async function listNotifications(
  params?: {
    unread_only?: boolean;
    organization_id?: number;
    type?: string;
    page?: number;
    per_page?: number;
  },
) {
  const response =
    await api.get<NotificationListResponse>(
      '/api/v1/notifications',
      {
        params,
      },
    );

  return response.data;
}

export async function getUnreadNotificationCount() {
  const response =
    await api.get<{
      data: {
        unread_count: number;
      };
    }>(
      '/api/v1/notifications/unread-count',
    );

  return response.data.data.unread_count;
}

export async function markNotificationRead(
  id: string,
) {
  const response =
    await api.post<{
      data: CricIntelNotification;
    }>(
      `/api/v1/notifications/${id}/read`,
    );

  return response.data.data;
}

export async function markNotificationUnread(
  id: string,
) {
  const response =
    await api.post<{
      data: CricIntelNotification;
    }>(
      `/api/v1/notifications/${id}/unread`,
    );

  return response.data.data;
}

export async function markAllNotificationsRead() {
  const response =
    await api.post<{
      data: {
        unread_count: number;
      };
    }>(
      '/api/v1/notifications/read-all',
    );

  return response.data.data;
}

export async function deleteNotification(
  id: string,
) {
  await api.delete(
    `/api/v1/notifications/${id}`,
  );
}

export async function getNotificationPreferences() {
  const response =
    await api.get<{
      data: NotificationPreference[];
    }>(
      '/api/v1/notification-preferences',
    );

  return response.data.data;
}

export async function updateNotificationPreferences(
  preferences: NotificationPreference[],
) {
  const response =
    await api.put<{
      data: NotificationPreference[];
    }>(
      '/api/v1/notification-preferences',
      {
        preferences: preferences.map(
          ({
            event_type,
            database_enabled,
            email_enabled,
            realtime_enabled,
          }) => ({
            event_type,
            database_enabled,
            email_enabled,
            realtime_enabled,
          }),
        ),
      },
    );

  return response.data.data;
}
