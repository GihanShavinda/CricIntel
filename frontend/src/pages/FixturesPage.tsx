import { useEffect, useMemo, useState } from 'react';
import { useParams } from 'react-router-dom';
import {
  createFixture,
  deleteFixture,
  listFixtures,
  listTournaments,
  listVenues,
} from '../api/competitions';
import { listTeams } from '../api/organizations';
import { AppLayout } from '../components/AppLayout';
import { ModalDialog } from '../components/ModalDialog';
import { FixtureForm } from '../components/competition/FixtureForm';
import type { Fixture, Tournament, Venue } from '../types/competition';
import type { Team } from '../types/organization';

export function FixturesPage() {
  const organizationId = Number(useParams().organizationId);
  const [fixtures, setFixtures] = useState<Fixture[]>([]);
  const [tournaments, setTournaments] = useState<Tournament[]>([]);
  const [venues, setVenues] = useState<Venue[]>([]);
  const [teams, setTeams] = useState<Team[]>([]);
  const [open, setOpen] = useState(false);

  const load = async () => {
    const [fixtureResult, tournamentResult, venueResult, teamResult] = await Promise.all([
      listFixtures(organizationId, { per_page: 100, sort: 'scheduled_at' }),
      listTournaments(organizationId, { per_page: 100 }),
      listVenues(organizationId, { per_page: 100 }),
      listTeams(organizationId, { per_page: 100 }),
    ]);

    setFixtures(fixtureResult.data);
    setTournaments(tournamentResult.data);
    setVenues(venueResult.data);
    setTeams(teamResult.data);
  };

  useEffect(() => { void load(); }, [organizationId]);

  const grouped = useMemo(() => {
    return fixtures.reduce<Record<string, Fixture[]>>((acc, fixture) => {
      const date = fixture.scheduled_at.slice(0, 10);
      acc[date] ??= [];
      acc[date].push(fixture);
      return acc;
    }, {});
  }, [fixtures]);

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <h1>Fixture Calendar</h1>
          <p>Schedule and review upcoming competition matches.</p>
        </div>
        <button onClick={() => setOpen(true)}>Schedule fixture</button>
      </div>

      <div className="fixture-calendar">
        {Object.keys(grouped).length ? (
          Object.entries(grouped).map(([date, items]) => (
            <section className="calendar-day" key={date}>
              <h2>{new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
              })}</h2>

              {items.map(fixture => (
                <article className="fixture-card" key={fixture.id}>
                  <div>
                    <strong>
                      {fixture.home_team?.name ?? 'TBD'} vs {fixture.away_team?.name ?? 'TBD'}
                    </strong>
                    <div>
                      {new Date(fixture.scheduled_at).toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit',
                      })}
                      {' · '}
                      {fixture.venue?.name ?? 'Venue TBD'}
                    </div>
                  </div>

                  <div className="fixture-actions">
                    <span className="status-badge">{fixture.status}</span>
                    <button onClick={async () => {
                      if (window.confirm('Delete this fixture?')) {
                        await deleteFixture(organizationId, fixture.id);
                        await load();
                      }
                    }}>Delete</button>
                  </div>
                </article>
              ))}
            </section>
          ))
        ) : (
          <p>No fixtures scheduled.</p>
        )}
      </div>

      <ModalDialog open={open} title="Schedule fixture" onClose={() => setOpen(false)}>
        <FixtureForm
          tournaments={tournaments}
          teams={teams}
          venues={venues}
          onCancel={() => setOpen(false)}
          onSubmit={async payload => {
            await createFixture(organizationId, payload);
            setOpen(false);
            await load();
          }}
        />
      </ModalDialog>
    </AppLayout>
  );
}
