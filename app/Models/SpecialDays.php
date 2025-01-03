<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Carbon;

class SpecialDays extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'description',
        'price_percentage',
        'is_increase',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime:Y-m-d H:i:s',
        'end_date' => 'datetime:Y-m-d H:i:s',
        'price_percentage' => 'float',
        'is_increase' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to filter special days based on given filters.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */

     public function setStartDateAttribute($value)
    {
        $this->attributes['start_date'] = Carbon::parse($value)->setTimezone('Asia/Jakarta');
    }

    public function setEndDateAttribute($value)
    {
        $this->attributes['end_date'] = Carbon::parse($value)->setTimezone('Asia/Jakarta');
    }
    
    public function scopeFilter($query, $filters)
    {
        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }
        if (isset($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }
        if (isset($filters['description'])) {
            $query->where('description', 'like', '%' . $filters['description'] . '%');
        }
        if (isset($filters['price_percentage'])) {
            $query->where('price_percentage', $filters['price_percentage']);
        }
        if (isset($filters['is_increase'])) {
            $query->where('is_increase', $filters['is_increase']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        if (isset($filters['created_by_id'])) {
            $query->where('created_by_id', $filters['created_by_id']);
        }
        if (isset($filters['updated_by_id'])) {
            $query->where('updated_by_id', $filters['updated_by_id']);
        }
    }

    /**
     * Get the user who created this special day.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who updated this special day.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
