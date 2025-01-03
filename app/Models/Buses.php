<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use DB; // Tambahkan ini untuk menggunakan DB

class Buses extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'bus_number',
        'type_bus',
        'capacity',
        'bus_name',
        'class_id',
        'description',
        'cargo',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Fungsi scope untuk memfilter data
public function scopeFilterWithJoin($query, $filters)
{
    $query->select('buses.id', 'buses.bus_number', 'buses.type_bus', 'classes.class_name', 'buses.capacity', 'buses.bus_name')
          ->join('classes', 'buses.class_id', '=', 'classes.id')
          ->orderBy('buses.bus_number', 'asc');

    if (isset($filters['bus_number'])) {
        $query->where('buses.bus_number', 'like', '%' . $filters['bus_number'] . '%');
    }
    if (isset($filters['bus_name'])) {
        $query->where('buses.bus_name', 'like', '%' . $filters['bus_name'] . '%');
    }
    if (isset($filters['class_name'])) {
        $query->where('classes.class_name', 'like', '%' . $filters['class_name'] . '%');
    }
    if (isset($filters['type_bus'])) {
        $query->where('buses.type_bus', 'like', '%' . $filters['type_bus'] . '%');
    }
    if (isset($filters['is_active'])) {
        $isActiveValue = $filters['is_active'] === 'true' ? 1 : ($filters['is_active'] === 'false' ? 0 : null);
        if ($isActiveValue !== null) {
            $query->where('buses.is_active', $isActiveValue);
        }
    }

    return $query;
}

public function scopeFilterBusesNotInSchedule($query, $dateTime)
{
    return $query->whereNotIn('buses.id', function ($subquery) use ($dateTime) {
        $subquery->select('buses.id')
        ->from('buses')
        ->join('schedules', 'buses.id', '=', 'schedules.bus_id')
        ->where('schedules.departure_time', '<=', $dateTime)
        ->where('schedules.arrival_time', '>=', $dateTime);
    });
}







    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function class()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
}
