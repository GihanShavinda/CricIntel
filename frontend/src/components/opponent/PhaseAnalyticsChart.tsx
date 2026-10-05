import {
  Bar,
  BarChart,
  CartesianGrid,
  Legend,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';

import type {
  PhaseBatting,
  PhaseBowling,
} from '../../types/opponentAnalytics';

export function BattingPhaseChart({
  phases,
}: {
  phases: Record<string, PhaseBatting>;
}) {
  const data = ['Powerplay', 'Middle', 'Death'].map((phase) => ({
    phase,
    strikeRate: phases[phase]?.strike_rate ?? 0,
    dotBall: phases[phase]?.dot_ball_percentage ?? 0,
    balls: phases[phase]?.balls ?? 0,
  }));

  return (
    <div className="opponent-chart">
      <ResponsiveContainer width="100%" height={280}>
        <BarChart data={data}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="phase" />
          <YAxis />
          <Tooltip />
          <Legend />
          <Bar dataKey="strikeRate" name="Strike rate" />
          <Bar dataKey="dotBall" name="Dot-ball %" />
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
}

export function BowlingPhaseChart({
  phases,
}: {
  phases: Record<string, PhaseBowling>;
}) {
  const data = ['Powerplay', 'Middle', 'Death'].map((phase) => ({
    phase,
    economy: phases[phase]?.economy ?? 0,
    wicketRate: phases[phase]?.wicket_rate_per_100_balls ?? 0,
    balls: phases[phase]?.balls ?? 0,
  }));

  return (
    <div className="opponent-chart">
      <ResponsiveContainer width="100%" height={280}>
        <BarChart data={data}>
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis dataKey="phase" />
          <YAxis />
          <Tooltip />
          <Legend />
          <Bar dataKey="economy" name="Economy" />
          <Bar dataKey="wicketRate" name="Wickets / 100 balls" />
        </BarChart>
      </ResponsiveContainer>
    </div>
  );
}
