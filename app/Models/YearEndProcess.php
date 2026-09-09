<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YearEndProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'source_academic_year',
        'target_academic_year',
        'status',
        'summary',
        'draft_payload',
        'created_by',
        'approved_by',
        'executed_at',
        'notes',
    ];

    protected $casts = [
        'summary' => 'array',
        'draft_payload' => 'array',
        'executed_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function clearances(): HasMany
    {
        return $this->hasMany(StudentClearance::class, 'year_end_process_id');
    }
}
