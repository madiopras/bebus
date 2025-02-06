<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListGroupRoute extends Model
{
    use HasFactory;

    protected $table = 'list_group_route';

    protected $fillable = [
        'route_group_id',
        'route_id',
        'time_difference',
        'start_location_id',
        'end_location_id',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'time_difference' => 'integer'
    ];

    public function routeGroup(): BelongsTo
    {
        return $this->belongsTo(RouteGroup::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Routes::class, 'route_id');
    }
} 