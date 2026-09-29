import {
  useEffect,
  useState,
} from 'react';

import type {
  AttendanceStatus,
  TrainingSession,
} from '../../types/training';

type RowState = {
  status: AttendanceStatus;
  arrival_time: string;
  notes: string;
};

export function AttendanceBoard({
  session,
  onSave,
}: {
  session: TrainingSession;
  onSave: (
    entries: Array<{
      player_id: number;
      status: AttendanceStatus;
      arrival_time?: string | null;
      notes?: string | null;
    }>,
  ) => Promise<void>;
}) {
  const [rows, setRows] = useState<Record<number, RowState>>({});

  useEffect(() => {
    const next: Record<number, RowState> = {};

    for (const membership of session.session_players ?? []) {
      next[membership.player_id] = {
        status:
          membership.attendance?.status ??
          'Present',
        arrival_time:
          membership.attendance?.arrival_time?.slice(0, 5) ??
          '',
        notes:
          membership.attendance?.notes ??
          '',
      };
    }

    setRows(next);
  }, [session]);

  const save = async () => {
    await onSave(
      (session.session_players ?? []).map((membership) => ({
        player_id: membership.player_id,
        status: rows[membership.player_id]?.status ?? 'Present',
        arrival_time:
          rows[membership.player_id]?.arrival_time || null,
        notes:
          rows[membership.player_id]?.notes || null,
      })),
    );
  };

  return (
    <div>
      <div className="training-attendance-list">
        {(session.session_players ?? []).map((membership) => {
          const row = rows[membership.player_id] ?? {
            status: 'Present' as AttendanceStatus,
            arrival_time: '',
            notes: '',
          };

          const name =
            membership.player?.display_name ??
            [membership.player?.first_name, membership.player?.last_name]
              .filter(Boolean)
              .join(' ') ??
            `Player ${membership.player_id}`;

          return (
            <div
              key={membership.player_id}
              className="training-attendance-row"
            >
              <div>
                <strong>{name}</strong>
                <span>
                  {membership.player?.primary_role ?? 'Player'}
                </span>
              </div>

              <select
                value={row.status}
                onChange={(event) =>
                  setRows((current) => ({
                    ...current,
                    [membership.player_id]: {
                      ...row,
                      status: event.target.value as AttendanceStatus,
                    },
                  }))
                }
              >
                <option value="Present">Present</option>
                <option value="Late">Late</option>
                <option value="Absent">Absent</option>
                <option value="Excused">Excused</option>
              </select>

              <input
                type="time"
                value={row.arrival_time}
                onChange={(event) =>
                  setRows((current) => ({
                    ...current,
                    [membership.player_id]: {
                      ...row,
                      arrival_time: event.target.value,
                    },
                  }))
                }
              />

              <input
                value={row.notes}
                placeholder="Attendance note"
                onChange={(event) =>
                  setRows((current) => ({
                    ...current,
                    [membership.player_id]: {
                      ...row,
                      notes: event.target.value,
                    },
                  }))
                }
              />
            </div>
          );
        })}
      </div>

      <div className="form-actions">
        <button
          type="button"
          onClick={() => void save()}
          disabled={(session.session_players ?? []).length === 0}
        >
          Save attendance
        </button>
      </div>
    </div>
  );
}
