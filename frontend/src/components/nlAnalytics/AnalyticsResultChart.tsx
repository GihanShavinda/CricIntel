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

import type {
  AnalyticsVisualization,
} from '../../types/nlAnalytics';

interface Props {
  rows: Array<Record<string, any>>;
  visualization: AnalyticsVisualization;
}

export function AnalyticsResultChart({
  rows,
  visualization,
}: Props) {
  if (
    visualization.type === 'table' ||
    !visualization.x_key ||
    !visualization.y_key ||
    rows.length === 0
  ) {
    return null;
  }

  const xKey =
    visualization.x_key;

  const yKey =
    visualization.y_key;

  const chartRows =
    rows.map((row) => ({
      ...row,
      [xKey]:
        formatXAxisValue(
          row[xKey],
        ),
    }));

  return (
    <div className="nl-analytics-chart">
      <ResponsiveContainer
        width="100%"
        height={320}
      >
        {visualization.type ===
        'line' ? (
          <LineChart
            data={chartRows}
            margin={{
              top: 12,
              right: 18,
              left: 4,
              bottom: 8,
            }}
          >
            <CartesianGrid
              strokeDasharray="3 3"
            />

            <XAxis
              dataKey={xKey}
              tick={{
                fontSize: 11,
              }}
              minTickGap={20}
            />

            <YAxis
              tick={{
                fontSize: 11,
              }}
            />

            <Tooltip />

            <Line
              type="monotone"
              dataKey={yKey}
              name={
                visualization.label ??
                yKey
              }
              stroke="currentColor"
              strokeWidth={2}
              activeDot={{
                r: 5,
              }}
            />
          </LineChart>
        ) : (
          <BarChart
            data={chartRows}
            margin={{
              top: 12,
              right: 18,
              left: 4,
              bottom: 8,
            }}
          >
            <CartesianGrid
              strokeDasharray="3 3"
            />

            <XAxis
              dataKey={xKey}
              tick={{
                fontSize: 11,
              }}
              minTickGap={12}
            />

            <YAxis
              tick={{
                fontSize: 11,
              }}
            />

            <Tooltip />

            <Bar
              dataKey={yKey}
              name={
                visualization.label ??
                yKey
              }
              fill="currentColor"
              radius={[
                5,
                5,
                0,
                0,
              ]}
            />
          </BarChart>
        )}
      </ResponsiveContainer>
    </div>
  );
}

function formatXAxisValue(
  value: unknown,
) {
  if (
    typeof value === 'string' &&
    /^\d{4}-\d{2}-\d{2}/.test(
      value,
    )
  ) {
    return value.slice(
      0,
      10,
    );
  }

  return String(
    value ?? '',
  );
}
