import {
  useEffect,
  useState,
  type FormEvent,
} from 'react';

import {
  Link,
  useNavigate,
  useParams,
} from 'react-router-dom';

import {
  createScoutingProfile,
  listScoutingProfiles,
} from '../api/scouting';

import {
  AppLayout,
} from '../components/AppLayout';

import type {
  ScoutingProfile,
} from '../types/scouting';

export function ScoutingDashboardPage() {
  const organizationId = Number(useParams().organizationId);
  const navigate = useNavigate();

  const [profiles, setProfiles] = useState<ScoutingProfile[]>([]);
  const [q, setQ] = useState('');
  const [role, setRole] = useState('');
  const [status, setStatus] = useState('');
  const [competition, setCompetition] = useState('');
  const [recommendation, setRecommendation] = useState('');
  const [selected, setSelected] = useState<number[]>([]);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');

  const load = async () => {
    setError('');

    try {
      const result = await listScoutingProfiles(organizationId, {
        q: q || undefined,
        role: role || undefined,
        status: status || undefined,
        competition: competition || undefined,
        recommendation: recommendation || undefined,
        per_page: 100,
      });

      setProfiles(Array.isArray(result.data) ? result.data : []);
    } catch (caught) {
      console.error(caught);
      setError('Unable to load scouting profiles.');
    }
  };

  useEffect(() => {
    void load();
  }, [
    organizationId,
    q,
    role,
    status,
    competition,
    recommendation,
  ]);

  const submitProfile = async (
    event: FormEvent<HTMLFormElement>,
  ) => {
    event.preventDefault();
    setError('');
    setMessage('');

    const form = event.currentTarget;
    const data = new FormData(form);

    try {
      await createScoutingProfile(organizationId, {
        first_name: data.get('first_name'),
        last_name: data.get('last_name') || null,
        display_name: data.get('display_name'),
        date_of_birth: data.get('date_of_birth') || null,
        nationality: data.get('nationality') || null,
        role: data.get('role') || null,
        batting_style: data.get('batting_style') || null,
        bowling_style: data.get('bowling_style') || null,
        current_team: data.get('current_team') || null,
        current_competition: data.get('current_competition') || null,
        source: data.get('source') || null,
        status: 'Watching',
        summary: data.get('summary') || null,
      });

      form.reset();
      setMessage('Scouting profile created.');
      await load();
    } catch (caught: any) {
      setError(
        caught?.response?.data?.message ??
          'Unable to create scouting profile.',
      );
    }
  };

  const toggleCompare = (profileId: number) => {
    setSelected((current) => {
      if (current.includes(profileId)) {
        return current.filter((id) => id !== profileId);
      }

      if (current.length >= 4) {
        return current;
      }

      return [...current, profileId];
    });
  };

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <p className="eyebrow">P11 Scouting & Recruitment</p>
          <h1>Scouting Dashboard</h1>
          <p>
            Record observations, rate prospects, compare candidates and
            convert approved prospects into organization players.
          </p>
        </div>

        <button
          type="button"
          disabled={selected.length < 2}
          onClick={() =>
            navigate(
              `/organizations/${organizationId}/scouting/compare?ids=${selected.join(',')}`,
            )
          }
        >
          Compare {selected.length || ''}
        </button>
      </div>

      {error && <div className="form-error-message">{error}</div>}
      {message && <div className="form-success-message">{message}</div>}

      <div className="scouting-layout">
        <section className="profile-section">
          <div className="section-heading">
            <div>
              <h2>Prospects</h2>
              <p>Search and filter scouting profiles.</p>
            </div>
          </div>

          <div className="scouting-filter-grid">
            <input
              value={q}
              onChange={(event) => setQ(event.target.value)}
              placeholder="Search player, team or nationality"
            />

            <input
              value={role}
              onChange={(event) => setRole(event.target.value)}
              placeholder="Role"
            />

            <select
              value={status}
              onChange={(event) => setStatus(event.target.value)}
            >
              <option value="">All statuses</option>
              <option>Watching</option>
              <option>Shortlisted</option>
              <option>Recommended</option>
              <option>Rejected</option>
              <option>Converted</option>
            </select>

            <input
              value={competition}
              onChange={(event) => setCompetition(event.target.value)}
              placeholder="Competition"
            />

            <select
              value={recommendation}
              onChange={(event) => setRecommendation(event.target.value)}
            >
              <option value="">All recommendations</option>
              <option>Highly Recommend</option>
              <option>Recommend</option>
              <option>Monitor</option>
              <option>Do Not Recommend</option>
            </select>
          </div>

          <div className="scouting-card-grid">
            {profiles.map((profile) => {
              const latest = profile.reports?.[0];
              const checked = selected.includes(profile.id);

              return (
                <article
                  className={`scouting-profile-card${checked ? ' selected' : ''}`}
                  key={profile.id}
                >
                  <div className="scouting-profile-head">
                    <div>
                      <strong>{profile.display_name}</strong>
                      <span>
                        {profile.role ?? 'Role unknown'}
                        {' · '}
                        {profile.nationality ?? 'Nationality unknown'}
                      </span>
                    </div>

                    <label className="scouting-compare-check">
                      <input
                        type="checkbox"
                        checked={checked}
                        onChange={() => toggleCompare(profile.id)}
                      />
                      Compare
                    </label>
                  </div>

                  <div className="scouting-profile-meta">
                    <span>
                      Team: {profile.current_team ?? '—'}
                    </span>
                    <span>
                      Competition: {profile.current_competition ?? '—'}
                    </span>
                    <span>
                      Status: {profile.status}
                    </span>
                  </div>

                  {latest && (
                    <div className="scouting-latest-report">
                      <strong>
                        {latest.rating?.overall_rating ?? '—'}/10
                      </strong>
                      <span>{latest.overall_recommendation}</span>
                      <small>
                        {String(latest.report_date).slice(0, 10)}
                      </small>
                    </div>
                  )}

                  <Link
                    className="button secondary"
                    to={`/organizations/${organizationId}/scouting/profiles/${profile.id}`}
                  >
                    Open profile
                  </Link>
                </article>
              );
            })}

            {profiles.length === 0 && (
              <div className="training-empty">
                No scouting profiles match the current filters.
              </div>
            )}
          </div>
        </section>

        <aside className="form-card scouting-create-card">
          <div className="section-heading">
            <div>
              <h2>New prospect</h2>
              <p>Create a scouting profile.</p>
            </div>
          </div>

          <form onSubmit={(event) => void submitProfile(event)}>
            <div className="form-grid">
              <label>
                First name
                <input name="first_name" required />
              </label>

              <label>
                Last name
                <input name="last_name" />
              </label>
            </div>

            <label>
              Display name
              <input name="display_name" required />
            </label>

            <div className="form-grid">
              <label>
                Date of birth
                <input name="date_of_birth" type="date" />
              </label>

              <label>
                Nationality
                <input name="nationality" />
              </label>
            </div>

            <label>
              Role
              <input
                name="role"
                placeholder="Batter / Bowler / All-rounder / Wicketkeeper"
              />
            </label>

            <div className="form-grid">
              <label>
                Batting style
                <input name="batting_style" />
              </label>

              <label>
                Bowling style
                <input name="bowling_style" />
              </label>
            </div>

            <label>
              Current team
              <input name="current_team" />
            </label>

            <label>
              Current competition
              <input name="current_competition" />
            </label>

            <label>
              Source
              <input
                name="source"
                placeholder="Tournament / academy / referral"
              />
            </label>

            <label>
              Summary
              <textarea name="summary" />
            </label>

            <div className="form-actions">
              <button type="submit">
                Create profile
              </button>
            </div>
          </form>
        </aside>
      </div>
    </AppLayout>
  );
}
