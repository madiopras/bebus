<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Schedules;

class UtilityBBM extends Model
{
    protected $table = 'utility_bbm';
    
    protected $fillable = [
        'tanggal',
        'schedule_id',
        'nomor_jadwal_bus',
        'odometer_awal',
        'jarak',
        'harga_liter_bbm',
        'total_perkiraan_harga_bbm',
        'total_aktual_harga_bbm',
        'description'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'odometer_awal' => 'integer',
        'jarak' => 'integer',
        'harga_liter_bbm' => 'integer',
        'total_perkiraan_harga_bbm' => 'integer',
        'total_aktual_harga_bbm' => 'integer',
        'description' => 'string'
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedules::class);
    }

    public static function getDataForCreate()
    {
        return Schedules::select(
                'schedules.id',
                'buses.bus_name',
                'schedules.departure_time'
            )
            ->join('buses', 'schedules.bus_id', '=', 'buses.id')
            ->with(['scheduleRoutes.route' => function($query) {
                $query->select('id', 'distance');
            }])
            ->get()
            ->map(function($schedule) {
                $totalDistance = $schedule->scheduleRoutes->sum(function($scheduleRoute) {
                    return $scheduleRoute->route->distance;
                });
                
                return [
                    'id' => $schedule->id,
                    'nomor_jadwal_bus' => $schedule->bus_name . ' - ' . $schedule->departure_time->format('d M Y H:i'),
                    'jarak' => $totalDistance
                ];
            });
    }
}
