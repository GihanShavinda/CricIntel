import {
  useMemo,
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

export function RegisterPage() {
  const navigate =
    useNavigate();

  const {
    register,
    authenticated,
  } = useAuth();

  const [
    name,
    setName,
  ] = useState('');

  const [
    email,
    setEmail,
  ] = useState('');

  const [
    password,
    setPassword,
  ] = useState('');

  const [
    passwordConfirmation,
    setPasswordConfirmation,
  ] = useState('');

  const [
    showPassword,
    setShowPassword,
  ] = useState(false);

  const [
    acceptedTerms,
    setAcceptedTerms,
  ] = useState(false);

  const [
    error,
    setError,
  ] = useState('');

  const [
    submitting,
    setSubmitting,
  ] = useState(false);

  const passwordStrength =
    useMemo(() => {
      let score = 0;

      if (
        password.length >= 8
      ) {
        score++;
      }

      if (
        /[A-Z]/.test(
          password,
        )
      ) {
        score++;
      }

      if (
        /[0-9]/.test(
          password,
        )
      ) {
        score++;
      }

      if (
        /[^A-Za-z0-9]/.test(
          password,
        )
      ) {
        score++;
      }

      if (
        score <= 1
      ) {
        return {
          level: 'weak',
          label: 'Weak',
        };
      }

      if (
        score === 2
      ) {
        return {
          level: 'fair',
          label: 'Fair',
        };
      }

      if (
        score === 3
      ) {
        return {
          level: 'good',
          label: 'Good',
        };
      }

      return {
        level: 'strong',
        label: 'Strong',
      };
    }, [password]);

  const passwordsMatch =
    passwordConfirmation.length ===
      0 ||
    password ===
      passwordConfirmation;

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

      setError('');

      if (
        password !==
        passwordConfirmation
      ) {
        setError(
          'Password confirmation does not match.',
        );
        return;
      }

      if (!acceptedTerms) {
        setError(
          'Please confirm that you agree to use CricIntel responsibly.',
        );
        return;
      }

      setSubmitting(true);

      try {
        await register({
          name,
          email,
          password,
          password_confirmation:
            passwordConfirmation,
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
          const validation =
            err.response?.data
              ?.errors;

          if (
            validation &&
            typeof validation ===
              'object'
          ) {
            const firstError =
              Object.values(
                validation,
              )
                .flat()
                .find(
                  (
                    message,
                  ) =>
                    typeof message ===
                    'string',
                );

            setError(
              typeof firstError ===
                'string'
                ? firstError
                : err.response
                    ?.data
                    ?.message ??
                  'Registration failed.',
            );
          } else {
            setError(
              err.response?.data
                ?.message ??
                'Registration failed.',
            );
          }
        } else {
          setError(
            'Registration failed. Please try again.',
          );
        }
      } finally {
        setSubmitting(false);
      }
    };

  return (
    <main className="auth-page">
      <section className="auth-visual-panel auth-register-visual">
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
              Build your cricket
              workspace
            </span>

            <h1>
              One platform for
              cricket operations,
              analytics and
              strategy.
            </h1>

            <p>
              Create your account
              and start organizing
              teams, players,
              matches, performance
              workflows and
              tactical
              intelligence.
            </p>

            <div className="auth-registration-features">
              <div>
                <span>
                  ✓
                </span>

                <p>
                  <strong>
                    Team
                    management
                  </strong>

                  <small>
                    Clubs, teams,
                    seasons and
                    player
                    workflows.
                  </small>
                </p>
              </div>

              <div>
                <span>
                  ✓
                </span>

                <p>
                  <strong>
                    Match
                    operations
                  </strong>

                  <small>
                    Fixtures,
                    scoring,
                    squads and
                    Playing XI.
                  </small>
                </p>
              </div>

              <div>
                <span>
                  ✓
                </span>

                <p>
                  <strong>
                    Intelligence
                  </strong>

                  <small>
                    Analytics,
                    scouting,
                    predictions
                    and tactical
                    planning.
                  </small>
                </p>
              </div>

              <div>
                <span>
                  ✓
                </span>

                <p>
                  <strong>
                    Role-aware
                    security
                  </strong>

                  <small>
                    Administrator,
                    Coach,
                    Analyst,
                    Selector and
                    Manager access.
                  </small>
                </p>
              </div>
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
        <div className="auth-form-wrapper auth-register-wrapper">
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
              Get started
            </span>

            <h2>
              Create your
              CricIntel account
            </h2>

            <p>
              Set up your account
              to access the
              CricIntel cricket
              intelligence
              workspace.
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
                  Account
                  creation failed
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
              <label htmlFor="name">
                Full name
              </label>

              <div className="auth-input-shell">
                <span className="auth-input-icon">
                  A
                </span>

                <input
                  id="name"
                  value={name}
                  required
                  autoComplete="name"
                  placeholder="Your full name"
                  onChange={(
                    event,
                  ) =>
                    setName(
                      event.target
                        .value,
                    )
                  }
                />
              </div>
            </div>

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
              <label htmlFor="password">
                Password
              </label>

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
                  minLength={8}
                  autoComplete="new-password"
                  placeholder="Create a secure password"
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
                >
                  {showPassword
                    ? 'Hide'
                    : 'Show'}
                </button>
              </div>

              {password && (
                <div className="auth-password-strength">
                  <div className="auth-strength-track">
                    <span
                      className={`auth-strength-fill ${passwordStrength.level}`}
                    />
                  </div>

                  <span>
                    Password
                    strength:
                    {' '}
                    <strong>
                      {
                        passwordStrength.label
                      }
                    </strong>
                  </span>
                </div>
              )}
            </div>

            <div className="auth-field">
              <label htmlFor="confirmation">
                Confirm password
              </label>

              <div
                className={[
                  'auth-input-shell',
                  !passwordsMatch
                    ? 'invalid'
                    : '',
                ].join(' ')}
              >
                <span className="auth-input-icon">
                  ✓
                </span>

                <input
                  id="confirmation"
                  type={
                    showPassword
                      ? 'text'
                      : 'password'
                  }
                  value={
                    passwordConfirmation
                  }
                  required
                  autoComplete="new-password"
                  placeholder="Repeat your password"
                  onChange={(
                    event,
                  ) =>
                    setPasswordConfirmation(
                      event.target
                        .value,
                    )
                  }
                />
              </div>

              {!passwordsMatch && (
                <span className="auth-inline-error">
                  Passwords do not
                  match.
                </span>
              )}
            </div>

            <label className="auth-checkbox-row auth-terms-row">
              <input
                type="checkbox"
                checked={
                  acceptedTerms
                }
                onChange={(
                  event,
                ) =>
                  setAcceptedTerms(
                    event.target
                      .checked,
                  )
                }
              />

              <span>
                I understand that
                CricIntel analytics
                and AI features
                support cricket
                decision-making and
                do not guarantee
                match outcomes.
              </span>
            </label>

            <button
              className="auth-primary-button"
              type="submit"
              disabled={
                submitting ||
                !passwordsMatch
              }
            >
              <span>
                {submitting
                  ? 'Creating account...'
                  : 'Create CricIntel account'}
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
              Already registered?
            </span>
          </div>

          <Link
            to="/login"
            className="auth-secondary-button"
          >
            Sign in instead
          </Link>

          <div className="auth-security-note">
            <span className="auth-security-icon">
              ✓
            </span>

            <span>
              Your account uses
              CricIntel's secure
              authenticated
              application
              workflow.
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