import axios from 'axios';
import { useState, type FormEvent } from 'react';
import type { Venue } from '../../types/competition';

export function VenueForm({
  venue,
  onSubmit,
  onCancel,
}: {
  venue?: Venue | null;
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
        name: data.get('name'),
        city: data.get('city') || null,
        country: data.get('country') || null,
        capacity: data.get('capacity') ? Number(data.get('capacity')) : null,
        pitch_type: data.get('pitch_type') || null,
        boundary_dimensions: {
          straight_m: data.get('straight_m') ? Number(data.get('straight_m')) : null,
          square_m: data.get('square_m') ? Number(data.get('square_m')) : null,
        },
        notes: data.get('notes') || null,
        status: data.get('status'),
      });
    } catch (error) {
      if (axios.isAxiosError(error)) {
        setMessage(error.response?.data?.message ?? 'Unable to save venue.');
      } else {
        setMessage('Unable to save venue.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  const straight = Number(venue?.boundary_dimensions?.straight_m ?? 0) || '';
  const square = Number(venue?.boundary_dimensions?.square_m ?? 0) || '';

  return (
    <form onSubmit={submit}>
      {message && <div className="form-error-message">{message}</div>}

      <label>
        Venue name
        <input name="name" defaultValue={venue?.name ?? ''} required />
      </label>

      <div className="form-grid">
        <label>
          City
          <input name="city" defaultValue={venue?.city ?? ''} />
        </label>

        <label>
          Country
          <input name="country" defaultValue={venue?.country ?? 'Sri Lanka'} />
        </label>

        <label>
          Capacity
          <input name="capacity" type="number" min="0" defaultValue={venue?.capacity ?? ''} />
        </label>

        <label>
          Pitch type
          <input name="pitch_type" defaultValue={venue?.pitch_type ?? ''} placeholder="Balanced / Batting / Bowling" />
        </label>

        <label>
          Straight boundary (m)
          <input name="straight_m" type="number" min="0" defaultValue={straight} />
        </label>

        <label>
          Square boundary (m)
          <input name="square_m" type="number" min="0" defaultValue={square} />
        </label>
      </div>

      <label>
        Notes
        <textarea name="notes" defaultValue={venue?.notes ?? ''} />
      </label>

      <label>
        Status
        <select name="status" defaultValue={venue?.status ?? 'active'}>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </label>

      <div className="form-actions">
        <button type="button" onClick={onCancel} disabled={submitting}>Cancel</button>
        <button type="submit" disabled={submitting}>
          {submitting ? 'Saving...' : venue ? 'Update venue' : 'Create venue'}
        </button>
      </div>
    </form>
  );
}
