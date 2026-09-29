import {
  useEffect,
  useState,
} from 'react';

import {
  Link,
  useParams,
  useSearchParams,
} from 'react-router-dom';

import {
  compareScoutingProfiles,
} from '../api/scouting';

import {
  AppLayout,
} from '../components/AppLayout';

import type {
  ScoutingComparisonRow,
} from '../types/scouting';

export function ScoutingComparePage() {
  const organizationId = Number(useParams().organizationId);
  const [searchParams] = useSearchParams();

  const ids = String(searchParams.get('ids') ?? '')
    .split(',')
    .map(Number)
    .filter((value) => Number.isFinite(value) && value > 0)
    .slice(0, 4);

  const [rows, setRows] = useState<ScoutingComparisonRow[]>([]);
  const [error, setError] = useState('');

  useEffect(() => {
    if (ids.length < 2) {
      setError('Select at least two prospects to compare.');
      return;
    }

    void compareScoutingProfiles(organizationId, ids)
      .then(setRows)
      .catch((caught) => {
        console.error(caught);
        setError('Unable to compare scouting profiles.');
      });
  }, [organizationId, searchParams.toString()]);

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <p className="eyebrow">P11 Comparison</p>
          <h1>Scouted Player Comparison</h1>
          <p>
            Side-by-side comparison using each prospect's latest
            scouting report.
          </p>
        </div>

        <Link
          className="button secondary"
          to={`/organizations/${organizationId}/scouting`}
        >
          Back to scouting
        </Link>
      </div>

      {error && <div className="form-error-message">{error}</div>}

      {rows.length > 0 && (
        <div className="table-wrap">
          <table className="data-table scouting-compare-table">
            <thead>
              <tr>
                <th>Measure</th>
                {rows.map((row) => (
                  <th key={row.profile.id}>
                    {row.profile.display_name}
                  </th>
                ))}
              </tr>
            </thead>

            <tbody>
              {[
                ['Role', (row: ScoutingComparisonRow) => row.profile.role ?? '—'],
                ['Team', (row: ScoutingComparisonRow) => row.profile.current_team ?? '—'],
                ['Competition', (row: ScoutingComparisonRow) => row.latest_report?.competition ?? row.profile.current_competition ?? '—'],
                ['Technical', (row: ScoutingComparisonRow) => row.latest_report?.rating?.technical_rating ?? '—'],
                ['Tactical', (row: ScoutingComparisonRow) => row.latest_report?.rating?.tactical_rating ?? '—'],
                ['Physical', (row: ScoutingComparisonRow) => row.latest_report?.rating?.physical_rating ?? '—'],
                ['Fielding', (row: ScoutingComparisonRow) => row.latest_report?.rating?.fielding_rating ?? '—'],
                ['Mental / decision', (row: ScoutingComparisonRow) => row.latest_report?.rating?.mental_decision_rating ?? '—'],
                ['Overall', (row: ScoutingComparisonRow) => row.latest_report?.rating?.overall_rating ?? '—'],
                ['Potential', (row: ScoutingComparisonRow) => row.latest_report?.potential ?? '—'],
                ['Recommendation', (row: ScoutingComparisonRow) => row.latest_report?.overall_recommendation ?? '—'],
              ].map(([label, resolver]) => (
                <tr key={String(label)}>
                  <td><strong>{String(label)}</strong></td>
                  {rows.map((row) => (
                    <td key={row.profile.id}>
                      {(resolver as (row: ScoutingComparisonRow) => string | number)(row)}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </AppLayout>
  );
}
