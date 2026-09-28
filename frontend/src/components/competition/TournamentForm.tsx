import axios from 'axios';
import { useState, type FormEvent } from 'react';
import type { Season } from '../../types/organization';
import type { Tournament } from '../../types/competition';

export function TournamentForm({
  seasons,
  tournament,
  onSubmit,
  onCancel,
}: {
  seasons: Season[];
  tournament?: Tournament | null;
  onSubmit: (payload: Record<string, unknown>) => Promise<void>;
  onCancel: () => void;
}) {
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState('');

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const data = new FormData(event.currentTarget);

    setSubmitting(true);
    setMessage('');

    try {
      const overs = data.get('overs');

      await onSubmit({
        season_id: Number(data.get('season_id')),
        name: data.get('name'),
        format: data.get('format'),
        start_date: data.get('start_date'),
        end_date: data.get('end_date'),
        status: data.get('status'),
        organizer: data.get('organizer') || null,
        rules_json: overs ? { overs: Number(overs) } : null,
      });
    } catch (error) {
      if (axios.isAxiosError(error)) {
        const errors = error.response?.data?.errors;
        const first = errors ? Object.values(errors).flat()[0] : null;
        setMessage(String(first ?? error.response?.data?.message ?? 'Unable to save tournament.'));
      } else {
        setMessage('Unable to save tournament.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={submit}>
      {message && <div className="form-error-message">{message}</div>}

      <label>
        Tournament name
        <input name="name" defaultValue={tournament?.name ?? ''} required />
      </label>

      <div className="form-grid">
        <label>
          Season
          <select name="season_id" defaultValue={tournament?.season_id ?? ''} required>
            <option value="">Select season</option>
            {seasons.map((season) => (
              <option key={season.id} value={season.id}>{season.name}</option>
            ))}
          </select>
        </label>

        <label>
          Format
          <select name="format" defaultValue={tournament?.format ?? 'T20'} required>
            <option value="T20">T20</option>
            <option value="ODI">ODI</option>
            <option value="Test">Test</option>
            <option value="T10">T10</option>
            <option value="Custom">Custom</option>
          </select>
        </label>

        <label>
          Start date
          <input name="start_date" type="date" defaultValue={tournament?.start_date ?? ''} required />
        </label>

        <label>
          End date
          <input name="end_date" type="date" defaultValue={tournament?.end_date ?? ''} required />
        </label>

        <label>
          Organizer
          <input name="organizer" defaultValue={tournament?.organizer ?? ''} />
        </label>

        <label>
          Overs / innings rule
          <input name="overs" type="number" min="1" defaultValue={
            typeof tournament?.rules_json?.overs === 'number'
              ? tournament.rules_json.overs
              : ''
          } />
        </label>
      </div>

      <label>
        Status
        <select name="status" defaultValue={tournament?.status ?? 'Scheduled'}>
          <option value="Scheduled">Scheduled</option>
          <option value="In Progress">In Progress</option>
          <option value="Completed">Completed</option>
          <option value="Cancelled">Cancelled</option>
        </select>
      </label>

      <div className="form-actions">
        <button type="button" onClick={onCancel} disabled={submitting}>Cancel</button>
        <button type="submit" disabled={submitting}>
          {submitting ? 'Saving...' : tournament ? 'Update tournament' : 'Create tournament'}
        </button>
      </div>
    </form>
  );
}
