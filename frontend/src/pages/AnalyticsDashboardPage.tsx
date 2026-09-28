import { useEffect } from "react";

import { Link, useParams } from "react-router-dom";

import { AppLayout } from "../components/AppLayout";

import { AnalyticsCard } from "../components/analytics/AnalyticsCard";

import {
  AnalyticsEmpty,
  AnalyticsError,
  AnalyticsLoading,
} from "../components/analytics/AnalyticsState";

import { AnalyticsFilters } from "../components/analytics/AnalyticsFilters";

import { BarMetricChart } from "../components/analytics/BarMetricChart";

import { LineMetricChart } from "../components/analytics/LineMetricChart";

import { WinLossChart } from "../components/analytics/WinLossChart";

import {
  useAnalyticsDashboard,
  useAnalyticsOptions,
} from "../hooks/useAnalytics";

import { useAnalyticsUrlFilters } from "../hooks/useAnalyticsUrlFilters";

function resultClass(result: string) {
  if (result === "W") {
    return "win";
  }

  if (result === "L") {
    return "loss";
  }

  return "neutral";
}

export function AnalyticsDashboardPage() {
  const organizationId = Number(useParams().organizationId);

  const { filters, setFilters } = useAnalyticsUrlFilters();

  const optionsQuery = useAnalyticsOptions(organizationId);

  useEffect(() => {
    if (!filters.team_id && optionsQuery.data?.teams?.length) {
      setFilters({
        ...filters,
        team_id: optionsQuery.data.teams[0].id,
      });
    }
  }, [filters, optionsQuery.data, setFilters]);

  const dashboardQuery = useAnalyticsDashboard(organizationId, filters);

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <h1>Cricket Analytics</h1>
          <p>
            Deterministic insights calculated from recorded match and
            ball-by-ball data.
          </p>
        </div>

        <Link
          className="analytics-heading-link"
          to={`/organizations/${organizationId}` + "/analytics/players/compare"}
        >
          Compare players
        </Link>
      </div>

      {optionsQuery.isLoading && <AnalyticsLoading title="Loading filters" />}

      {optionsQuery.isError && (
        <AnalyticsError
          title="Could not load analytics filters"
          action={
            <button type="button" onClick={() => optionsQuery.refetch()}>
              Retry
            </button>
          }
        />
      )}

      {optionsQuery.data && (
        <AnalyticsFilters
          options={optionsQuery.data}
          filters={filters}
          onChange={setFilters}
        />
      )}

      {!filters.team_id && optionsQuery.data && (
        <AnalyticsEmpty
          title="Select a team"
          message="Choose a team to load the analytics dashboard."
        />
      )}

      {dashboardQuery.isLoading && filters.team_id && (
        <AnalyticsLoading title="Building analytics dashboard" />
      )}

      {dashboardQuery.isError && (
        <AnalyticsError
          title="Dashboard analytics unavailable"
          message={
            dashboardQuery.error instanceof Error
              ? dashboardQuery.error.message
              : "Unable to load dashboard analytics."
          }
          action={
            <button type="button" onClick={() => dashboardQuery.refetch()}>
              Retry
            </button>
          }
        />
      )}

      {dashboardQuery.data && (
        <div className="analytics-dashboard-grid">
          <AnalyticsCard
            title="Team Form"
            subtitle="Latest completed matches"
            className="analytics-card-wide"
          >
            {dashboardQuery.data.team_form.length ? (
              <div className="team-form-strip">
                {dashboardQuery.data.team_form.map((item) => (
                  <div
                    key={item.match_id}
                    className={`form-result ${resultClass(item.result)}`}
                    title={item.result_type ?? item.date}
                  >
                    {item.result}
                  </div>
                ))}
              </div>
            ) : (
              <AnalyticsEmpty title="No form data" />
            )}
          </AnalyticsCard>

          <AnalyticsCard title="Win / Loss" subtitle="Completed match outcomes">
            {dashboardQuery.data.win_loss.some((item) => item.value > 0) ? (
              <WinLossChart data={dashboardQuery.data.win_loss} />
            ) : (
              <AnalyticsEmpty title="No completed matches" />
            )}
          </AnalyticsCard>

          <AnalyticsCard
            title="Runs Trend"
            subtitle="Team innings total by match"
            className="analytics-card-wide"
          >
            {dashboardQuery.data.runs_trend.length ? (
              <LineMetricChart
                data={dashboardQuery.data.runs_trend}
                xKey="date"
                yKey="runs"
                label="Runs"
              />
            ) : (
              <AnalyticsEmpty title="No runs trend" />
            )}
          </AnalyticsCard>

          <AnalyticsCard
            title="Run Rate Trend"
            subtitle="Runs per six legal balls"
          >
            {dashboardQuery.data.run_rate_trend.length ? (
              <LineMetricChart
                data={dashboardQuery.data.run_rate_trend}
                xKey="date"
                yKey="run_rate"
                label="Run rate"
                formatter={(value) => value.toFixed(2)}
              />
            ) : (
              <AnalyticsEmpty title="No run-rate data" />
            )}
          </AnalyticsCard>

          <AnalyticsCard
            title="Wicket Trend"
            subtitle="Wickets lost per innings"
          >
            {dashboardQuery.data.wicket_trend.length ? (
              <BarMetricChart
                data={dashboardQuery.data.wicket_trend}
                xKey="date"
                yKey="wickets_lost"
                label="Wickets"
              />
            ) : (
              <AnalyticsEmpty title="No wicket trend" />
            )}
          </AnalyticsCard>

          <AnalyticsCard title="Top Batters" subtitle="Runs and strike rate">
            {dashboardQuery.data.top_batters.length ? (
              <div className="analytics-table-wrap">
                <table className="analytics-table">
                  <thead>
                    <tr>
                      <th>Player</th>
                      <th>Runs</th>
                      <th>SR</th>
                      <th>4s</th>
                      <th>6s</th>
                    </tr>
                  </thead>
                  <tbody>
                    {dashboardQuery.data.top_batters.map((player) => (
                      <tr key={player.player_id}>
                        <td>{player.name}</td>
                        <td>{player.runs}</td>
                        <td>{player.strike_rate}</td>
                        <td>{player.fours}</td>
                        <td>{player.sixes}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <AnalyticsEmpty title="No batting data" />
            )}
          </AnalyticsCard>

          <AnalyticsCard title="Top Bowlers" subtitle="Wickets and economy">
            {dashboardQuery.data.top_bowlers.length ? (
              <div className="analytics-table-wrap">
                <table className="analytics-table">
                  <thead>
                    <tr>
                      <th>Player</th>
                      <th>Wkts</th>
                      <th>Runs</th>
                      <th>Eco</th>
                    </tr>
                  </thead>
                  <tbody>
                    {dashboardQuery.data.top_bowlers.map((player) => (
                      <tr key={player.player_id}>
                        <td>{player.name}</td>
                        <td>{player.wickets}</td>
                        <td>{player.runs_conceded}</td>
                        <td>{player.economy}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <AnalyticsEmpty title="No bowling data" />
            )}
          </AnalyticsCard>

          <AnalyticsCard
            title="Phase Analysis"
            subtitle="Powerplay, middle and death overs"
            className="analytics-card-wide"
          >
            {dashboardQuery.data.phase_analysis.length ? (
              <BarMetricChart
                data={dashboardQuery.data.phase_analysis}
                xKey="phase"
                yKey="run_rate"
                label="Run rate"
              />
            ) : (
              <AnalyticsEmpty title="No phase data" />
            )}
          </AnalyticsCard>

          <AnalyticsCard
            title="Opponent Summary"
            subtitle="Historical team results"
          >
            {dashboardQuery.data.opponent_summary.length ? (
              <div className="analytics-table-wrap">
                <table className="analytics-table">
                  <thead>
                    <tr>
                      <th>Opponent</th>
                      <th>M</th>
                      <th>W</th>
                      <th>L</th>
                    </tr>
                  </thead>
                  <tbody>
                    {dashboardQuery.data.opponent_summary.map((row) => (
                      <tr key={row.opponent_id}>
                        <td>{row.opponent}</td>
                        <td>{row.matches}</td>
                        <td>{row.wins}</td>
                        <td>{row.losses}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <AnalyticsEmpty title="No opponent data" />
            )}
          </AnalyticsCard>

          <AnalyticsCard
            title="Venue Performance"
            subtitle="Win percentage by ground"
          >
            {dashboardQuery.data.venue_performance.length ? (
              <div className="analytics-table-wrap">
                <table className="analytics-table">
                  <thead>
                    <tr>
                      <th>Venue</th>
                      <th>M</th>
                      <th>W</th>
                      <th>Win %</th>
                    </tr>
                  </thead>
                  <tbody>
                    {dashboardQuery.data.venue_performance.map((row) => (
                      <tr key={row.venue_id ?? row.venue}>
                        <td>{row.venue}</td>
                        <td>{row.matches}</td>
                        <td>{row.wins}</td>
                        <td>{row.win_percentage}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <AnalyticsEmpty title="No venue data" />
            )}
          </AnalyticsCard>
        </div>
      )}
    </AppLayout>
  );
}
