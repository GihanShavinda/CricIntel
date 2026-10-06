<?php

namespace App\Services\Reports\Exporters;

use App\Services\Reports\ReportDocument;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelReportExporter implements ReportExporter
{
    public function export(ReportDocument $document, string $absolutePath): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Report');

        $row = 1;

        $sheet->setCellValue("A{$row}", $document->title);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(16);
        $row++;

        $sheet->setCellValue("A{$row}", $document->subtitle);
        $row += 2;

        foreach ($document->metadata as $label => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", (string) $value);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }

        $row++;

        foreach ($document->summary as $item) {
            $sheet->setCellValue("A{$row}", $item['label']);
            $sheet->setCellValue("B{$row}", $item['value']);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }

        foreach ($document->tables as $tableIndex => $table) {
            $row += 2;
            $sheet->setCellValue("A{$row}", $table['title']);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
            $row++;

            $column = 1;
            foreach ($table['columns'] as $heading) {
                $sheet->setCellValue([$column, $row], $heading);
                $sheet->getStyle([$column, $row])->getFont()->setBold(true);
                $column++;
            }

            $row++;

            foreach ($table['rows'] as $dataRow) {
                $column = 1;

                foreach ($dataRow as $value) {
                    $sheet->setCellValue([$column, $row], $value);
                    $column++;
                }

                $row++;
            }
        }

        foreach (range('A', 'N') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        if ($document->charts !== []) {
            $chartSheet = $spreadsheet->createSheet();
            $chartSheet->setTitle('Chart Data');

            $row = 1;

            foreach ($document->charts as $chart) {
                $chartSheet->setCellValue("A{$row}", $chart['title']);
                $chartSheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;

                $chartSheet->setCellValue("A{$row}", 'Label');
                $chartSheet->setCellValue("B{$row}", 'Value');
                $row++;

                foreach ($chart['labels'] as $index => $label) {
                    $chartSheet->setCellValue("A{$row}", $label);
                    $chartSheet->setCellValue("B{$row}", $chart['values'][$index] ?? 0);
                    $row++;
                }

                $row += 2;
            }

            $chartSheet->getColumnDimension('A')->setAutoSize(true);
            $chartSheet->getColumnDimension('B')->setAutoSize(true);
        }

        (new Xlsx($spreadsheet))->save($absolutePath);
    }

    public function mimeType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }
}
