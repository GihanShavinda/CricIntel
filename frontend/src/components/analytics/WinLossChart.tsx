import {
  Cell,
  Legend,
  Pie,
  PieChart,
  ResponsiveContainer,
  Tooltip,
} from 'recharts';

type Point = {
  name: string;
  value: number;
};

type Props = {
  data: Point[];
};

const fills = [
  'var(--orange-700)',
  'var(--orange-400)',
  'var(--orange-200)',
];

export function WinLossChart({
  data,
}: Props) {
  return (
    <div className="chart-shell">
      <ResponsiveContainer
        width="100%"
        height={280}
      >
        <PieChart>
          <Pie
            data={data}
            dataKey="value"
            nameKey="name"
            innerRadius={65}
            outerRadius={95}
            paddingAngle={3}
          >
            {data.map(
              (_, index) => (
                <Cell
                  key={index}
                  fill={
                    fills[
                      index %
                        fills.length
                    ]
                  }
                />
              )
            )}
          </Pie>

          <Tooltip />
          <Legend />
        </PieChart>
      </ResponsiveContainer>
    </div>
  );
}
