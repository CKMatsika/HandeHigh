<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Timetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'academic_year',
        'term',
        'status',
        'settings',
        'generated_at',
        'published_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
        'status' => 'string',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class);
    }

    public function fixedActivities(): HasMany
    {
        return $this->hasMany(TimetableFixedActivity::class);
    }

    public function examinations(): HasMany
    {
        return $this->hasMany(TimetableExamination::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(TimetableRequirement::class);
    }

    public function generationRuns(): HasMany
    {
        return $this->hasMany(TimetableGenerationRun::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(TimetableCandidate::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByAcademicYear($query, $year)
    {
        return $query->where('academic_year', $year);
    }

    public function scopeByTerm($query, $term)
    {
        return $query->where('term', $term);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isGenerated(): bool
    {
        return $this->status === 'generated';
    }

    public function hasConflicts(): bool
    {
        return $this->slots()->where('status', 'conflict')->exists();
    }

    public function getConflictCount(): int
    {
        return $this->slots()->where('status', 'conflict')->count();
    }

    public function getHardConflictCount(): int
    {
        return $this->slots()->where('status', 'conflict')->get()->filter(function ($slot) {
            $conflicts = $slot->conflicts ?? [];
            if (! is_array($conflicts)) {
                return true;
            }
            foreach ($conflicts as $c) {
                if (is_array($c) && ($c['severity'] ?? '') === 'HARD') {
                    return true;
                }
            }

            return true;
        })->count();
    }
}
