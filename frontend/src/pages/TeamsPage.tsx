import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import {
  createTeam,
  deleteTeam,
  listClubs,
  listTeams,
} from '../api/organizations';
import { AppLayout } from '../components/AppLayout';
import { DataTable } from '../components/DataTable';
import { ModalDialog } from '../components/ModalDialog';
import { TeamForm } from '../components/organization/TeamForm';
import type { Club, Team } from '../types/organization';

export function TeamsPage() {
  const organizationId = Number(useParams().organizationId);
  const [rows, setRows] = useState<Team[]>([]);
  const [clubs, setClubs] = useState<Club[]>([]);
  const [open, setOpen] = useState(false);

  const load = async () => {
    const [teams, clubsResult] = await Promise.all([
      listTeams(organizationId, { per_page: 100, sort: 'name' }),
      listClubs(organizationId, { per_page: 100, sort: 'name' }),
    ]);
    setRows(teams.data);
    setClubs(clubsResult.data);
  };

  useEffect(() => {
    void load();
  }, [organizationId]);

  return (
    <AppLayout>
      <div className="page-heading">
        <h1>Teams</h1>
        <button onClick={() => setOpen(true)}>New team</button>
      </div>

      <DataTable
        rows={rows}
        columns={[
          { key: 'name', header: 'Team', render: row => row.name },
          { key: 'club', header: 'Club', render: row => row.club?.name ?? '—' },
          { key: 'category', header: 'Category', render: row => row.category ?? '—' },
          { key: 'age', header: 'Age group', render: row => row.age_group ?? '—' },
          { key: 'status', header: 'Status', render: row => row.status },
          {
            key: 'actions',
            header: 'Actions',
            render: row => (
              <button onClick={async () => {
                await deleteTeam(organizationId, row.id);
                await load();
              }}>Delete</button>
            ),
          },
        ]}
      />

      <ModalDialog open={open} title="Create team" onClose={() => setOpen(false)}>
        <TeamForm
          clubs={clubs}
          onCancel={() => setOpen(false)}
          onSubmit={async payload => {
            await createTeam(organizationId, payload);
            setOpen(false);
            await load();
          }}
        />
      </ModalDialog>
    </AppLayout>
  );
}
