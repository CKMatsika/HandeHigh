<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'period_sequence',
        'day_of_week',
        'start_time',
        'end_time',
        'period_type',
        'is_active',
    ];

    protected $casts = [
        'period_sequence' => 'integer',
        'is_active' => 'boolean',
        'period_type' => 'string',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function timetableSlots(): HasMany
    {
        return $this->hasMany(TimetableSlot::class);
    }

    public function fixedActivities(): HasMany
    {
        return $this->hasMany(TimetableFixedActivity::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDay($query, ?string $day)
    {
        if (! $day) {
            return $query;
        }

        return $query->where(function ($q) use ($day) {
            $q->where('day_of_week', $day)
                ->orWhereNull('day_of_week');
        });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('period_type', $type);
    }

    public function scopeLessonsOnly($query)
    {
        return $query->where('period_type', 'lesson');
    }

    public function getDurationMinutes(): int
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);

        return $start->diffInMinutes($end);
    }

    public function getFormattedTime(): string
    {
        $start = is_string($this->start_time) ? substr($this->start_time, 0, 5) : Carbon::parse($this->start_time)->format('H:i');
        $end = is_string($this->end_time) ? substr($this->end_time, 0, 5) : Carbon::parse($this->end_time)->format('H:i');

        return "{$start} - {$end}";
    }

    public function isLesson(): bool
    {
        return $this->period_type === 'lesson';
    }

    public function isBreak(): bool
    {
        return in_array($this->period_type, ['break', 'lunch']);
    }

    public function isNonLessonActivity(): bool
    {
        return ! $this->isLesson();
    }
}
