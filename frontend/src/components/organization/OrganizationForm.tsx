import { useState, type FormEvent } from 'react';

export function OrganizationForm({
  onSubmit,
  onCancel,
}: {
  onSubmit: (form: FormData) => Promise<void>;
  onCancel: () => void;
}) {
  const [submitting, setSubmitting] = useState(false);

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSubmitting(true);
    try {
      await onSubmit(new FormData(event.currentTarget));
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={submit}>
      <label>Name<input name="name" required /></label>
      <label>Short name<input name="short_name" maxLength={50} /></label>
      <label>Country<input name="country" /></label>
      <label>Timezone<input name="timezone" defaultValue="Asia/Colombo" required /></label>
      <label>Description<textarea name="description" /></label>
      <label>Status
        <select name="status" defaultValue="active">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="archived">Archived</option>
        </select>
      </label>
      <label>Logo<input name="logo" type="file" accept="image/png,image/jpeg,image/webp" /></label>
      <div className="form-actions">
        <button type="button" onClick={onCancel}>Cancel</button>
        <button disabled={submitting} type="submit">
          {submitting ? 'Saving...' : 'Create organization'}
        </button>
      </div>
    </form>
  );
}
