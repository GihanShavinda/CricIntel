import { useState, type FormEvent } from 'react';

export function SeasonForm({
  onSubmit,
  onCancel,
}: {
  onSubmit: (payload: Record<string, unknown>) => Promise<void>;
  onCancel: () => void;
}) {
  const [submitting, setSubmitting] = useState(false);

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    const data = new FormData(event.currentTarget);

    setSubmitting(true);
    try {
      await onSubmit({
        name: data.get('name'),
        start_date: data.get('start_date'),
        end_date: data.get('end_date'),
        status: data.get('status'),
      });
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={submit}>
      <label>Season name<input name="name" placeholder="2026/27" required /></label>
      <label>Start date<input name="start_date" type="date" required /></label>
      <label>End date<input name="end_date" type="date" required /></label>
      <label>Status
        <select name="status" defaultValue="planned">
          <option value="planned">Planned</option>
          <option value="active">Active</option>
          <option value="completed">Completed</option>
          <option value="archived">Archived</option>
        </select>
      </label>
      <div className="form-actions">
        <button type="button" onClick={onCancel}>Cancel</button>
        <button disabled={submitting} type="submit">
          {submitting ? 'Saving...' : 'Create season'}
        </button>
      </div>
    </form>
  );
}
