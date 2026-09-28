import axios from "axios";

import { useEffect, useMemo, useState } from "react";

import { useNavigate, useParams } from "react-router-dom";

import {
  getTournament,
  listFixtures,
  syncTournamentTeams,
} from "../api/competitions";

import { listTeams } from "../api/organizations";

import { AppLayout } from "../components/AppLayout";

import type { Fixture, Tournament } from "../types/competition";

import type { Team } from "../types/organization";

export function TournamentDetailsPage() {
  const organizationId = Number(useParams().organizationId);

  const tournamentId = Number(useParams().tournamentId);

  const navigate = useNavigate();

  const [tournament, setTournament] = useState<Tournament | null>(null);

  const [fixtures, setFixtures] = useState<Fixture[]>([]);

  const [allTeams, setAllTeams] = useState<Team[]>([]);

  const [selectedTeams, setSelectedTeams] = useState<number[]>([]);

  const [savingTeams, setSavingTeams] = useState(false);

  const [loading, setLoading] = useState(true);

  const [message, setMessage] = useState("");

  const [errorMessage, setErrorMessage] = useState("");

  const load = async () => {
    setLoading(true);
    setErrorMessage("");

    try {
      const [tournamentResult, fixtureResult, teamResult] = await Promise.all([
        getTournament(organizationId, tournamentId),

        listFixtures(organizationId, {
          tournament_id: tournamentId,
          per_page: 100,
          sort: "scheduled_at",
          direction: "asc",
        }),

        listTeams(organizationId, {
          per_page: 100,
        }),
      ]);

      setTournament(tournamentResult);

      setFixtures(fixtureResult.data);

      setAllTeams(teamResult.data);

      setSelectedTeams(tournamentResult.teams?.map((team) => team.id) ?? []);
    } catch (error) {
      console.error("Unable to load tournament:", error);

      if (axios.isAxiosError(error)) {
        setErrorMessage(
          error.response?.data?.message ??
            "Unable to load tournament information.",
        );
      } else {
        setErrorMessage("Unable to load tournament information.");
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, [organizationId, tournamentId]);

  const toggleTeam = (teamId: number) => {
    setSelectedTeams((current) => {
      if (current.includes(teamId)) {
        return current.filter((id) => id !== teamId);
      }

      return [...current, teamId];
    });
  };

  const selectAllTeams = () => {
    setSelectedTeams(allTeams.map((team) => team.id));
  };

  const clearTeams = () => {
    setSelectedTeams([]);
  };

  const saveTournamentTeams = async () => {
    setMessage("");
    setErrorMessage("");

    if (selectedTeams.length < 2) {
      setErrorMessage(
        "Select at least two teams before saving tournament registration.",
      );

      return;
    }

    setSavingTeams(true);

    try {
      const registrations = selectedTeams.map((teamId, index) => ({
        team_id: teamId,
        seed: index + 1,
        status: "registered" as const,
      }));

      await syncTournamentTeams(organizationId, tournamentId, registrations);

      setMessage("Tournament teams registered successfully.");

      await load();
    } catch (error) {
      console.error("Unable to register tournament teams:", error);

      if (axios.isAxiosError(error)) {
        const response = error.response?.data;

        const validationErrors = response?.errors;

        const firstError = validationErrors
          ? Object.values(validationErrors).flat().at(0)
          : null;

        setErrorMessage(
          String(
            firstError ??
              response?.message ??
              "Unable to register tournament teams.",
          ),
        );
      } else {
        setErrorMessage("Unable to register tournament teams.");
      }
    } finally {
      setSavingTeams(false);
    }
  };

  const registeredTeamIds = useMemo(
    () => new Set(tournament?.teams?.map((team) => team.id) ?? []),
    [tournament],
  );

  if (loading) {
    return (
      <AppLayout>
        <div className="page-state">Loading tournament...</div>
      </AppLayout>
    );
  }

  if (!tournament) {
    return (
      <AppLayout>
        <div className="form-error-message">
          {errorMessage || "Tournament not found."}
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <p className="eyebrow">Tournament</p>

          <h1>{tournament.name}</h1>

          <p>
            {tournament.format}
            {" · "}
            {tournament.start_date}
            {" → "}
            {tournament.end_date}
          </p>
        </div>

        <div className="tournament-heading-actions">
          <button
            type="button"
            onClick={() =>
              navigate(`/organizations/${organizationId}/fixtures`)
            }
          >
            Fixture calendar
          </button>
        </div>
      </div>

      {message && <div className="form-success-message">{message}</div>}

      {errorMessage && <div className="form-error-message">{errorMessage}</div>}

      <div className="summary-grid tournament-summary-grid">
        <article>
          <strong>{tournament.format}</strong>

          <span>Format</span>
        </article>

        <article>
          <strong>{tournament.status}</strong>

          <span>Status</span>
        </article>

        <article>
          <strong>{tournament.organizer ?? "—"}</strong>

          <span>Organizer</span>
        </article>

        <article>
          <strong>{tournament.teams?.length ?? 0}</strong>

          <span>Registered teams</span>
        </article>
      </div>

      {/* =====================================================
          REGISTER TEAMS
          ===================================================== */}

      <section className="profile-section tournament-section tournament-registration-section">
        <div className="tournament-section-header">
          <div>
            <p className="tournament-section-kicker">Participation</p>

            <h2>Register teams</h2>

            <p>Select the teams that are participating in this tournament.</p>
          </div>

          <div className="team-registration-actions">
            <button
              type="button"
              className="secondary"
              onClick={selectAllTeams}
              disabled={savingTeams || allTeams.length === 0}
            >
              Select all
            </button>

            <button
              type="button"
              className="secondary"
              onClick={clearTeams}
              disabled={savingTeams}
            >
              Clear
            </button>

            <button
              type="button"
              onClick={saveTournamentTeams}
              disabled={savingTeams || selectedTeams.length < 2}
            >
              {savingTeams ? "Saving..." : "Save teams"}
            </button>
          </div>
        </div>

        <div className="team-selection-summary">
          <strong>{selectedTeams.length}</strong>

          <span>
            team
            {selectedTeams.length === 1 ? "" : "s"} selected
          </span>
        </div>

        {allTeams.length ? (
          <div className="tournament-team-grid">
            {allTeams.map((team) => {
              const selected = selectedTeams.includes(team.id);

              const registered = registeredTeamIds.has(team.id);

              return (
                <label
                  key={team.id}
                  className={
                    selected
                      ? "tournament-team-option selected"
                      : "tournament-team-option"
                  }
                >
                  <input
                    type="checkbox"
                    checked={selected}
                    onChange={() => toggleTeam(team.id)}
                  />

                  <div className="tournament-team-details">
                    <strong>{team.name}</strong>

                    <div className="tournament-team-meta">
                      <span>{team.short_name || "No short name"}</span>

                      {registered && (
                        <span className="registered-inline-badge">
                          Registered
                        </span>
                      )}
                    </div>
                  </div>

                  <span
                    className={
                      selected
                        ? "team-select-indicator selected"
                        : "team-select-indicator"
                    }
                  >
                    {selected ? "✓" : ""}
                  </span>
                </label>
              );
            })}
          </div>
        ) : (
          <div className="tournament-empty-state">
            <div className="tournament-empty-icon">T</div>

            <div>
              <strong>No teams available</strong>

              <p>
                Create teams in this organization before registering tournament
                participants.
              </p>
            </div>

            <button
              type="button"
              onClick={() => navigate(`/organizations/${organizationId}/teams`)}
            >
              Create teams
            </button>
          </div>
        )}
      </section>

      {/* =====================================================
          REGISTERED TEAMS
          ===================================================== */}

      <section className="profile-section tournament-section">
        <div className="tournament-section-header">
          <div>
            <p className="tournament-section-kicker">Eligibility</p>

            <h2>Registered teams</h2>

            <p>Teams currently eligible to play fixtures in this tournament.</p>
          </div>

          <span className="tournament-count-badge">
            {tournament.teams?.length ?? 0} registered
          </span>
        </div>

        {tournament.teams?.length ? (
          <div className="registered-team-list">
            {tournament.teams.map((team) => (
              <article className="registered-team-card" key={team.id}>
                <div className="registered-team-seed">
                  {team.seed ? `#${team.seed}` : "—"}
                </div>

                <div className="registered-team-content">
                  <strong>{team.name}</strong>

                  <div className="registered-team-meta">
                    <span className="registration-status-badge">
                      {team.status}
                    </span>
                  </div>
                </div>
              </article>
            ))}
          </div>
        ) : (
          <div className="tournament-empty-state">
            <div className="tournament-empty-icon">0</div>

            <div>
              <strong>No registered teams</strong>

              <p>Select at least two teams above and click Save teams.</p>
            </div>
          </div>
        )}
      </section>

      {/* =====================================================
          FIXTURES
          ===================================================== */}

      <section className="profile-section tournament-section">
        <div className="tournament-section-header">
          <div>
            <p className="tournament-section-kicker">Schedule</p>

            <h2>Fixtures</h2>

            <p>Scheduled matches for this tournament.</p>
          </div>

          <button
            type="button"
            disabled={!tournament.teams?.length || tournament.teams.length < 2}
            onClick={() =>
              navigate(`/organizations/${organizationId}/fixtures`)
            }
          >
            Schedule fixture
          </button>
        </div>

        {fixtures.length ? (
          <div className="tournament-fixture-list">
            {fixtures.map((fixture) => (
              <article className="tournament-fixture-card" key={fixture.id}>
                <div className="fixture-teams">
                  <div className="fixture-team-name">
                    <span>Home</span>

                    <strong>{fixture.home_team?.name ?? "TBD"}</strong>
                  </div>

                  <div className="fixture-vs">VS</div>

                  <div className="fixture-team-name fixture-team-away">
                    <span>Away</span>

                    <strong>{fixture.away_team?.name ?? "TBD"}</strong>
                  </div>
                </div>

                <div className="fixture-information">
                  <div>
                    <span>Date & time</span>

                    <strong>
                      {new Date(fixture.scheduled_at).toLocaleString()}
                    </strong>
                  </div>

                  <div>
                    <span>Venue</span>

                    <strong>{fixture.venue?.name ?? "Venue TBD"}</strong>
                  </div>

                  <div>
                    <span>Status</span>

                    <strong className="fixture-status-pill">
                      {fixture.status}
                    </strong>
                  </div>

                  {fixture.round && (
                    <div>
                      <span>Round</span>

                      <strong>{fixture.round}</strong>
                    </div>
                  )}
                </div>

                {fixture.match_number && (
                  <div className="fixture-match-number">
                    <span>Match</span>

                    <strong>{fixture.match_number}</strong>
                  </div>
                )}
              </article>
            ))}
          </div>
        ) : (
          <div className="tournament-empty-state">
            <div className="tournament-empty-icon">F</div>

            <div>
              <strong>No fixtures scheduled</strong>

              <p>
                {tournament.teams?.length && tournament.teams.length >= 2
                  ? "The tournament is ready for fixture scheduling."
                  : "Register at least two teams before scheduling fixtures."}
              </p>
            </div>

            {tournament.teams?.length && tournament.teams.length >= 2 && (
              <button
                type="button"
                onClick={() =>
                  navigate(`/organizations/${organizationId}/fixtures`)
                }
              >
                Schedule first fixture
              </button>
            )}
          </div>
        )}
      </section>
    </AppLayout>
  );
}
