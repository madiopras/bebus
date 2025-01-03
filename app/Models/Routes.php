<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Routes extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'start_location_id',
        'end_location_id',
        'distance',
        'price',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'distance' => 'float',
        'price' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    
    public function scopeFilterWithJoin($query, $filters)
    {
        $query->select('r.id', 'l1.name as start_location', 'l2.name as end_location', 'r.distance', 'r.price')
            ->from('routes as r')
            ->join('locations as l1', 'r.start_location_id', '=', 'l1.id')
            ->join('locations as l2', 'r.end_location_id', '=', 'l2.id')
            ->orderBy('l1.name', 'asc'); // Atau Anda bisa mengganti 'r.id' dengan kolom lain yang ingin Anda urutkan

        if (isset($filters['start_location'])) {
            $query->where('l1.name', 'like', '%' . $filters['start_location'] . '%');
        }
        if (isset($filters['end_location'])) {
            $query->where('l2.name', 'like', '%' . $filters['end_location'] . '%');
        }
        if (isset($filters['distance'])) {
            $query->where('distance', $filters['distance']);
        }
        if (isset($filters['price'])) {
            $query->where('price', $filters['price']);
        }
    }

    /**
     * Get the start location of the route.
     */
    public function startLocation()
    {
        return $this->belongsTo(Locations::class, 'start_location_id');
    }

    /**
     * Get the end location of the route.
     */
    public function endLocation()
    {
        return $this->belongsTo(Locations::class, 'end_location_id');
    }

    /**
     * Get the user who created this route.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who updated this route.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
