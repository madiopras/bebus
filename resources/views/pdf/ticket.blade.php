<!DOCTYPE html>
<html lang="en">
<head>
    @php
        use Carbon\Carbon;
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Tiket Bus</title>
    <style>
        @page {
            margin: 20mm;
            size: A4 portrait;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #000000;
            font-size: 12px;
            line-height: 1.4;
            background: #ffffff;
        }

        .ticket {
            border: 1px solid #cccccc;
            background: #ffffff;
        }

        .header {
            background: #1e40af;
            padding: 20px;
        }

        .logo {
            width: 100px;
            height: auto;
        }

        .ticket-title {
            font-size: 24px;
            margin: 0;
            font-weight: bold;
            color: #ffffff;
        }

        .info-cell {
            background: #ffffff;
            padding: 15px;
            border: 1px solid #cccccc;
            margin: 5px;
        }

        .info-label {
            font-size: 11px;
            color: #666666;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 13px;
            color: #000000;
            font-weight: bold;
            line-height: 1.4;
        }

        .journey-time {
            font-size: 12px;
            color: #1e40af;
            font-weight: bold;
            text-align: center;
            background: #f8fafc;
            padding: 6px 12px;
            display: inline-block;
            margin-top: 10px;
            border-radius: 15px;
            border: 1px solid #bfdbfe;
        }

        .journey-time::before {
            content: 'Durasi Perjalanan';
            display: block;
            font-size: 9px;
            color: #64748b;
            margin-bottom: 2px;
            font-weight: normal;
        }

        .passenger-info {
            padding: 20px;
            background: #ffffff;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 15px;
            text-transform: uppercase;
            padding-left: 10px;
            border-left: 4px solid #1e40af;
        }

        .passenger-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-top: 15px;
        }

        .passenger-table th {
            background: #f0f7ff;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            color: #000000;
            border: 1px solid #cccccc;
        }

        .passenger-table td {
            padding: 12px;
            border: 1px solid #cccccc;
            background: #ffffff;
        }

        .important-notes {
            margin-top: 20px;
            background: #fff7e6;
            padding: 15px;
            border: 1px solid #ffcc80;
        }

        .notes-title {
            font-size: 14px;
            font-weight: bold;
            color: #cc7a00;
            margin-bottom: 10px;
        }

        .notes-list {
            color: #995c00;
            font-size: 12px;
            padding: 0;
            margin: 0;
        }

        .note-item {
            margin-bottom: 8px;
            padding: 8px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 6px;
            display: flex;
            align-items: center;
            position: relative;
            padding-left: 30px;
            border: 1px solid #fde68a;
        }

        .note-number {
            position: absolute;
            left: 6px;
            background: #fbbf24;
            color: white;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 10px;
        }

        .qr-section {
            background: rgba(255, 255, 255, 0.7);
            padding: 12px;
            border-radius: 6px;
            text-align: center;
            border: 1px solid #fde68a;
        }

        .qr-code {
            display: inline-block;
            padding: 10px;
            background: white;
            border: 1px solid #fde68a;
            border-radius: 6px;
        }

        .qr-label {
            font-size: 10px;
            color: #92400e;
            margin-top: 6px;
            font-weight: bold;
        }

        .status-paid {
            color: #047857;
            font-weight: bold;
            background: #f0fdf4;
            padding: 5px 12px;
            border-radius: 15px;
            display: inline-block;
            min-width: 60px;
            text-align: center;
            border: 1px solid #86efac;
            font-size: 11px;
        }

        .status-unpaid {
            color: #b45309;
            font-weight: bold;
            background: #fffbeb;
            padding: 5px 12px;
            border-radius: 15px;
            display: inline-block;
            min-width: 60px;
            text-align: center;
            border: 1px solid #fcd34d;
            font-size: 11px;
        }

        .status-cancelled {
            color: #be123c;
            font-weight: bold;
            background: #fff1f2;
            padding: 5px 12px;
            border-radius: 15px;
            display: inline-block;
            min-width: 60px;
            text-align: center;
            border: 1px solid #fda4af;
            font-size: 11px;
        }

        .route-info {
            padding: 15px;
            background: #ffffff;
            border: 1px solid #cccccc;
            margin-top: 10px;
        }

        .route-arrow {
            width: 30px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <table border="0" width="100%">
                <tr>
                    <td width="80%">
                        <div>
                            <h1 class="ticket-title" style="color: #ffffff;">E-Ticket / E-Tiket</h1>
                            <div class="ticket-title" style="color: #ffffff; font-size: 16px;">Perjalanan Bus</div>
                            <div class="ticket-title" style="color: #ffffff; font-size: 14px;">Sumatra App</div>
                        </div>
                    </td>
                    <td width="20%" style="text-align: right; vertical-align: middle;">
                        <img src="{{ public_path('assets/images/logo.jpg') }}" alt="Logo" class="logo" style="width: 120px; height: auto;">
                    </td>
                </tr>
            </table>
        </div>

        <table border="0" width="100%" cellspacing="10" cellpadding="0">
            <tr>
                <td width="33%" class="info-cell">
                    <div class="info-section">
                        <div class="info-label">Kode Booking</div>
                        <div class="info-value">{{ $booking->payment_id }}</div>
                    </div>
                    <div class="info-section" style="margin-top: 8px;">
                        <div class="info-label">Tanggal Pemesanan</div>
                        <div class="info-value">{{ Carbon::parse($booking->created_at)->format('d M Y H:i') }}</div>
                    </div>
                    <div class="info-section" style="margin-top: 8px;">
                        <div class="info-label">Status</div>
                        @php
                            $statusClass = '';
                            $statusText = '';
                            
                            switch($booking->payment_status) {
                                case 'PAID':
                                    $statusClass = 'status-paid';
                                    $statusText = 'Lunas';
                                    break;
                                case 'UNPAID':
                                    $statusClass = 'status-unpaid';
                                    $statusText = 'Belum Bayar';
                                    break;
                                case 'CANCELLED':
                                    $statusClass = 'status-cancelled';
                                    $statusText = 'Batal';
                                    break;
                                default:
                                    $statusClass = '';
                                    $statusText = $booking->payment_status;
                            }
                        @endphp
                        <div class="info-value {{ $statusClass }}">{{ $statusText }}</div>
                    </div>
                </td>
                <td width="33%" class="info-cell">
                    <div class="info-section">
                        <div class="info-label">Bus</div>
                        <div class="info-value">{{ $scheduleRute->bus_name }}</div>
                    </div>
                    <div class="info-section" style="margin-top: 8px;">
                        <div class="info-label">Kelas</div>
                        <div class="info-value">{{ $scheduleRute->class_name }}</div>5
                        <div class="info-label">Nomor</div>
                        <div class="info-value"> {{ $scheduleRute->bus_number }}</div>
                    </div>
                </td>
                <td width="34%" class="info-cell">
                    <table border="0" width="100%" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="40%" style="text-align: center;">
                                <div class="info-label">Keberangkatan</div>
                                <div class="info-value">{{ $scheduleRute->origin_name }}</div>
                                <div class="info-value" style="color: #1a237e; font-size: 11px;">
                                    {{ Carbon::parse($scheduleRute->departure_time)->format('d M Y') }}<br>
                                    {{ Carbon::parse($scheduleRute->departure_time)->format('H:i') }}
                                </div>
                            </td>
                            <td width="20%" style="text-align: center; vertical-align: middle;">
                                <img src="{{ public_path('assets/images/panah kanan.png') }}" alt="→" class="route-arrow">
                            </td>
                            <td width="40%" style="text-align: center;">
                                <div class="info-label">Kedatangan</div>
                                <div class="info-value">{{ $scheduleRute->destination_name }}</div>
                                <div class="info-value" style="color: #1a237e; font-size: 11px;">
                                    {{ Carbon::parse($scheduleRute->arrival_time)->format('d M Y') }}<br>
                                    {{ Carbon::parse($scheduleRute->arrival_time)->format('H:i') }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: center; padding-top: 10px;">
                                <div class="journey-time">
                                    {{ Carbon::parse($scheduleRute->departure_time)->locale('id')->diffForHumans(Carbon::parse($scheduleRute->arrival_time), ['parts' => 2, 'join' => ' ', 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="passenger-info">
            <div class="section-title">Detail Penumpang</div>
            <table class="passenger-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Nama Penumpang</th>
                        <th>Jenis</th>
                        <th>Identitas</th>
                        <th>Nomor Kursi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($scheduleSeats as $index => $seat)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $seat->gender == 'L' ? 'Tuan ' : 'Nona ' }}{{ $seat->name }}</td>
                        <td>Dewasa</td>
                        <td>KTP - {{ $seat->phone_number ?? '-' }}</td>
                        <td>{{ str_pad($seat->seat_number, 2, '0', STR_PAD_LEFT) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="important-notes">
                <div class="notes-title">Informasi Penting</div>
                <table width="100%">
                    <tr>
                        <td style="width: 65%; vertical-align: top; padding-right: 20px;">
                            <div class="notes-list">
                                <div class="note-item">
                                    <span class="note-number" style="text-align: center;"> 1 </span>
                                    Gunakan e-tiket untuk cetak boarding pass di terminal
                                </div>
                                <div class="note-item">
                                    <span class="note-number" style="text-align: center;"> 2 </span>
                                    Bawa tanda pengenal resmi sesuai pemesanan
                                </div>
                                <div class="note-item">
                                    <span class="note-number" style="text-align: center;"> 3 </span>
                                    Tiba 60 menit sebelum keberangkatan
                                </div>
                            </div>
                        </td>
                        <td style="width: 35%; vertical-align: top;">
                            <div class="qr-section">
                                <div class="qr-code">
                                    {!! DNS2D::getBarcodeHTML($booking->payment_id, 'QRCODE', 5, 5) !!}
                                </div>
                                <div class="qr-label">Scan untuk verifikasi tiket</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 