<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleSeats extends Model
{
    protected $table = 'scheduleseats';

    protected $fillable = [
        'schedule_id',
        'booking_Id',
        'seat_id',
        'schedule_rute_id',
        'passengers_id',
        'is_available',
        'description',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function schedule()
    {
        return $this->belongsTo(Schedules::class, 'schedule_id');
    }

    public function scheduleRute()
    {
        return $this->belongsTo(ScheduleRute::class, 'schedule_rute_id');
    }

    public function booking()
    {
        return $this->belongsTo(Bookings::class, 'booking_Id');
    }

    public function seat()
    {
        return $this->belongsTo(Seats::class, 'seat_id');
    }

    public function passenger()
    {
        return $this->belongsTo(Passenger::class, 'passengers_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
} 