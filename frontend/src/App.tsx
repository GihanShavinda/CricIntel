import { Navigate, Route, Routes } from "react-router-dom";

import { ProtectedRoute } from "./auth/ProtectedRoute";

import { RoleRoute } from "./auth/RoleRoute";

import { AdminPage } from "./pages/AdminPage";

import { AnalyticsDashboardPage } from "./pages/AnalyticsDashboardPage";

import { ClubsPage } from "./pages/ClubsPage";

import { DashboardPage } from "./pages/DashboardPage";

import { FixturesPage } from "./pages/FixturesPage";

import { LoginPage } from "./pages/LoginPage";

import { MatchOperatorPage } from "./pages/MatchOperatorPage";

import { MatchScorecardPage } from "./pages/MatchScorecardPage";

import { MatchesPage } from "./pages/MatchesPage";

import { OrganizationDetailsPage } from "./pages/OrganizationDetailsPage";

import { OrganizationsPage } from "./pages/OrganizationsPage";

import { PlayerComparisonPage } from "./pages/PlayerComparisonPage";

import { PlayerCreatePage } from "./pages/PlayerCreatePage";

import { PlayerEditPage } from "./pages/PlayerEditPage";

import { PlayerProfilePage } from "./pages/PlayerProfilePage";

import { PlayersPage } from "./pages/PlayersPage";

import { RegisterPage } from "./pages/RegisterPage";

import { SeasonsPage } from "./pages/SeasonsPage";

import { TeamsPage } from "./pages/TeamsPage";

import { TournamentDetailsPage } from "./pages/TournamentDetailsPage";

import { TournamentsPage } from "./pages/TournamentsPage";

import { VenuesPage } from "./pages/VenuesPage";

import { LiveMatchCentrePage } from "./pages/LiveMatchCentrePage";

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<Navigate to="/dashboard" replace />} />

      <Route path="/login" element={<LoginPage />} />

      <Route path="/register" element={<RegisterPage />} />

      <Route
        path="/dashboard"
        element={
          <ProtectedRoute>
            <DashboardPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations"
        element={
          <ProtectedRoute>
            <OrganizationsPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId"
        element={
          <ProtectedRoute>
            <OrganizationDetailsPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/clubs"
        element={
          <ProtectedRoute>
            <ClubsPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/teams"
        element={
          <ProtectedRoute>
            <TeamsPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/seasons"
        element={
          <ProtectedRoute>
            <SeasonsPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/players"
        element={
          <ProtectedRoute>
            <PlayersPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/players/new"
        element={
          <ProtectedRoute>
            <PlayerCreatePage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/players/:playerId"
        element={
          <ProtectedRoute>
            <PlayerProfilePage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/players/:playerId/edit"
        element={
          <ProtectedRoute>
            <PlayerEditPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/venues"
        element={
          <ProtectedRoute>
            <VenuesPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/tournaments"
        element={
          <ProtectedRoute>
            <TournamentsPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/tournaments/:tournamentId"
        element={
          <ProtectedRoute>
            <TournamentDetailsPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/fixtures"
        element={
          <ProtectedRoute>
            <FixturesPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/matches"
        element={
          <ProtectedRoute>
            <MatchesPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/matches/:matchId/operator"
        element={
          <ProtectedRoute>
            <MatchOperatorPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/matches/:matchId/scorecard"
        element={
          <ProtectedRoute>
            <MatchScorecardPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/analytics"
        element={
          <ProtectedRoute>
            <AnalyticsDashboardPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/analytics/players/compare"
        element={
          <ProtectedRoute>
            <PlayerComparisonPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/admin"
        element={
          <ProtectedRoute>
            <RoleRoute allowedRoles={["Administrator"]}>
              <AdminPage />
            </RoleRoute>
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/matches/:matchId/live"
        element={
          <ProtectedRoute>
            <LiveMatchCentrePage />
          </ProtectedRoute>
        }
      />

      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  );
}
