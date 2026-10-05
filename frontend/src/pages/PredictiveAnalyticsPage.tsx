import {
  useEffect,
  useMemo,
  useState,
  type FormEvent,
} from 'react';

import {
  Bar,
  BarChart,
  CartesianGrid,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';

import {
  useParams,
} from 'react-router-dom';

import {
  getPlayerFormTrend,
  getPredictiveOptions,
  getPredictiveReadiness,
  predictBatterScore,
  predictBowlerEconomy,
  predictTeamTotal,
  trainPredictiveModels,
} from '../api/predictive';

import {
  useAuth,
} from '../auth/AuthContext';

import {
  AppIcon,
} from '../components/AppIcon';

import {
  AppLayout,
} from '../components/AppLayout';

import type {
  PlayerFormTrend,
  PredictionResult,
  PredictiveModelKind,
  PredictiveOptions,
  PredictiveReadiness,
} from '../types/predictive';

const modelLabels: Record<PredictiveModelKind, string> = {
  batter_score: 'Expected batter score range',
  bowler_economy: 'Bowler expected economy',
  team_total: 'Expected team total',
};

export function PredictiveAnalyticsPage() {
  const organizationId = Number(
    useParams().organizationId,
  );

  const {
    user,
  } = useAuth();

  const [
    options,
    setOptions,
  ] = useState<PredictiveOptions>({
    players: [],
    teams: [],
    venues: [],
  });

  const [
    readiness,
    setReadiness,
  ] = useState<PredictiveReadiness | null>(null);

  const [
    modelKind,
    setModelKind,
  ] = useState<PredictiveModelKind>('batter_score');

  const [
    subjectId,
    setSubjectId,
  ] = useState('');

  const [
    opponentTeamId,
    setOpponentTeamId,
  ] = useState('');

  const [
    venueId,
    setVenueId,
  ] = useState('');

  const [
    maxOvers,
    setMaxOvers,
  ] = useState('20');

  const [
    result,
    setResult,
  ] = useState<PredictionResult | null>(null);

  const [
    formTrend,
    setFormTrend,
  ] = useState<PlayerFormTrend | null>(null);

  const [
    training,
    setTraining,
  ] = useState(false);

  const [
    error,
    setError,
  ] = useState('');

  const [
    message,
    setMessage,
  ] = useState('');

  const roles = user?.roles ?? [];
  const canTrain =
    roles.includes('Administrator') ||
    roles.includes('Coach');

  const load = async () => {
    setError('');

    try {
      const [optionRows, readinessRow] = await Promise.all([
        getPredictiveOptions(organizationId),
        getPredictiveReadiness(organizationId),
      ]);

      setOptions(optionRows);
      setReadiness(readinessRow);
    } catch (caught: any) {
      setError(
        caught?.response?.data?.message ??
          'Unable to reach the local predictive analytics service.',
      );
    }
  };

  useEffect(() => {
    void load();
  }, [organizationId]);

  useEffect(() => {
    setSubjectId('');
    setResult(null);
    setFormTrend(null);
  }, [modelKind]);

  const subjectOptions = useMemo(() => {
    if (modelKind === 'team_total') {
      return options.teams.map((team) => ({
        id: team.id,
        label: team.name,
      }));
    }

    return options.players.map((player) => ({
      id: player.id,
      label: `${player.display_name} · ${player.primary_role ?? 'Player'}`,
    }));
  }, [modelKind, options]);

  const selectedProblem = readiness?.problems?.[modelKind];

  const submitPrediction = async (
    event: FormEvent<HTMLFormElement>,
  ) => {
    event.preventDefault();
    setError('');
    setMessage('');
    setResult(null);

    if (!subjectId) {
      setError('Select a player or team first.');
      return;
    }

    const context = {
      opponent_team_id: opponentTeamId
        ? Number(opponentTeamId)
        : null,
      venue_id: venueId
        ? Number(venueId)
        : null,
      max_overs: Number(maxOvers || 20),
    };

    try {
      let prediction: PredictionResult;

      if (modelKind === 'batter_score') {
        prediction = await predictBatterScore(
          organizationId,
          Number(subjectId),
          context,
        );

        setFormTrend(
          await getPlayerFormTrend(
            organizationId,
            Number(subjectId),
          ),
        );
      } else if (modelKind === 'bowler_economy') {
        prediction = await predictBowlerEconomy(
          organizationId,
          Number(subjectId),
          context,
        );
        setFormTrend(null);
      } else {
        prediction = await predictTeamTotal(
          organizationId,
          Number(subjectId),
          context,
        );
        setFormTrend(null);
      }

      setResult(prediction);
    } catch (caught: any) {
      setError(
        caught?.response?.data?.message ??
          'Prediction could not be generated.',
      );
    }
  };

  const trainReadyModels = async () => {
    setTraining(true);
    setError('');
    setMessage('');

    try {
      const readyKinds = (
        ['batter_score', 'bowler_economy', 'team_total'] as PredictiveModelKind[]
      ).filter(
        (kind) => readiness?.problems?.[kind]?.realistic,
      );

      if (!readyKinds.length) {
        setError(
          'No model currently meets the minimum leakage-safe data threshold.',
        );
        return;
      }

      const response = await trainPredictiveModels(
        organizationId,
        readyKinds,
      );

      const trained = response.results.filter(
        (row) => row.status === 'trained',
      ).length;

      setMessage(
        `${trained} predictive model${trained === 1 ? '' : 's'} trained and versioned successfully.`,
      );

      await load();
    } catch (caught: any) {
      setError(
        caught?.response?.data?.message ??
          'Model training failed.',
      );
    } finally {
      setTraining(false);
    }
  };

  const intervalChart = result
    ? [
        {label: 'Lower', value: result.interval.lower},
        {label: 'Prediction', value: result.interval.prediction},
        {label: 'Upper', value: result.interval.upper},
      ]
    : [];

  const formChart = formTrend?.recent_runs.map(
    (runs, index) => ({
      innings: index + 1,
      runs,
    }),
  ) ?? [];

  return (
    <AppLayout>
      <div className="page-heading predictive-heading">
        <div>
          <p className="eyebrow">P14 Predictive Analytics</p>
          <h1>Coaching Prediction Lab</h1>
          <p>
            Leakage-controlled local machine learning over CricIntel's own
            historical cricket data. No LLM is used.
          </p>
        </div>

        {canTrain && (
          <button
            type="button"
            disabled={training}
            onClick={() => void trainReadyModels()}
          >
            <AppIcon name="analytics" size={17} />
            {training ? 'Training models...' : 'Train ready models'}
          </button>
        )}
      </div>

      <div className="predictive-safety-banner">
        <AppIcon name="shield" size={20} />
        <div>
          <strong>Coaching support — not a guaranteed outcome</strong>
          <span>
            Predictions are uncertain historical estimates and are not designed
            for gambling, betting, or wagering decisions.
          </span>
        </div>
      </div>

      {error && <div className="form-error-message">{error}</div>}
      {message && <div className="form-success-message">{message}</div>}

      {readiness && (
        <section className="predictive-readiness-grid">
          <article>
            <strong>{readiness.available_data.matches}</strong>
            <span>Historical matches</span>
          </article>
          <article>
            <strong>{readiness.available_data.batter_innings_rows}</strong>
            <span>Leakage-safe batter rows</span>
          </article>
          <article>
            <strong>{readiness.available_data.bowler_innings_rows}</strong>
            <span>Leakage-safe bowler rows</span>
          </article>
          <article>
            <strong>{readiness.available_data.team_innings_rows}</strong>
            <span>First-innings team rows</span>
          </article>
        </section>
      )}

      {readiness && (
        <section className="profile-section">
          <div className="section-heading">
            <div>
              <h2>Statistical readiness review</h2>
              <p>
                Models are enabled only when CricIntel actually has enough
                leakage-safe historical rows.
              </p>
            </div>
          </div>

          <div className="predictive-problem-grid">
            {Object.entries(readiness.problems).map(([key, problem]) => (
              <article key={key} className={problem.realistic ? 'ready' : 'blocked'}>
                <div>
                  <span>{problem.realistic ? 'Ready / feasible' : 'Not enabled'}</span>
                  <strong>{problem.title ?? key.replaceAll('_', ' ')}</strong>
                </div>

                <p>{problem.reason}</p>

                {typeof problem.rows === 'number' && (
                  <small>
                    {problem.rows} rows / {problem.minimum_rows ?? '—'} minimum
                  </small>
                )}
              </article>
            ))}
          </div>
        </section>
      )}

      <section className="predictive-workbench">
        <div className="predictive-form-panel">
          <div className="section-heading">
            <div>
              <h2>Prediction context</h2>
              <p>
                Select only context CricIntel already stores. No hidden or invented inputs.
              </p>
            </div>
          </div>

          <form onSubmit={(event) => void submitPrediction(event)}>
            <label>
              Prediction
              <select
                value={modelKind}
                onChange={(event) =>
                  setModelKind(event.target.value as PredictiveModelKind)
                }
              >
                {Object.entries(modelLabels).map(([kind, label]) => (
                  <option key={kind} value={kind}>{label}</option>
                ))}
              </select>
            </label>

            <label>
              {modelKind === 'team_total' ? 'Team' : 'Player'}
              <select
                value={subjectId}
                onChange={(event) => setSubjectId(event.target.value)}
                required
              >
                <option value="">Select subject</option>
                {subjectOptions.map((item) => (
                  <option key={item.id} value={item.id}>{item.label}</option>
                ))}
              </select>
            </label>

            <label>
              Opponent team
              <select
                value={opponentTeamId}
                onChange={(event) => setOpponentTeamId(event.target.value)}
              >
                <option value="">Historical average context</option>
                {options.teams.map((team) => (
                  <option key={team.id} value={team.id}>{team.name}</option>
                ))}
              </select>
            </label>

            <label>
              Venue
              <select
                value={venueId}
                onChange={(event) => setVenueId(event.target.value)}
              >
                <option value="">Historical average context</option>
                {options.venues.map((venue) => (
                  <option key={venue.id} value={venue.id}>{venue.name}</option>
                ))}
              </select>
            </label>

            <label>
              Maximum overs
              <input
                type="number"
                min="1"
                max="450"
                value={maxOvers}
                onChange={(event) => setMaxOvers(event.target.value)}
              />
            </label>

            <button
              type="submit"
              disabled={!selectedProblem?.realistic}
            >
              Generate prediction
            </button>

            {!selectedProblem?.realistic && (
              <p className="predictive-form-warning">
                This model is not statistically ready for the current organization.
              </p>
            )}
          </form>
        </div>

        <div className="predictive-result-panel">
          {!result ? (
            <div className="predictive-empty-result">
              <AppIcon name="analytics" size={34} />
              <strong>No prediction generated</strong>
              <span>
                Train a statistically ready model, then choose a prediction context.
              </span>
            </div>
          ) : (
            <>
              <div className="predictive-result-head">
                <div>
                  <span>{modelLabels[result.model_kind]}</span>
                  <strong>{result.prediction}</strong>
                  <small>
                    80% empirical interval: {result.interval.lower} – {result.interval.upper}
                  </small>
                </div>

                <span className={`predictive-confidence ${result.confidence_label}`}>
                  {result.confidence_label} confidence
                </span>
              </div>

              <div className="predictive-chart-box">
                <ResponsiveContainer width="100%" height={240}>
                  <BarChart data={intervalChart}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="label" />
                    <YAxis />
                    <Tooltip />
                    <Bar dataKey="value" name="Estimate" />
                  </BarChart>
                </ResponsiveContainer>
              </div>

              <div className="predictive-result-meta">
                <span>Model {result.model_version}</span>
                <span>{result.sample_size} subject-history rows</span>
              </div>

              <div className="predictive-explainability">
                <h3>Model explainability</h3>
                <p>
                  Top global feature importances for this trained model. Importance
                  does not prove causation.
                </p>
                <div>
                  {result.explainability.slice(0, 8).map((item) => (
                    <article key={item.feature}>
                      <span>{item.feature.replaceAll('numeric__', '').replaceAll('categorical__', '')}</span>
                      <strong>{(item.importance * 100).toFixed(1)}%</strong>
                    </article>
                  ))}
                </div>
              </div>

              <div className="analytics-limitations">
                <strong>Uncertainty & limitations</strong>
                <ul>
                  {result.limitations.map((item) => (
                    <li key={item}>{item}</li>
                  ))}
                </ul>
              </div>
            </>
          )}
        </div>
      </section>

      {formTrend && (
        <section className="profile-section">
          <div className="section-heading">
            <div>
              <h2>Player form trend</h2>
              <p>
                Deterministic recent-innings trend; no supervised model is required.
              </p>
            </div>

            <span className={`predictive-trend ${formTrend.trend}`}>
              {formTrend.trend.replaceAll('_', ' ')}
            </span>
          </div>

          <div className="predictive-chart-box">
            <ResponsiveContainer width="100%" height={260}>
              <LineChart data={formChart}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="innings" />
                <YAxis />
                <Tooltip />
                <Line type="monotone" dataKey="runs" name="Runs" />
              </LineChart>
            </ResponsiveContainer>
          </div>

          <p className="predictive-trend-note">
            {formTrend.message}
          </p>
        </section>
      )}
    </AppLayout>
  );
}
