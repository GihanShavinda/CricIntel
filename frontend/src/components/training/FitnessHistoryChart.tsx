import {
  CartesianGrid,
  Legend,
  Line,
  LineChart,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';

import type {
  FitnessTest,
} from '../../types/training';

export function FitnessHistoryChart({
  tests,
}: {
  tests: FitnessTest[];
}) {
  const chartData = tests
    .filter(
      (test) =>
        typeof test.value === 'number' &&
        Number.isFinite(test.value),
    )
    .map((test) => ({
      date: String(test.tested_at).slice(0, 10),
      value: Number(test.value),
      type: test.test_type,
      unit: test.unit ?? '',
    }));

  if (chartData.length === 0) {
    return (
      <div className="training-empty">
        No numeric fitness history is available yet.
      </div>
    );
  }

  return (
    <div className="training-chart-shell">
      <ResponsiveContainer width="100%" height={300}>
        <LineChart data={chartData}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="date" />
          <YAxis />
          <Tooltip />
          <Legend />
          <Line
            type="monotone"
            dataKey="value"
            name="Measured value"
            strokeWidth={3}
          />
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
}
