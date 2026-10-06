export interface CricIntelNotification {
  id: string;
  type: string;
  title: string;
  message: string;
  url?: string | null;
  organization_id?: number | null;
  actor_id?: number | null;
  data?: Record<string, unknown>;
  read_at?: string | null;
  created_at?: string | null;
}

export interface NotificationPreference {
  event_type: string;
  label: string;
  database_enabled: boolean;
  email_enabled: boolean;
  realtime_enabled: boolean;
}

export interface NotificationPage {
  data: CricIntelNotification[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface NotificationListResponse {
  data: NotificationPage;
  meta: {
    unread_count: number;
  };
}
