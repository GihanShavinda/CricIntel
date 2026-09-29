import { api } from "./client";
import type {
  BattingOrderEntry,
  BowlingAssignment,
  MatchSquad,
  PlayingXiEntry,
  SelectionCandidate,
  TournamentSquad,
} from "../types/selection";
type R<T> = { data: T };
export async function getTournamentSquad(o: number, t: number, team: number) {
  return (
    await api.get<R<TournamentSquad | null>>(
      `/api/v1/organizations/${o}/tournaments/${t}/teams/${team}/squad`,
    )
  ).data.data;
}
export async function createTournamentSquad(
  o: number,
  t: number,
  team: number,
  p: { name: string; min_players?: number; max_players?: number },
) {
  return (
    await api.post<R<TournamentSquad>>(
      `/api/v1/organizations/${o}/tournaments/${t}/teams/${team}/squad`,
      p,
    )
  ).data.data;
}
export async function syncTournamentSquadPlayers(
  o: number,
  s: number,
  ids: number[],
) {
  return (
    await api.put<R<TournamentSquad>>(
      `/api/v1/organizations/${o}/squads/${s}/players`,
      { player_ids: ids },
    )
  ).data.data;
}
export async function finalizeTournamentSquad(o: number, s: number) {
  return (
    await api.post<R<TournamentSquad>>(
      `/api/v1/organizations/${o}/squads/${s}/finalize`,
    )
  ).data.data;
}
export async function getMatchSquad(o: number, m: number, t: number) {
  return (
    await api.get<R<MatchSquad | null>>(
      `/api/v1/organizations/${o}/matches/${m}/teams/${t}/squad`,
    )
  ).data.data;
}
export async function listSelectionCandidates(o: number, m: number, t: number) {
  return (
    await api.get<R<SelectionCandidate[]>>(
      `/api/v1/organizations/${o}/matches/${m}/teams/${t}/selection/candidates`,
    )
  ).data.data;
}
export async function saveMatchSquad(
  o: number,
  m: number,
  t: number,
  p: {
    tournament_squad_id?: number | null;
    players: Array<{
      player_id: number;
      override?: boolean;
      override_reason?: string | null;
      reason?: string | null;
    }>;
  },
) {
  return (
    await api.post<R<MatchSquad>>(
      `/api/v1/organizations/${o}/matches/${m}/teams/${t}/squad`,
      p,
    )
  ).data.data;
}
export async function savePlayingXi(
  o: number,
  m: number,
  t: number,
  players: PlayingXiEntry[],
) {
  return (
    await api.put<R<MatchSquad>>(
      `/api/v1/organizations/${o}/matches/${m}/teams/${t}/playing-xi`,
      { players },
    )
  ).data.data;
}
export async function confirmPlayingXi(o: number, m: number, t: number) {
  return (
    await api.post<R<MatchSquad>>(
      `/api/v1/organizations/${o}/matches/${m}/teams/${t}/playing-xi/confirm`,
    )
  ).data.data;
}
export async function saveBattingOrder(
  o: number,
  m: number,
  t: number,
  players: BattingOrderEntry[],
) {
  return (
    await api.put<R<MatchSquad>>(
      `/api/v1/organizations/${o}/matches/${m}/teams/${t}/batting-order`,
      { players },
    )
  ).data.data;
}
export async function saveBowlingAssignments(
  o: number,
  m: number,
  t: number,
  assignments: BowlingAssignment[],
) {
  return (
    await api.put<R<MatchSquad>>(
      `/api/v1/organizations/${o}/matches/${m}/teams/${t}/bowling-assignments`,
      { assignments },
    )
  ).data.data;
}
