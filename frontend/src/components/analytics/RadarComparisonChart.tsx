import {
  Legend,
  PolarAngleAxis,
  PolarGrid,
  Radar,
  RadarChart,
  ResponsiveContainer,
  Tooltip,
} from 'recharts';

export type RadarPoint = {
  metric: string;
  playerA: number;
  playerB: number;
};

type Props = {
  data: RadarPoint[];
  playerAName: string;
  playerBName: string;
};

export function RadarComparisonChart({
  data,
  playerAName,
  playerBName,
}: Props) {
  return (
    <div className="chart-shell chart-shell-large">
      <ResponsiveContainer
        width="100%"
        height={360}
      >
        <RadarChart
          data={data}
          outerRadius="75%"
        >
          <PolarGrid />

          <PolarAngleAxis
            dataKey="metric"
          />

          <Tooltip />
          <Legend />

          <Radar
            name={playerAName}
            dataKey="playerA"
            stroke="var(--orange-800)"
            fill="var(--orange-800)"
            fillOpacity={0.24}
          />

          <Radar
            name={playerBName}
            dataKey="playerB"
            stroke="var(--orange-400)"
            fill="var(--orange-400)"
            fillOpacity={0.18}
          />
        </RadarChart>
      </ResponsiveContainer>
    </div>
  );
}
