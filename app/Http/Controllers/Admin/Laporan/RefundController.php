<?php

namespace App\Http\Controllers\Admin\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class RefundController extends Controller
{
    private function getBaseQuery($startDate, $endDate)
    {
        return Refund::select([
            'refunds.created_at as tanggal_refund',
            'bookings.payment_id',
            'bookings.name as customer_name',
            'bookings.phone_number',
            DB::raw("CONCAT(l1.name, ' - ', l2.name) as rute_name"),
            'c.class_name as kelas',
            'bookings.final_price as harga_pemesanan',
            'refunds.persentase as potongan',
            'refunds.estimasi_refund as total_refund',
            DB::raw('(refunds.persentase / 100 * bookings.final_price) as pendapatan_refund'),
            'refunds.alasan'
        ])
        ->join('bookings', 'refunds.booking_id', '=', 'bookings.id')
        ->join('schedule_rute as sr', 'bookings.schedule_id', '=', 'sr.id')
        ->join('schedules as s', 'sr.schedule_id', '=', 's.id')
        ->join('buses as b', 's.bus_id', '=', 'b.id')
        ->join('classes as c', 'b.class_id', '=', 'c.id')
        ->join('routes as r', 'sr.route_id', '=', 'r.id')
        ->join('locations as l1', 'r.start_location_id', '=', 'l1.id')
        ->join('locations as l2', 'r.end_location_id', '=', 'l2.id')
        ->when($startDate, function($q) use ($startDate) {
            return $q->whereDate('refunds.created_at', '>=', $startDate);
        })
        ->when($endDate, function($q) use ($endDate) {
            return $q->whereDate('refunds.created_at', '<=', $endDate);
        })
        ->orderBy('refunds.created_at', 'desc');
    }

    public function getSummaryData($startDate, $endDate)
    {
        $totalRefund = DB::table('refunds')
            ->when($startDate, function($q) use ($startDate) {
                return $q->whereDate('created_at', '>=', $startDate);
            })
            ->when($endDate, function($q) use ($endDate) {
                return $q->whereDate('created_at', '<=', $endDate);
            })
            ->count();

        $totalPendapatanRefund = DB::table('refunds')
            ->join('bookings', 'refunds.booking_id', '=', 'bookings.id')
            ->when($startDate, function($q) use ($startDate) {
                return $q->whereDate('refunds.created_at', '>=', $startDate);
            })
            ->when($endDate, function($q) use ($endDate) {
                return $q->whereDate('refunds.created_at', '<=', $endDate);
            })
            ->select(DB::raw('SUM(refunds.persentase / 100 * bookings.final_price) as total_pendapatan'))
            ->value('total_pendapatan');

        return [
            'total_refund' => (int)$totalRefund,
            'total_pendapatan_refund' => (int)$totalPendapatanRefund
        ];
    }

    public function index(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $perPage = $request->input('per_page', 10);
            $page = $request->input('page', 1);

            $query = $this->getBaseQuery($startDate, $endDate);

            $clone = clone $query;
            $total = $clone->count();

            $results = $query->offset(($page - 1) * $perPage)
                           ->limit($perPage)
                           ->get();

            $summary = $this->getSummaryData($startDate, $endDate);

            return response()->json([
                'status' => true,
                'data' => [
                    'laporan' => $results->map(function($item) {
                        return [
                            'tanggal_refund' => Carbon::parse($item->tanggal_refund)->format('d/m/Y H:i'),
                            'payment_id' => $item->payment_id,
                            'customer_name' => $item->customer_name,
                            'phone_number' => $item->phone_number,
                            'rute' => $item->rute_name,
                            'kelas' => $item->kelas,
                            'harga_pemesanan' => (int)$item->harga_pemesanan,
                            'potongan' => $item->potongan . '%',
                            'total_refund' => (int)$item->total_refund,
                            'pendapatan_refund' => (int)$item->pendapatan_refund,
                            'alasan' => $item->alasan
                        ];
                    }),
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
                'message' => 'Gagal mengambil data laporan refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function download(Request $request)
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            Log::info('Downloading refund report', [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);

            $data = $this->getBaseQuery($startDate, $endDate)->get();
            if ($data->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak ada data untuk periode yang dipilih'
                ], 404);
            }
            
            $summary = $this->getSummaryData($startDate, $endDate);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->mergeCells('A1:K1');
            $sheet->setCellValue('A1', 'LAPORAN REFUND');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A2:K2');
            $periode = 'Periode: ' . ($startDate ? Carbon::parse($startDate)->format('d/m/Y') : 'All Time');
            $periode .= ' - ' . ($endDate ? Carbon::parse($endDate)->format('d/m/Y') : 'Now');
            $sheet->setCellValue('A2', $periode);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $headers = ['No', 'Tanggal', 'ID Pembayaran', 'Nama Customer', 'No. Telepon', 'Rute', 'Kelas', 'Harga Pemesanan', 'Potongan', 'Total Refund', 'Pendapatan Refund'];
            foreach ($headers as $index => $header) {
                $sheet->setCellValue(chr(65 + $index) . '4', $header);
            }

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
            $sheet->getStyle('A4:K4')->applyFromArray($headerStyle);

            $row = 5;
            $no = 1;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $no);
                $sheet->setCellValue('B' . $row, Carbon::parse($item->tanggal_refund)->format('d/m/Y H:i'));
                $sheet->setCellValue('C' . $row, $item->payment_id);
                $sheet->setCellValue('D' . $row, $item->customer_name);
                $sheet->setCellValue('E' . $row, $item->phone_number);
                $sheet->setCellValue('F' . $row, $item->rute_name);
                $sheet->setCellValue('G' . $row, $item->kelas);
                $sheet->setCellValue('H' . $row, $item->harga_pemesanan);
                $sheet->setCellValue('I' . $row, $item->potongan . '%');
                $sheet->setCellValue('J' . $row, $item->total_refund);
                $sheet->setCellValue('K' . $row, $item->pendapatan_refund);

                $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('#,##0');

                $no++;
                $row++;
            }

            $dataStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
            $sheet->getStyle('A4:K' . ($row - 1))->applyFromArray($dataStyle);

            $row += 1;
            $sheet->mergeCells('A' . $row . ':I' . $row);
            $sheet->setCellValue('A' . $row, 'TOTAL');
            $sheet->setCellValue('J' . $row, $summary['total_refund'] . ' Refund');
            $sheet->setCellValue('K' . $row, $summary['total_pendapatan_refund']);

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
            $sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($totalStyle);
            $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('#,##0');

            foreach (range('A', 'K') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $filename = 'Laporan_Refund_' . date('Y-m-d_His') . '.xlsx';
            $directory = storage_path('app/public/reports');
            if (!file_exists($directory)) {
                if (!mkdir($directory, 0777, true)) {
                    throw new \Exception('Gagal membuat direktori untuk menyimpan laporan');
                }
            }
            
            if (!is_writable($directory)) {
                throw new \Exception('Direktori tidak bisa ditulis: ' . $directory);
            }
            
            $path = $directory . '/' . $filename;
            $writer = new Xlsx($spreadsheet);
            $writer->save($path);

            if (!file_exists($path)) {
                throw new \Exception('File tidak berhasil dibuat: ' . $path);
            }

            Log::info('Refund report generated successfully', [
                'filename' => $filename,
                'path' => $path
            ]);

            return response()->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Error generating refund report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengunduh laporan refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 