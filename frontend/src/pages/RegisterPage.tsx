import {
  useState,
  type FormEvent,
} from 'react';

import axios from 'axios';

import {
  Link,
  Navigate,
  useNavigate,
} from 'react-router-dom';

import { useAuth } from '../auth/AuthContext';

export function RegisterPage() {
  const navigate = useNavigate();

  const {
    register,
    authenticated,
  } = useAuth();

  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] =
    useState('');

  const [
    passwordConfirmation,
    setPasswordConfirmation,
  ] = useState('');

  const [error, setError] = useState('');

  const [submitting, setSubmitting] =
    useState(false);

  if (authenticated) {
    return <Navigate to="/dashboard" replace />;
  }

  const handleSubmit = async (
    event: FormEvent
  ) => {
    event.preventDefault();
    setSubmitting(true);
    setError('');

    try {
      await register({
        name,
        email,
        password,
        password_confirmation:
          passwordConfirmation,
      });

      navigate('/dashboard');
    } catch (err) {
      if (axios.isAxiosError(err)) {
        setError(
          err.response?.data?.message ??
            'Registration failed.'
        );
      } else {
        setError(
          'Registration failed.'
        );
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <main>
      <h1>Create CricIntel Account</h1>

      {error && (
        <p role="alert">{error}</p>
      )}

      <form onSubmit={handleSubmit}>
        <div>
          <label htmlFor="name">
            Name
          </label>
          <input
            id="name"
            value={name}
            required
            onChange={(event) =>
              setName(event.target.value)
            }
          />
        </div>

        <div>
          <label htmlFor="email">
            Email
          </label>
          <input
            id="email"
            type="email"
            value={email}
            required
            onChange={(event) =>
              setEmail(event.target.value)
            }
          />
        </div>

        <div>
          <label htmlFor="password">
            Password
          </label>
          <input
            id="password"
            type="password"
            value={password}
            required
            onChange={(event) =>
              setPassword(event.target.value)
            }
          />
        </div>

        <div>
          <label htmlFor="confirmation">
            Confirm Password
          </label>
          <input
            id="confirmation"
            type="password"
            value={passwordConfirmation}
            required
            onChange={(event) =>
              setPasswordConfirmation(
                event.target.value
              )
            }
          />
        </div>

        <button
          type="submit"
          disabled={submitting}
        >
          {submitting
            ? 'Creating...'
            : 'Create account'}
        </button>
      </form>

      <Link to="/login">
        Already registered?
      </Link>
    </main>
  );
}
