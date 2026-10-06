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

import {
  useAuth,
} from '../auth/AuthContext';

export function LoginPage() {
  const navigate =
    useNavigate();

  const {
    login,
    authenticated,
  } = useAuth();

  const [
    email,
    setEmail,
  ] = useState('');

  const [
    password,
    setPassword,
  ] = useState('');

  const [
    showPassword,
    setShowPassword,
  ] = useState(false);

  const [
    remember,
    setRemember,
  ] = useState(false);

  const [
    error,
    setError,
  ] = useState('');

  const [
    submitting,
    setSubmitting,
  ] = useState(false);

  if (authenticated) {
    return (
      <Navigate
        to="/dashboard"
        replace
      />
    );
  }

  const handleSubmit =
    async (
      event:
        FormEvent<HTMLFormElement>,
    ) => {
      event.preventDefault();

      setSubmitting(true);
      setError('');

      try {
        await login({
          email,
          password,
          remember,
        });

        navigate(
          '/dashboard',
          {
            replace: true,
          },
        );
      } catch (err) {
        if (
          axios.isAxiosError(
            err,
          )
        ) {
          setError(
            err.response?.data
              ?.message ??
              'Unable to sign in. Please check your credentials and try again.',
          );
        } else {
          setError(
            'Unable to sign in. Please try again.',
          );
        }
      } finally {
        setSubmitting(false);
      }
    };

  return (
    <main className="auth-page">
      <section className="auth-visual-panel">
        <div className="auth-visual-overlay" />

        <div className="auth-visual-content">
          <Link
            to="/"
            className="auth-brand"
          >
            <span className="auth-brand-mark">
              CI
            </span>

            <span className="auth-brand-copy">
              <strong>
                CricIntel
              </strong>

              <small>
                CRICKET
                INTELLIGENCE
              </small>
            </span>
          </Link>

          <div className="auth-hero-copy">
            <span className="auth-kicker">
              Cricket intelligence
              platform
            </span>

            <h1>
              Turn cricket data
              into better tactical
              decisions.
            </h1>

            <p>
              Manage teams,
              analyze matches,
              evaluate players,
              plan tactics and
              access grounded
              cricket intelligence
              from one secure
              workspace.
            </p>

            <div className="auth-capability-grid">
              <article>
                <span>
                  01
                </span>

                <div>
                  <strong>
                    Match
                    intelligence
                  </strong>

                  <small>
                    Ball-by-ball
                    scoring and
                    performance
                    analytics
                  </small>
                </div>
              </article>

              <article>
                <span>
                  02
                </span>

                <div>
                  <strong>
                    Tactical
                    planning
                  </strong>

                  <small>
                    Opponent,
                    matchup and
                    strategy
                    workflows
                  </small>
                </div>
              </article>

              <article>
                <span>
                  03
                </span>

                <div>
                  <strong>
                    AI-assisted
                    analysis
                  </strong>

                  <small>
                    Evidence-based
                    coaching support
                  </small>
                </div>
              </article>
            </div>
          </div>

          <div className="auth-visual-footer">
            <span className="auth-live-indicator">
              <span />
              Platform operational
            </span>

            <span>
              CricIntel AI
              Platform
            </span>
          </div>
        </div>
      </section>

      <section className="auth-form-panel">
        <div className="auth-form-wrapper">
          <div className="auth-mobile-brand">
            <span className="auth-brand-mark">
              CI
            </span>

            <div>
              <strong>
                CricIntel
              </strong>

              <small>
                CRICKET
                INTELLIGENCE
              </small>
            </div>
          </div>

          <div className="auth-form-heading">
            <span className="auth-eyebrow">
              Welcome back
            </span>

            <h2>
              Sign in to
              CricIntel
            </h2>

            <p>
              Access your cricket
              operations,
              analytics and
              tactical workspace.
            </p>
          </div>

          {error && (
            <div
              className="auth-alert auth-alert-error"
              role="alert"
            >
              <span className="auth-alert-icon">
                !
              </span>

              <div>
                <strong>
                  Sign in failed
                </strong>

                <span>
                  {error}
                </span>
              </div>
            </div>
          )}

          <form
            className="auth-form"
            onSubmit={
              handleSubmit
            }
          >
            <div className="auth-field">
              <label htmlFor="email">
                Email address
              </label>

              <div className="auth-input-shell">
                <span className="auth-input-icon">
                  @
                </span>

                <input
                  id="email"
                  type="email"
                  value={email}
                  required
                  autoComplete="email"
                  placeholder="name@example.com"
                  onChange={(
                    event,
                  ) =>
                    setEmail(
                      event.target
                        .value,
                    )
                  }
                />
              </div>
            </div>

            <div className="auth-field">
              <div className="auth-label-row">
                <label htmlFor="password">
                  Password
                </label>
              </div>

              <div className="auth-input-shell">
                <span className="auth-input-icon">
                  ●
                </span>

                <input
                  id="password"
                  type={
                    showPassword
                      ? 'text'
                      : 'password'
                  }
                  value={password}
                  required
                  autoComplete="current-password"
                  placeholder="Enter your password"
                  onChange={(
                    event,
                  ) =>
                    setPassword(
                      event.target
                        .value,
                    )
                  }
                />

                <button
                  type="button"
                  className="auth-password-toggle"
                  onClick={() =>
                    setShowPassword(
                      (current) =>
                        !current,
                    )
                  }
                  aria-label={
                    showPassword
                      ? 'Hide password'
                      : 'Show password'
                  }
                >
                  {showPassword
                    ? 'Hide'
                    : 'Show'}
                </button>
              </div>
            </div>

            <label className="auth-checkbox-row">
              <input
                type="checkbox"
                checked={remember}
                onChange={(
                  event,
                ) =>
                  setRemember(
                    event.target
                      .checked,
                  )
                }
              />

              <span>
                Keep me signed
                in on this
                device
              </span>
            </label>

            <button
              className="auth-primary-button"
              type="submit"
              disabled={
                submitting
              }
            >
              <span>
                {submitting
                  ? 'Signing in...'
                  : 'Sign in to CricIntel'}
              </span>

              {!submitting && (
                <span>
                  →
                </span>
              )}
            </button>
          </form>

          <div className="auth-divider">
            <span>
              New to CricIntel?
            </span>
          </div>

          <Link
            to="/register"
            className="auth-secondary-button"
          >
            Create an account
          </Link>

          <div className="auth-security-note">
            <span className="auth-security-icon">
              ✓
            </span>

            <span>
              Secure
              organization-based
              access with role
              permissions.
            </span>
          </div>
        </div>

        <footer className="auth-form-footer">
          <span>
            © 2026 CricIntel
          </span>

          <span>
            Intelligent cricket
            operations
          </span>
        </footer>
      </section>
    </main>
  );
}