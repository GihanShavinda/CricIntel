import { Navigate, Route, Routes } from "react-router-dom";

import { ProtectedRoute } from "./auth/ProtectedRoute";
import { RoleRoute } from "./auth/RoleRoute";

import { AdminPage } from "./pages/AdminPage";
import { AnalyticsDashboardPage } from "./pages/AnalyticsDashboardPage";
import { ClubsPage } from "./pages/ClubsPage";
import { DashboardPage } from "./pages/DashboardPage";
import { FixturesPage } from "./pages/FixturesPage";
import { LiveMatchCentrePage } from "./pages/LiveMatchCentrePage";
import { LoginPage } from "./pages/LoginPage";
import { MatchOperatorPage } from "./pages/MatchOperatorPage";
import { MatchScorecardPage } from "./pages/MatchScorecardPage";
import { MatchSquadPage } from "./pages/MatchSquadPage";
import { MatchesPage } from "./pages/MatchesPage";
import { OrganizationDetailsPage } from "./pages/OrganizationDetailsPage";
import { OrganizationsPage } from "./pages/OrganizationsPage";
import { OpponentIntelligencePage } from "./pages/OpponentIntelligencePage";
import { PlayerComparisonPage } from "./pages/PlayerComparisonPage";
import { PredictiveAnalyticsPage } from "./pages/PredictiveAnalyticsPage";
import { PlayerCreatePage } from "./pages/PlayerCreatePage";
import { PlayerEditPage } from "./pages/PlayerEditPage";
import { PlayerProfilePage } from "./pages/PlayerProfilePage";
import { PlayersPage } from "./pages/PlayersPage";
import { PlayingXiPage } from "./pages/PlayingXiPage";
import { RegisterPage } from "./pages/RegisterPage";
import { SeasonsPage } from "./pages/SeasonsPage";
import { ScoutingComparePage } from "./pages/ScoutingComparePage";
import { ScoutingDashboardPage } from "./pages/ScoutingDashboardPage";
import { ScoutingProfilePage } from "./pages/ScoutingProfilePage";
import { ScoutingReportPage } from "./pages/ScoutingReportPage";
import { StrategyPlansPage } from "./pages/StrategyPlansPage";
import { TacticalWorkspacePage } from "./pages/TacticalWorkspacePage";
import { StrategyAssistantPage } from "./pages/StrategyAssistantPage";
import { NaturalLanguageAnalyticsPage } from "./pages/NaturalLanguageAnalyticsPage";
import { NotificationsPage } from "./pages/NotificationsPage";
import { NotificationPreferencesPage } from "./pages/NotificationPreferencesPage";
import { ReportsPage } from "./pages/ReportsPage";

import { TeamsPage } from "./pages/TeamsPage";
import { TrainingCalendarPage } from "./pages/TrainingCalendarPage";
import { TrainingSessionPage } from "./pages/TrainingSessionPage";
import { PlayerDevelopmentPage } from "./pages/PlayerDevelopmentPage";
import { TournamentDetailsPage } from "./pages/TournamentDetailsPage";
import { TournamentSquadPage } from "./pages/TournamentSquadPage";
import { TournamentsPage } from "./pages/TournamentsPage";
import { VenuesPage } from "./pages/VenuesPage";

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

      {/*
      |--------------------------------------------------------------------------
      | P9 - Tournament Squad
      |--------------------------------------------------------------------------
      */}
      <Route
        path="/organizations/:organizationId/tournaments/:tournamentId/teams/:teamId/squad"
        element={
          <ProtectedRoute>
            <TournamentSquadPage />
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

      {/*
      |--------------------------------------------------------------------------
      | P9 - Match Squad
      |--------------------------------------------------------------------------
      */}
      <Route
        path="/organizations/:organizationId/matches/:matchId/teams/:teamId/squad"
        element={
          <ProtectedRoute>
            <MatchSquadPage />
          </ProtectedRoute>
        }
      />

      {/*
      |--------------------------------------------------------------------------
      | P9 - Playing XI / Batting Order / Bowling Roles
      |--------------------------------------------------------------------------
      */}
      <Route
        path="/organizations/:organizationId/matches/:matchId/teams/:teamId/playing-xi"
        element={
          <ProtectedRoute>
            <PlayingXiPage />
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
        path="/organizations/:organizationId/matches/:matchId/live"
        element={
          <ProtectedRoute>
            <LiveMatchCentrePage />
          </ProtectedRoute>
        }
      />

      {/*
      |--------------------------------------------------------------------------
      | P10 - Training & Player Development
      |--------------------------------------------------------------------------
      */}
      <Route
        path="/organizations/:organizationId/training"
        element={
          <ProtectedRoute>
            <TrainingCalendarPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/training/sessions/:sessionId"
        element={
          <ProtectedRoute>
            <TrainingSessionPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/players/:playerId/development"
        element={
          <ProtectedRoute>
            <PlayerDevelopmentPage />
          </ProtectedRoute>
        }
      />

      {/*
      |--------------------------------------------------------------------------
      | P11 - Scouting & Recruitment
      |--------------------------------------------------------------------------
      */}
      <Route
        path="/organizations/:organizationId/scouting"
        element={
          <ProtectedRoute>
            <ScoutingDashboardPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/scouting/profiles/:profileId"
        element={
          <ProtectedRoute>
            <ScoutingProfilePage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/scouting/reports/:reportId"
        element={
          <ProtectedRoute>
            <ScoutingReportPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/scouting/compare"
        element={
          <ProtectedRoute>
            <ScoutingComparePage />
          </ProtectedRoute>
        }
      />

      {/*
      |--------------------------------------------------------------------------
      | P12 - Deterministic Opponent Intelligence
      |--------------------------------------------------------------------------
      */}
      <Route
        path="/organizations/:organizationId/opponent-intelligence"
        element={
          <ProtectedRoute>
            <OpponentIntelligencePage />
          </ProtectedRoute>
        }
      />

      {/*
      |--------------------------------------------------------------------------
      | P13 - Tactical Planning Workspace
      |--------------------------------------------------------------------------
      */}
      <Route
        path="/organizations/:organizationId/strategy"
        element={
          <ProtectedRoute>
            <StrategyPlansPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/organizations/:organizationId/strategy/:planId"
        element={
          <ProtectedRoute>
            <TacticalWorkspacePage />
          </ProtectedRoute>
        }
      />

      {/*
      |--------------------------------------------------------------------------
      | P14 - Predictive Analytics
      |--------------------------------------------------------------------------
      */}
      <Route
        path="/organizations/:organizationId/predictive"
        element={
          <ProtectedRoute>
            <PredictiveAnalyticsPage />
          </ProtectedRoute>
        }
      />

      {/*
      |--------------------------------------------------------------------------
      | P15 - AI Strategy Assistant
      |--------------------------------------------------------------------------
      */}
      <Route
        path="/organizations/:organizationId/strategy-assistant"
        element={
          <ProtectedRoute>
            <StrategyAssistantPage />
          </ProtectedRoute>
        }
      />

      {/*
      |--------------------------------------------------------------------------
      | P16 - Natural-Language Analytics
      |--------------------------------------------------------------------------
      */}
      <Route
        path="/organizations/:organizationId/nl-analytics"
        element={
          <ProtectedRoute>
            <NaturalLanguageAnalyticsPage />
          </ProtectedRoute>
        }
      />


      {/*
      |--------------------------------------------------------------------------
      | P18 - Professional Reporting
      |--------------------------------------------------------------------------
      */}
      <Route
        path="/organizations/:organizationId/reports"
        element={
          <ProtectedRoute>
            <ReportsPage />
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

      {/*
      |--------------------------------------------------------------------------
      | P17 - Notifications
      |--------------------------------------------------------------------------
      */}
      <Route
        path="/notifications"
        element={
          <ProtectedRoute>
            <NotificationsPage />
          </ProtectedRoute>
        }
      />

      <Route
        path="/notification-preferences"
        element={
          <ProtectedRoute>
            <NotificationPreferencesPage />
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

      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  );
}
