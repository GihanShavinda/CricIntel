import {
  useEffect,
  useMemo,
  useState,
  type FormEvent,
} from 'react';

import {
  Link,
  useParams,
} from 'react-router-dom';

import {
  createTrainingDrill,
  createTrainingSession,
  getTrainingOptions,
  listTrainingSessions,
} from '../api/training';

import {
  AppLayout,
} from '../components/AppLayout';

import type {
  TrainingOptions,
  TrainingSession,
} from '../types/training';

function currentMonth() {
  const now = new Date();

  return `${now.getFullYear()}-${String(
    now.getMonth() + 1,
  ).padStart(2, '0')}`;
}

export function TrainingCalendarPage() {
  const organizationId = Number(
    useParams().organizationId,
  );

  const [month, setMonth] = useState(currentMonth());
  const [sessions, setSessions] = useState<TrainingSession[]>([]);
  const [options, setOptions] = useState<TrainingOptions>({
    teams: [],
    players: [],
    drills: [],
    coaches: [],
  });
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const load = async () => {
    setError('');

    try {
      const [sessionRows, optionRows] = await Promise.all([
        listTrainingSessions(organizationId, { month }),
        getTrainingOptions(organizationId),
      ]);

      setSessions(
        Array.isArray(sessionRows)
          ? sessionRows
          : [],
      );

      setOptions(optionRows);
    } catch (caught) {
      console.error(caught);
      setError('Unable to load training calendar.');
    }
  };

  useEffect(() => {
    void load();
  }, [organizationId, month]);

  const groupedSessions = useMemo(() => {
    const map = new Map<string, TrainingSession[]>();

    for (const session of sessions) {
      const date = String(session.session_date).slice(0, 10);
      const rows = map.get(date) ?? [];
      rows.push(session);
      map.set(date, rows);
    }

    return [...map.entries()].sort(([a], [b]) =>
      a.localeCompare(b),
    );
  }, [sessions]);

  const createSession = async (
    event: FormEvent<HTMLFormElement>,
  ) => {
    event.preventDefault();
    setError('');
    setMessage('');

    const form = event.currentTarget;
    const data = new FormData(form);

    try {
      await createTrainingSession(organizationId, {
        team_id: Number(data.get('team_id')),
        coach_id: data.get('coach_id')
          ? Number(data.get('coach_id'))
          : null,
        session_date: data.get('session_date'),
        start_time: data.get('start_time') || null,
        location: data.get('location') || null,
        duration_minutes: Number(data.get('duration_minutes')),
        session_type: data.get('session_type'),
        status: 'Scheduled',
        notes: data.get('notes') || null,
        drill_ids: data
          .getAll('drill_ids')
          .map(Number),
      });

      form.reset();
      setMessage('Training session created.');
      await load();
    } catch (caught: any) {
      console.error(caught);
      setError(
        caught?.response?.data?.message ??
          'Unable to create training session.',
      );
    }
  };

  const createDrill = async (
    event: FormEvent<HTMLFormElement>,
  ) => {
    event.preventDefault();
    setError('');
    setMessage('');

    const form = event.currentTarget;
    const data = new FormData(form);

    try {
      await createTrainingDrill(organizationId, {
        name: data.get('name'),
        category: data.get('category'),
        objective: data.get('objective') || null,
        duration_minutes: data.get('duration_minutes')
          ? Number(data.get('duration_minutes'))
          : null,
        difficulty: data.get('difficulty'),
        notes: data.get('notes') || null,
        is_active: true,
      });

      form.reset();
      setMessage('Training drill created.');
      await load();
    } catch (caught: any) {
      console.error(caught);
      setError(
        caught?.response?.data?.message ??
          'Unable to create training drill.',
      );
    }
  };

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <p className="eyebrow">P10 Training & Development</p>
          <h1>Training Calendar</h1>
          <p>
            Plan team sessions, assign drills and manage player
            development without automated AI decisions.
          </p>
        </div>

        <label className="training-month-filter">
          Month
          <input
            type="month"
            value={month}
            onChange={(event) => setMonth(event.target.value)}
          />
        </label>
      </div>

      {error && (
        <div className="form-error-message">{error}</div>
      )}

      {message && (
        <div className="form-success-message">{message}</div>
      )}

      <div className="training-dashboard-grid">
        <section className="profile-section">
          <div className="section-heading">
            <div>
              <h2>Calendar</h2>
              <p>{sessions.length} sessions in {month}</p>
            </div>
          </div>

          <div className="training-calendar">
            {groupedSessions.length === 0 && (
              <div className="training-empty">
                No sessions scheduled for this month.
              </div>
            )}

            {groupedSessions.map(([date, rows]) => (
              <section className="training-calendar-day" key={date}>
                <h3>{date}</h3>

                <div className="training-session-list">
                  {rows.map((session) => (
                    <Link
                      key={session.id}
                      className="training-session-card"
                      to={`/organizations/${organizationId}/training/sessions/${session.id}`}
                    >
                      <div>
                        <strong>
                          {session.session_type}
                        </strong>
                        <span>
                          {session.team?.name ?? `Team ${session.team_id}`}
                        </span>
                      </div>

                      <div>
                        <span>
                          {session.start_time?.slice(0, 5) ?? 'Time TBD'}
                        </span>
                        <span>
                          {session.duration_minutes} min
                        </span>
                        <span>
                          {session.location ?? 'Location TBD'}
                        </span>
                      </div>

                      <span className="status-badge">
                        {session.status}
                      </span>
                    </Link>
                  ))}
                </div>
              </section>
            ))}
          </div>
        </section>

        <aside className="training-side-stack">
          <section className="form-card">
            <div className="section-heading">
              <div>
                <h2>New session</h2>
                <p>Create a scheduled training session.</p>
              </div>
            </div>

            <form onSubmit={(event) => void createSession(event)}>
              <label>
                Team
                <select name="team_id" required>
                  <option value="">Select team</option>
                  {options.teams.map((team) => (
                    <option key={team.id} value={team.id}>
                      {team.name}
                    </option>
                  ))}
                </select>
              </label>

              <label>
                Coach
                <select name="coach_id">
                  <option value="">Current/default coach</option>
                  {options.coaches.map((coach) => (
                    <option key={coach.id} value={coach.id}>
                      {coach.name}
                    </option>
                  ))}
                </select>
              </label>

              <div className="form-grid">
                <label>
                  Date
                  <input
                    name="session_date"
                    type="date"
                    required
                  />
                </label>

                <label>
                  Start time
                  <input
                    name="start_time"
                    type="time"
                  />
                </label>
              </div>

              <div className="form-grid">
                <label>
                  Duration
                  <input
                    name="duration_minutes"
                    type="number"
                    min="1"
                    defaultValue="90"
                    required
                  />
                </label>

                <label>
                  Session type
                  <select name="session_type" defaultValue="Skills">
                    <option>Skills</option>
                    <option>Nets</option>
                    <option>Fitness</option>
                    <option>Fielding</option>
                    <option>Tactical</option>
                    <option>Recovery</option>
                    <option>Match Simulation</option>
                  </select>
                </label>
              </div>

              <label>
                Location
                <input
                  name="location"
                  placeholder="Ground / nets / gym"
                />
              </label>

              <label>
                Drills
                <select
                  name="drill_ids"
                  multiple
                  size={Math.min(Math.max(options.drills.length, 3), 8)}
                >
                  {options.drills.map((drill) => (
                    <option key={drill.id} value={drill.id}>
                      {drill.name} · {drill.category}
                    </option>
                  ))}
                </select>
              </label>

              <label>
                Notes
                <textarea
                  name="notes"
                  placeholder="Session focus, coaching notes..."
                />
              </label>

              <div className="form-actions">
                <button type="submit">
                  Create session
                </button>
              </div>
            </form>
          </section>

          <section className="form-card">
            <div className="section-heading">
              <div>
                <h2>New drill</h2>
                <p>Build the reusable drill library.</p>
              </div>
            </div>

            <form onSubmit={(event) => void createDrill(event)}>
              <label>
                Drill name
                <input
                  name="name"
                  placeholder="Sweep Drill"
                  required
                />
              </label>

              <div className="form-grid">
                <label>
                  Category
                  <select name="category" defaultValue="Batting">
                    <option>Batting</option>
                    <option>Bowling</option>
                    <option>Fielding</option>
                    <option>Fitness</option>
                    <option>Tactical</option>
                    <option>Recovery</option>
                  </select>
                </label>

                <label>
                  Difficulty
                  <select
                    name="difficulty"
                    defaultValue="Intermediate"
                  >
                    <option>Beginner</option>
                    <option>Intermediate</option>
                    <option>Advanced</option>
                  </select>
                </label>
              </div>

              <label>
                Objective
                <textarea
                  name="objective"
                  placeholder="What should this drill improve?"
                />
              </label>

              <label>
                Duration minutes
                <input
                  name="duration_minutes"
                  type="number"
                  min="1"
                  placeholder="20"
                />
              </label>

              <label>
                Notes
                <textarea name="notes" />
              </label>

              <div className="form-actions">
                <button type="submit">
                  Add drill
                </button>
              </div>
            </form>
          </section>
        </aside>
      </div>
    </AppLayout>
  );
}
