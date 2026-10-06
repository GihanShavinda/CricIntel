import {
  api,
} from './client';

import type {
  ReportDocument,
  ReportExport,
  ReportFilters,
  ReportFormat,
  ReportOptions,
  ReportType,
} from '../types/reports';

export async function getReportOptions(
  organizationId: number,
) {
  const response =
    await api.get<{
      data: ReportOptions;
    }>(
      `/api/v1/organizations/${organizationId}/reports/options`,
    );

  return response.data.data;
}

export async function previewReport(
  organizationId: number,
  payload: {
    report_type: ReportType;
    format: ReportFormat;
    filters: ReportFilters;
  },
) {
  const response =
    await api.post<{
      data: ReportDocument;
    }>(
      `/api/v1/organizations/${organizationId}/reports/preview`,
      payload,
    );

  return response.data.data;
}

export async function generateReport(
  organizationId: number,
  payload: {
    report_type: ReportType;
    format: ReportFormat;
    filters: ReportFilters;
  },
) {
  const response =
    await api.post<{
      data: ReportExport;
    }>(
      `/api/v1/organizations/${organizationId}/reports/exports`,
      payload,
    );

  return response.data.data;
}

export async function listReportExports(
  organizationId: number,
) {
  const response =
    await api.get<{
      data: {
        data: ReportExport[];
      };
    }>(
      `/api/v1/organizations/${organizationId}/reports/exports`,
    );

  return response.data.data.data;
}

export async function getReportExport(
  organizationId: number,
  exportId: number,
) {
  const response =
    await api.get<{
      data: ReportExport;
    }>(
      `/api/v1/organizations/${organizationId}/reports/exports/${exportId}`,
    );

  return response.data.data;
}

export async function downloadReportExport(
  organizationId: number,
  report: ReportExport,
) {
  const response =
    await api.get(
      `/api/v1/organizations/${organizationId}/reports/exports/${report.id}/download`,
      {
        responseType: 'blob',
      },
    );

  const url =
    URL.createObjectURL(
      response.data,
    );

  const anchor =
    document.createElement(
      'a',
    );

  anchor.href = url;
  anchor.download =
    report.filename ??
    `CricIntel_Report_${report.id}.${report.format}`;

  document.body.appendChild(
    anchor,
  );

  anchor.click();
  anchor.remove();

  URL.revokeObjectURL(
    url,
  );
}

export async function deleteReportExport(
  organizationId: number,
  exportId: number,
) {
  await api.delete(
    `/api/v1/organizations/${organizationId}/reports/exports/${exportId}`,
  );
}
