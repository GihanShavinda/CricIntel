import {
  Bar,
  BarChart,
  CartesianGrid,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';

type Props = {
  data: Array<
    Record<string, string | number | null>
  >;
  xKey: string;
  yKey: string;
  label?: string;
};

export function BarMetricChart({
  data,
  xKey,
  yKey,
  label,
}: Props) {
  return (
    <div className="chart-shell">
      <ResponsiveContainer
        width="100%"
        height={280}
      >
        <BarChart
          data={data}
          margin={{
            top: 8,
            right: 12,
            bottom: 8,
            left: 0,
          }}
        >
          <CartesianGrid
            strokeDasharray="3 3"
            vertical={false}
          />

          <XAxis
            dataKey={xKey}
          />

          <YAxis />

          <Tooltip
            formatter={(value) => [
              Number(value),
              label ?? yKey,
            ]}
          />

          <Bar
            dataKey={yKey}
            fill="var(--orange-600)"
            radius={[
              8,
              8,
              0,
              0,
            ]}
          />
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
}
