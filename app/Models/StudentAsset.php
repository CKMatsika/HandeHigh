<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAsset extends Model
{
    protected $fillable = [
        'student_id',
        'school_asset_id',
        'quantity',
        'allocated_date',
        'returned_date',
        'condition_at_issue',
        'condition_at_return',
        'replacement_cost',
        'notes',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'replacement_cost' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolAsset(): BelongsTo
    {
        return $this->belongsTo(SchoolAsset::class);
    }

    public function scopeAllocated($query)
    {
        return $query->where('status', 'allocated');
    }
}
