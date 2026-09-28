import axios from 'axios';
import { useState, type FormEvent } from 'react';
import type { Team } from '../../types/organization';
import type { Tournament, Venue } from '../../types/competition';

export function FixtureForm({
  tournaments,
  teams,
  venues,
  onSubmit,
  onCancel,
}: {
  tournaments: Tournament[];
  teams: Team[];
  venues: Venue[];
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
      await onSubmit({
        tournament_id: Number(data.get('tournament_id')),
        home_team_id: Number(data.get('home_team_id')),
        away_team_id: Number(data.get('away_team_id')),
        venue_id: data.get('venue_id') ? Number(data.get('venue_id')) : null,
        scheduled_at: data.get('scheduled_at'),
        match_number: data.get('match_number') ? Number(data.get('match_number')) : null,
        round: data.get('round') || null,
        status: data.get('status'),
        notes: data.get('notes') || null,
      });
    } catch (error) {
      if (axios.isAxiosError(error)) {
        const errors = error.response?.data?.errors;
        const first = errors ? Object.values(errors).flat()[0] : null;
        setMessage(String(first ?? error.response?.data?.message ?? 'Unable to schedule fixture.'));
      } else {
        setMessage('Unable to schedule fixture.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={submit}>
      {message && <div className="form-error-message">{message}</div>}

      <label>
        Tournament
        <select name="tournament_id" required>
          <option value="">Select tournament</option>
          {tournaments.map((tournament) => (
            <option key={tournament.id} value={tournament.id}>{tournament.name}</option>
          ))}
        </select>
      </label>

      <div className="form-grid">
        <label>
          Home team
          <select name="home_team_id" required>
            <option value="">Select team</option>
            {teams.map((team) => <option key={team.id} value={team.id}>{team.name}</option>)}
          </select>
        </label>

        <label>
          Away team
          <select name="away_team_id" required>
            <option value="">Select team</option>
            {teams.map((team) => <option key={team.id} value={team.id}>{team.name}</option>)}
          </select>
        </label>

        <label>
          Venue
          <select name="venue_id">
            <option value="">TBD</option>
            {venues.map((venue) => <option key={venue.id} value={venue.id}>{venue.name}</option>)}
          </select>
        </label>

        <label>
          Scheduled at
          <input name="scheduled_at" type="datetime-local" required />
        </label>

        <label>
          Match number
          <input name="match_number" type="number" min="1" />
        </label>

        <label>
          Round
          <input name="round" placeholder="League / Semi Final / Final" />
        </label>
      </div>

      <label>
        Status
        <select name="status" defaultValue="Scheduled">
          <option value="Scheduled">Scheduled</option>
          <option value="Delayed">Delayed</option>
          <option value="In Progress">In Progress</option>
          <option value="Completed">Completed</option>
          <option value="Abandoned">Abandoned</option>
          <option value="Cancelled">Cancelled</option>
        </select>
      </label>

      <label>
        Notes
        <textarea name="notes" />
      </label>

      <div className="form-actions">
        <button type="button" onClick={onCancel} disabled={submitting}>Cancel</button>
        <button type="submit" disabled={submitting}>
          {submitting ? 'Scheduling...' : 'Schedule fixture'}
        </button>
      </div>
    </form>
  );
}
