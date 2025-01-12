<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Passenger extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'passengers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id',
        'schedule_seat_id',
        'name',
        'gender',
        'phone_number',
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to filter passengers based on given filters.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, $filters)
    {
        if (isset($filters['booking_id'])) {
            $query->where('booking_id', $filters['booking_id']);
        }
        if (isset($filters['schedule_seat_id'])) {
            $query->where('schedule_seat_id', $filters['schedule_seat_id']);
        }
        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }
        if (isset($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }
        if (isset($filters['phone_number'])) {
            $query->where('phone_number', 'like', '%' . $filters['phone_number'] . '%');
        }
        if (isset($filters['description'])) {
            $query->where('description', 'like', '%' . $filters['description'] . '%');
        }
        if (isset($filters['created_by_id'])) {
            $query->where('created_by_id', $filters['created_by_id']);
        }
        if (isset($filters['updated_by_id'])) {
            $query->where('updated_by_id', $filters['updated_by_id']);
        }
    }

    /**
     * Get the booking associated with the passenger.
     */
    public function booking()
    {
        return $this->belongsTo(Bookings::class, 'booking_id');
    }

    /**
     * Get the schedule seat associated with the passenger.
     */
    public function scheduleSeat()
    {
        return $this->belongsTo(ScheduleSeat::class, 'schedule_seat_id');
    }

    /**
     * Get the user who created this passenger.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who updated this passenger.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
