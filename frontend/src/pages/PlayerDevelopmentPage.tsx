import {
  useEffect,
  useMemo,
  useState,
  type FormEvent,
} from 'react';

import {
  useParams,
} from 'react-router-dom';

import {
  createDevelopmentPlan,
  createFitnessTest,
  createPlayerAssessment,
  createTrainingObjective,
  getTrainingOptions,
  listDevelopmentPlans,
  listFitnessTests,
  listPlayerAssessments,
  listTrainingObjectives,
} from '../api/training';

import {
  getPlayer,
} from '../api/players';

import {
  AppLayout,
} from '../components/AppLayout';

import {
  DevelopmentPlanCard,
} from '../components/training/DevelopmentPlanCard';

import {
  FitnessHistoryChart,
} from '../components/training/FitnessHistoryChart';

import type {
  DevelopmentPlan,
  FitnessTest,
  PlayerAssessment,
  TrainingObjective,
  TrainingOptions,
} from '../types/training';

export function PlayerDevelopmentPage() {
  const params = useParams();

  const organizationId = Number(params.organizationId);
  const playerId = Number(params.playerId);

  const [playerName, setPlayerName] = useState(`Player ${playerId}`);
  const [fitnessTests, setFitnessTests] = useState<FitnessTest[]>([]);
  const [assessments, setAssessments] = useState<PlayerAssessment[]>([]);
  const [objectives, setObjectives] = useState<TrainingObjective[]>([]);
  const [plans, setPlans] = useState<DevelopmentPlan[]>([]);
  const [options, setOptions] = useState<TrainingOptions>({
    teams: [],
    players: [],
    drills: [],
    coaches: [],
  });
  const [fitnessFilter, setFitnessFilter] = useState('');
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const load = async () => {
    setError('');

    try {
      const [
        player,
        fitness,
        assessmentRows,
        objectiveRows,
        planRows,
        optionRows,
      ] = await Promise.all([
        getPlayer(organizationId, playerId),
        listFitnessTests(organizationId, playerId),
        listPlayerAssessments(organizationId, playerId),
        listTrainingObjectives(organizationId, playerId),
        listDevelopmentPlans(organizationId, playerId),
        getTrainingOptions(organizationId),
      ]);

      setPlayerName(
        player.display_name ??
          [player.first_name, player.last_name]
            .filter(Boolean)
            .join(' ') ??
          `Player ${playerId}`,
      );

      setFitnessTests(Array.isArray(fitness) ? fitness : []);
      setAssessments(Array.isArray(assessmentRows) ? assessmentRows : []);
      setObjectives(Array.isArray(objectiveRows) ? objectiveRows : []);
      setPlans(Array.isArray(planRows) ? planRows : []);
      setOptions(optionRows);
    } catch (caught) {
      console.error(caught);
      setError('Unable to load player development data.');
    }
  };

  useEffect(() => {
    void load();
  }, [organizationId, playerId]);

  const filteredFitness = useMemo(
    () =>
      fitnessFilter
        ? fitnessTests.filter(
            (test) => test.test_type === fitnessFilter,
          )
        : fitnessTests,
    [fitnessTests, fitnessFilter],
  );

  const submitFitness = async (
    event: FormEvent<HTMLFormElement>,
  ) => {
    event.preventDefault();
    const form = event.currentTarget;
    const data = new FormData(form);

    try {
      await createFitnessTest(organizationId, {
        player_id: playerId,
        test_type: data.get('test_type'),
        tested_at: data.get('tested_at'),
        value: data.get('value')
          ? Number(data.get('value'))
          : null,
        unit: data.get('unit') || null,
        measurements: null,
        notes: data.get('notes') || null,
      });

      form.reset();
      setMessage('Fitness test recorded.');
      await load();
    } catch (caught: any) {
      setError(
        caught?.response?.data?.message ??
          'Unable to record fitness test.',
      );
    }
  };

  const submitAssessment = async (
    event: FormEvent<HTMLFormElement>,
  ) => {
    event.preventDefault();
    const form = event.currentTarget;
    const data = new FormData(form);

    try {
      await createPlayerAssessment(organizationId, {
        player_id: playerId,
        assessed_at: data.get('assessed_at'),
        technical_rating: Number(data.get('technical_rating')),
        tactical_rating: Number(data.get('tactical_rating')),
        fitness_rating: Number(data.get('fitness_rating')),
        attitude_rating: Number(data.get('attitude_rating')),
        strengths: data.get('strengths') || null,
        weaknesses: data.get('weaknesses') || null,
        notes: data.get('notes') || null,
      });

      form.reset();
      setMessage('Coach assessment recorded.');
      await load();
    } catch (caught: any) {
      setError(
        caught?.response?.data?.message ??
          'Unable to save assessment.',
      );
    }
  };

  const submitObjective = async (
    event: FormEvent<HTMLFormElement>,
  ) => {
    event.preventDefault();
    const form = event.currentTarget;
    const data = new FormData(form);

    try {
      await createTrainingObjective(organizationId, {
        player_id: playerId,
        title: data.get('title'),
        weakness: data.get('weakness') || null,
        statistic_scope: data.get('statistic_scope') || null,
        metric_key: data.get('metric_key') || null,
        observed_value: data.get('observed_value')
          ? Number(data.get('observed_value'))
          : null,
        target_value: data.get('target_value')
          ? Number(data.get('target_value'))
          : null,
        source_context: {
          source: 'P6 statistics',
          linked_manually_by_coach: true,
        },
        status: 'Active',
        target_date: data.get('target_date') || null,
        notes: data.get('notes') || null,
      });

      form.reset();
      setMessage('Training objective created.');
      await load();
    } catch (caught: any) {
      setError(
        caught?.response?.data?.message ??
          'Unable to create objective.',
      );
    }
  };

  const submitPlan = async (
    event: FormEvent<HTMLFormElement>,
  ) => {
    event.preventDefault();
    const form = event.currentTarget;
    const data = new FormData(form);

    const objectiveText = String(
      data.get('objectives') ?? '',
    )
      .split('\n')
      .map((line) => line.trim())
      .filter(Boolean);

    try {
      await createDevelopmentPlan(organizationId, {
        player_id: playerId,
        title: data.get('title'),
        weakness: data.get('weakness') || null,
        objectives: objectiveText,
        objective_ids: data
          .getAll('objective_ids')
          .map(Number),
        drill_ids: data
          .getAll('drill_ids')
          .map(Number),
        start_date: data.get('start_date'),
        target_date: data.get('target_date') || null,
        status: 'Active',
        review_notes: data.get('review_notes') || null,
      });

      form.reset();
      setMessage('Development plan created.');
      await load();
    } catch (caught: any) {
      setError(
        caught?.response?.data?.message ??
          'Unable to create development plan.',
      );
    }
  };

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <p className="eyebrow">P10 Player Development</p>
          <h1>{playerName}</h1>
          <p>
            Fitness history, coach assessments, statistics-linked
            objectives and development plans.
          </p>
        </div>
      </div>

      {error && (
        <div className="form-error-message">{error}</div>
      )}

      {message && (
        <div className="form-success-message">{message}</div>
      )}

      <section className="profile-section">
        <div className="section-heading">
          <div>
            <h2>Fitness history</h2>
            <p>
              Yo-Yo, sprint, endurance, strength and custom tests.
            </p>
          </div>

          <select
            className="training-inline-filter"
            value={fitnessFilter}
            onChange={(event) => setFitnessFilter(event.target.value)}
          >
            <option value="">All tests</option>
            <option>Yo-Yo Test</option>
            <option>Sprint</option>
            <option>Endurance</option>
            <option>Strength</option>
            <option>Custom</option>
          </select>
        </div>

        <FitnessHistoryChart tests={filteredFitness} />

        <div className="training-history-list">
          {filteredFitness
            .slice()
            .reverse()
            .map((test) => (
              <article key={test.id}>
                <strong>{test.test_type}</strong>
                <span>{String(test.tested_at).slice(0, 10)}</span>
                <span>
                  {test.value ?? '—'} {test.unit ?? ''}
                </span>
              </article>
            ))}
        </div>

        <form
          className="training-inline-form"
          onSubmit={(event) => void submitFitness(event)}
        >
          <select name="test_type" required defaultValue="Yo-Yo Test">
            <option>Yo-Yo Test</option>
            <option>Sprint</option>
            <option>Endurance</option>
            <option>Strength</option>
            <option>Custom</option>
          </select>

          <input
            name="tested_at"
            type="date"
            required
          />

          <input
            name="value"
            type="number"
            step="0.001"
            placeholder="Value"
          />

          <input
            name="unit"
            placeholder="Unit"
          />

          <input
            name="notes"
            placeholder="Notes"
          />

          <button type="submit">
            Record test
          </button>
        </form>
      </section>

      <section className="profile-section">
        <div className="section-heading">
          <div>
            <h2>Coach assessments</h2>
            <p>Ratings are 1–10 and remain coach-entered.</p>
          </div>
        </div>

        <div className="assessment-grid">
          {assessments.map((assessment) => (
            <article className="assessment-card" key={assessment.id}>
              <div className="assessment-card-heading">
                <strong>
                  {String(assessment.assessed_at).slice(0, 10)}
                </strong>
                <span>{assessment.coach?.name ?? 'Coach'}</span>
              </div>

              <div className="assessment-rating-grid">
                <span>
                  Technical <strong>{assessment.technical_rating ?? '—'}</strong>
                </span>
                <span>
                  Tactical <strong>{assessment.tactical_rating ?? '—'}</strong>
                </span>
                <span>
                  Fitness <strong>{assessment.fitness_rating ?? '—'}</strong>
                </span>
                <span>
                  Attitude <strong>{assessment.attitude_rating ?? '—'}</strong>
                </span>
              </div>

              {assessment.strengths && (
                <p><strong>Strengths:</strong> {assessment.strengths}</p>
              )}

              {assessment.weaknesses && (
                <p><strong>Weaknesses:</strong> {assessment.weaknesses}</p>
              )}
            </article>
          ))}
        </div>

        <form
          className="form-card training-subform"
          onSubmit={(event) => void submitAssessment(event)}
        >
          <div className="form-grid">
            <label>
              Assessment date
              <input
                name="assessed_at"
                type="date"
                required
              />
            </label>

            <label>
              Technical
              <input
                name="technical_rating"
                type="number"
                min="1"
                max="10"
                defaultValue="5"
              />
            </label>

            <label>
              Tactical
              <input
                name="tactical_rating"
                type="number"
                min="1"
                max="10"
                defaultValue="5"
              />
            </label>

            <label>
              Fitness
              <input
                name="fitness_rating"
                type="number"
                min="1"
                max="10"
                defaultValue="5"
              />
            </label>

            <label>
              Attitude
              <input
                name="attitude_rating"
                type="number"
                min="1"
                max="10"
                defaultValue="5"
              />
            </label>
          </div>

          <label>
            Strengths
            <textarea name="strengths" />
          </label>

          <label>
            Weaknesses
            <textarea name="weaknesses" />
          </label>

          <label>
            Coach notes
            <textarea name="notes" />
          </label>

          <div className="form-actions">
            <button type="submit">
              Save assessment
            </button>
          </div>
        </form>
      </section>

      <section className="profile-section">
        <div className="section-heading">
          <div>
            <h2>Training objectives</h2>
            <p>
              Link a coach-defined objective to a weakness observed in
              the deterministic P6 statistics.
            </p>
          </div>
        </div>

        <div className="training-objective-list">
          {objectives.map((objective) => (
            <article key={objective.id}>
              <div>
                <strong>{objective.title}</strong>
                <span>{objective.status}</span>
              </div>

              {objective.weakness && (
                <p>{objective.weakness}</p>
              )}

              {objective.metric_key && (
                <small>
                  {objective.statistic_scope ?? 'Statistics'} ·{' '}
                  {objective.metric_key}:{' '}
                  {objective.observed_value ?? '—'}
                  {' → '}
                  {objective.target_value ?? '—'}
                </small>
              )}
            </article>
          ))}
        </div>

        <form
          className="form-card training-subform"
          onSubmit={(event) => void submitObjective(event)}
        >
          <label>
            Objective title
            <input
              name="title"
              placeholder="Improve strike rotation against off-spin"
              required
            />
          </label>

          <label>
            Weakness
            <textarea
              name="weakness"
              placeholder="Strike rate against off-spin is below target."
            />
          </label>

          <div className="form-grid">
            <label>
              Statistics scope
              <select name="statistic_scope" defaultValue="Batting">
                <option>Batting</option>
                <option>Bowling</option>
                <option>Fielding</option>
                <option>Team</option>
                <option>Match</option>
              </select>
            </label>

            <label>
              Metric key
              <input
                name="metric_key"
                placeholder="strike_rate_vs_off_spin"
              />
            </label>

            <label>
              Observed value
              <input
                name="observed_value"
                type="number"
                step="0.0001"
              />
            </label>

            <label>
              Target value
              <input
                name="target_value"
                type="number"
                step="0.0001"
              />
            </label>
          </div>

          <label>
            Target date
            <input
              name="target_date"
              type="date"
            />
          </label>

          <label>
            Notes
            <textarea name="notes" />
          </label>

          <div className="form-actions">
            <button type="submit">
              Create objective
            </button>
          </div>
        </form>
      </section>

      <section className="profile-section">
        <div className="section-heading">
          <div>
            <h2>Development plans</h2>
            <p>
              Combine weakness, objectives and selected training drills.
            </p>
          </div>
        </div>

        <div className="development-plan-grid">
          {plans.map((plan) => (
            <DevelopmentPlanCard
              key={plan.id}
              plan={plan}
              drills={options.drills}
              objectives={objectives}
            />
          ))}
        </div>

        <form
          className="form-card training-subform"
          onSubmit={(event) => void submitPlan(event)}
        >
          <label>
            Plan title
            <input
              name="title"
              placeholder="Off-spin strike-rotation plan"
              required
            />
          </label>

          <label>
            Weakness
            <textarea
              name="weakness"
              placeholder="Strike rate against off-spin"
            />
          </label>

          <label>
            Objectives
            <textarea
              name="objectives"
              placeholder={'Improve strike rotation.\nUse sweep safely.\nReduce dot-ball percentage.'}
            />
          </label>

          <label>
            Link statistics objectives
            <select
              name="objective_ids"
              multiple
              size={Math.min(Math.max(objectives.length, 3), 8)}
            >
              {objectives.map((objective) => (
                <option key={objective.id} value={objective.id}>
                  {objective.title}
                </option>
              ))}
            </select>
          </label>

          <label>
            Training drills
            <select
              name="drill_ids"
              multiple
              size={Math.min(Math.max(options.drills.length, 3), 8)}
            >
              {options.drills.map((drill) => (
                <option key={drill.id} value={drill.id}>
                  {drill.name} · {drill.category}
                </option>
              ))}
            </select>
          </label>

          <div className="form-grid">
            <label>
              Start date
              <input
                name="start_date"
                type="date"
                required
              />
            </label>

            <label>
              Target date
              <input
                name="target_date"
                type="date"
              />
            </label>
          </div>

          <label>
            Review notes
            <textarea name="review_notes" />
          </label>

          <div className="form-actions">
            <button type="submit">
              Create development plan
            </button>
          </div>
        </form>
      </section>
    </AppLayout>
  );
}
