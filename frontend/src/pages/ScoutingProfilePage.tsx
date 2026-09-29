import {
  useEffect,
  useState,
  type FormEvent,
} from 'react';

import {
  Link,
  useParams,
} from 'react-router-dom';

import {
  addScoutingNote,
  convertScoutingProfile,
  createScoutingReport,
  deleteScoutingProfile,
  getScoutingProfile,
  updateScoutingProfile,
} from '../api/scouting';

import {
  AppLayout,
} from '../components/AppLayout';

import type {
  ScoutingProfile,
} from '../types/scouting';

export function ScoutingProfilePage() {
  const params = useParams();
  const organizationId = Number(params.organizationId);
  const profileId = Number(params.profileId);

  const [profile, setProfile] = useState<ScoutingProfile | null>(null);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');

  const load = async () => {
    setError('');

    try {
      setProfile(
        await getScoutingProfile(organizationId, profileId),
      );
    } catch (caught) {
      console.error(caught);
      setError('Unable to load scouting profile.');
    }
  };

  useEffect(() => {
    void load();
  }, [organizationId, profileId]);

  if (!profile) {
    return (
      <AppLayout>
        <div className={error ? 'page-state error-state' : 'page-state'}>
          {error || 'Loading scouting profile...'}
        </div>
      </AppLayout>
    );
  }

  const submitReport = async (
    event: FormEvent<HTMLFormElement>,
  ) => {
    event.preventDefault();
    const form = event.currentTarget;
    const data = new FormData(form);

    try {
      await createScoutingReport(
        organizationId,
        profileId,
        {
          competition: data.get('competition') || null,
          report_date: data.get('report_date'),
          observed_role: data.get('observed_role') || null,
          strengths: data.get('strengths') || null,
          weaknesses: data.get('weaknesses') || null,
          potential: data.get('potential')
            ? Number(data.get('potential'))
            : null,
          overall_recommendation: data.get('overall_recommendation'),
          notes: data.get('notes') || null,
          technical_rating: Number(data.get('technical_rating')),
          tactical_rating: Number(data.get('tactical_rating')),
          physical_rating: Number(data.get('physical_rating')),
          fielding_rating: Number(data.get('fielding_rating')),
          mental_decision_rating: Number(data.get('mental_decision_rating')),
        },
      );

      form.reset();
      setMessage('Scouting report created.');
      await load();
    } catch (caught: any) {
      setError(
        caught?.response?.data?.message ??
          'Unable to create scouting report.',
      );
    }
  };

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <p className="eyebrow">Scouting Profile</p>
          <h1>{profile.display_name}</h1>
          <p>
            {profile.role ?? 'Role unknown'}
            {' · '}
            {profile.current_team ?? 'Team unknown'}
            {' · '}
            {profile.status}
          </p>
        </div>

        <Link
          className="button secondary"
          to={`/organizations/${organizationId}/scouting`}
        >
          Scouting dashboard
        </Link>
      </div>

      {error && <div className="form-error-message">{error}</div>}
      {message && <div className="form-success-message">{message}</div>}

      <div className="summary-grid">
        <article>
          <strong>{profile.nationality ?? '—'}</strong>
          <span>Nationality</span>
        </article>
        <article>
          <strong>{profile.current_competition ?? '—'}</strong>
          <span>Competition</span>
        </article>
        <article>
          <strong>{profile.reports?.length ?? 0}</strong>
          <span>Reports</span>
        </article>
        <article>
          <strong>{profile.converted_player_id ? 'Yes' : 'No'}</strong>
          <span>Converted player</span>
        </article>
      </div>

      <section className="profile-section">
        <div className="section-heading">
          <div>
            <h2>Profile details</h2>
            <p>Update prospect identity and scouting status.</p>
          </div>
        </div>

        <form
          onSubmit={async (event) => {
            event.preventDefault();
            const data = new FormData(event.currentTarget);

            try {
              const updated = await updateScoutingProfile(
                organizationId,
                profileId,
                {
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
                  status: data.get('status'),
                  summary: data.get('summary') || null,
                },
              );

              setProfile(updated);
              setMessage('Profile updated.');
            } catch (caught: any) {
              setError(
                caught?.response?.data?.message ??
                  'Unable to update profile.',
              );
            }
          }}
        >
          <div className="form-grid">
            <label>
              First name
              <input name="first_name" defaultValue={profile.first_name} required />
            </label>
            <label>
              Last name
              <input name="last_name" defaultValue={profile.last_name ?? ''} />
            </label>
          </div>

          <label>
            Display name
            <input name="display_name" defaultValue={profile.display_name} required />
          </label>

          <div className="form-grid">
            <label>
              Date of birth
              <input
                name="date_of_birth"
                type="date"
                defaultValue={profile.date_of_birth?.slice(0, 10) ?? ''}
              />
            </label>
            <label>
              Nationality
              <input name="nationality" defaultValue={profile.nationality ?? ''} />
            </label>
          </div>

          <div className="form-grid">
            <label>
              Role
              <input name="role" defaultValue={profile.role ?? ''} />
            </label>
            <label>
              Status
              <select name="status" defaultValue={profile.status}>
                <option>Watching</option>
                <option>Shortlisted</option>
                <option>Recommended</option>
                <option>Rejected</option>
                <option>Converted</option>
              </select>
            </label>
          </div>

          <div className="form-grid">
            <label>
              Batting style
              <input name="batting_style" defaultValue={profile.batting_style ?? ''} />
            </label>
            <label>
              Bowling style
              <input name="bowling_style" defaultValue={profile.bowling_style ?? ''} />
            </label>
          </div>

          <label>
            Current team
            <input name="current_team" defaultValue={profile.current_team ?? ''} />
          </label>

          <label>
            Current competition
            <input
              name="current_competition"
              defaultValue={profile.current_competition ?? ''}
            />
          </label>

          <label>
            Source
            <input name="source" defaultValue={profile.source ?? ''} />
          </label>

          <label>
            Summary
            <textarea name="summary" defaultValue={profile.summary ?? ''} />
          </label>

          <div className="form-actions">
            <button type="submit">Save profile</button>
            <button
              type="button"
              className="danger"
              onClick={async () => {
                if (!window.confirm('Delete this scouting profile?')) {
                  return;
                }

                await deleteScoutingProfile(organizationId, profileId);
                window.location.href = `/organizations/${organizationId}/scouting`;
              }}
            >
              Delete profile
            </button>
          </div>
        </form>
      </section>

      <section className="profile-section">
        <div className="section-heading">
          <div>
            <h2>Scouting reports</h2>
            <p>Multiple reports can be recorded for the same prospect.</p>
          </div>
        </div>

        <div className="scouting-report-list">
          {(profile.reports ?? []).map((report) => (
            <Link
              key={report.id}
              to={`/organizations/${organizationId}/scouting/reports/${report.id}`}
              className="scouting-report-row"
            >
              <div>
                <strong>{String(report.report_date).slice(0, 10)}</strong>
                <span>{report.competition ?? 'Competition not recorded'}</span>
              </div>
              <span>{report.overall_recommendation}</span>
              <strong>{report.rating?.overall_rating ?? '—'}/10</strong>
            </Link>
          ))}
        </div>

        <form className="form-card scouting-subform" onSubmit={(event) => void submitReport(event)}>
          <div className="form-grid">
            <label>
              Competition
              <input name="competition" defaultValue={profile.current_competition ?? ''} />
            </label>
            <label>
              Report date
              <input name="report_date" type="date" required />
            </label>
          </div>

          <label>
            Observed role
            <input name="observed_role" defaultValue={profile.role ?? ''} />
          </label>

          <label>
            Strengths
            <textarea name="strengths" />
          </label>

          <label>
            Weaknesses
            <textarea name="weaknesses" />
          </label>

          <div className="scouting-rating-grid">
            {[
              ['technical_rating', 'Technical'],
              ['tactical_rating', 'Tactical'],
              ['physical_rating', 'Physical'],
              ['fielding_rating', 'Fielding'],
              ['mental_decision_rating', 'Mental / decision'],
            ].map(([name, label]) => (
              <label key={name}>
                {label}
                <input
                  name={name}
                  type="number"
                  min="1"
                  max="10"
                  defaultValue="5"
                  required
                />
              </label>
            ))}
          </div>

          <div className="form-grid">
            <label>
              Potential
              <input name="potential" type="number" min="1" max="10" />
            </label>
            <label>
              Recommendation
              <select name="overall_recommendation" defaultValue="Monitor">
                <option>Highly Recommend</option>
                <option>Recommend</option>
                <option>Monitor</option>
                <option>Do Not Recommend</option>
              </select>
            </label>
          </div>

          <label>
            Notes
            <textarea name="notes" />
          </label>

          <div className="form-actions">
            <button type="submit">Create report</button>
          </div>
        </form>
      </section>

      <section className="profile-section">
        <div className="section-heading">
          <div>
            <h2>Scouting notes</h2>
            <p>Profile-level notes can be public to scouts or private to the author.</p>
          </div>
        </div>

        <div className="scouting-notes-list">
          {(profile.notes ?? []).map((note) => (
            <article key={note.id}>
              <div>
                <strong>{note.author?.name ?? 'Scout'}</strong>
                <span>{note.is_private ? 'Private' : 'Shared'}</span>
              </div>
              <p>{note.note}</p>
            </article>
          ))}
        </div>

        <form
          className="training-inline-form"
          onSubmit={async (event) => {
            event.preventDefault();
            const form = event.currentTarget;
            const data = new FormData(form);

            try {
              await addScoutingNote(
                organizationId,
                profileId,
                {
                  note: String(data.get('note') ?? ''),
                  is_private: data.get('is_private') === 'on',
                },
              );

              form.reset();
              setMessage('Scouting note added.');
              await load();
            } catch (caught: any) {
              setError(
                caught?.response?.data?.message ??
                  'Unable to add scouting note.',
              );
            }
          }}
        >
          <input name="note" placeholder="Add scouting note" required />
          <label className="scouting-private-toggle">
            <input name="is_private" type="checkbox" />
            Private
          </label>
          <button type="submit">Add note</button>
        </form>
      </section>

      {!profile.converted_player_id && (
        <section className="profile-section">
          <div className="section-heading">
            <div>
              <h2>Recruitment conversion</h2>
              <p>
                Authorized selectors/managers can convert this prospect
                into an organization player.
              </p>
            </div>
          </div>

          <form
            onSubmit={async (event) => {
              event.preventDefault();
              const form = event.currentTarget;
              const data = new FormData(form);

              try {
                await convertScoutingProfile(
                  organizationId,
                  profileId,
                  {
                    primary_role: data.get('primary_role'),
                    batting_style: data.get('batting_style') || null,
                    bowling_style: data.get('bowling_style') || null,
                    fitness_status: data.get('fitness_status') || 'Unknown',
                    status: 'active',
                    notes: data.get('notes') || null,
                  },
                );

                setMessage('Prospect converted to organization player.');
                await load();
              } catch (caught: any) {
                setError(
                  caught?.response?.data?.message ??
                    'Unable to convert prospect.',
                );
              }
            }}
          >
            <div className="form-grid">
              <label>
                Primary role
                <input name="primary_role" defaultValue={profile.role ?? ''} required />
              </label>
              <label>
                Fitness status
                <input name="fitness_status" defaultValue="Unknown" />
              </label>
            </div>

            <div className="form-grid">
              <label>
                Batting style
                <input name="batting_style" defaultValue={profile.batting_style ?? ''} />
              </label>
              <label>
                Bowling style
                <input name="bowling_style" defaultValue={profile.bowling_style ?? ''} />
              </label>
            </div>

            <label>
              Recruitment notes
              <textarea name="notes" />
            </label>

            <div className="form-actions">
              <button type="submit">Convert to player</button>
            </div>
          </form>
        </section>
      )}
    </AppLayout>
  );
}
