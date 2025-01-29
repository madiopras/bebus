<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RouteGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_location_id',
        'end_location_id',
        'time_difference',
        'description',
        'is_active',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'time_difference' => 'integer',
        'is_active' => 'boolean'
    ];

    public function startLocation(): BelongsTo
    {
        return $this->belongsTo(Locations::class, 'start_location_id');
    }

    public function endLocation(): BelongsTo
    {
        return $this->belongsTo(Locations::class, 'end_location_id');
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Routes::class, 'route_group_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    public function scopeFilter($query, $filters)
    {
        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (isset($filters['start_location_id'])) {
            $query->where('start_location_id', $filters['start_location_id']);
        }

        if (isset($filters['end_location_id'])) {
            $query->where('end_location_id', $filters['end_location_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query;
    }
} 