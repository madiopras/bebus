<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'guard_name'
    ];

    // Relasi ke role melalui tabel pivot
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions');
    }
} 