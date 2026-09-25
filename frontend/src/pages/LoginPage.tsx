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

export function LoginPage() {
  const navigate = useNavigate();

  const {
    login,
    authenticated,
  } = useAuth();

  const [email, setEmail] =
    useState('');

  const [password, setPassword] =
    useState('');

  const [error, setError] =
    useState('');

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
      await login({
        email,
        password,
      });

      navigate('/dashboard');
    } catch (err) {
      if (axios.isAxiosError(err)) {
        setError(
          err.response?.data?.message ??
            'Login failed.'
        );
      } else {
        setError('Login failed.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <main>
      <h1>CricIntel AI</h1>
      <h2>Sign in</h2>

      {error && (
        <p role="alert">{error}</p>
      )}

      <form onSubmit={handleSubmit}>
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

        <button
          type="submit"
          disabled={submitting}
        >
          {submitting
            ? 'Signing in...'
            : 'Sign in'}
        </button>
      </form>

      <p>
        No account?{' '}
        <Link to="/register">
          Create one
        </Link>
      </p>
    </main>
  );
}
