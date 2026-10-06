import {
  api,
} from './client';

import type {
  StrategyAssistantAnswer,
  StrategyAssistantContextPayload,
  StrategyAssistantHistoryRow,
  StrategyAssistantMatchOption,
  StrategyAssistantStatus,
} from '../types/strategyAssistant';

type DataResponse<T> = {
  data: T;
  message?: string;
};

export async function getStrategyAssistantStatus(
  organizationId: number,
) {
  const response =
    await api.get<DataResponse<StrategyAssistantStatus>>(
      `/api/v1/organizations/${organizationId}/strategy-assistant/status`,
    );

  return response.data.data;
}

export async function getStrategyAssistantOptions(
  organizationId: number,
) {
  const response =
    await api.get<DataResponse<{
      matches: StrategyAssistantMatchOption[];
      example_questions: string[];
    }>>(
      `/api/v1/organizations/${organizationId}/strategy-assistant/options`,
    );

  return response.data.data;
}

export async function getStrategyAssistantContext(
  organizationId: number,
  matchId: number,
) {
  const response =
    await api.get<DataResponse<StrategyAssistantContextPayload>>(
      `/api/v1/organizations/${organizationId}/strategy-assistant/matches/${matchId}/context`,
    );

  return response.data.data;
}

export async function askStrategyAssistant(
  organizationId: number,
  matchId: number,
  question: string,
) {
  const response =
    await api.post<DataResponse<StrategyAssistantAnswer>>(
      `/api/v1/organizations/${organizationId}/strategy-assistant/matches/${matchId}/ask`,
      {
        question,
      },
    );

  return response.data.data;
}

export async function getStrategyAssistantHistory(
  organizationId: number,
  matchId?: number,
) {
  const response =
    await api.get<DataResponse<StrategyAssistantHistoryRow[]>>(
      `/api/v1/organizations/${organizationId}/strategy-assistant/history`,
      {
        params: {
          match_id:
            matchId ||
            undefined,
        },
      },
    );

  return response.data.data;
}
