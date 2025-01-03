<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Classes extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'class_name',
        'description',
        'has_ac',
        'has_toilet',
        'has_tv',
        'has_music',
        'has_air_mineral',
        'has_wifi',
        'has_snack',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'has_ac' => 'boolean',
        'has_toilet' => 'boolean',
        'has_tv' => 'boolean',
        'has_music' => 'boolean',
        'has_air_mineral' => 'boolean',
        'has_wifi' => 'boolean',
        'has_snack' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to filter classes based on given filters.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, $filters)
    {
        if (isset($filters['class_name'])) {
            $query->where('class_name', 'like', '%' . $filters['class_name'] . '%');
        }
        if (isset($filters['description'])) {
            $query->where('description', 'like', '%' . $filters['description'] . '%');
        }
        if (isset($filters['has_ac'])) {
            $query->where('has_ac', $filters['has_ac']);
        }
        if (isset($filters['has_toilet'])) {
            $query->where('has_toilet', $filters['has_toilet']);
        }
        if (isset($filters['has_tv'])) {
            $query->where('has_tv', $filters['has_tv']);
        }
        if (isset($filters['has_music'])) {
            $query->where('has_music', $filters['has_music']);
        }
        if (isset($filters['has_air_mineral'])) {
            $query->where('has_air_mineral', $filters['has_air_mineral']);
        }
        if (isset($filters['has_wifi'])) {
            $query->where('has_wifi', $filters['has_wifi']);
        }
        if (isset($filters['has_snack'])) {
            $query->where('has_snack', $filters['has_snack']);
        }
    }

    /**
     * Get the user who created this class.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who updated this class.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
