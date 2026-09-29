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
  addScoutingMedia,
  getScoutingReport,
} from '../api/scouting';

import {
  AppLayout,
} from '../components/AppLayout';

import type {
  ScoutingReport,
} from '../types/scouting';

export function ScoutingReportPage() {
  const params = useParams();
  const organizationId = Number(params.organizationId);
  const reportId = Number(params.reportId);

  const [report, setReport] = useState<ScoutingReport | null>(null);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');

  const load = async () => {
    setError('');

    try {
      setReport(
        await getScoutingReport(organizationId, reportId),
      );
    } catch (caught) {
      console.error(caught);
      setError('Unable to load scouting report.');
    }
  };

  useEffect(() => {
    void load();
  }, [organizationId, reportId]);

  if (!report) {
    return (
      <AppLayout>
        <div className={error ? 'page-state error-state' : 'page-state'}>
          {error || 'Loading scouting report...'}
        </div>
      </AppLayout>
    );
  }

  const submitMedia = async (
    event: FormEvent<HTMLFormElement>,
  ) => {
    event.preventDefault();
    const form = event.currentTarget;
    const data = new FormData(form);

    try {
      await addScoutingMedia(
        organizationId,
        reportId,
        data,
      );

      form.reset();
      setMessage('Media attached.');
      await load();
    } catch (caught: any) {
      setError(
        caught?.response?.data?.message ??
          'Unable to attach media.',
      );
    }
  };

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <p className="eyebrow">Scouting Report</p>
          <h1>
            {report.profile?.display_name ?? `Report ${report.id}`}
          </h1>
          <p>
            {String(report.report_date).slice(0, 10)}
            {' · '}
            {report.competition ?? 'Competition not recorded'}
            {' · '}
            {report.overall_recommendation}
          </p>
        </div>

        {report.profile && (
          <Link
            className="button secondary"
            to={`/organizations/${organizationId}/scouting/profiles/${report.profile.id}`}
          >
            Back to profile
          </Link>
        )}
      </div>

      {error && <div className="form-error-message">{error}</div>}
      {message && <div className="form-success-message">{message}</div>}

      <div className="scouting-rating-summary">
        {[
          ['Technical', report.rating?.technical_rating],
          ['Tactical', report.rating?.tactical_rating],
          ['Physical', report.rating?.physical_rating],
          ['Fielding', report.rating?.fielding_rating],
          ['Mental / decision', report.rating?.mental_decision_rating],
          ['Overall', report.rating?.overall_rating],
        ].map(([label, value]) => (
          <article key={String(label)}>
            <strong>{value ?? '—'}</strong>
            <span>{label}</span>
          </article>
        ))}
      </div>

      <div className="scouting-report-detail-grid">
        <section className="profile-section">
          <h2>Observation</h2>

          <dl className="scouting-definition-list">
            <div>
              <dt>Observed role</dt>
              <dd>{report.observed_role ?? '—'}</dd>
            </div>

            <div>
              <dt>Potential</dt>
              <dd>{report.potential ?? '—'}/10</dd>
            </div>

            <div>
              <dt>Scout</dt>
              <dd>{report.scout?.name ?? '—'}</dd>
            </div>

            <div>
              <dt>Recommendation</dt>
              <dd>{report.overall_recommendation}</dd>
            </div>
          </dl>

          <div className="scouting-text-block">
            <strong>Strengths</strong>
            <p>{report.strengths ?? 'No strengths recorded.'}</p>
          </div>

          <div className="scouting-text-block">
            <strong>Weaknesses</strong>
            <p>{report.weaknesses ?? 'No weaknesses recorded.'}</p>
          </div>

          <div className="scouting-text-block">
            <strong>Notes</strong>
            <p>{report.notes ?? 'No report notes recorded.'}</p>
          </div>
        </section>

        <section className="profile-section">
          <div className="section-heading">
            <div>
              <h2>Video & files</h2>
              <p>
                Attach an external video URL or upload supported media.
              </p>
            </div>
          </div>

          <div className="scouting-media-list">
            {(report.media ?? []).map((media) => (
              <article key={media.id}>
                <div>
                  <strong>
                    {media.caption ??
                      media.original_filename ??
                      media.media_type}
                  </strong>
                  <span>{media.media_type}</span>
                </div>

                {media.video_url && (
                  <a
                    className="button secondary"
                    href={media.video_url}
                    target="_blank"
                    rel="noreferrer"
                  >
                    Open video
                  </a>
                )}

                {media.file_url && (
                  <a
                    className="button secondary"
                    href={media.file_url}
                    target="_blank"
                    rel="noreferrer"
                  >
                    Open file
                  </a>
                )}
              </article>
            ))}
          </div>

          <form onSubmit={(event) => void submitMedia(event)}>
            <label>
              Attachment type
              <select
                name="media_type"
                defaultValue="Video URL"
              >
                <option>Video URL</option>
                <option>File</option>
              </select>
            </label>

            <label>
              Video URL
              <input
                name="video_url"
                type="url"
                placeholder="https://..."
              />
            </label>

            <label>
              Upload file
              <input
                name="file"
                type="file"
                accept=".mp4,.mov,.avi,.webm,.jpg,.jpeg,.png,.pdf"
              />
            </label>

            <label>
              Caption
              <input name="caption" />
            </label>

            <div className="form-actions">
              <button type="submit">
                Add media
              </button>
            </div>
          </form>
        </section>
      </div>

      <section className="profile-section">
        <div className="section-heading">
          <div>
            <h2>Report notes</h2>
            <p>
              Notes connected specifically to this observation.
            </p>
          </div>
        </div>

        <div className="scouting-notes-list">
          {(report.scouting_notes ?? []).map((note) => (
            <article key={note.id}>
              <div>
                <strong>{note.author?.name ?? 'Scout'}</strong>
                <span>{note.is_private ? 'Private' : 'Shared'}</span>
              </div>
              <p>{note.note}</p>
            </article>
          ))}
        </div>
      </section>
    </AppLayout>
  );
}
