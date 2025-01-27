<?php

namespace App\Http\Controllers\Admin\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Bookings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class PendapatanController extends Controller
{
    private function getBaseQuery($startDate, $endDate)
    {
        return Bookings::select([
            'bookings.booking_date',
            'bookings.payment_id',
            DB::raw("CONCAT(l1.name, ' - ', l2.name) as rute_name"),
            'c.class_name as kelas',
            'bookings.final_price',
            DB::raw('(SELECT COUNT(*) FROM passengers WHERE passengers.booking_id = bookings.id) as jumlah_tiket'),
            'bookings.final_price as total_pendapatan'
        ])
        ->join('schedule_rute as sr', 'bookings.schedule_id', '=', 'sr.id')
        ->join('schedules as s', 'sr.schedule_id', '=', 's.id')
        ->join('buses as b', 's.bus_id', '=', 'b.id')
        ->join('classes as c', 'b.class_id', '=', 'c.id')
        ->join('routes as r', 'sr.route_id', '=', 'r.id')
        ->join('locations as l1', 'r.start_location_id', '=', 'l1.id')
        ->join('locations as l2', 'r.end_location_id', '=', 'l2.id')
        ->where('bookings.payment_status', 'PAID')
        ->when($startDate, function($q) use ($startDate) {
            return $q->whereDate('bookings.booking_date', '>=', $startDate);
        })
        ->when($endDate, function($q) use ($endDate) {
            return $q->whereDate('bookings.booking_date', '<=', $endDate);
        })
        ->orderBy('bookings.booking_date', 'desc');
    }

    public function getSummaryData($startDate, $endDate)
    {
        $totalTiket = DB::table('bookings')
            ->join('passengers', 'bookings.id', '=', 'passengers.booking_id')
            ->where('bookings.payment_status', 'PAID')
            ->when($startDate, function($q) use ($startDate) {
                return $q->whereDate('bookings.booking_date', '>=', $startDate);
            })
            ->when($endDate, function($q) use ($endDate) {
                return $q->whereDate('bookings.booking_date', '<=', $endDate);
            })
            ->count('passengers.id');

        $totalPendapatan = DB::table('bookings')
            ->where('bookings.payment_status', 'PAID')
            ->when($startDate, function($q) use ($startDate) {
                return $q->whereDate('bookings.booking_date', '>=', $startDate);
            })
            ->when($endDate, function($q) use ($endDate) {
                return $q->whereDate('bookings.booking_date', '<=', $endDate);
            })
            ->sum('final_price');

        return [
            'total_tiket' => (int)$totalTiket,
            'total_pendapatan' => (int)$totalPendapatan
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
            $total = $clone->get()->count();

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
                'message' => 'Gagal mengambil data laporan pendapatan',
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
            Log::info('Downloading revenue report', [
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
            $sheet->mergeCells('A1:G1');
            $sheet->setCellValue('A1', 'LAPORAN PENDAPATAN');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Set periode laporan
            $sheet->mergeCells('A2:G2');
            $periode = 'Periode: ' . ($startDate ? Carbon::parse($startDate)->format('d/m/Y') : 'All Time');
            $periode .= ' - ' . ($endDate ? Carbon::parse($endDate)->format('d/m/Y') : 'Now');
            $sheet->setCellValue('A2', $periode);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Set header kolom
            $headers = ['No', 'Tanggal', 'ID Pembayaran', 'Rute', 'Kelas', 'Jumlah Tiket', 'Total Pendapatan'];
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
            $sheet->getStyle('A4:G4')->applyFromArray($headerStyle);

            // Isi data
            $row = 5;
            $no = 1;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $no);
                $sheet->setCellValue('B' . $row, Carbon::parse($item->booking_date)->format('d/m/Y H:i'));
                $sheet->setCellValue('C' . $row, $item->payment_id);
                $sheet->setCellValue('D' . $row, $item->rute_name);
                $sheet->setCellValue('E' . $row, $item->kelas);
                $sheet->setCellValue('F' . $row, $item->jumlah_tiket);
                $sheet->setCellValue('G' . $row, $item->total_pendapatan);

                // Format number
                $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');

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
            $sheet->getStyle('A4:G' . ($row - 1))->applyFromArray($dataStyle);

            // Set total
            $row += 1;
            $sheet->mergeCells('A' . $row . ':E' . $row);
            $sheet->setCellValue('A' . $row, 'TOTAL');
            $sheet->setCellValue('F' . $row, $summary['total_tiket']);
            $sheet->setCellValue('G' . $row, $summary['total_pendapatan']);

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
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($totalStyle);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');

            // Auto size columns
            foreach (range('A', 'G') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Create Excel file
            $writer = new Xlsx($spreadsheet);
            $filename = 'Laporan_Pendapatan_' . date('Y-m-d_His') . '.xlsx';
            
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

            Log::info('Revenue report generated successfully', [
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
                'message' => 'Gagal mengunduh laporan pendapatan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 