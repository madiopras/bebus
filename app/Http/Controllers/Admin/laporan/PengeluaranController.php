<?php

namespace App\Http\Controllers\Admin\Laporan;

use App\Http\Controllers\Controller;
use App\Models\UtilityBBM;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class PengeluaranController extends Controller
{
    private function getBaseQuery($startDate, $endDate)
    {
        return UtilityBBM::select([
            'utility_bbm.tanggal',
            DB::raw("'BBM' as jenis_pengeluaran"),
            'utility_bbm.description',
            'utility_bbm.total_aktual_harga_bbm as jumlah_pengeluaran'
        ])
        ->when($startDate, function($q) use ($startDate) {
            return $q->whereDate('tanggal', '>=', $startDate);
        })
        ->when($endDate, function($q) use ($endDate) {
            return $q->whereDate('tanggal', '<=', $endDate);
        })
        ->orderBy('tanggal', 'desc');
    }

    public function getSummaryData($startDate, $endDate)
    {
        $totalPengeluaran = UtilityBBM::when($startDate, function($q) use ($startDate) {
                return $q->whereDate('tanggal', '>=', $startDate);
            })
            ->when($endDate, function($q) use ($endDate) {
                return $q->whereDate('tanggal', '<=', $endDate);
            })
            ->sum('total_aktual_harga_bbm');

        return [
            'total_pengeluaran' => (int)$totalPengeluaran
        ];
    }

    public function index(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            // Query untuk data laporan
            $query = $this->getBaseQuery($startDate, $endDate);

            // Hitung total untuk pagination
            $clone = clone $query;
            $total = $clone->count();

            // Ambil data dengan pagination
            $results = $query->offset(($page - 1) * $perPage)
                           ->limit($perPage)
                           ->get();

            // Get summary data
            $summary = $this->getSummaryData($startDate, $endDate);

            return response()->json([
                'status' => true,
                'data' => [
                    'laporan' => $results,
                    'summary' => $summary,
                    'pagination' => [
                        'current_page' => (int)$page,
                        'per_page' => (int)$perPage,
                        'total_items' => $total,
                        'total_pages' => ceil($total / $perPage)
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data laporan pengeluaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function download(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Log request parameters
            Log::info('Downloading expense report', [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            // Ambil semua data tanpa pagination
            $data = $this->getBaseQuery($startDate, $endDate)->get();
            if ($data->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada data untuk periode yang dipilih'
                ], 404);
            }
            
            // Get summary data
            $summary = $this->getSummaryData($startDate, $endDate);

            // Buat spreadsheet baru
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set judul laporan
            $sheet->mergeCells('A1:D1');
            $sheet->setCellValue('A1', 'LAPORAN PENGELUARAN');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Set periode laporan
            $sheet->mergeCells('A2:D2');
            $periode = 'Periode: ' . ($startDate ? Carbon::parse($startDate)->format('d/m/Y') : 'All Time');
            $periode .= ' - ' . ($endDate ? Carbon::parse($endDate)->format('d/m/Y') : 'Now');
            $sheet->setCellValue('A2', $periode);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Set header kolom
            $headers = ['No', 'Tanggal', 'Jenis Pengeluaran', 'Keterangan', 'Jumlah Pengeluaran'];
            foreach ($headers as $index => $header) {
                $sheet->setCellValue(chr(65 + $index) . '4', $header);
            }

            // Style header
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
            $sheet->getStyle('A4:E4')->applyFromArray($headerStyle);

            // Isi data
            $row = 5;
            $no = 1;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $no);
                $sheet->setCellValue('B' . $row, Carbon::parse($item->tanggal)->format('d/m/Y'));
                $sheet->setCellValue('C' . $row, $item->jenis_pengeluaran);
                $sheet->setCellValue('D' . $row, $item->description ?? '-');
                $sheet->setCellValue('E' . $row, $item->jumlah_pengeluaran);

                // Format number
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');

                $no++;
                $row++;
            }

            // Style untuk seluruh data
            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
            $sheet->getStyle('A4:E' . ($row - 1))->applyFromArray($dataStyle);

            // Set total
            $row += 1;
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->setCellValue('A' . $row, 'TOTAL');
            $sheet->setCellValue('E' . $row, $summary['total_pengeluaran']);

            // Style total
            $totalStyle = [
                'font' => ['bold' => true],
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
            $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($totalStyle);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');

            // Auto size columns
            foreach (range('A', 'E') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Create Excel file
            $writer = new Xlsx($spreadsheet);
            $filename = 'Laporan_Pengeluaran_' . date('Y-m-d_His') . '.xlsx';
            
            // Pastikan direktori ada dan bisa ditulis
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

            Log::info('Expense report generated successfully', [
                'filename' => $filename,
                'path' => $path
            ]);

            // Return download response
            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            Log::error('PhpSpreadsheet error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat file Excel',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('General error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengunduh laporan pengeluaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 