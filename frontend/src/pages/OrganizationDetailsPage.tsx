import {
  useEffect,
  useState,
} from 'react';

import {
  Link,
  useParams,
} from 'react-router-dom';

import {
  getOrganization,
} from '../api/organizations';

import {
  AppLayout,
} from '../components/AppLayout';

import type {
  Organization,
} from '../types/organization';

export function OrganizationDetailsPage() {
  const id = Number(
    useParams().organizationId
  );

  const [
    organization,
    setOrganization,
  ] = useState<
    Organization | null
  >(null);

  const [
    loading,
    setLoading,
  ] = useState(true);

  const [
    error,
    setError,
  ] = useState('');

  useEffect(() => {
    let active = true;

    const load =
      async () => {
        setLoading(true);
        setError('');

        try {
          const result =
            await getOrganization(
              id
            );

          if (active) {
            setOrganization(
              result
            );
          }
        } catch {
          if (active) {
            setError(
              'Unable to load organization.'
            );
          }
        } finally {
          if (active) {
            setLoading(
              false
            );
          }
        }
      };

    void load();

    return () => {
      active = false;
    };
  }, [id]);

  if (loading) {
    return (
      <AppLayout>
        <div className="page-state">
          Loading organization...
        </div>
      </AppLayout>
    );
  }

  if (
    error ||
    !organization
  ) {
    return (
      <AppLayout>
        <div className="page-state error-state">
          {error ||
            'Organization not found.'}
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <p className="eyebrow">
            Organization
          </p>

          <h1>
            {
              organization.name
            }
          </h1>

          <p>
            {
              organization.description ??
              'No description.'
            }
          </p>
        </div>

        {organization.logo_url && (
          <img
            className="entity-logo"
            src={
              organization.logo_url
            }
            alt={
              `${organization.name} logo`
            }
          />
        )}
      </div>

      <div className="summary-grid">
        <article>
          <strong>
            {
              organization.short_name ??
              '—'
            }
          </strong>

          <span>
            Short name
          </span>
        </article>

        <article>
          <strong>
            {
              organization.country ??
              '—'
            }
          </strong>

          <span>
            Country
          </span>
        </article>

        <article>
          <strong>
            {
              organization.timezone
            }
          </strong>

          <span>
            Timezone
          </span>
        </article>

        <article>
          <strong>
            {
              organization.status
            }
          </strong>

          <span>
            Status
          </span>
        </article>
      </div>

      <section className="organization-modules">
        <div className="section-heading">
          <div>
            <h2>
              CricIntel Modules
            </h2>

            <p>
              Manage the organization,
              competition workflow,
              live scoring, and
              deterministic analytics.
            </p>
          </div>
        </div>

        <div className="module-links">
          <Link
            to={
              `/organizations/${id}/clubs`
            }
          >
            <strong>
              Clubs
            </strong>
            <span>
              Manage cricket clubs
            </span>
          </Link>

          <Link
            to={
              `/organizations/${id}/teams`
            }
          >
            <strong>
              Teams
            </strong>
            <span>
              Manage squads and teams
            </span>
          </Link>

          <Link
            to={
              `/organizations/${id}/seasons`
            }
          >
            <strong>
              Seasons
            </strong>
            <span>
              Competition periods
            </span>
          </Link>

          <Link
            to={
              `/organizations/${id}/players`
            }
          >
            <strong>
              Players
            </strong>
            <span>
              Profiles and membership
            </span>
          </Link>

          <Link
            to={
              `/organizations/${id}/venues`
            }
          >
            <strong>
              Venues
            </strong>
            <span>
              Grounds and locations
            </span>
          </Link>

          <Link
            to={
              `/organizations/${id}/tournaments`
            }
          >
            <strong>
              Tournaments
            </strong>
            <span>
              Formats and registration
            </span>
          </Link>

          <Link
            to={
              `/organizations/${id}/fixtures`
            }
          >
            <strong>
              Fixture Calendar
            </strong>
            <span>
              Match scheduling
            </span>
          </Link>

          <Link
            to={
              `/organizations/${id}/matches`
            }
          >
            <strong>
              Live Match Scoring
            </strong>
            <span>
              Ball-by-ball operator
            </span>
          </Link>

          <Link
            className="analytics-module-link"
            to={
              `/organizations/${id}/analytics`
            }
          >
            <strong>
              Analytics
            </strong>
            <span>
              P6 statistics,
              trends and comparisons
            </span>
          </Link>
        </div>
      </section>
    </AppLayout>
  );
}
