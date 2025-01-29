<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reschedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_previous_id',
        'booking_new_id',
        'schedule_rute_id',
        'harga_baru',
        'alasan'
    ];

    protected $casts = [
        'harga_baru' => 'decimal:2'
    ];

    public function previousBooking()
    {
        return $this->belongsTo(Bookings::class, 'booking_previous_id');
    }

    public function newBooking()
    {
        return $this->belongsTo(Bookings::class, 'booking_new_id');
    }

    public function scheduleRute()
    {
        return $this->belongsTo(ScheduleRute::class, 'schedule_rute_id');
    }
} 