<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_name',
        'model_type',
        'description'
    ];

    public function scopeFilter($query, array $filters)
    {
        if (isset($filters['menu_name'])) {
            $query->where('menu_name', 'like', '%' . $filters['menu_name'] . '%');
        }

        if (isset($filters['model_type'])) {
            $query->where('model_type', 'like', '%' . $filters['model_type'] . '%');
        }
    }
}
