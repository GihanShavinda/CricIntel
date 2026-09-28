import { useState, type FormEvent } from 'react';
import type { Club } from '../../types/organization';

export function TeamForm({
  clubs,
  onSubmit,
  onCancel,
}: {
  clubs: Club[];
  onSubmit: (payload: Record<string, unknown>) => Promise<void>;
  onCancel: () => void;
}) {
  const [submitting, setSubmitting] = useState(false);

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const data = new FormData(event.currentTarget);
    const formats = String(data.get('format_preferences') ?? '')
      .split(',').map(v => v.trim()).filter(Boolean);

    setSubmitting(true);
    try {
      await onSubmit({
        club_id: Number(data.get('club_id')),
        name: data.get('name'),
        short_name: data.get('short_name') || null,
        gender: data.get('gender') || null,
        category: data.get('category') || null,
        age_group: data.get('age_group') || null,
        format_preferences: formats,
        home_ground: data.get('home_ground') || null,
        status: data.get('status'),
      });
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={submit}>
      <label>Club
        <select name="club_id" required>
          <option value="">Select club</option>
          {clubs.map(club => <option key={club.id} value={club.id}>{club.name}</option>)}
        </select>
      </label>
      <label>Name<input name="name" required /></label>
      <label>Short name<input name="short_name" /></label>
      <label>Gender
        <select name="gender" defaultValue="male">
          <option value="male">Male</option>
          <option value="female">Female</option>
          <option value="mixed">Mixed</option>
          <option value="other">Other</option>
        </select>
      </label>
      <label>Category<input name="category" placeholder="senior" /></label>
      <label>Age group<input name="age_group" placeholder="open / U19 / U15" /></label>
      <label>Format preferences<input name="format_preferences" placeholder="T20, ODI, Test" /></label>
      <label>Home ground<input name="home_ground" /></label>
      <label>Status
        <select name="status" defaultValue="active">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="archived">Archived</option>
        </select>
      </label>
      <div className="form-actions">
        <button type="button" onClick={onCancel}>Cancel</button>
        <button disabled={submitting} type="submit">
          {submitting ? 'Saving...' : 'Create team'}
        </button>
      </div>
    </form>
  );
}
