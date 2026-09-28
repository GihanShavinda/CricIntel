import { useNavigate, useParams } from 'react-router-dom';

import { createPlayer } from '../api/players';
import { AppLayout } from '../components/AppLayout';
import { PlayerForm } from '../components/player/PlayerForm';

export function PlayerCreatePage() {
  const organizationId = Number(useParams().organizationId);
  const navigate = useNavigate();

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <h1>Create player</h1>
          <p>Add a new cricket player profile.</p>
        </div>
      </div>

      <div className="form-card">
        <PlayerForm
          onCancel={() =>
            navigate(`/organizations/${organizationId}/players`)
          }
          onSubmit={async (form) => {
            const player = await createPlayer(
              organizationId,
              form
            );

            navigate(
              `/organizations/${organizationId}/players/${player.id}`
            );
          }}
        />
      </div>
    </AppLayout>
  );
}
