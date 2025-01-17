<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

class ScheduleRute extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Nama tabel di database
     *
     * @var string
     */
    protected $table = 'schedule_rute'; // Pastikan nama tabel sesuai dengan yang ada di database

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'schedule_id',
        'route_id',
        'sequence_route',
        'departure_time',
        'arrival_time',
        'price_rute',
        'description',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * The attributes that are nullable.
     *
     * @var array<string>
     */
    protected $nullable = ['description'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sequence_route' => 'integer',
        'departure_time' => 'datetime:Y-m-d H:i:s',
        'arrival_time' => 'datetime:Y-m-d H:i:s',
        'price_rute' => 'float',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'description' => 'string'
    ];

    /**
     * Set departure time attribute with timezone.
     *
     * @param mixed $value
     */
    public function setDepartureTimeAttribute($value)
    {
        $this->attributes['departure_time'] = Carbon::parse($value)->setTimezone('Asia/Jakarta');
    }
 
    /**
     * Set arrival time attribute with timezone.
     *
     * @param mixed $value
     */
    public function setArrivalTimeAttribute($value)
    {
        $this->attributes['arrival_time'] = Carbon::parse($value)->setTimezone('Asia/Jakarta');
    }

    /**
     * Scope a query to filter schedules based on given filters.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, $filters)
    {
        if (isset($filters['description'])) {
            $query->where('description', 'like', '%' . $filters['description'] . '%');
        }
    }

    /**
     * Define the relationship with the Schedules model.
     */
    public function schedule()
    {
        return $this->belongsTo(Schedules::class, 'schedule_id');
    }

    /**
     * Define the relationship with the Routes model.
     */
    public function rute()
    {
        return $this->belongsTo(Routes::class, 'route_id'); // Pastikan model dan nama relasi sesuai
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
