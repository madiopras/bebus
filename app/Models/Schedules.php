<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Carbon;

class Schedules extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'location_id',
        'bus_id',
        'supir_id',
        'departure_time',
        'arrival_time',
        'description',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'departure_time' => 'datetime:Y-m-d H:i:s',
        'arrival_time' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to filter schedules based on given filters.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */

     public function setDepartureTimeAttribute($value)
     {
        $this->attributes['departure_time'] = Carbon::parse($value)->setTimezone('Asia/Jakarta');
     }
 
     public function setArrivalTimeAttribute($value)
     {
        $this->attributes['arrival_time'] = Carbon::parse($value)->setTimezone('Asia/Jakarta');
     }

    // Fungsi scope untuk memfilter data
    public function scopeFilterWithJoin($query, $filters)
    {
        $query->select('schedules.id', 'locations.name', 'schedules.departure_time', 'schedules.arrival_time', 'buses.bus_number', 'buses.bus_name', 'buses.type_bus', 'classes.class_name')
              ->join('buses', 'buses.id', '=', 'schedules.bus_id')
              ->join('classes', 'buses.class_id', '=', 'classes.id')
              ->join('locations', 'schedules.location_id', '=', 'locations.id')
              ->orderBy('schedules.departure_time', 'asc');

        
        if (isset($filters['departure_time'])) {
            $query->where('schedules.departure_time', $filters['departure_time']);
        }
        if (isset($filters['bus_number'])) {
            $query->where('buses.bus_number', 'like', '%' . $filters['bus_number'] . '%');
        }
        if (isset($filters['class_name'])) {
            $query->where('classes.class_name', 'like', '%' . $filters['class_name'] . '%');
        }
        if (isset($filters['type_bus'])) {
            $query->where('buses.type_bus', 'like', '%' . $filters['type_bus'] . '%');
        }
        if (isset($filters['bus_name'])) {
            $query->where('buses.bus_name', 'like', '%' . $filters['bus_name'] . '%');
        }
        if (isset($filters['is_active'])) {
            $isActiveValue = $filters['is_active'] === 'true' ? 1 : ($filters['is_active'] === 'false' ? 0 : '');
            $query->where('is_active', 'like', '%' . $isActiveValue . '%');
        }
        
        return $query;
    }
    /**
     * Get the bus associated with the schedule.
     */

     public function scheduleRutes()
     {
         return $this->hasMany(ScheduleRute::class, 'schedule_id');
     }
     
    public function bus()   
    {
        return $this->belongsTo(Buses::class, 'bus_id');
    }

    public function supir()
    {
        return $this->belongsTo(User::class, 'supir_id');
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
    /**
     * Get the user who created this schedule.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who updated this schedule.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
