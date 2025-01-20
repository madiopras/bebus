<?php

namespace App\Http\Controllers\Admin\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class BersihController extends Controller
{
    private function getPendapatanData($startDate, $endDate)
    {
        $pendapatanController = new PendapatanController();
        $summary = $pendapatanController->getSummaryData($startDate, $endDate);
        return $summary['total_pendapatan'];
    }

    private function getPengeluaranData($startDate, $endDate)
    {
        $pengeluaranController = new PengeluaranController();
        $summary = $pengeluaranController->getSummaryData($startDate, $endDate);
        return $summary['total_pengeluaran'];
    }

    public function index(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Get data
            $totalPendapatan = $this->getPendapatanData($startDate, $endDate);
            $totalPengeluaran = $this->getPengeluaranData($startDate, $endDate);
            $labaBersih = $totalPendapatan - $totalPengeluaran;

            // Format response
            $data = [
                [
                    'keterangan' => 'Total Pendapatan',
                    'jumlah' => $totalPendapatan
                ],
                [
                    'keterangan' => 'Total Pengeluaran',
                    'jumlah' => $totalPengeluaran
                ],
                [
                    'keterangan' => 'Laba Bersih',
                    'jumlah' => $labaBersih
                ]
            ];

            return response()->json([
                'status' => true,
                'data' => [
                    'periode' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ],
                    'laporan' => $data
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data laporan bersih',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function download(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Get data
            $totalPendapatan = $this->getPendapatanData($startDate, $endDate);
            $totalPengeluaran = $this->getPengeluaranData($startDate, $endDate);
            $labaBersih = $totalPendapatan - $totalPengeluaran;

            // Create new spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set title
            $sheet->mergeCells('A1:C1');
            $sheet->setCellValue('A1', 'LAPORAN LABA BERSIH');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Set period
            $sheet->mergeCells('A2:C2');
            $periode = 'Periode: ' . ($startDate ? Carbon::parse($startDate)->format('d/m/Y') : 'All Time');
            $periode .= ' - ' . ($endDate ? Carbon::parse($endDate)->format('d/m/Y') : 'Now');
            $sheet->setCellValue('A2', $periode);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Set headers
            $headers = ['No', 'Keterangan', 'Jumlah'];
            foreach ($headers as $index => $header) {
                $sheet->setCellValue(chr(65 + $index) . '4', $header);
            }

            // Style headers
            $headerStyle = [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2EFDA'],
                ],
            ];
            $sheet->getStyle('A4:C4')->applyFromArray($headerStyle);

            // Fill data
            $data = [
                [1, 'Total Pendapatan', $totalPendapatan],
                [2, 'Total Pengeluaran', $totalPengeluaran],
                [3, 'Laba Bersih', $labaBersih]
            ];

            $row = 5;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item[0]);
                $sheet->setCellValue('B' . $row, $item[1]);
                $sheet->setCellValue('C' . $row, $item[2]);
                
                // Format numbers
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');
                
                $row++;
            }

            // Style data
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
            $sheet->getStyle('A4:C' . ($row - 1))->applyFromArray($dataStyle);

            // Auto size columns
            foreach (range('A', 'C') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Create Excel file
            $writer = new Xlsx($spreadsheet);
            $filename = 'Laporan_Laba_Bersih_' . date('Y-m-d_His') . '.xlsx';
            
            // Ensure directory exists and is writable
            $directory = storage_path('app/public/reports');
            if (!file_exists($directory)) {
                if (!mkdir($directory, 0777, true)) {
                    throw new \Exception('Gagal membuat direktori untuk menyimpan laporan');
                }
            }
            
            if (!is_writable($directory)) {
                throw new \Exception('Direktori tidak bisa ditulis: ' . $directory);
            }
            
            // Save file
            $path = $directory . '/' . $filename;
            $writer->save($path);

            if (!file_exists($path)) {
                throw new \Exception('File tidak berhasil dibuat: ' . $path);
            }

            // Return download response
            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengunduh laporan laba bersih',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 