<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelHasRole extends Model
{
    protected $table = 'model_has_roles';
    
    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'model_type',
        'model_id'
    ];

    /**
     * Get role yang terkait.
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Get model yang memiliki role ini.
     */
    public function model()
    {
        return $this->morphTo();
    }
} 