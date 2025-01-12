<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ClassFacility extends Pivot
{
    protected $table = 'class_facilities';

    protected $fillable = [
        'class_id',
        'facility_id',
        'created_by_id',
        'updated_by_id'
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->foreignKey = 'class_id';
        $this->relatedKey = 'facility_id';
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
} 