<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IctLabItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'device_name',
        'device_code',
        'location',
        'condition_status',
        'last_checked_at',
        'last_checked_by',
        'notes',
    ];

    protected $casts = [
        'last_checked_at' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'last_checked_by');
    }

    public function getConditionBadgeColorAttribute(): string
    {
        return match ($this->condition_status) {
            'working' => 'emerald',
            'needs_attention' => 'amber',
            'faulty' => 'red',
            'under_repair' => 'purple',
            default => 'slate',
        };
    }

    public function getConditionLabelAttribute(): string
    {
        return match ($this->condition_status) {
            'working' => 'Operational',
            'needs_attention' => 'Needs Attention',
            'faulty' => 'Faulty',
            'under_repair' => 'Under Repair',
            default => ucfirst(str_replace('_', ' ', $this->condition_status)),
        };
    }
}
