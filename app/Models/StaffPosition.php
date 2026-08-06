<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffPosition extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'slug',
        'description',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function assignments()
    {
        return $this->hasMany(StaffPositionAssignment::class);
    }
}
