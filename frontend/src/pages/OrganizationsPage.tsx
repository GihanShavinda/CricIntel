import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  createOrganization,
  deleteOrganization,
  listOrganizations,
} from '../api/organizations';
import { AppLayout } from '../components/AppLayout';
import { DataTable } from '../components/DataTable';
import { ModalDialog } from '../components/ModalDialog';
import { Pagination } from '../components/Pagination';
import { OrganizationForm } from '../components/organization/OrganizationForm';
import type { Organization } from '../types/organization';

export function OrganizationsPage() {
  const [rows, setRows] = useState<Organization[]>([]);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [open, setOpen] = useState(false);

  const load = async () => {
    const result = await listOrganizations({
      search,
      page,
      per_page: 10,
      sort: 'name',
      direction: 'asc',
    });

    setRows(result.data);
    setLastPage(result.meta.last_page);
  };

  useEffect(() => {
    void load();
  }, [page]);

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <h1>Organizations</h1>
          <p>Manage cricket organizations and their structures.</p>
        </div>
        <button onClick={() => setOpen(true)}>New organization</button>
      </div>

      <div className="toolbar">
        <input
          placeholder="Search organizations..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
        <button onClick={() => {
          setPage(1);
          void load();
        }}>Search</button>
      </div>

      <DataTable
        rows={rows}
        columns={[
          {
            key: 'name',
            header: 'Organization',
            render: row => <Link to={`/organizations/${row.id}`}>{row.name}</Link>,
          },
          {
            key: 'country',
            header: 'Country',
            render: row => row.country ?? '—',
          },
          {
            key: 'status',
            header: 'Status',
            render: row => row.status,
          },
          {
            key: 'clubs',
            header: 'Clubs',
            render: row => row.clubs_count ?? 0,
          },
          {
            key: 'actions',
            header: 'Actions',
            render: row => (
              <button onClick={async () => {
                if (window.confirm(`Delete ${row.name}?`)) {
                  await deleteOrganization(row.id);
                  await load();
                }
              }}>
                Delete
              </button>
            ),
          },
        ]}
      />

      <Pagination page={page} lastPage={lastPage} onPageChange={setPage} />

      <ModalDialog
        open={open}
        title="Create organization"
        onClose={() => setOpen(false)}
      >
        <OrganizationForm
          onCancel={() => setOpen(false)}
          onSubmit={async form => {
            await createOrganization(form);
            setOpen(false);
            await load();
          }}
        />
      </ModalDialog>
    </AppLayout>
  );
}
