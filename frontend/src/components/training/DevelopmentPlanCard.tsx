import type {
  DevelopmentPlan,
  TrainingDrill,
  TrainingObjective,
} from '../../types/training';

export function DevelopmentPlanCard({
  plan,
  drills,
  objectives,
}: {
  plan: DevelopmentPlan;
  drills: TrainingDrill[];
  objectives: TrainingObjective[];
}) {
  const drillNames = (plan.drill_ids ?? [])
    .map(
      (id) =>
        drills.find((drill) => drill.id === id)?.name ??
        `Drill ${id}`,
    );

  const linkedObjectives = (plan.objective_ids ?? [])
    .map(
      (id) =>
        objectives.find((objective) => objective.id === id)?.title ??
        `Objective ${id}`,
    );

  return (
    <article className="development-plan-card">
      <div className="development-plan-header">
        <div>
          <strong>{plan.title}</strong>
          <span>
            {plan.start_date}
            {plan.target_date ? ` → ${plan.target_date}` : ''}
          </span>
        </div>

        <span className="status-badge">{plan.status}</span>
      </div>

      {plan.weakness && (
        <div className="development-plan-block">
          <span>Weakness</span>
          <p>{plan.weakness}</p>
        </div>
      )}

      {(plan.objectives ?? []).length > 0 && (
        <div className="development-plan-block">
          <span>Objectives</span>
          <ul>
            {(plan.objectives ?? []).map((objective) => (
              <li key={objective}>{objective}</li>
            ))}
          </ul>
        </div>
      )}

      {linkedObjectives.length > 0 && (
        <div className="development-plan-block">
          <span>Linked statistics objectives</span>
          <ul>
            {linkedObjectives.map((objective) => (
              <li key={objective}>{objective}</li>
            ))}
          </ul>
        </div>
      )}

      {drillNames.length > 0 && (
        <div className="development-plan-block">
          <span>Drills</span>
          <div className="tag-list">
            {drillNames.map((name) => (
              <span className="tag" key={name}>
                {name}
              </span>
            ))}
          </div>
        </div>
      )}

      {plan.review_notes && (
        <div className="development-plan-block">
          <span>Review notes</span>
          <p>{plan.review_notes}</p>
        </div>
      )}
    </article>
  );
}
