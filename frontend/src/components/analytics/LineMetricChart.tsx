import {
  CartesianGrid,
  Line,
  LineChart,
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
  formatter?: (
    value: number
  ) => string;
};

export function LineMetricChart({
  data,
  xKey,
  yKey,
  label,
  formatter,
}: Props) {
  return (
    <div className="chart-shell">
      <ResponsiveContainer
        width="100%"
        height={280}
      >
        <LineChart
          data={data}
          margin={{
            top: 8,
            right: 14,
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
            tickFormatter={(value) =>
              typeof value === 'string'
                ? value.slice(0, 10)
                : String(value)
            }
          />

          <YAxis />

          <Tooltip
            formatter={(value) => {
              const numeric =
                Number(value);

              return [
                formatter
                  ? formatter(numeric)
                  : numeric,
                label ?? yKey,
              ];
            }}
          />

          <Line
            type="monotone"
            dataKey={yKey}
            stroke="var(--orange-600)"
            strokeWidth={3}
            dot={{
              r: 4,
              fill: 'var(--orange-600)',
            }}
            activeDot={{
              r: 6,
            }}
          />
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
}
