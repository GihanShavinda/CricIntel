import { api } from './client';
import type {
  LiveMatchSnapshot,
} from '../types/liveMatch';

type ApiResponse<T> = {
  data: T;
};

export async function getLiveMatch(
  organizationId: number,
  matchId: number
): Promise<LiveMatchSnapshot> {
  const response =
    await api.get<
      ApiResponse<LiveMatchSnapshot>
    >(
      `/api/v1/organizations/${organizationId}/matches/${matchId}/live`
    );

  return response.data.data;
}
