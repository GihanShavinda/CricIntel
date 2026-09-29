import {
  useEffect,
  useMemo,
  useState,
} from 'react';

import {
  Link,
  useParams,
} from 'react-router-dom';

import {
  getTrainingOptions,
  getTrainingSession,
  saveAttendance,
  syncTrainingSessionPlayers,
} from '../api/training';

import {
  AppLayout,
} from '../components/AppLayout';

import {
  AttendanceBoard,
} from '../components/training/AttendanceBoard';

import type {
  TrainingOptions,
  TrainingSession,
} from '../types/training';

export function TrainingSessionPage() {
  const params = useParams();

  const organizationId = Number(params.organizationId);
  const sessionId = Number(params.sessionId);

  const [session, setSession] = useState<TrainingSession | null>(null);
  const [options, setOptions] = useState<TrainingOptions>({
    teams: [],
    players: [],
    drills: [],
    coaches: [],
  });
  const [selectedPlayers, setSelectedPlayers] = useState<number[]>([]);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const selectedSet = useMemo(
    () => new Set(selectedPlayers),
    [selectedPlayers],
  );

  const load = async () => {
    setError('');

    try {
      const [sessionRow, optionRows] = await Promise.all([
        getTrainingSession(organizationId, sessionId),
        getTrainingOptions(organizationId),
      ]);

      setSession(sessionRow);
      setOptions(optionRows);
      setSelectedPlayers(
        sessionRow.session_players?.map((row) => row.player_id) ?? [],
      );
    } catch (caught) {
      console.error(caught);
      setError('Unable to load training session.');
    }
  };

  useEffect(() => {
    void load();
  }, [organizationId, sessionId]);

  if (!session) {
    return (
      <AppLayout>
        {error ? (
          <div className="form-error-message">{error}</div>
        ) : (
          <p>Loading training session...</p>
        )}
      </AppLayout>
    );
  }

  const togglePlayer = (playerId: number) => {
    setSelectedPlayers((current) =>
      current.includes(playerId)
        ? current.filter((id) => id !== playerId)
        : [...current, playerId],
    );
  };

  const savePlayers = async () => {
    setError('');
    setMessage('');

    try {
      const updated = await syncTrainingSessionPlayers(
        organizationId,
        sessionId,
        selectedPlayers,
      );

      setSession(updated);
      setMessage('Training players updated.');
    } catch (caught: any) {
      console.error(caught);
      setError(
        caught?.response?.data?.message ??
          'Unable to update players.',
      );
    }
  };

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <p className="eyebrow">P10 Session Detail</p>
          <h1>{session.session_type}</h1>
          <p>
            {String(session.session_date).slice(0, 10)}
            {' · '}
            {session.team?.name ?? `Team ${session.team_id}`}
            {' · '}
            {session.duration_minutes} minutes
          </p>
        </div>

        <Link
          className="button secondary"
          to={`/organizations/${organizationId}/training`}
        >
          Training calendar
        </Link>
      </div>

      {error && (
        <div className="form-error-message">{error}</div>
      )}

      {message && (
        <div className="form-success-message">{message}</div>
      )}

      <div className="summary-grid">
        <article>
          <strong>{session.status}</strong>
          <span>Status</span>
        </article>

        <article>
          <strong>{session.start_time?.slice(0, 5) ?? 'TBD'}</strong>
          <span>Start time</span>
        </article>

        <article>
          <strong>{session.location ?? 'TBD'}</strong>
          <span>Location</span>
        </article>

        <article>
          <strong>{session.coach?.name ?? 'Not assigned'}</strong>
          <span>Coach</span>
        </article>
      </div>

      <section className="profile-section">
        <div className="section-heading">
          <div>
            <h2>Session drills</h2>
            <p>
              Planned technical, tactical and physical work.
            </p>
          </div>
        </div>

        <div className="training-drill-grid">
          {(session.drills ?? []).map((drill) => (
            <article className="training-drill-card" key={drill.id}>
              <div>
                <strong>{drill.name}</strong>
                <span>{drill.category}</span>
              </div>

              <p>{drill.objective ?? 'No objective recorded.'}</p>

              <div className="tag-list">
                <span className="tag">{drill.difficulty}</span>
                {drill.duration_minutes && (
                  <span className="tag">
                    {drill.duration_minutes} min
                  </span>
                )}
              </div>
            </article>
          ))}

          {(session.drills ?? []).length === 0 && (
            <div className="training-empty">
              No drills assigned.
            </div>
          )}
        </div>
      </section>

      <section className="profile-section">
        <div className="section-heading">
          <div>
            <h2>Session players</h2>
            <p>
              Select the players expected to attend.
            </p>
          </div>

          <span className="status-badge">
            {selectedPlayers.length} selected
          </span>
        </div>

        <div className="training-player-picker">
          {options.players.map((player) => {
            const checked = selectedSet.has(player.id);

            return (
              <label
                key={player.id}
                className={checked ? 'selected' : ''}
              >
                <input
                  type="checkbox"
                  checked={checked}
                  onChange={() => togglePlayer(player.id)}
                />

                <span>
                  <strong>
                    {player.display_name ??
                      [player.first_name, player.last_name]
                        .filter(Boolean)
                        .join(' ')}
                  </strong>
                  <small>
                    {player.primary_role ?? 'Player'}
                    {' · '}
                    {player.fitness_status ?? 'Fitness unknown'}
                  </small>
                </span>
              </label>
            );
          })}
        </div>

        <div className="form-actions">
          <button
            type="button"
            onClick={() => void savePlayers()}
          >
            Save session players
          </button>
        </div>
      </section>

      <section className="profile-section">
        <div className="section-heading">
          <div>
            <h2>Attendance</h2>
            <p>
              Mark present, absent, late or excused.
            </p>
          </div>
        </div>

        <AttendanceBoard
          session={session}
          onSave={async (entries) => {
            setError('');
            setMessage('');

            try {
              const updated = await saveAttendance(
                organizationId,
                sessionId,
                entries,
              );

              setSession(updated);
              setMessage('Attendance saved.');
            } catch (caught: any) {
              console.error(caught);
              setError(
                caught?.response?.data?.message ??
                  'Unable to save attendance.',
              );
            }
          }}
        />
      </section>

      <section className="profile-section">
        <div className="section-heading">
          <div>
            <h2>Player development shortcuts</h2>
            <p>
              Open an assigned player to record fitness, assessment,
              objectives and development plan.
            </p>
          </div>
        </div>

        <div className="module-links">
          {(session.session_players ?? []).map((membership) => (
            <Link
              key={membership.player_id}
              to={`/organizations/${organizationId}/players/${membership.player_id}/development`}
            >
              <strong>
                {membership.player?.display_name ??
                  `Player ${membership.player_id}`}
              </strong>
              <span>
                Fitness history · assessment · objectives · plan
              </span>
            </Link>
          ))}
        </div>
      </section>
    </AppLayout>
  );
}
