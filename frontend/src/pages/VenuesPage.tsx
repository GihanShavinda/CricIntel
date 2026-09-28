import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import {
  createVenue,
  deleteVenue,
  listVenues,
  updateVenue,
} from '../api/competitions';
import { AppLayout } from '../components/AppLayout';
import { DataTable } from '../components/DataTable';
import { ModalDialog } from '../components/ModalDialog';
import { VenueForm } from '../components/competition/VenueForm';
import type { Venue } from '../types/competition';

export function VenuesPage() {
  const organizationId = Number(useParams().organizationId);
  const [rows, setRows] = useState<Venue[]>([]);
  const [open, setOpen] = useState(false);
  const [editing, setEditing] = useState<Venue | null>(null);

  const load = async () => {
    const result = await listVenues(organizationId, { per_page: 100, sort: 'name' });
    setRows(result.data);
  };

  useEffect(() => { void load(); }, [organizationId]);

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <h1>Venues</h1>
          <p>Manage grounds used for competitions and fixtures.</p>
        </div>
        <button onClick={() => { setEditing(null); setOpen(true); }}>New venue</button>
      </div>

      <DataTable
        rows={rows}
        columns={[
          { key: 'name', header: 'Venue', render: row => row.name },
          { key: 'city', header: 'City', render: row => row.city ?? '—' },
          { key: 'capacity', header: 'Capacity', render: row => row.capacity?.toLocaleString() ?? '—' },
          { key: 'pitch', header: 'Pitch', render: row => row.pitch_type ?? '—' },
          { key: 'status', header: 'Status', render: row => row.status },
          {
            key: 'actions',
            header: 'Actions',
            render: row => (
              <div className="table-actions">
                <button onClick={() => { setEditing(row); setOpen(true); }}>Edit</button>
                <button onClick={async () => {
                  if (window.confirm(`Delete ${row.name}?`)) {
                    await deleteVenue(organizationId, row.id);
                    await load();
                  }
                }}>Delete</button>
              </div>
            ),
          },
        ]}
      />

      <ModalDialog
        open={open}
        title={editing ? 'Edit venue' : 'Create venue'}
        onClose={() => setOpen(false)}
      >
        <VenueForm
          venue={editing}
          onCancel={() => setOpen(false)}
          onSubmit={async payload => {
            if (editing) {
              await updateVenue(organizationId, editing.id, payload);
            } else {
              await createVenue(organizationId, payload);
            }
            setOpen(false);
            await load();
          }}
        />
      </ModalDialog>
    </AppLayout>
  );
}
