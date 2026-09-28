import type { ReactNode } from "react";

type Props = {
  title: string;
  subtitle?: string;
  children: ReactNode;
  className?: string;
  action?: ReactNode;
};

export function AnalyticsCard({
  title,
  subtitle,
  children,
  className = "",
  action,
}: Props) {
  return (
    <section className={`analytics-card ${className}`.trim()}>
      <div className="analytics-card-header">
        <div>
          <h2>{title}</h2>
          {subtitle && <p>{subtitle}</p>}
        </div>
        {action}
      </div>

      <div className="analytics-card-body">{children}</div>
    </section>
  );
}
