<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffPositionAssignment extends Model
{
    protected $fillable = [
        'school_id',
        'staff_position_id',
        'assignable_id',
        'assignable_type',
        'target_id',
        'target_type',
        'start_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function position()
    {
        return $this->belongsTo(StaffPosition::class, 'staff_position_id');
    }

    public function assignable()
    {
        return $this->morphTo();
    }

    public function target()
    {
        return $this->morphTo();
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
