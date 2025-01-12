<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleHasPermission extends Model
{
    protected $table = 'role_has_permissions';

    public $timestamps = false;

    protected $fillable = [
        'permission_id',
        'role_id'
    ];

    // Relasi ke model Permission
    public function permission()
    {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

    // Relasi ke model Role
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
} 