import {
  useEffect,
  useMemo,
  useState,
} from 'react';

import {
  useNavigate,
  useParams,
} from 'react-router-dom';

import axios from 'axios';

import {
  getPlayer,
  syncPlayerTeams,
  updatePlayer,
} from '../api/players';

import {
  listTeams,
} from '../api/organizations';

import {
  AppLayout,
} from '../components/AppLayout';

import {
  PlayerForm,
} from '../components/player/PlayerForm';

import type {
  Player,
} from '../types/player';

import type {
  Team,
} from '../types/organization';

export function PlayerEditPage() {
  const organizationId = Number(
    useParams().organizationId
  );

  const playerId = Number(
    useParams().playerId
  );

  const navigate = useNavigate();

  const [
    player,
    setPlayer,
  ] = useState<Player | null>(
    null
  );

  const [
    teams,
    setTeams,
  ] = useState<Team[]>([]);

  const [
    selectedTeamId,
    setSelectedTeamId,
  ] = useState<number | ''>('');

  const [
    jerseyNumber,
    setJerseyNumber,
  ] = useState('');

  const [
    joinedAt,
    setJoinedAt,
  ] = useState('');

  const [
    savingTeam,
    setSavingTeam,
  ] = useState(false);

  const [
    teamMessage,
    setTeamMessage,
  ] = useState('');

  const [
    teamError,
    setTeamError,
  ] = useState('');

  const load = async () => {
    try {
      const [
        playerResult,
        teamResult,
      ] = await Promise.all([
        getPlayer(
          organizationId,
          playerId
        ),

        listTeams(
          organizationId,
          {
            per_page: 100,
          }
        ),
      ]);

      setPlayer(
        playerResult
      );

      setTeams(
        teamResult.data
      );

      const currentTeam =
        playerResult.teams?.find(
          (team) =>
            team.is_current
        );

      if (currentTeam) {
        setSelectedTeamId(
          currentTeam.id
        );

        setJerseyNumber(
          currentTeam
            .jersey_number
            ?.toString() ??
            ''
        );

        setJoinedAt(
          currentTeam
            .joined_at ??
            ''
        );
      }
    } catch (error) {
      console.error(
        'Unable to load player edit page:',
        error
      );
    }
  };

  useEffect(() => {
    void load();
  }, [
    organizationId,
    playerId,
  ]);

  const currentMemberships =
    useMemo(
      () =>
        player?.teams ?? [],
      [player]
    );

  const saveTeamMembership =
    async () => {
      setTeamMessage('');
      setTeamError('');

      if (!selectedTeamId) {
        setTeamError(
          'Please select a team.'
        );

        return;
      }

      setSavingTeam(true);

      try {
        const memberships =
          currentMemberships.map(
            (team) => ({
              team_id:
                team.id,

              jersey_number:
                team.id ===
                selectedTeamId
                  ? jerseyNumber
                    ? Number(
                        jerseyNumber
                      )
                    : null
                  : team
                      .jersey_number,

              joined_at:
                team.id ===
                selectedTeamId
                  ? joinedAt ||
                    null
                  : team
                      .joined_at,

              left_at:
                team.id ===
                selectedTeamId
                  ? null
                  : team
                      .left_at,

              is_current:
                team.id ===
                selectedTeamId,
            })
          );

        const alreadyExists =
          memberships.some(
            (membership) =>
              membership.team_id ===
              selectedTeamId
          );

        if (!alreadyExists) {
          memberships.push({
            team_id:
              selectedTeamId,

            jersey_number:
              jerseyNumber
                ? Number(
                    jerseyNumber
                  )
                : null,

            joined_at:
              joinedAt ||
              null,

            left_at:
              null,

            is_current:
              true,
          });
        }

        const updated =
          await syncPlayerTeams(
            organizationId,
            playerId,
            memberships
          );

        setPlayer(
          updated
        );

        setTeamMessage(
          'Team membership updated successfully.'
        );
      } catch (error) {
        console.error(
          'Unable to save team membership:',
          error
        );

        if (
          axios.isAxiosError(
            error
          )
        ) {
          const response =
            error.response
              ?.data;

          const validationErrors =
            response?.errors;

          const firstError =
            validationErrors
              ? Object
                  .values(
                    validationErrors
                  )
                  .flat()
                  .at(0)
              : null;

          setTeamError(
            String(
              firstError ??
                response
                  ?.message ??
                'Unable to update team membership.'
            )
          );
        } else {
          setTeamError(
            'Unable to update team membership.'
          );
        }
      } finally {
        setSavingTeam(false);
      }
    };

  if (!player) {
    return (
      <AppLayout>
        <p>
          Loading player...
        </p>
      </AppLayout>
    );
  }

  return (
    <AppLayout>

      <div className="page-heading">
        <div>
          <h1>
            Edit {
              player.display_name
            }
          </h1>

          <p>
            Update player profile
            information and team
            membership.
          </p>
        </div>
      </div>

      <div className="form-card">

        <PlayerForm
          player={player}
          onCancel={() =>
            navigate(
              `/organizations/${organizationId}/players/${playerId}`
            )
          }
          onSubmit={async (
            form
          ) => {
            await updatePlayer(
              organizationId,
              playerId,
              form
            );

            await load();

            navigate(
              `/organizations/${organizationId}/players/${playerId}`
            );
          }}
        />

      </div>

      <section className="profile-section">

        <div className="section-heading">

          <div>
            <h2>
              Team Membership
            </h2>

            <p>
              Assign this player
              to a current team
              and maintain
              historical team
              membership.
            </p>
          </div>

        </div>

        {teamMessage && (
          <div className="form-success-message">
            {teamMessage}
          </div>
        )}

        {teamError && (
          <div className="form-error-message">
            {teamError}
          </div>
        )}

        <div className="form-grid">

          <label>
            Current team

            <select
              value={
                selectedTeamId
              }
              onChange={(
                event
              ) =>
                setSelectedTeamId(
                  event
                    .target
                    .value
                    ? Number(
                        event
                          .target
                          .value
                      )
                    : ''
                )
              }
            >
              <option value="">
                Select team
              </option>

              {teams.map(
                (team) => (
                  <option
                    key={
                      team.id
                    }
                    value={
                      team.id
                    }
                  >
                    {
                      team.name
                    }
                  </option>
                )
              )}
            </select>
          </label>

          <label>
            Jersey number

            <input
              type="number"
              min="0"
              max="999"
              value={
                jerseyNumber
              }
              onChange={(
                event
              ) =>
                setJerseyNumber(
                  event
                    .target
                    .value
                )
              }
              placeholder="18"
            />
          </label>

          <label>
            Joined date

            <input
              type="date"
              value={
                joinedAt
              }
              onChange={(
                event
              ) =>
                setJoinedAt(
                  event
                    .target
                    .value
                )
              }
            />
          </label>

        </div>

        <div className="form-actions">

          <button
            type="button"
            disabled={
              savingTeam ||
              !selectedTeamId
            }
            onClick={
              saveTeamMembership
            }
          >
            {savingTeam
              ? 'Saving membership...'
              : 'Save Team Membership'}
          </button>

        </div>

      </section>

      <section className="profile-section">

        <h2>
          Team History
        </h2>

        {player.teams?.length ? (
          <div className="table-wrap">

            <table className="data-table">

              <thead>
                <tr>
                  <th>
                    Team
                  </th>

                  <th>
                    Jersey
                  </th>

                  <th>
                    Joined
                  </th>

                  <th>
                    Left
                  </th>

                  <th>
                    Status
                  </th>
                </tr>
              </thead>

              <tbody>

                {player.teams.map(
                  (team) => (
                    <tr
                      key={
                        team.id
                      }
                    >
                      <td>
                        {
                          team.name
                        }
                      </td>

                      <td>
                        {
                          team
                            .jersey_number ??
                          '—'
                        }
                      </td>

                      <td>
                        {
                          team
                            .joined_at ??
                          '—'
                        }
                      </td>

                      <td>
                        {
                          team
                            .left_at ??
                          '—'
                        }
                      </td>

                      <td>
                        {team
                          .is_current
                          ? 'Current'
                          : 'Former'}
                      </td>
                    </tr>
                  )
                )}

              </tbody>

            </table>

          </div>
        ) : (
          <div className="empty-state">
            <p>
              This player has
              not been assigned
              to a team yet.
            </p>
          </div>
        )}

      </section>

    </AppLayout>
  );
}