import axios from 'axios';
import {
  useEffect,
  useState,
  type FormEvent,
} from 'react';

import type { Player } from '../../types/player';

type Props = {
  player?: Player | null;
  onSubmit: (form: FormData) => Promise<void>;
  onCancel: () => void;
};

type ValidationErrors = Record<string, string[]>;

const ROLES = [
  'Batter',
  'Bowler',
  'All-rounder',
  'Wicketkeeper',
  'Wicketkeeper-Batter',
];

const BOWLING_STYLES = [
  '',
  'Right-arm fast',
  'Right-arm medium',
  'Left-arm fast',
  'Left-arm medium',
  'Off-spin',
  'Leg-spin',
  'Left-arm orthodox',
  'Chinaman',
];

export function PlayerForm({
  player,
  onSubmit,
  onCancel,
}: Props) {
  const [errors, setErrors] = useState<ValidationErrors>({});
  const [message, setMessage] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    setErrors({});
    setMessage('');

    const form = new FormData(event.currentTarget);

    for (const [key, value] of Array.from(form.entries())) {
      if (typeof value === 'string' && value.trim() === '') {
        form.delete(key);
      }

      if (value instanceof File && value.size === 0) {
        form.delete(key);
      }
    }

    const positions = String(
      form.get('positions_csv') ?? ''
    )
      .split(',')
      .map((value) => value.trim())
      .filter(Boolean);

    form.delete('positions_csv');

    positions.forEach((position, index) => {
      form.append(`positions[${index}]`, position);
    });

    setSubmitting(true);

    try {
      await onSubmit(form);
    } catch (error) {
      if (axios.isAxiosError(error)) {
        setMessage(
          error.response?.data?.message ??
          'Unable to save player.'
        );

        setErrors(
          error.response?.data?.errors ?? {}
        );
      } else {
        setMessage('Unable to save player.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  const fieldError = (name: string) =>
    errors[name]?.map((error) => (
      <small className="field-error" key={error}>
        {error}
      </small>
    ));

  return (
    <form onSubmit={submit}>
      {message && (
        <div className="form-error-message" role="alert">
          {message}
        </div>
      )}

      <div className="form-grid">
        <label>
          First name
          <input
            name="first_name"
            defaultValue={player?.first_name ?? ''}
            required
          />
          {fieldError('first_name')}
        </label>

        <label>
          Last name
          <input
            name="last_name"
            defaultValue={player?.last_name ?? ''}
            required
          />
          {fieldError('last_name')}
        </label>

        <label>
          Display name
          <input
            name="display_name"
            defaultValue={player?.display_name ?? ''}
            required
          />
          {fieldError('display_name')}
        </label>

        <label>
          Date of birth
          <input
            name="date_of_birth"
            type="date"
            defaultValue={player?.date_of_birth ?? ''}
          />
          {fieldError('date_of_birth')}
        </label>

        <label>
          Nationality
          <input
            name="nationality"
            defaultValue={player?.nationality ?? 'Sri Lankan'}
          />
          {fieldError('nationality')}
        </label>

        <label>
          Primary role
          <select
            name="primary_role"
            defaultValue={player?.primary_role ?? 'Batter'}
            required
          >
            {ROLES.map((role) => (
              <option key={role} value={role}>
                {role}
              </option>
            ))}
          </select>
          {fieldError('primary_role')}
        </label>

        <label>
          Batting style
          <select
            name="batting_style"
            defaultValue={player?.batting_style ?? 'Right-handed'}
          >
            <option value="Right-handed">Right-handed</option>
            <option value="Left-handed">Left-handed</option>
          </select>
          {fieldError('batting_style')}
        </label>

        <label>
          Bowling style
          <select
            name="bowling_style"
            defaultValue={player?.bowling_style ?? ''}
          >
            {BOWLING_STYLES.map((style) => (
              <option key={style || 'none'} value={style}>
                {style || 'None'}
              </option>
            ))}
          </select>
          {fieldError('bowling_style')}
        </label>

        <label>
          Fitness status
          <select
            name="fitness_status"
            defaultValue={player?.fitness_status ?? 'Fit'}
          >
            <option value="Fit">Fit</option>
            <option value="Under Observation">Under Observation</option>
            <option value="Rehabilitation">Rehabilitation</option>
            <option value="Unfit">Unfit</option>
          </select>
          {fieldError('fitness_status')}
        </label>

        <label>
          Player status
          <select
            name="status"
            defaultValue={player?.status ?? 'Active'}
          >
            <option value="Active">Active</option>
            <option value="Unavailable">Unavailable</option>
            <option value="Injured">Injured</option>
            <option value="Suspended">Suspended</option>
            <option value="Retired">Retired</option>
          </select>
          {fieldError('status')}
        </label>
      </div>

      <label>
        Preferred positions
        <input
          name="positions_csv"
          defaultValue={
            player?.positions
              ?.map((position) => position.position)
              .join(', ') ?? ''
          }
          placeholder="Opening Batter, Slip, Wicketkeeper"
        />
        <small>Separate multiple positions with commas.</small>
      </label>

      <label>
        Notes
        <textarea
          name="notes"
          defaultValue={player?.notes ?? ''}
        />
      </label>

      <label>
        Player photo
        <input
          name="photo"
          type="file"
          accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
        />
        <small>Optional. JPG, PNG or WEBP, maximum 4 MB.</small>
        {fieldError('photo')}
      </label>

      <div className="form-actions">
        <button
          type="button"
          onClick={onCancel}
          disabled={submitting}
        >
          Cancel
        </button>

        <button type="submit" disabled={submitting}>
          {submitting
            ? 'Saving...'
            : player
              ? 'Update player'
              : 'Create player'}
        </button>
      </div>
    </form>
  );
}
