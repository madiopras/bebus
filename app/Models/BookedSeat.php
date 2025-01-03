<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;;

class BookedSeat extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $table = 'booked_seat';

    protected $fillable = [
        'schedule_id',
        'bus_id',
        'seat_number',
        'remarks',
    ];

     /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */

     /**
     * Scope a query to filter schedules based on given filters.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */

     public function scopeFilter($query, $filters)
    {
        if (isset($filters['remarks'])) {
            $query->where('remarks', $filters['remarks']);
        }
    }


    public function schedule()
    {
        return $this->belongsTo(Schedules::class, 'schedule_id');
    }

    public function bus()
    {
        return $this->belongsTo(Buses::class, 'bus_id');
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
