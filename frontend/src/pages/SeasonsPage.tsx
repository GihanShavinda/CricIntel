import axios from "axios";

import { useEffect, useMemo, useState } from "react";

import { useParams } from "react-router-dom";

import {
  createSeason,
  deleteSeason,
  listSeasons,
  listTeams,
  syncSeasonTeams,
} from "../api/organizations";

import { AppLayout } from "../components/AppLayout";

import { DataTable } from "../components/DataTable";

import { ModalDialog } from "../components/ModalDialog";

import { SeasonForm } from "../components/organization/SeasonForm";

import type { Season, Team } from "../types/organization";

export function SeasonsPage() {
  const organizationId = Number(useParams().organizationId);

  const [rows, setRows] = useState<Season[]>([]);

  const [teams, setTeams] = useState<Team[]>([]);

  const [open, setOpen] = useState(false);

  const [teamModalOpen, setTeamModalOpen] = useState(false);

  const [selectedSeason, setSelectedSeason] = useState<Season | null>(null);

  const [selectedTeamIds, setSelectedTeamIds] = useState<number[]>([]);

  const [savingTeams, setSavingTeams] = useState(false);

  const [loading, setLoading] = useState(true);

  const [message, setMessage] = useState("");

  const [errorMessage, setErrorMessage] = useState("");

  /*
  |--------------------------------------------------------------------------
  | Load Seasons + Teams
  |--------------------------------------------------------------------------
  */

  const load = async () => {
    setLoading(true);

    setErrorMessage("");

    try {
      const [seasonResult, teamResult] = await Promise.all([
        listSeasons(organizationId, {
          per_page: 100,
          sort: "start_date",
          direction: "desc",
        }),

        listTeams(organizationId, {
          per_page: 100,
        }),
      ]);

      setRows(seasonResult.data);

      setTeams(teamResult.data);
    } catch (error) {
      console.error("Unable to load seasons:", error);

      if (axios.isAxiosError(error)) {
        setErrorMessage(
          error.response?.data?.message ?? "Unable to load seasons.",
        );
      } else {
        setErrorMessage("Unable to load seasons.");
      }
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, [organizationId]);

  /*
  |--------------------------------------------------------------------------
  | Open Team Assignment
  |--------------------------------------------------------------------------
  */

  const openTeamManager = (season: Season) => {
    setSelectedSeason(season);

    /*
      |--------------------------------------------------------------------------
      | If the backend includes season.teams, use those IDs.
      |
      | If only teams_count is returned, it starts empty and you can select
      | teams before saving.
      |--------------------------------------------------------------------------
      */

    const existingIds = season.teams?.map((team) => team.id) ?? [];

    setSelectedTeamIds(existingIds);

    setMessage("");
    setErrorMessage("");

    setTeamModalOpen(true);
  };

  /*
  |--------------------------------------------------------------------------
  | Toggle Team
  |--------------------------------------------------------------------------
  */

  const toggleTeam = (teamId: number) => {
    setSelectedTeamIds((current) =>
      current.includes(teamId)
        ? current.filter((id) => id !== teamId)
        : [...current, teamId],
    );
  };

  /*
  |--------------------------------------------------------------------------
  | Select All
  |--------------------------------------------------------------------------
  */

  const selectAllTeams = () => {
    setSelectedTeamIds(teams.map((team) => team.id));
  };

  /*
  |--------------------------------------------------------------------------
  | Clear All
  |--------------------------------------------------------------------------
  */

  const clearTeams = () => {
    setSelectedTeamIds([]);
  };

  /*
  |--------------------------------------------------------------------------
  | Save Season Teams
  |--------------------------------------------------------------------------
  */

  const saveSeasonTeams = async () => {
    if (!selectedSeason) {
      return;
    }

    setSavingTeams(true);

    setMessage("");
    setErrorMessage("");

    try {
      await syncSeasonTeams(organizationId, selectedSeason.id, selectedTeamIds);

      setMessage("Season teams updated successfully.");

      await load();

      setTeamModalOpen(false);

      setSelectedSeason(null);
    } catch (error) {
      console.error("Unable to save season teams:", error);

      if (axios.isAxiosError(error)) {
        const response = error.response?.data;

        const validationErrors = response?.errors;

        const firstError = validationErrors
          ? Object.values(validationErrors).flat().at(0)
          : null;

        setErrorMessage(
          String(
            firstError ?? response?.message ?? "Unable to update season teams.",
          ),
        );
      } else {
        setErrorMessage("Unable to update season teams.");
      }
    } finally {
      setSavingTeams(false);
    }
  };

  /*
  |--------------------------------------------------------------------------
  | Team Count
  |--------------------------------------------------------------------------
  */

  const selectedCount = useMemo(
    () => selectedTeamIds.length,
    [selectedTeamIds],
  );

  /*
  |--------------------------------------------------------------------------
  | Render
  |--------------------------------------------------------------------------
  */

  return (
    <AppLayout>
      <div className="page-heading">
        <div>
          <p className="eyebrow">Organization</p>

          <h1>Seasons</h1>

          <p>
            Manage competition seasons and assign existing organization teams to
            each season.
          </p>
        </div>

        <button type="button" onClick={() => setOpen(true)}>
          New season
        </button>
      </div>

      {message && <div className="form-success-message">{message}</div>}

      {errorMessage && <div className="form-error-message">{errorMessage}</div>}

      {loading ? (
        <div className="page-state">Loading seasons...</div>
      ) : (
        <DataTable
          rows={rows}
          columns={[
            {
              key: "name",

              header: "Season",

              render: (row) => row.name,
            },

            {
              key: "start",

              header: "Start",

              render: (row) => row.start_date,
            },

            {
              key: "end",

              header: "End",

              render: (row) => row.end_date,
            },

            {
              key: "status",

              header: "Status",

              render: (row) => (
                <span className="status-badge">{row.status}</span>
              ),
            },

            {
              key: "teams",

              header: "Teams",

              render: (row) => (
                <div className="season-team-count">
                  <strong>{row.teams_count ?? row.teams?.length ?? 0}</strong>

                  <span>assigned</span>
                </div>
              ),
            },

            {
              key: "actions",

              header: "Actions",

              render: (row) => (
                <div className="table-actions">
                  <button type="button" onClick={() => openTeamManager(row)}>
                    Manage Teams
                  </button>

                  <button
                    type="button"
                    className="danger"
                    onClick={async () => {
                      const confirmed = window.confirm(
                        `Delete season "${row.name}"?`,
                      );

                      if (!confirmed) {
                        return;
                      }

                      await deleteSeason(organizationId, row.id);

                      await load();
                    }}
                  >
                    Delete
                  </button>
                </div>
              ),
            },
          ]}
        />
      )}

      {/* =====================================================
          CREATE SEASON MODAL
          ===================================================== */}

      <ModalDialog
        open={open}
        title="Create season"
        onClose={() => setOpen(false)}
      >
        <SeasonForm
          onCancel={() => setOpen(false)}
          onSubmit={async (payload) => {
            await createSeason(organizationId, payload);

            setOpen(false);

            await load();
          }}
        />
      </ModalDialog>

      {/* =====================================================
          MANAGE SEASON TEAMS MODAL
          ===================================================== */}

      <ModalDialog
        open={teamModalOpen}
        title={
          selectedSeason
            ? `Manage Teams · ${selectedSeason.name}`
            : "Manage Season Teams"
        }
        onClose={() => {
          if (savingTeams) {
            return;
          }

          setTeamModalOpen(false);

          setSelectedSeason(null);
        }}
      >
        <div className="season-team-manager">
          <div className="season-team-manager-header">
            <div>
              <h3>Season teams</h3>

              <p>Select existing teams that participate in this season.</p>
            </div>

            <span className="season-selected-count">
              {selectedCount} selected
            </span>
          </div>

          <div className="team-registration-actions">
            <button
              type="button"
              className="secondary"
              onClick={selectAllTeams}
              disabled={savingTeams || teams.length === 0}
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
          </div>

          {teams.length ? (
            <div className="season-team-grid">
              {teams.map((team) => {
                const selected = selectedTeamIds.includes(team.id);

                return (
                  <label
                    key={team.id}
                    className={
                      selected
                        ? "season-team-option selected"
                        : "season-team-option"
                    }
                  >
                    <input
                      type="checkbox"
                      checked={selected}
                      onChange={() => toggleTeam(team.id)}
                    />

                    <div className="season-team-details">
                      <strong>{team.name}</strong>

                      <span>{team.short_name ?? "No short name"}</span>
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
            <div className="page-state">
              No teams exist in this organization yet.
            </div>
          )}

          <div className="form-actions">
            <button
              type="button"
              className="secondary"
              disabled={savingTeams}
              onClick={() => {
                setTeamModalOpen(false);

                setSelectedSeason(null);
              }}
            >
              Cancel
            </button>

            <button
              type="button"
              disabled={savingTeams}
              onClick={() => void saveSeasonTeams()}
            >
              {savingTeams ? "Saving..." : "Save Teams"}
            </button>
          </div>
        </div>
      </ModalDialog>
    </AppLayout>
  );
}
