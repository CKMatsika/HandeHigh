<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function hasConflicts(): bool
    {
        return $this->slots()->where('status', 'conflict')->exists();
    }

    public function getConflictCount(): int
    {
        return $this->slots()->where('status', 'conflict')->count();
    }
}
