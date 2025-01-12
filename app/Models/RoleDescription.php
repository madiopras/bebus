<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleDescription extends Model
{
    protected $fillable = ['role_id', 'description'];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
} 