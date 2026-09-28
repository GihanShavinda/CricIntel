import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

import {
  addPlayerAvailability,
  getPlayer,
} from '../api/players';

import { AppLayout } from '../components/AppLayout';

import type { Player } from '../types/player';

export function PlayerProfilePage() {
  const organizationId = Number(useParams().organizationId);
  const playerId = Number(useParams().playerId);
  const navigate = useNavigate();

  const [player, setPlayer] = useState<Player | null>(null);

  const load = async () => {
    setPlayer(
      await getPlayer(
        organizationId,
        playerId
      )
    );
  };

  useEffect(() => {
    void load();
  }, [organizationId, playerId]);

  if (!player) {
    return (
      <AppLayout>
        <p>Loading player profile...</p>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <div className="page-heading">
        <div className="player-profile-heading">
          {player.photo_url ? (
            <img
              className="player-profile-photo"
              src={player.photo_url}
              alt={player.display_name}
            />
          ) : (
            <div className="player-profile-photo player-avatar-placeholder">
              {player.display_name.charAt(0)}
            </div>
          )}

          <div>
            <h1>{player.display_name}</h1>
            <p>
              {player.primary_role}
              {' · '}
              {player.status}
            </p>
          </div>
        </div>

        <button
          onClick={() =>
            navigate(
              `/organizations/${organizationId}/players/${player.id}/edit`
            )
          }
        >
          Edit profile
        </button>
      </div>

      <div className="summary-grid">
        <article>
          <strong>{player.primary_role}</strong>
          <span>Primary role</span>
        </article>

        <article>
          <strong>{player.batting_style ?? '—'}</strong>
          <span>Batting style</span>
        </article>

        <article>
          <strong>{player.bowling_style ?? '—'}</strong>
          <span>Bowling style</span>
        </article>

        <article>
          <strong>{player.fitness_status}</strong>
          <span>Fitness status</span>
        </article>

        <article>
          <strong>{player.nationality ?? '—'}</strong>
          <span>Nationality</span>
        </article>

        <article>
          <strong>{player.date_of_birth ?? '—'}</strong>
          <span>Date of birth</span>
        </article>
      </div>

      <section className="profile-section">
        <h2>Preferred positions</h2>

        <div className="tag-list">
          {player.positions?.length ? (
            player.positions.map((position) => (
              <span className="tag" key={position.id}>
                {position.position}
              </span>
            ))
          ) : (
            <p>No preferred positions recorded.</p>
          )}
        </div>
      </section>

      <section className="profile-section">
        <h2>Team history</h2>

        {player.teams?.length ? (
          <div className="table-wrap">
            <table className="data-table">
              <thead>
                <tr>
                  <th>Team</th>
                  <th>Jersey</th>
                  <th>Joined</th>
                  <th>Left</th>
                  <th>Current</th>
                </tr>
              </thead>
              <tbody>
                {player.teams.map((team) => (
                  <tr key={`${team.id}-${team.joined_at}`}>
                    <td>{team.name}</td>
                    <td>{team.jersey_number ?? '—'}</td>
                    <td>{team.joined_at ?? '—'}</td>
                    <td>{team.left_at ?? '—'}</td>
                    <td>{team.is_current ? 'Yes' : 'No'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <p>No team history recorded.</p>
        )}
      </section>

      <section className="profile-section">
        <div className="section-heading">
          <h2>Availability</h2>

          <button
            onClick={async () => {
              const from = window.prompt(
                'Available from (YYYY-MM-DD):'
              );

              if (!from) return;

              const to = window.prompt(
                'Available to (YYYY-MM-DD), or leave blank:'
              );

              const reason = window.prompt(
                'Reason / note:'
              );

              await addPlayerAvailability(
                organizationId,
                playerId,
                {
                  available_from: from,
                  available_to: to || null,
                  reason: reason || null,
                  status: 'Available',
                }
              );

              await load();
            }}
          >
            Add availability
          </button>
        </div>

        {player.availability?.length ? (
          <div className="table-wrap">
            <table className="data-table">
              <thead>
                <tr>
                  <th>From</th>
                  <th>To</th>
                  <th>Status</th>
                  <th>Reason</th>
                </tr>
              </thead>
              <tbody>
                {player.availability.map((item) => (
                  <tr key={item.id}>
                    <td>{item.available_from}</td>
                    <td>{item.available_to ?? 'Open-ended'}</td>
                    <td>{item.status}</td>
                    <td>{item.reason ?? '—'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <p>No availability records.</p>
        )}
      </section>
    </AppLayout>
  );
}
