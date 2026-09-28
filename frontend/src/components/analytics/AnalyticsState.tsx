type Props = {
  title: string;
  message?: string;
  action?: React.ReactNode;
};

export function AnalyticsLoading({
  title,
}: Pick<Props, 'title'>) {
  return (
    <div
      className="analytics-state analytics-loading"
      role="status"
    >
      <div className="analytics-spinner" />
      <strong>{title}</strong>
      <span>Loading deterministic cricket analytics…</span>
    </div>
  );
}

export function AnalyticsEmpty({
  title,
  message = 'No matching data is available for the selected filters.',
  action,
}: Props) {
  return (
    <div className="analytics-state analytics-empty">
      <div className="analytics-state-icon">∅</div>
      <strong>{title}</strong>
      <span>{message}</span>
      {action}
    </div>
  );
}

export function AnalyticsError({
  title,
  message = 'Unable to load analytics.',
  action,
}: Props) {
  return (
    <div
      className="analytics-state analytics-error"
      role="alert"
    >
      <div className="analytics-state-icon">!</div>
      <strong>{title}</strong>
      <span>{message}</span>
      {action}
    </div>
  );
}
