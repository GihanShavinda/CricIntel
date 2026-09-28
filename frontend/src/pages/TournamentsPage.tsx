import { useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import {
  createTournament,
  deleteTournament,
  listTournaments,
} from '../api/competitions';
import { listSeasons } from '../api/organizations';
import { AppLayout } from '../components/AppLayout';
import { DataTable } from '../components/DataTable';
import { ModalDialog } from '../components/ModalDialog';
import { TournamentForm } from '../components/competition/TournamentForm';
import type { Season } from '../types/organization';
import type { Tournament } from '../types/competition';

export function TournamentsPage() {
  const organizationId = Number(useParams().organizationId);
  const [rows, setRows] = useState<Tournament[]>([]);
  const [seasons, setSeasons] = useState<Season[]>([]);
  const [open, setOpen] = useState(false);

  const load = async () => {
    const [tournaments, seasonResult] = await Promise.all([
      listTournaments(organizationId, { per_page: 100, sort: 'start_date', direction: 'desc' }),
      listSeasons(organizationId, { per_page: 100 }),
    ]);
    setRows(tournaments.data);
    setSeasons(seasonResult.data);
  };

  useEffect(() => { void load(); }, [organizationId]);

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <h1>Tournaments</h1>
          <p>Create and manage competitions for each season.</p>
        </div>
        <button onClick={() => setOpen(true)}>New tournament</button>
      </div>

      <DataTable
        rows={rows}
        columns={[
          {
            key: 'name',
            header: 'Tournament',
            render: row => (
              <Link to={`/organizations/${organizationId}/tournaments/${row.id}`}>
                {row.name}
              </Link>
            ),
          },
          { key: 'format', header: 'Format', render: row => row.format },
          { key: 'dates', header: 'Dates', render: row => `${row.start_date} → ${row.end_date}` },
          { key: 'status', header: 'Status', render: row => row.status },
          { key: 'organizer', header: 'Organizer', render: row => row.organizer ?? '—' },
          {
            key: 'actions',
            header: 'Actions',
            render: row => (
              <button onClick={async () => {
                if (window.confirm(`Delete ${row.name}?`)) {
                  await deleteTournament(organizationId, row.id);
                  await load();
                }
              }}>Delete</button>
            ),
          },
        ]}
      />

      <ModalDialog open={open} title="Create tournament" onClose={() => setOpen(false)}>
        <TournamentForm
          seasons={seasons}
          onCancel={() => setOpen(false)}
          onSubmit={async payload => {
            await createTournament(organizationId, payload);
            setOpen(false);
            await load();
          }}
        />
      </ModalDialog>
    </AppLayout>
  );
}
