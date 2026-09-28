import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { getScorecard } from '../api/matches';
import { AppLayout } from '../components/AppLayout';
import type { MatchScorecard } from '../types/match';

export function MatchScorecardPage() {
  const organizationId = Number(useParams().organizationId);
  const matchId = Number(useParams().matchId);
  const navigate = useNavigate();
  const [match, setMatch] = useState<MatchScorecard | null>(null);

  useEffect(() => {
    void getScorecard(organizationId, matchId).then(setMatch);
  }, [organizationId, matchId]);

  if (!match) return <AppLayout><p>Loading scorecard...</p></AppLayout>;

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <h1>Match Scorecard</h1>
          <p>
            {match.fixture?.home_team?.name ?? 'Home'} vs {match.fixture?.away_team?.name ?? 'Away'} · {match.status}
          </p>
        </div>
        <button
          type="button"
          onClick={() =>
            navigate(`/organizations/${organizationId}/matches/${matchId}/operator`)
          }
        >
          Scoring operator
        </button>
      </div>

      {match.result_type && (
        <div className="match-result-banner">
          <strong>{match.result_type}</strong>
        </div>
      )}

      <div className="scorecard-innings-grid">
        {match.innings.map((innings) => (
          <section className="scorecard-innings" key={innings.id}>
            <div className="scorecard-innings-heading">
              <div>
                <span>Innings {innings.innings_number}</span>
                <strong>{innings.score.display}</strong>
                <small>{innings.score.overs} overs</small>
              </div>
              {innings.score.target && (
                <div>
                  <span>Target</span>
                  <strong>{innings.score.target}</strong>
                </div>
              )}
            </div>

            <div className="table-wrap">
              <table className="data-table">
                <thead>
                  <tr>
                    <th>Ball</th>
                    <th>Runs</th>
                    <th>Extra</th>
                    <th>Free hit</th>
                    <th>Wicket</th>
                  </tr>
                </thead>
                <tbody>
                  {innings.deliveries.map((delivery) => (
                    <tr key={delivery.id}>
                      <td>{delivery.ball_number}</td>
                      <td>{delivery.runs_off_bat} + {delivery.extra_runs} = {delivery.total_runs}</td>
                      <td>{delivery.extra_type}</td>
                      <td>{delivery.is_free_hit ? 'Yes' : 'No'}</td>
                      <td>{delivery.wicket ? delivery.wicket_type ?? 'Wicket' : '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
        ))}
      </div>
    </AppLayout>
  );
}
