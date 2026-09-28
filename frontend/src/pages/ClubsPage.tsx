import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { createClub, deleteClub, listClubs } from '../api/organizations';
import { AppLayout } from '../components/AppLayout';
import { DataTable } from '../components/DataTable';
import { ModalDialog } from '../components/ModalDialog';
import { ClubForm } from '../components/organization/ClubForm';
import type { Club } from '../types/organization';

export function ClubsPage() {
  const organizationId = Number(useParams().organizationId);
  const [rows, setRows] = useState<Club[]>([]);
  const [open, setOpen] = useState(false);

  const load = async () => {
    const result = await listClubs(organizationId, { per_page: 100, sort: 'name' });
    setRows(result.data);
  };

  useEffect(() => {
    void load();
  }, [organizationId]);

  return (
    <AppLayout>
      <div className="page-heading">
        <h1>Clubs</h1>
        <button onClick={() => setOpen(true)}>New club</button>
      </div>

      <DataTable
        rows={rows}
        columns={[
          { key: 'name', header: 'Club', render: row => row.name },
          { key: 'code', header: 'Code', render: row => row.code ?? '—' },
          { key: 'location', header: 'Location', render: row => row.location ?? '—' },
          { key: 'teams', header: 'Teams', render: row => row.teams_count ?? 0 },
          {
            key: 'actions',
            header: 'Actions',
            render: row => (
              <button onClick={async () => {
                await deleteClub(organizationId, row.id);
                await load();
              }}>Delete</button>
            ),
          },
        ]}
      />

      <ModalDialog open={open} title="Create club" onClose={() => setOpen(false)}>
        <ClubForm
          onCancel={() => setOpen(false)}
          onSubmit={async form => {
            await createClub(organizationId, form);
            setOpen(false);
            await load();
          }}
        />
      </ModalDialog>
    </AppLayout>
  );
}
