import { useCallback, useEffect, useRef, useState } from "react";

import { getLiveMatch } from "../api/liveMatch";

import { echo } from "../realtime/echo";

import { EventDeduplicator } from "../realtime/eventDeduplicator";

import type { LiveMatchEvent, LiveMatchSnapshot } from "../types/liveMatch";

type ConnectionState =
  | "connecting"
  | "connected"
  | "reconnecting"
  | "disconnected"
  | "error";

const EVENT_NAMES = [
  ".delivery.recorded",
  ".wicket.recorded",
  ".innings.completed",
  ".match.completed",
];

export function useLiveMatch(organizationId: number, matchId: number) {
  const [snapshot, setSnapshot] = useState<LiveMatchSnapshot | null>(null);

  const [loading, setLoading] = useState(true);

  const [error, setError] = useState("");

  const [connectionState, setConnectionState] =
    useState<ConnectionState>("connecting");

  const deduplicator = useRef(new EventDeduplicator(250));

  const rememberEvent = useCallback(
    (eventId: string) => deduplicator.current.accept(eventId),
    [],
  );

  const loadSnapshot = useCallback(async () => {
    if (!organizationId || !matchId) {
      return;
    }

    try {
      setError("");

      const data = await getLiveMatch(organizationId, matchId);

      setSnapshot(data);
    } catch (caught) {
      setError(
        caught instanceof Error ? caught.message : "Unable to load live match.",
      );
    } finally {
      setLoading(false);
    }
  }, [organizationId, matchId]);

  useEffect(() => {
    void loadSnapshot();
  }, [loadSnapshot]);

  useEffect(() => {
    if (!matchId) {
      return;
    }

    setConnectionState("connecting");

    const channel = echo.private(`match.${matchId}`);

    const receive = (event: LiveMatchEvent) => {
      if (!event.event_id || !rememberEvent(event.event_id)) {
        return;
      }

      if (event.match_id !== matchId) {
        return;
      }

      setSnapshot(event.snapshot);

      setConnectionState("connected");
    };

    EVENT_NAMES.forEach((name) => {
      channel.listen(name, receive);
    });

    const connection = (
      echo.connector as {
        pusher?: {
          connection?: {
            bind: (event: string, handler: () => void) => void;
            unbind: (event: string, handler: () => void) => void;
          };
        };
      }
    ).pusher?.connection;

    const onConnected = () => {
      setConnectionState("connected");

      void loadSnapshot();
    };

    const onConnecting = () => {
      setConnectionState("reconnecting");
    };

    const onDisconnected = () => {
      setConnectionState("disconnected");
    };

    const onError = () => {
      setConnectionState("error");
    };

    connection?.bind("connected", onConnected);

    connection?.bind("connecting", onConnecting);

    connection?.bind("disconnected", onDisconnected);

    connection?.bind("error", onError);

    return () => {
      EVENT_NAMES.forEach((name) => {
        channel.stopListening(name);
      });

      echo.leave(`match.${matchId}`);

      connection?.unbind("connected", onConnected);

      connection?.unbind("connecting", onConnecting);

      connection?.unbind("disconnected", onDisconnected);

      connection?.unbind("error", onError);
    };
  }, [loadSnapshot, matchId, rememberEvent]);

  return {
    snapshot,
    loading,
    error,
    connectionState,
    refresh: loadSnapshot,
  };
}
