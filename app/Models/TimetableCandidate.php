<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableCandidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'generation_run_id',
        'timetable_id',
        'candidate_number',
        'score',
        'hard_conflicts_count',
        'soft_warnings_count',
        'score_breakdown',
        'allocations',
        'unallocated_requirements',
        'metrics',
        'is_applied',
        'applied_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'hard_conflicts_count' => 'integer',
        'soft_warnings_count' => 'integer',
        'score_breakdown' => 'array',
        'allocations' => 'array',
        'unallocated_requirements' => 'array',
        'metrics' => 'array',
        'is_applied' => 'boolean',
        'applied_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function generationRun(): BelongsTo
    {
        return $this->belongsTo(TimetableGenerationRun::class, 'generation_run_id');
    }

    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    public function isApplied(): bool
    {
        return (bool) $this->is_applied;
    }
}
