import { useMemo } from "react";

import type {
  AnalyticsFilterValues,
  AnalyticsOptions,
} from "../../types/analytics";

type Props = {
  options: AnalyticsOptions;
  filters: AnalyticsFilterValues;
  onChange: (filters: AnalyticsFilterValues) => void;
  showTeam?: boolean;
};

export function AnalyticsFilters({
  options,
  filters,
  onChange,
  showTeam = true,
}: Props) {
  const tournaments = useMemo(
    () =>
      filters.season_id
        ? options.tournaments.filter(
            (item) => item.season_id === filters.season_id,
          )
        : options.tournaments,
    [filters.season_id, options.tournaments],
  );

  const update = (key: keyof AnalyticsFilterValues, value: string) => {
    const numericKeys = [
      "team_id",
      "season_id",
      "tournament_id",
      "opponent_id",
      "venue_id",
      "batting_position",
      "limit",
    ];

    const next = {
      ...filters,
      [key]:
        value === ""
          ? undefined
          : numericKeys.includes(key)
            ? Number(value)
            : value,
    };

    if (key === "season_id") {
      next.tournament_id = undefined;
    }

    onChange(next);
  };

  const clear = () => {
    onChange(
      showTeam && filters.team_id
        ? {
            team_id: filters.team_id,
          }
        : {},
    );
  };

  return (
    <section className="analytics-filter-panel">
      <div className="analytics-filter-heading">
        <div>
          <strong>Analytics Filters</strong>
          <span>Filters are stored in the page URL.</span>
        </div>

        <button type="button" onClick={clear}>
          Reset filters
        </button>
      </div>

      <div className="analytics-filter-grid">
        {showTeam && (
          <label>
            Team
            <select
              value={filters.team_id ?? ""}
              onChange={(event) => update("team_id", event.target.value)}
            >
              <option value="">Select team</option>
              {options.teams.map((team) => (
                <option key={team.id} value={team.id}>
                  {team.name}
                </option>
              ))}
            </select>
          </label>
        )}

        <label>
          Season
          <select
            value={filters.season_id ?? ""}
            onChange={(event) => update("season_id", event.target.value)}
          >
            <option value="">All seasons</option>
            {options.seasons.map((season) => (
              <option key={season.id} value={season.id}>
                {season.name}
              </option>
            ))}
          </select>
        </label>

        <label>
          Tournament
          <select
            value={filters.tournament_id ?? ""}
            onChange={(event) => update("tournament_id", event.target.value)}
          >
            <option value="">All tournaments</option>
            {tournaments.map((tournament) => (
              <option key={tournament.id} value={tournament.id}>
                {tournament.name}
              </option>
            ))}
          </select>
        </label>

        <label>
          Opponent
          <select
            value={filters.opponent_id ?? ""}
            onChange={(event) => update("opponent_id", event.target.value)}
          >
            <option value="">All opponents</option>
            {options.teams
              .filter((team) => team.id !== filters.team_id)
              .map((team) => (
                <option key={team.id} value={team.id}>
                  {team.name}
                </option>
              ))}
          </select>
        </label>

        <label>
          Venue
          <select
            value={filters.venue_id ?? ""}
            onChange={(event) => update("venue_id", event.target.value)}
          >
            <option value="">All venues</option>
            {options.venues.map((venue) => (
              <option key={venue.id} value={venue.id}>
                {venue.name}
              </option>
            ))}
          </select>
        </label>

        <label>
          Format
          <select
            value={filters.format ?? ""}
            onChange={(event) => update("format", event.target.value)}
          >
            <option value="">All formats</option>
            {options.formats.map((format) => (
              <option key={format} value={format}>
                {format}
              </option>
            ))}
          </select>
        </label>

        <label>
          Batting position
          <select
            value={filters.batting_position ?? ""}
            onChange={(event) => update("batting_position", event.target.value)}
          >
            <option value="">All positions</option>
            {Array.from(
              {
                length: 11,
              },
              (_, index) => index + 1,
            ).map((position) => (
              <option key={position} value={position}>
                {position}
              </option>
            ))}
          </select>
        </label>

        <label>
          Spin / Pace
          <select
            value={filters.bowling_type ?? ""}
            onChange={(event) => update("bowling_type", event.target.value)}
          >
            <option value="">All bowling</option>
            <option value="pace">Pace</option>
            <option value="spin">Spin</option>
          </select>
        </label>

        <label>
          Phase
          <select
            value={filters.phase ?? ""}
            onChange={(event) => update("phase", event.target.value)}
          >
            <option value="">All phases</option>
            <option value="powerplay">Powerplay</option>
            <option value="middle">Middle overs</option>
            <option value="death">Death overs</option>
          </select>
        </label>

        <label>
          From
          <input
            type="date"
            value={filters.from ?? ""}
            onChange={(event) => update("from", event.target.value)}
          />
        </label>

        <label>
          To
          <input
            type="date"
            value={filters.to ?? ""}
            onChange={(event) => update("to", event.target.value)}
          />
        </label>
      </div>
    </section>
  );
}
