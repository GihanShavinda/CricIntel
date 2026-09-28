import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';

import {
  deletePlayer,
  listPlayers,
} from '../api/players';

import { AppLayout } from '../components/AppLayout';
import { DataTable } from '../components/DataTable';
import { Pagination } from '../components/Pagination';

import type { Player } from '../types/player';

export function PlayersPage() {
  const organizationId = Number(useParams().organizationId);
  const navigate = useNavigate();

  const [rows, setRows] = useState<Player[]>([]);
  const [search, setSearch] = useState('');
  const [role, setRole] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);

  const load = async () => {
    const result = await listPlayers(organizationId, {
      search,
      primary_role: role || undefined,
      status: status || undefined,
      page,
      per_page: 10,
      sort: 'display_name',
    });

    setRows(result.data);
    setLastPage(result.meta.last_page);
  };

  useEffect(() => {
    void load();
  }, [organizationId, page]);

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <h1>Players</h1>
          <p>Manage player profiles, roles, availability and team history.</p>
        </div>

        <button
          onClick={() =>
            navigate(`/organizations/${organizationId}/players/new`)
          }
        >
          New player
        </button>
      </div>

      <div className="toolbar player-toolbar">
        <input
          value={search}
          placeholder="Search players..."
          onChange={(event) => setSearch(event.target.value)}
        />

        <select
          value={role}
          onChange={(event) => setRole(event.target.value)}
        >
          <option value="">All roles</option>
          <option value="Batter">Batter</option>
          <option value="Bowler">Bowler</option>
          <option value="All-rounder">All-rounder</option>
          <option value="Wicketkeeper">Wicketkeeper</option>
          <option value="Wicketkeeper-Batter">Wicketkeeper-Batter</option>
        </select>

        <select
          value={status}
          onChange={(event) => setStatus(event.target.value)}
        >
          <option value="">All statuses</option>
          <option value="Active">Active</option>
          <option value="Unavailable">Unavailable</option>
          <option value="Injured">Injured</option>
          <option value="Suspended">Suspended</option>
          <option value="Retired">Retired</option>
        </select>

        <button
          onClick={() => {
            setPage(1);
            void load();
          }}
        >
          Apply filters
        </button>
      </div>

      <DataTable
        rows={rows}
        columns={[
          {
            key: 'player',
            header: 'Player',
            render: (player) => (
              <div className="player-cell">
                {player.photo_url ? (
                  <img
                    className="player-avatar"
                    src={player.photo_url}
                    alt={player.display_name}
                  />
                ) : (
                  <div className="player-avatar player-avatar-placeholder">
                    {player.display_name.charAt(0)}
                  </div>
                )}

                <Link
                  to={`/organizations/${organizationId}/players/${player.id}`}
                >
                  {player.display_name}
                </Link>
              </div>
            ),
          },
          {
            key: 'role',
            header: 'Role',
            render: (player) => player.primary_role,
          },
          {
            key: 'batting',
            header: 'Batting',
            render: (player) => player.batting_style ?? '—',
          },
          {
            key: 'bowling',
            header: 'Bowling',
            render: (player) => player.bowling_style ?? '—',
          },
          {
            key: 'fitness',
            header: 'Fitness',
            render: (player) => player.fitness_status,
          },
          {
            key: 'status',
            header: 'Status',
            render: (player) => player.status,
          },
          {
            key: 'actions',
            header: 'Actions',
            render: (player) => (
              <div className="table-actions">
                <button
                  onClick={() =>
                    navigate(
                      `/organizations/${organizationId}/players/${player.id}/edit`
                    )
                  }
                >
                  Edit
                </button>

                <button
                  onClick={async () => {
                    if (
                      window.confirm(
                        `Delete ${player.display_name}?`
                      )
                    ) {
                      await deletePlayer(
                        organizationId,
                        player.id
                      );
                      await load();
                    }
                  }}
                >
                  Delete
                </button>
              </div>
            ),
          },
        ]}
      />

      <Pagination
        page={page}
        lastPage={lastPage}
        onPageChange={setPage}
      />
    </AppLayout>
  );
}
