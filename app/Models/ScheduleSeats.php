<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ScheduleSeat extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'schedule_id',
        'seat_id',
        'is_available',
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
        'is_available' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to filter schedule seats based on given filters.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, $filters)
    {
        if (isset($filters['schedule_id'])) {
            $query->where('schedule_id', $filters['schedule_id']);
        }
        if (isset($filters['seat_id'])) {
            $query->where('seat_id', $filters['seat_id']);
        }
        if (isset($filters['is_available'])) {
            $query->where('is_available', $filters['is_available']);
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
     * Get the schedule associated with the schedule seat.
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    /**
     * Get the seat associated with the schedule seat.
     */
    public function seat()
    {
        return $this->belongsTo(Seat::class, 'seat_id');
    }

    /**
     * Get the user who created this schedule seat.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who updated this schedule seat.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
