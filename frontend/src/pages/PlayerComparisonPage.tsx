import { useMemo, useState } from "react";

import { Link, useParams } from "react-router-dom";

import { AppLayout } from "../components/AppLayout";

import { AnalyticsCard } from "../components/analytics/AnalyticsCard";

import {
  AnalyticsEmpty,
  AnalyticsError,
  AnalyticsLoading,
} from "../components/analytics/AnalyticsState";

import { AnalyticsFilters } from "../components/analytics/AnalyticsFilters";

import {
  RadarComparisonChart,
  type RadarPoint,
} from "../components/analytics/RadarComparisonChart";

import {
  useAnalyticsOptions,
  usePlayerComparison,
} from "../hooks/useAnalytics";

import { useAnalyticsUrlFilters } from "../hooks/useAnalyticsUrlFilters";

function normalize(a: number, b: number): [number, number] {
  const max = Math.max(Math.abs(a), Math.abs(b), 1);

  return [
    Number(((a / max) * 100).toFixed(2)),
    Number(((b / max) * 100).toFixed(2)),
  ];
}

export function PlayerComparisonPage() {
  const organizationId = Number(useParams().organizationId);

  const { filters, setFilters } = useAnalyticsUrlFilters();

  const [playerA, setPlayerA] = useState<number>(
    Number(new URLSearchParams(window.location.search).get("player_a")) || 0,
  );

  const [playerB, setPlayerB] = useState<number>(
    Number(new URLSearchParams(window.location.search).get("player_b")) || 0,
  );

  const optionsQuery = useAnalyticsOptions(organizationId);

  const comparisonFilters = useMemo(() => {
    const { team_id: _team, ...rest } = filters;

    return rest;
  }, [filters]);

  const comparisonQuery = usePlayerComparison(
    organizationId,
    playerA,
    playerB,
    comparisonFilters,
  );

  const radarData = useMemo<RadarPoint[]>(() => {
    if (!comparisonQuery.data) {
      return [];
    }

    const a = comparisonQuery.data.player_a;

    const b = comparisonQuery.data.player_b;

    const metrics = [
      {
        metric: "Bat Avg",
        a: a.batting.average ?? 0,
        b: b.batting.average ?? 0,
      },
      {
        metric: "Bat SR",
        a: a.batting.strike_rate,
        b: b.batting.strike_rate,
      },
      {
        metric: "Runs",
        a: a.batting.runs,
        b: b.batting.runs,
      },
      {
        metric: "Wickets",
        a: a.bowling.wickets,
        b: b.bowling.wickets,
      },
      {
        metric: "Dot %",
        a: a.bowling.dot_percentage,
        b: b.bowling.dot_percentage,
      },
      {
        metric: "Field %",
        a: a.fielding.fielding_efficiency ?? 0,
        b: b.fielding.fielding_efficiency ?? 0,
      },
    ];

    return metrics.map((item) => {
      const [normalizedA, normalizedB] = normalize(item.a, item.b);

      return {
        metric: item.metric,
        playerA: normalizedA,
        playerB: normalizedB,
      };
    });
  }, [comparisonQuery.data]);

  const updatePlayerUrl = (a: number, b: number) => {
    const next = {
      ...filters,
    } as Record<string, unknown>;

    if (a) {
      next.player_a = a;
    }

    if (b) {
      next.player_b = b;
    }

    const params = new URLSearchParams();

    Object.entries(next).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== "") {
        params.set(key, String(value));
      }
    });

    window.history.replaceState(
      null,
      "",
      `${window.location.pathname}?${params.toString()}`,
    );
  };

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <h1>Player Comparison</h1>
          <p>Compare two players using deterministic P6 statistics.</p>
        </div>

        <Link
          className="analytics-heading-link"
          to={`/organizations/${organizationId}/analytics`}
        >
          Analytics dashboard
        </Link>
      </div>

      {optionsQuery.isLoading && <AnalyticsLoading title="Loading players" />}

      {optionsQuery.isError && (
        <AnalyticsError title="Unable to load comparison options" />
      )}

      {optionsQuery.data && (
        <>
          <section className="player-comparison-picker">
            <label>
              Player A
              <select
                value={playerA || ""}
                onChange={(event) => {
                  const value = Number(event.target.value);

                  setPlayerA(value);

                  updatePlayerUrl(value, playerB);
                }}
              >
                <option value="">Select Player A</option>
                {optionsQuery.data.players
                  .filter((player) => player.id !== playerB)
                  .map((player) => (
                    <option key={player.id} value={player.id}>
                      {player.display_name}
                    </option>
                  ))}
              </select>
            </label>

            <div className="comparison-vs">VS</div>

            <label>
              Player B
              <select
                value={playerB || ""}
                onChange={(event) => {
                  const value = Number(event.target.value);

                  setPlayerB(value);

                  updatePlayerUrl(playerA, value);
                }}
              >
                <option value="">Select Player B</option>
                {optionsQuery.data.players
                  .filter((player) => player.id !== playerA)
                  .map((player) => (
                    <option key={player.id} value={player.id}>
                      {player.display_name}
                    </option>
                  ))}
              </select>
            </label>
          </section>

          <AnalyticsFilters
            options={optionsQuery.data}
            filters={comparisonFilters}
            onChange={setFilters}
            showTeam={false}
          />
        </>
      )}

      {(!playerA || !playerB) && optionsQuery.data && (
        <AnalyticsEmpty
          title="Choose two players"
          message="Select Player A and Player B to generate a comparison."
        />
      )}

      {playerA === playerB && playerA !== 0 && (
        <AnalyticsError
          title="Choose different players"
          message="A player cannot be compared with themselves."
        />
      )}

      {comparisonQuery.isLoading && (
        <AnalyticsLoading title="Calculating player comparison" />
      )}

      {comparisonQuery.isError && (
        <AnalyticsError
          title="Comparison unavailable"
          action={
            <button type="button" onClick={() => comparisonQuery.refetch()}>
              Retry
            </button>
          }
        />
      )}

      {comparisonQuery.data && (
        <>
          <div className="comparison-player-headings">
            <article>
              <span>Player A</span>
              <strong>{comparisonQuery.data.player_a.player.name}</strong>
              <small>{comparisonQuery.data.player_a.player.role ?? "—"}</small>
            </article>

            <article>
              <span>Player B</span>
              <strong>{comparisonQuery.data.player_b.player.name}</strong>
              <small>{comparisonQuery.data.player_b.player.role ?? "—"}</small>
            </article>
          </div>

          <AnalyticsCard
            title="Performance Radar"
            subtitle="Each metric is normalized to the stronger of the two values for visual comparison."
            className="analytics-card-wide"
          >
            <RadarComparisonChart
              data={radarData}
              playerAName={comparisonQuery.data.player_a.player.name}
              playerBName={comparisonQuery.data.player_b.player.name}
            />
          </AnalyticsCard>

          <div className="comparison-grid">
            <AnalyticsCard title="Batting" subtitle="P6 batting statistics">
              <ComparisonTable
                rows={[
                  [
                    "Matches",
                    comparisonQuery.data.player_a.batting.matches,
                    comparisonQuery.data.player_b.batting.matches,
                  ],
                  [
                    "Runs",
                    comparisonQuery.data.player_a.batting.runs,
                    comparisonQuery.data.player_b.batting.runs,
                  ],
                  [
                    "Highest",
                    comparisonQuery.data.player_a.batting.highest_score,
                    comparisonQuery.data.player_b.batting.highest_score,
                  ],
                  [
                    "Average",
                    comparisonQuery.data.player_a.batting.average ?? "—",
                    comparisonQuery.data.player_b.batting.average ?? "—",
                  ],
                  [
                    "Strike rate",
                    comparisonQuery.data.player_a.batting.strike_rate,
                    comparisonQuery.data.player_b.batting.strike_rate,
                  ],
                  [
                    "50s",
                    comparisonQuery.data.player_a.batting.fifties,
                    comparisonQuery.data.player_b.batting.fifties,
                  ],
                  [
                    "100s",
                    comparisonQuery.data.player_a.batting.hundreds,
                    comparisonQuery.data.player_b.batting.hundreds,
                  ],
                  [
                    "Boundary %",
                    comparisonQuery.data.player_a.batting.boundary_percentage,
                    comparisonQuery.data.player_b.batting.boundary_percentage,
                  ],
                  [
                    "Dot-ball %",
                    comparisonQuery.data.player_a.batting.dot_ball_percentage,
                    comparisonQuery.data.player_b.batting.dot_ball_percentage,
                  ],
                ]}
              />
            </AnalyticsCard>

            <AnalyticsCard title="Bowling" subtitle="P6 bowling statistics">
              <ComparisonTable
                rows={[
                  [
                    "Overs",
                    comparisonQuery.data.player_a.bowling.overs,
                    comparisonQuery.data.player_b.bowling.overs,
                  ],
                  [
                    "Wickets",
                    comparisonQuery.data.player_a.bowling.wickets,
                    comparisonQuery.data.player_b.bowling.wickets,
                  ],
                  [
                    "Average",
                    comparisonQuery.data.player_a.bowling.average ?? "—",
                    comparisonQuery.data.player_b.bowling.average ?? "—",
                  ],
                  [
                    "Economy",
                    comparisonQuery.data.player_a.bowling.economy,
                    comparisonQuery.data.player_b.bowling.economy,
                  ],
                  [
                    "Strike rate",
                    comparisonQuery.data.player_a.bowling.strike_rate ?? "—",
                    comparisonQuery.data.player_b.bowling.strike_rate ?? "—",
                  ],
                  [
                    "Dot %",
                    comparisonQuery.data.player_a.bowling.dot_percentage,
                    comparisonQuery.data.player_b.bowling.dot_percentage,
                  ],
                  [
                    "Maidens",
                    comparisonQuery.data.player_a.bowling.maidens,
                    comparisonQuery.data.player_b.bowling.maidens,
                  ],
                ]}
              />
            </AnalyticsCard>

            <AnalyticsCard
              title="Fielding"
              subtitle="Recorded fielding outcomes"
            >
              <ComparisonTable
                rows={[
                  [
                    "Catches",
                    comparisonQuery.data.player_a.fielding.catches,
                    comparisonQuery.data.player_b.fielding.catches,
                  ],
                  [
                    "Run-outs",
                    comparisonQuery.data.player_a.fielding.run_outs,
                    comparisonQuery.data.player_b.fielding.run_outs,
                  ],
                  [
                    "Stumpings",
                    comparisonQuery.data.player_a.fielding.stumpings,
                    comparisonQuery.data.player_b.fielding.stumpings,
                  ],
                  [
                    "Drops",
                    comparisonQuery.data.player_a.fielding.drops,
                    comparisonQuery.data.player_b.fielding.drops,
                  ],
                  [
                    "Efficiency %",
                    comparisonQuery.data.player_a.fielding
                      .fielding_efficiency ?? "—",
                    comparisonQuery.data.player_b.fielding
                      .fielding_efficiency ?? "—",
                  ],
                ]}
              />
            </AnalyticsCard>
          </div>
        </>
      )}
    </AppLayout>
  );
}

type ComparisonValue = string | number;

function ComparisonTable({
  rows,
}: {
  rows: Array<[string, ComparisonValue, ComparisonValue]>;
}) {
  return (
    <div className="analytics-table-wrap">
      <table className="analytics-table comparison-table">
        <thead>
          <tr>
            <th>Metric</th>
            <th>A</th>
            <th>B</th>
          </tr>
        </thead>
        <tbody>
          {rows.map((row) => (
            <tr key={row[0]}>
              <td>{row[0]}</td>
              <td>{row[1]}</td>
              <td>{row[2]}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
